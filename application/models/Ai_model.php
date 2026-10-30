<?php
/*
  ###########################################################
  # This is Ai_model Model
  ###########################################################
  # AI Insight storage: per-company settings (on/off, monthly
  # limit, models, thresholds), the usage log that enforces the
  # limits, and the company list of the AI Companies page.
  # Calculations live in Ai_metrics_model.
  # Plan: docs/ai_insight_plan.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_model extends CI_Model {

    /**
     * Settings row of a company, created with the config/ai.php defaults (AI off) on first use.
     * @param int $company_id
     * @return object
     */
    public function getSettings($company_id) {
        $company_id = (int) $company_id;
        $row = $this->db->query("SELECT * FROM tbl_ai_settings WHERE company_id = ?", array($company_id))->row();
        if ($row) {
            return $row;
        }
        $defaults = ai_config('ai_defaults');
        $this->db->query("INSERT IGNORE INTO tbl_ai_settings
            (company_id, enabled, monthly_budget_usd, report_model, chat_model, chat_daily_limit,
             target_food_cost_pct, price_rounding, overbuy_factor, cover_days_limit, safety_days, updated_at)
            VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(
            $company_id,
            $defaults['monthly_budget_usd'],
            ai_config('ai_default_report_model'),
            ai_config('ai_default_chat_model'),
            $defaults['chat_daily_limit'],
            $defaults['target_food_cost_pct'],
            $defaults['price_rounding'],
            $defaults['overbuy_factor'],
            $defaults['cover_days_limit'],
            $defaults['safety_days'],
            date('Y-m-d H:i:s'),
        ));
        return $this->db->query("SELECT * FROM tbl_ai_settings WHERE company_id = ?", array($company_id))->row();
    }

    /**
     * Main-admin values of one company (AI Companies page). Values must already be validated.
     * @param int $company_id
     * @param array $values enabled, monthly_budget_usd, report_model, chat_model
     * @param int $user_id
     */
    public function saveCompanySwitch($company_id, $values, $user_id) {
        $this->getSettings($company_id);
        $this->db->where('company_id', (int) $company_id);
        $this->db->update('tbl_ai_settings', array(
            'enabled' => $values['enabled'] ? 1 : 0,
            'monthly_budget_usd' => $values['monthly_budget_usd'],
            'report_model' => $values['report_model'],
            'chat_model' => $values['chat_model'],
            'updated_by' => (int) $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Every live company with its AI settings and this month's usage, for the AI Companies page.
     * @return array
     */
    public function getCompaniesOverview() {
        $month_start = date('Y-m-01 00:00:00');
        return $this->db->query("SELECT c.id, c.business_name,
                (SELECT COUNT(*) FROM tbl_outlets o WHERE o.company_id = c.id AND o.del_status = 'Live') AS outlets,
                s.enabled, s.monthly_budget_usd, s.report_model, s.chat_model,
                (SELECT COALESCE(SUM(u.cost_usd), 0) FROM tbl_ai_usage u WHERE u.company_id = c.id AND u.created_at >= ?) AS spent,
                (SELECT COUNT(*) FROM tbl_ai_usage u WHERE u.company_id = c.id AND u.created_at >= ? AND u.status <> 'blocked') AS calls
            FROM tbl_companies c
            LEFT JOIN tbl_ai_settings s ON s.company_id = c.id
            WHERE c.del_status = 'Live'
            ORDER BY c.id", array($month_start, $month_start))->result();
    }

    /**
     * Is the company a live company?
     * @param int $company_id
     * @return bool
     */
    public function companyExists($company_id) {
        return (bool) $this->db->query("SELECT id FROM tbl_companies WHERE id = ? AND del_status = 'Live'", array((int) $company_id))->row();
    }

    /**
     * The outlet, only when it is live and belongs to the company (plan S2).
     * @param int $outlet_id
     * @param int $company_id
     * @return object|null
     */
    public function getCompanyOutlet($outlet_id, $company_id) {
        return $this->db->query("SELECT id, outlet_name, company_id, has_kitchen, food_menus FROM tbl_outlets
            WHERE id = ? AND company_id = ? AND del_status = 'Live'", array((int) $outlet_id, (int) $company_id))->row();
    }

    /**
     * USD spent by the company in the current calendar month.
     * @param int $company_id
     * @return float
     */
    public function monthSpend($company_id) {
        return (float) $this->db->query("SELECT COALESCE(SUM(cost_usd), 0) AS spent FROM tbl_ai_usage
            WHERE company_id = ? AND created_at >= ?", array((int) $company_id, date('Y-m-01 00:00:00')))->row('spent');
    }

    /**
     * One usage row. Never pass the API key or a raw request in $row['error'].
     * @param array $row
     * @return int
     */
    public function logUsage($row) {
        $row['created_at'] = date('Y-m-d H:i:s');
        if (isset($row['error'])) {
            $row['error'] = mb_substr(ai_redact($row['error']), 0, 255);
        }
        $this->db->insert('tbl_ai_usage', $row);
        return $this->db->insert_id();
    }
}
