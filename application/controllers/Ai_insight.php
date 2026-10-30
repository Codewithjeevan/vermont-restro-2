<?php
/*
  ###########################################################
  # This is Ai_insight Controller
  ###########################################################
  # AI Insight page of the selected outlet (data health now;
  # menu, price, purchase, P&L, prediction and top 5 once the
  # daily report runs) and the AI Companies page where the main
  # admin switches AI on per company and sets the monthly limit.
  # Plan: docs/ai_insight_plan.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_insight extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/ai_insight_migration.sql
    const ACCESS_INSIGHT = 437;
    const ACCESS_SETTINGS = 441;

    // methods only the main admin (company 1, role Admin) may open
    private $main_admin_methods = array('companies', 'saveCompany', 'testConnection');

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

        if (!$this->session->has_userdata('user_id')) {
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
            rememberClickedRedirect();
            redirect('Outlet/outlets');
        }
        $this->company_id = (int) $this->session->userdata('company_id');
        $this->outlet_id = (int) $this->session->userdata('outlet_id');
        $this->user_id = (int) $this->session->userdata('user_id');

        //start check access function
        $segment_2 = $this->uri->segment(2);
        if (!$segment_2) {
            $segment_2 = 'index';
        }
        $map = array(
            'index' => array(self::ACCESS_INSIGHT, 'view'),
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
                $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
                redirect('Outlet/outlets');
            }
        }

        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

    private function deny() {
        $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
        redirect('Authentication/userProfile');
    }

    /** a full page with one notice, then stop */
    private function message($text) {
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

    public function index() {
        $today = $this->Ai_metrics_model->companyToday($this->company_id);
        $data = array();
        $data['health'] = $this->Ai_metrics_model->dataHealth($this->company_id, $this->outlet_id, $today);
        $data['checked_at'] = date('Y-m-d H:i');
        $this->render('ai_insight/index', $data);
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
        if (!is_numeric($budget) || $budget < 0 || $budget > 1000 || !isset($models[$report_model]) || !isset($models[$chat_model])) {
            $this->back('Ai_insight/companies', lang('ai_invalid_value'));
        }
        $values = array(
            'enabled' => (int) $this->input->post('enabled') === 1,
            'monthly_budget_usd' => round((float) $budget, 2),
            'report_model' => $report_model,
            'chat_model' => $chat_model,
        );
        $this->Ai_model->saveCompanySwitch($company_id, $values, $this->user_id);
        putAuditLog($this->user_id, 'AI Insight for company #' . $company_id . ': ' . ($values['enabled'] ? 'on' : 'off')
            . ', limit ' . $values['monthly_budget_usd'] . ' USD, report ' . $report_model . ', chat ' . $chat_model,
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
