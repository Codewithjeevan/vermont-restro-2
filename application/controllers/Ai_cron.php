<?php
/*
  ###########################################################
  # This is Ai_cron Controller
  ###########################################################
  # AI Insight jobs. Command line (cPanel cron, every hour):
  #   php index.php Ai_cron run
  #       today's report of every outlet of every company that is
  #       switched on, once its local time is past ai_report_hour
  #   php index.php Ai_cron outlet <company_id> <outlet_id> [Y-m-d] [force]
  #       one outlet now (support / tests)
  #   php index.php Ai_cron status
  #       last runs, today's reports, this month's spend
  #   php index.php Ai_cron test <company_id>
  #       one tiny API call with the company's chat model
  #   php index.php Ai_cron health <company_id> <outlet_id>
  #       Data Health of an outlet as JSON
  # Web (only for hosts without a command-line cron, off unless
  # AI_CRON_WEB_TOKEN is set in application/.env):
  #   https://<site>/Ai_cron/web/<token>   -> same as "run"
  # Extends CI_Controller, not Cl_Controller: no session or
  # licence checks. Plan: docs/ai_insight_plan.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!is_cli() && !($this->uri->segment(2) == 'web' && $this->webTokenOk((string) $this->uri->segment(3)))) {
            show_404();
        }
        $this->load->model('Common_model');
        $this->load->model('Ai_model');
        $this->load->model('Ai_metrics_model');
        // same clock as the web screens, so usage rows and month limits line up
        $this->Common_model->setDefaultTimezone();
        if (!ai_tables_ready()) {
            $this->line('AI tables missing: run Update/ai_insight_migration.sql first.');
            exit(1);
        }
    }

    private function webTokenOk($given) {
        $token = (string) ai_env('AI_CRON_WEB_TOKEN', '');
        return strlen($token) >= 32 && hash_equals($token, $given);
    }

    private function line($text) {
        echo $text . PHP_EOL;
        if (!is_cli()) {
            @flush();
        }
    }

    public function index() {
        $this->line('Usage:');
        $this->line('  php index.php Ai_cron run');
        $this->line('  php index.php Ai_cron outlet <company_id> <outlet_id> [Y-m-d] [force]');
        $this->line('  php index.php Ai_cron status');
        $this->line('  php index.php Ai_cron test <company_id>');
        $this->line('  php index.php Ai_cron health <company_id> <outlet_id>');
    }

    /** the hourly job */
    public function run() {
        @set_time_limit(0);
        ignore_user_abort(true);
        $lock = 'ai_cron_' . $this->db->database;
        if ((int) $this->db->query("SELECT GET_LOCK(?, 0) AS l", array($lock))->row('l') !== 1) {
            $this->line(date('Y-m-d H:i:s') . ' another AI cron run is still working; nothing to do');
            return;
        }
        $this->db->insert('tbl_ai_cron_log', array('started_at' => date('Y-m-d H:i:s')));
        $log_id = $this->db->insert_id();
        $lines = array();
        $log = function ($text) use (&$lines) {
            $lines[] = $text;
            $this->line(date('H:i:s') . ' ' . $text);
        };
        $stats = array('companies' => 0, 'outlets_done' => 0, 'errors' => 0);
        try {
            $this->load->library('Ai/Ai_report', null, 'Ai_report');
            $stats = $this->Ai_report->runDue($log);
        } catch (Throwable $e) {
            $stats['errors']++;
            $log('ERROR ' . ai_redact($e->getMessage()));
        }
        $this->db->update('tbl_ai_cron_log', array(
            'finished_at' => date('Y-m-d H:i:s'),
            'companies' => $stats['companies'],
            'outlets_done' => $stats['outlets_done'],
            'errors' => $stats['errors'],
            'message' => mb_substr(implode("\n", $lines), 0, 60000),
        ), array('id' => $log_id));
        $this->db->query("SELECT RELEASE_LOCK(?)", array($lock));
    }

    /** web cron for hosts without a command line; the token was checked in the constructor */
    public function web($token = '') {
        header('Content-Type: text/plain; charset=utf-8');
        $this->run();
    }

    public function outlet($company_id = 0, $outlet_id = 0, $date = '', $force = '') {
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        if (!$this->Ai_model->getCompanyOutlet($outlet_id, $company_id)) {
            $this->line('Outlet ' . $outlet_id . ' is not a live outlet of company ' . $company_id);
            exit(1);
        }
        if ($date === '' || $date === 'today') {
            $date = $this->Ai_metrics_model->companyToday($company_id);
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $this->line('Date must be Y-m-d');
            exit(1);
        }
        $this->load->library('Ai/Ai_report', null, 'Ai_report');
        $r = $this->Ai_report->runOutlet($company_id, $outlet_id, $date, $force === 'force');
        $this->line(sprintf('company %d outlet %d %s: %s %s (%.1f s, %.6f USD)', $company_id, $outlet_id, $date, $r['action'], $r['status'], $r['seconds'], $r['cost_usd']));
        foreach ($r['notes'] as $n) {
            $this->line('  note: ' . $n);
        }
    }

    public function status() {
        $this->line('Last runs:');
        foreach ($this->db->query("SELECT * FROM tbl_ai_cron_log ORDER BY id DESC LIMIT 10")->result() as $r) {
            $this->line(sprintf('  #%d %s -> %s  companies %d, outlets %d, errors %d', $r->id, $r->started_at, $r->finished_at ?: 'running/killed',
                $r->companies, $r->outlets_done, $r->errors));
        }
        $this->line('Reports of the last 2 days:');
        foreach ($this->db->query("SELECT s.company_id, s.outlet_id, o.outlet_name, s.report_date, s.narrative_status, s.attempts, s.error, s.updated_at
                FROM tbl_ai_snapshots s LEFT JOIN tbl_outlets o ON o.id = s.outlet_id
                WHERE s.report_date >= ? ORDER BY s.report_date DESC, s.company_id, s.outlet_id", array(date('Y-m-d', strtotime('-1 day'))))->result() as $s) {
            $this->line(sprintf('  %s company %d outlet %d %s: %s, attempts %d%s', $s->report_date, $s->company_id, $s->outlet_id,
                html_entity_decode((string) $s->outlet_name, ENT_QUOTES, 'UTF-8'), $s->narrative_status, $s->attempts, $s->error ? ' | ' . $s->error : ''));
        }
        $this->line('This month:');
        foreach ($this->db->query("SELECT u.company_id, s.enabled, s.monthly_budget_usd, COUNT(*) AS calls, SUM(u.cost_usd) AS spent
                FROM tbl_ai_usage u LEFT JOIN tbl_ai_settings s ON s.company_id = u.company_id
                WHERE u.created_at >= ? GROUP BY u.company_id, s.enabled, s.monthly_budget_usd", array(date('Y-m-01 00:00:00')))->result() as $u) {
            $this->line(sprintf('  company %d (%s): %d calls, %.6f of %.2f USD', $u->company_id, (int) $u->enabled ? 'on' : 'off', $u->calls, $u->spent, $u->monthly_budget_usd));
        }
    }

    public function test($company_id = 0) {
        $company_id = (int) $company_id;
        if (!$this->Ai_model->companyExists($company_id)) {
            $this->line('Unknown company: ' . $company_id);
            exit(1);
        }
        $this->load->library('Ai/Ai_client', null, 'Ai_client');
        $settings = $this->Ai_model->getSettings($company_id);
        $this->line('Key configured: ' . ($this->Ai_client->keyConfigured() ? 'yes' : 'NO') . ($this->Ai_client->mockMode() ? ' (AI_MOCK=1)' : ''));
        $this->line('Company AI switch: ' . ((int) $settings->enabled ? 'on' : 'off') . ', limit ' . $settings->monthly_budget_usd
            . ' USD, spent this month ' . number_format($this->Ai_model->monthSpend($company_id), 6) . ' USD');
        $started = microtime(true);
        $r = $this->Ai_client->send(
            array('company_id' => $company_id, 'feature' => 'test', 'ignore_switch' => true),
            array(
                'model' => $settings->chat_model,
                'maxTokens' => 64,
                'outputConfig' => array('effort' => 'low'),
                'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: OK')),
            ),
            (int) ai_config('ai_timeout_chat')
        );
        $this->line('Model: ' . $settings->chat_model);
        $this->line('Result: ' . ($r['ok'] ? 'OK' : 'FAILED (' . $r['error_code'] . ') ' . $r['error']));
        $this->line('Reply: ' . trim($r['text']));
        $this->line('Tokens: ' . json_encode($r['usage']) . ', cost ' . number_format($r['cost_usd'], 6) . ' USD, '
            . number_format(microtime(true) - $started, 2) . ' s');
        exit($r['ok'] ? 0 : 1);
    }

    public function health($company_id = 0, $outlet_id = 0) {
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        if (!$this->Ai_model->getCompanyOutlet($outlet_id, $company_id)) {
            $this->line('Outlet ' . $outlet_id . ' is not a live outlet of company ' . $company_id);
            exit(1);
        }
        $today = $this->Ai_metrics_model->companyToday($company_id);
        $this->line(json_encode($this->Ai_metrics_model->dataHealth($company_id, $outlet_id, $today), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
