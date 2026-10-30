<?php
/*
  ###########################################################
  # This is Ai_cron Controller
  ###########################################################
  # Command line only. Support commands for AI Insight:
  #   php index.php Ai_cron test <company_id>
  #       one tiny API call with the company's chat model,
  #       logged in tbl_ai_usage as feature "test"
  #   php index.php Ai_cron health <company_id> <outlet_id>
  #       Data Health of an outlet as JSON
  # The daily report runner (Ai_cron run) is added in phase 3.
  # Extends CI_Controller, not Cl_Controller: no session or
  # licence checks on the command line.
  # Plan: docs/ai_insight_plan.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
        $this->load->model('Ai_model');
        $this->load->model('Ai_metrics_model');
        if (!ai_tables_ready()) {
            $this->line('AI tables missing: run Update/ai_insight_migration.sql first.');
            exit(1);
        }
    }

    private function line($text) {
        echo $text . PHP_EOL;
    }

    public function index() {
        $this->line('Usage:');
        $this->line('  php index.php Ai_cron test <company_id>');
        $this->line('  php index.php Ai_cron health <company_id> <outlet_id>');
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
