<?php
/*
  ###########################################################
  # This is Ai_report Library
  ###########################################################
  # Makes the daily report of an outlet: numbers (Ai_metrics_model)
  # -> tbl_ai_snapshots -> text (Ai_narrator). One row per outlet
  # per day (UNIQUE outlet_id + report_date), so a re-run updates
  # instead of adding. Used by Ai_cron (hourly), the page's
  # Regenerate button, and pageData() for the AI Insight page.
  # Plan: docs/ai_insight_plan.md 5, 10
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
     * The page's Regenerate button: today's report of one outlet again, at most
     * ai_regen_per_day times per outlet per day, never two at once for the same outlet.
     * @param int $company_id
     * @param int $outlet_id
     * @param int $user_id
     * @return array action busy|limit|done (+ runOutlet() fields when done)
     */
    public function regenerate($company_id, $outlet_id, $user_id) {
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        $date = $this->CI->Ai_metrics_model->companyToday($company_id);
        $lock = 'ai_regen_' . $this->CI->db->database . '_' . $outlet_id;
        if ((int) $this->CI->db->query("SELECT GET_LOCK(?, 0) AS l", array($lock))->row('l') !== 1) {
            return array('action' => 'busy');
        }
        try {
            // the day's row must exist before a click can be counted on it
            $now = date('Y-m-d H:i:s');
            $this->CI->db->query("INSERT IGNORE INTO tbl_ai_snapshots (company_id, outlet_id, report_date, window_days, narrative_status, created_at, updated_at)
                VALUES (?, ?, ?, 28, 'pending', ?, ?)", array($company_id, $outlet_id, $date, $now, $now));
            $this->CI->db->query("UPDATE tbl_ai_snapshots SET regen_count = regen_count + 1 WHERE outlet_id = ? AND report_date = ? AND regen_count < ?",
                array($outlet_id, $date, (int) ai_config('ai_regen_per_day')));
            if ($this->CI->db->affected_rows() !== 1) {
                return array('action' => 'limit');
            }
            return $this->runOutlet($company_id, $outlet_id, $date, true, (int) $user_id);
        } finally {
            $this->CI->db->query("SELECT RELEASE_LOCK(?)", array($lock));
        }
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

    /**
     * Everything the AI Insight page shows for one outlet, except the permission flags.
     * Reads only: no AI call. The live Data Health is checked here; the sections use the report.
     * @param int $days 28 or 7: period of the menu, purchase and P&L tables
     * @return array view data of ai_insight/index
     */
    public function pageData($company_id, $outlet_id, $days) {
        $today = $this->CI->Ai_metrics_model->companyToday($company_id);
        $data = array();
        $data['days'] = (int) $days === 7 ? 7 : 28;
        $data['today'] = $today;
        $data['health'] = $this->CI->Ai_metrics_model->dataHealth($company_id, $outlet_id, $today);
        $data['checked_at'] = date('Y-m-d H:i');
        $data['report'] = $this->pageReport($this->CI->Ai_model->latestSnapshot($outlet_id, $today), $today);
        // the report's numbers; every section partial reads them
        $data['m'] = $data['report'] ? $data['report']['m'] : null;
        $data['regen_max'] = (int) ai_config('ai_regen_per_day');
        $data['regen_left'] = max(0, $data['regen_max'] - $this->CI->Ai_model->regenCount($outlet_id, $today));
        $data['report_hour'] = (int) ai_config('ai_report_hour');

        $this->CI->load->library('Ai/Ai_format', null, 'Ai_format');
        if ($data['m']) {
            $this->CI->Ai_format->setCurrency($data['m']['meta']['currency'], $data['m']['meta']['precision']);
        } else {
            $company = $this->CI->Ai_metrics_model->companyInfo($company_id);
            $this->CI->Ai_format->setCurrency($company->currency, $company->precision);
        }
        $data['f'] = $this->CI->Ai_format;
        return $data;
    }

    /**
     * Numbers and text of a snapshot row, or null when it has none.
     * A row without its text yet (run killed, or still running) gets the template text.
     */
    private function pageReport($snap, $today) {
        $m = $snap ? json_decode((string) $snap->metrics_json, true) : null;
        if (!is_array($m) || !isset($m['meta'], $m['health'], $m['top5'], $m['pnl'], $m['forecast'])) {
            return null;
        }
        $text = json_decode((string) $snap->narrative_json, true);
        if (!is_array($text) || !isset($text['headline'], $text['summary'], $text['issues'], $text['sections'])) {
            $text = $this->CI->Ai_narrator->template($m);
            $text['source'] = 'template';
        }
        $issue_text = array();
        foreach ($text['issues'] as $i) {
            $issue_text[$i['id']] = $i;
        }
        return array(
            'm' => $m,
            'text' => $text,
            'issue_text' => $issue_text,
            'status' => $this->textStatus($snap, $text),
            'date' => $snap->report_date,
            'made_at' => $snap->updated_at,
            'is_today' => $snap->report_date == $today,
        );
    }

    /**
     * Who wrote the text, in words for the user. Raw errors stay in the row (Ai_cron status), never on the page (plan S12).
     * @return array kind ai|template|pending|failed, label, reason
     */
    private function textStatus($snap, $text) {
        $error = (string) $snap->error;
        if ($snap->narrative_status == 'ok' && isset($text['source']) && $text['source'] == 'ai') {
            return array('kind' => 'ai', 'label' => lang('ai_text_ai'), 'reason' => $error !== '' ? lang('ai_text_ai_partly') : '');
        }
        if ($snap->narrative_status == 'pending') {
            return array('kind' => 'pending', 'label' => lang('ai_text_standard'), 'reason' => lang('ai_text_pending'));
        }
        if ($snap->narrative_status == 'failed') {
            return array('kind' => 'failed', 'label' => lang('ai_text_standard'), 'reason' => lang('ai_text_retry'));
        }
        $reason = lang('ai_text_unavailable');
        if (strpos($error, 'call: budget') !== false) {
            $reason = lang('ai_budget_reached');
        } elseif (strpos($error, 'call: disabled') !== false) {
            $reason = lang('ai_disabled_company');
        } elseif (strpos($error, 'call: key_missing') !== false) {
            $reason = lang('ai_key_missing');
        }
        return array('kind' => 'template', 'label' => lang('ai_text_standard'), 'reason' => $reason);
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
