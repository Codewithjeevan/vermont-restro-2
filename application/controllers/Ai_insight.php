<?php
/*
  ###########################################################
  # This is Ai_insight Controller
  ###########################################################
  # AI Insight page of the selected outlet: the latest daily
  # report (top 5, menu, price, purchase vs usage, P&L,
  # prediction) and live Data Health; the Regenerate button;
  # the company's AI Settings; and the AI Companies page where
  # the main admin switches AI on per company and sets the
  # monthly limit. The page never calls the AI itself, only
  # Regenerate does. Plan: docs/ai_insight_plan.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_insight extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/ai_insight_migration.sql
    const ACCESS_INSIGHT = 437;
    const ACCESS_SETTINGS = 441;

    // methods only the main admin (company 1, role Admin) may open
    private $main_admin_methods = array('companies', 'saveCompany', 'testConnection');
    // Ask AI endpoints: they answer JSON, also when refused
    private $json_methods = array('chatSend', 'chatNew');
    private $is_json = false;

    private $company_id;
    private $outlet_id;
    private $user_id;

    public function __construct() {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('Common_model');
        $this->load->model('Ai_model');
        $this->load->model('Ai_metrics_model');
        $this->Common_model->setDefaultTimezone();

        $segment_2 = $this->uri->segment(2);
        if (!$segment_2) {
            $segment_2 = 'index';
        }
        $this->is_json = in_array($segment_2, $this->json_methods);
        if (!$this->session->has_userdata('user_id')) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ai_chat_reload')), 401);
            }
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ai_chat_reload')), 409);
            }
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
            rememberClickedRedirect();
            redirect('Outlet/outlets');
        }
        $this->company_id = (int) $this->session->userdata('company_id');
        $this->outlet_id = (int) $this->session->userdata('outlet_id');
        $this->user_id = (int) $this->session->userdata('user_id');

        //start check access function
        $map = array(
            'index' => array(self::ACCESS_INSIGHT, 'view'),
            'regenerate' => array(self::ACCESS_INSIGHT, 'regenerate'),
            'chatSend' => array(self::ACCESS_INSIGHT, 'chat'),
            'chatNew' => array(self::ACCESS_INSIGHT, 'chat'),
            'settings' => array(self::ACCESS_SETTINGS, 'update'),
            'saveSettings' => array(self::ACCESS_SETTINGS, 'update'),
        );
        if (in_array($segment_2, $this->main_admin_methods)) {
            if (!ai_is_main_admin()) {
                $this->deny();
            }
        } elseif (!isset($map[$segment_2]) || !checkAccess($map[$segment_2][0], $map[$segment_2][1])) {
            $this->deny();
        }
        //end check access function

        if (!ai_tables_ready()) {
            $this->message(lang('ai_not_ready'));
        }
        if (!in_array($segment_2, $this->main_admin_methods)) {
            if (!ai_company_enabled($this->company_id)) {
                $this->message(lang('ai_not_enabled'));
            }
            // the session outlet must belong to the session company (plan S2)
            if (!$this->Ai_model->getCompanyOutlet($this->outlet_id, $this->company_id)) {
                if ($this->is_json) {
                    $this->json(array('ok' => false, 'error' => lang('ai_chat_reload')), 409);
                }
                $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
                redirect('Outlet/outlets');
            }
        }

        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

    private function deny() {
        if ($this->is_json) {
            $this->json(array('ok' => false, 'error' => lang('menu_not_permit_access')), 403);
        }
        $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
        redirect('Authentication/userProfile');
    }

    /** answer JSON and stop */
    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** a full page with one notice, then stop */
    private function message($text) {
        if ($this->is_json) {
            $this->json(array('ok' => false, 'error' => $text), 403);
        }
        $data = array('notice' => $text);
        $data['main_content'] = $this->load->view('ai_insight/message', $data, TRUE);
        $this->load->view('userHome', $data);
        $this->output->_display();
        exit;
    }

    private function render($view, $data) {
        $data['main_content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('userHome', $data);
    }

    private function back($url, $error = '', $ok = '') {
        if ($error) {
            $this->session->set_flashdata('exception_er', $error);
        }
        if ($ok) {
            $this->session->set_flashdata('exception', $ok);
        }
        redirect($url);
    }

    /* =================================================================
     * AI Insight page of the selected outlet
     * ================================================================= */

    /**
     * @param int $days 28 (default) or 7: the period of the menu, purchase and P&L tables
     */
    public function index($days = 28) {
        $this->load->library('Ai/Ai_report', null, 'Ai_report');
        $data = $this->Ai_report->pageData($this->company_id, $this->outlet_id, $days);
        $data['can_regenerate'] = checkAccess(self::ACCESS_INSIGHT, 'regenerate');
        $data['chat'] = null;
        if (checkAccess(self::ACCESS_INSIGHT, 'chat')) {
            $this->load->library('Ai/Ai_chat', null, 'Ai_chat');
            $data['chat'] = $this->Ai_chat->panelData($this->company_id, $this->outlet_id, $this->user_id);
        }
        $this->render('ai_insight/index', $data);
    }

    /* =================================================================
     * Ask AI (permission 439): JSON endpoints of the chat panel
     * ================================================================= */

    /** one question; POST question, session_id (0 = new chat), header X-AI-CSRF */
    public function chatSend() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->json(array('ok' => false, 'error' => lang('ai_invalid_request')), 403);
        }
        $question = (string) $this->input->post('question');
        $session_id = (int) $this->input->post('session_id');
        // the answer can take up to half a minute: free the user's session for their other tabs
        session_write_close();
        @set_time_limit(180);
        $this->load->library('Ai/Ai_chat', null, 'Ai_chat');
        $this->load->library('Ai/Ai_format', null, 'Ai_format');
        try {
            $r = $this->Ai_chat->ask($this->company_id, $this->outlet_id, $this->user_id, $session_id, $question);
        } catch (Throwable $e) {
            log_message('error', 'AI chat outlet ' . $this->outlet_id . ': ' . ai_redact($e->getMessage()));
            $this->json(array('ok' => false, 'error' => lang('ai_chat_failed')), 500);
        }
        $panel = $this->Ai_chat->panelData($this->company_id, $this->outlet_id, $this->user_id);
        $this->json(array(
            'ok' => $r['ok'],
            // escaped and formatted on the server: the page inserts this HTML, never the raw text (plan S6)
            'html' => $r['ok'] ? $this->Ai_format->text($r['answer']) : '',
            'error' => $r['error'],
            'session_id' => $r['session_id'],
            'left' => $panel ? $this->chatLeft($panel, $r['session_id'] ? $r['questions'] : 0) : '',
            'chat_full' => $panel && $r['session_id'] && $r['questions'] >= $panel['per_chat'],
        ));
    }

    /** "New chat": close the user's open conversation on this outlet */
    public function chatNew() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->json(array('ok' => false, 'error' => lang('ai_invalid_request')), 403);
        }
        $this->load->library('Ai/Ai_chat', null, 'Ai_chat');
        $this->Ai_chat->closeSessions($this->company_id, $this->outlet_id, $this->user_id);
        $panel = $this->Ai_chat->panelData($this->company_id, $this->outlet_id, $this->user_id);
        $this->json(array('ok' => true, 'left' => $panel ? $this->chatLeft($panel, 0) : ''));
    }

    /** "12 of 30 questions left today · 7 of 10 in this chat" */
    private function chatLeft($panel, $questions) {
        return sprintf(lang('ai_chat_left'), max(0, $panel['per_day'] - $panel['today']), $panel['per_day'])
            . ' · ' . sprintf(lang('ai_chat_left_chat'), max(0, $panel['per_chat'] - $questions), $panel['per_chat']);
    }

    /** today's report of the session outlet again (permission 440, ai_regen_per_day per outlet per day) */
    public function regenerate() {
        $back = 'Ai_insight/index' . ((int) $this->input->post('days') === 7 ? '/7' : '');
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->back($back, lang('ai_invalid_request'));
        }
        @set_time_limit(300);
        ignore_user_abort(true);
        $this->load->library('Ai/Ai_report', null, 'Ai_report');
        try {
            $r = $this->Ai_report->regenerate($this->company_id, $this->outlet_id, $this->user_id);
        } catch (Throwable $e) {
            log_message('error', 'AI regenerate outlet ' . $this->outlet_id . ': ' . ai_redact($e->getMessage()));
            $this->back($back, lang('ai_error_generic'));
        }
        if ($r['action'] == 'busy') {
            $this->back($back, lang('ai_regen_busy'));
        }
        if ($r['action'] == 'limit') {
            $this->back($back, sprintf(lang('ai_regen_limit'), (int) ai_config('ai_regen_per_day')));
        }
        $this->back($back, '', $r['status'] == 'ok' ? lang('ai_regen_done') : lang('ai_regen_done_standard'));
    }

    /* =================================================================
     * AI Settings of the company (permission 441)
     * ================================================================= */

    public function settings() {
        $data = array();
        $data['settings'] = $this->Ai_model->getSettings($this->company_id);
        $data['steps'] = ai_config('ai_rounding_steps');
        $data['currency'] = $this->Ai_metrics_model->companyInfo($this->company_id)->currency;
        $this->render('ai_insight/settings', $data);
    }

    public function saveSettings() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->back('Ai_insight/settings', lang('ai_invalid_request'));
        }
        $number = function ($name, $min, $max, $whole) {
            $v = trim((string) $this->input->post($name));
            if (!is_numeric($v) || ($whole && !ctype_digit($v)) || $v < $min || $v > $max) {
                return null;
            }
            return $whole ? (int) $v : round((float) $v, 2);
        };
        $values = array(
            'target_food_cost_pct' => $number('target_food_cost_pct', 15, 60, false),
            'overbuy_pct' => $number('overbuy_pct', 5, 200, true),
            'cover_days_limit' => $number('cover_days_limit', 3, 60, true),
            'safety_days' => $number('safety_days', 0, 7, true),
            'price_rounding' => null,
        );
        $rounding = (float) $this->input->post('price_rounding');
        foreach (ai_config('ai_rounding_steps') as $step) {
            if (abs($rounding - $step) < 0.0001) {
                $values['price_rounding'] = $step;
            }
        }
        if (in_array(null, $values, true)) {
            $this->back('Ai_insight/settings', lang('ai_settings_invalid'));
        }
        $values['overbuy_factor'] = round(1 + $values['overbuy_pct'] / 100, 2);
        $this->Ai_model->saveCompanySettings($this->company_id, $values, $this->user_id);
        putAuditLog($this->user_id, 'AI Insight settings: target food cost ' . $values['target_food_cost_pct'] . '%, rounding ' . $values['price_rounding']
            . ', over-purchase above ' . $values['overbuy_pct'] . '%, stock cover ' . $values['cover_days_limit'] . ' days, safety ' . $values['safety_days'] . ' days',
            'AI Insight settings', date('Y-m-d H:i:s'));
        $this->back('Ai_insight/settings', '', lang('ai_settings_saved'));
    }

    /* =================================================================
     * AI Companies: main admin only
     * ================================================================= */

    public function companies() {
        $this->load->library('Ai/Ai_client', null, 'Ai_client');
        $companies = $this->Ai_model->getCompaniesOverview();
        foreach ($companies as $c) {
            if ($c->enabled === null) {
                // first visit: show the defaults a new settings row would get
                $settings = $this->Ai_model->getSettings($c->id);
                $c->enabled = $settings->enabled;
                $c->monthly_budget_usd = $settings->monthly_budget_usd;
                $c->report_model = $settings->report_model;
                $c->chat_model = $settings->chat_model;
                $c->chat_enabled = $settings->chat_enabled;
                $c->chat_daily_limit = $settings->chat_daily_limit;
            }
        }
        $data = array();
        $data['companies'] = $companies;
        $data['models'] = ai_config('ai_models');
        $data['key_configured'] = $this->Ai_client->keyConfigured();
        $data['mock_mode'] = $this->Ai_client->mockMode();
        $data['test_result'] = $this->session->flashdata('ai_test_result');
        unset($_SESSION['ai_test_result']);
        $this->render('ai_insight/companies', $data);
    }

    public function saveCompany() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->back('Ai_insight/companies', lang('ai_invalid_request'));
        }
        $company_id = (int) $this->input->post('company_id');
        if (!$this->Ai_model->companyExists($company_id)) {
            $this->back('Ai_insight/companies', lang('ai_invalid_request'));
        }
        $models = ai_config('ai_models');
        $budget = str_replace(',', '', (string) $this->input->post('monthly_budget_usd'));
        $report_model = (string) $this->input->post('report_model');
        $chat_model = (string) $this->input->post('chat_model');
        $daily = trim((string) $this->input->post('chat_daily_limit'));
        if (!is_numeric($budget) || $budget < 0 || $budget > 1000 || !isset($models[$report_model]) || !isset($models[$chat_model])
            || !ctype_digit($daily) || (int) $daily > 200) {
            $this->back('Ai_insight/companies', lang('ai_invalid_value'));
        }
        $values = array(
            'enabled' => (int) $this->input->post('enabled') === 1,
            'monthly_budget_usd' => round((float) $budget, 2),
            'report_model' => $report_model,
            'chat_model' => $chat_model,
            'chat_enabled' => (int) $this->input->post('chat_enabled') === 1,
            'chat_daily_limit' => (int) $daily,
        );
        $this->Ai_model->saveCompanySwitch($company_id, $values, $this->user_id);
        putAuditLog($this->user_id, 'AI Insight for company #' . $company_id . ': ' . ($values['enabled'] ? 'on' : 'off')
            . ', limit ' . $values['monthly_budget_usd'] . ' USD, report ' . $report_model . ', chat ' . $chat_model
            . ' ' . ($values['chat_enabled'] ? 'on' : 'off') . ' (' . $values['chat_daily_limit'] . ' questions per user per day)',
            'AI Insight settings', date('Y-m-d H:i:s'));
        $this->back('Ai_insight/companies', '', lang('ai_saved'));
    }

    /** one tiny request with the main company's chat model (a fraction of a cent) */
    public function testConnection() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            $this->back('Ai_insight/companies', lang('ai_invalid_request'));
        }
        $this->load->library('Ai/Ai_client', null, 'Ai_client');
        $settings = $this->Ai_model->getSettings($this->company_id);
        $started = microtime(true);
        $r = $this->Ai_client->send(
            array('company_id' => $this->company_id, 'outlet_id' => $this->outlet_id, 'user_id' => $this->user_id,
                'feature' => 'test', 'ignore_switch' => true),
            array(
                'model' => $settings->chat_model,
                'maxTokens' => 64,
                'outputConfig' => array('effort' => 'low'),
                'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: OK')),
            ),
            (int) ai_config('ai_timeout_chat')
        );
        $models = ai_config('ai_models');
        $name = isset($models[$settings->chat_model]) ? $models[$settings->chat_model] : $settings->chat_model;
        if ($r['ok']) {
            $text = sprintf(lang('ai_test_ok'), $name, number_format(microtime(true) - $started, 1));
            $this->session->set_flashdata('ai_test_result', array('ok' => true, 'text' => $text));
        } else {
            $text = sprintf(lang('ai_test_failed'), $r['error'] . ' (' . $r['error_code'] . ')');
            $this->session->set_flashdata('ai_test_result', array('ok' => false, 'text' => $text));
        }
        redirect('Ai_insight/companies');
    }
}
