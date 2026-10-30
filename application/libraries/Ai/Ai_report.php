<?php
/*
  ###########################################################
  # This is Ai_report Library
  ###########################################################
  # Makes the daily report of an outlet: numbers (Ai_metrics_model)
  # -> tbl_ai_snapshots -> text (Ai_narrator). One row per outlet
  # per day (UNIQUE outlet_id + report_date), so a re-run updates
  # instead of adding. Used by Ai_cron (hourly) and later by the
  # page's Regenerate button. Plan: docs/ai_insight_plan.md 5, 10
  # Load: $this->load->library('Ai/Ai_report', null, 'Ai_report');
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_report {

    private $CI;

    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model('Ai_model');
        $this->CI->load->model('Ai_metrics_model');
        $this->CI->load->library('Ai/Ai_narrator', null, 'Ai_narrator');
    }

    /**
     * Today's report of one outlet.
     * @param int $company_id
     * @param int $outlet_id
     * @param string $date report date (company local Y-m-d); the numbers cover the days before it
     * @param bool $force make it again even when today's report is done
     * @param int|null $user_id who asked (null = cron)
     * @return array action skipped|done, status, cost_usd, notes, seconds
     */
    public function runOutlet($company_id, $outlet_id, $date, $force = false, $user_id = null) {
        $started = microtime(true);
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        $snapshot = $this->getSnapshot($outlet_id, $date);
        $max = (int) ai_config('ai_report_max_attempts');
        if ($snapshot && !$force && (in_array($snapshot->narrative_status, array('ok', 'template')) || (int) $snapshot->attempts >= $max)) {
            return array('action' => 'skipped', 'status' => $snapshot->narrative_status, 'cost_usd' => 0.0, 'notes' => array(), 'seconds' => 0);
        }
        $settings = $this->CI->Ai_model->getSettings($company_id);
        $metrics = $this->CI->Ai_metrics_model->build($company_id, $outlet_id, $date, $settings);
        $attempts = $snapshot && !$force ? (int) $snapshot->attempts : 0;
        // numbers first: if the AI call dies, the page still has today's figures
        $this->saveSnapshot($company_id, $outlet_id, $date, array('metrics_json' => json_encode($metrics), 'narrative_status' => 'pending', 'attempts' => $attempts));

        $result = $this->CI->Ai_narrator->narrate($metrics, $settings, array('company_id' => $company_id, 'outlet_id' => $outlet_id, 'user_id' => $user_id));
        $attempts++;
        $status = $result['status'];
        if ($status == 'failed' && $attempts >= $max) {
            $status = 'template'; // gave up: keep the template text for today
            $result['notes'][] = "gave up after $attempts attempts";
        }
        $this->saveSnapshot($company_id, $outlet_id, $date, array(
            'narrative_json' => json_encode($result['narrative']),
            'narrative_status' => $status,
            'attempts' => $attempts,
            'error' => $result['notes'] ? mb_substr(implode(' | ', $result['notes']), 0, 500) : null,
        ));
        return array('action' => 'done', 'status' => $status, 'cost_usd' => $result['cost_usd'], 'notes' => $result['notes'],
            'seconds' => round(microtime(true) - $started, 1));
    }

    /**
     * Every enabled company whose local time is past the report hour: today's report of each live outlet.
     * @param callable $log receives one line of text per event
     * @return array companies, outlets_done, errors
     */
    public function runDue($log) {
        $hour = (int) ai_config('ai_report_hour');
        $stats = array('companies' => 0, 'outlets_done' => 0, 'errors' => 0);
        $companies = $this->CI->db->query("SELECT s.company_id, c.business_name FROM tbl_ai_settings s
            JOIN tbl_companies c ON c.id = s.company_id AND c.del_status = 'Live'
            WHERE s.enabled = 1 ORDER BY s.company_id")->result();
        foreach ($companies as $c) {
            $now = $this->CI->Ai_metrics_model->companyNow($c->company_id);
            if ((int) $now->format('G') < $hour) {
                $log("company {$c->company_id}: local time " . $now->format('H:i') . ", reports start at $hour:00");
                continue;
            }
            $stats['companies']++;
            $date = $now->format('Y-m-d');
            $outlets = $this->CI->db->query("SELECT id, outlet_name FROM tbl_outlets WHERE company_id = ? AND del_status = 'Live' ORDER BY id",
                array((int) $c->company_id))->result();
            foreach ($outlets as $o) {
                try {
                    $r = $this->runOutlet($c->company_id, $o->id, $date);
                    if ($r['action'] == 'done') {
                        $stats['outlets_done']++;
                    }
                    $log(sprintf('company %d outlet %d %s: %s %s%s', $c->company_id, $o->id, $date, $r['action'], $r['status'],
                        $r['action'] == 'done' ? sprintf(' (%.1f s, %.4f USD%s)', $r['seconds'], $r['cost_usd'], $r['notes'] ? '; ' . implode(' | ', $r['notes']) : '') : ''));
                } catch (Throwable $e) {
                    $stats['errors']++;
                    $message = ai_redact($e->getMessage());
                    $log("company {$c->company_id} outlet {$o->id}: ERROR $message");
                    log_message('error', "AI report company {$c->company_id} outlet {$o->id}: $message");
                    $snapshot = $this->getSnapshot($o->id, $date);
                    $this->saveSnapshot($c->company_id, $o->id, $date, array(
                        'narrative_status' => 'failed',
                        'attempts' => ($snapshot ? (int) $snapshot->attempts : 0) + 1,
                        'error' => mb_substr('error: ' . $message, 0, 500),
                    ));
                }
            }
        }
        return $stats;
    }

    public function getSnapshot($outlet_id, $date) {
        return $this->CI->db->query("SELECT * FROM tbl_ai_snapshots WHERE outlet_id = ? AND report_date = ?", array((int) $outlet_id, $date))->row();
    }

    /** insert or update the outlet's row of the day (unique outlet_id + report_date) */
    private function saveSnapshot($company_id, $outlet_id, $date, $fields) {
        $now = date('Y-m-d H:i:s');
        $cols = array_merge(array('company_id' => (int) $company_id, 'outlet_id' => (int) $outlet_id, 'report_date' => $date,
            'window_days' => 28, 'created_at' => $now, 'updated_at' => $now), $fields);
        $names = array_keys($cols);
        $update = array();
        foreach (array_keys($fields) as $f) {
            $update[] = "$f = VALUES($f)";
        }
        $update[] = 'updated_at = VALUES(updated_at)';
        $this->CI->db->query('INSERT INTO tbl_ai_snapshots (' . implode(', ', $names) . ') VALUES (' . implode(', ', array_fill(0, count($names), '?')) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', $update), array_values($cols));
    }
}
