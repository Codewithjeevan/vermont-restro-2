<?php
/*
  ###########################################################
  # This is Email_invoice Controller
  ###########################################################
  # Email bills to customers (docs/email_invoice.md).
  #   settings / saveSettings / testEmail   SMTP account (permission 443 update)
  #   log / retry                           sent + failed emails (443 view / send)
  #   send / saleInfo                       Sales list "Email Invoice" (443 send)
  #   process / customerEmail               used by the POS, any logged-in user of
  #                                         the company (only sends what is queued)
  # Sending never happens inside Sale/push_online: that only queues.
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_invoice extends Cl_Controller {

    // tbl_access parent id, mirrored in Update/email_invoice_migration.sql
    const ACCESS_EMAIL = 443;

    private $json_methods = array('testEmail', 'send', 'saleInfo', 'process', 'customerEmail');
    private $is_json = false;

    private $company_id;
    private $outlet_id;
    private $user_id;

    public function __construct() {
        parent::__construct();
        $this->load->model('Common_model');
        $this->load->library('Invoice_mailer');
        $this->Common_model->setDefaultTimezone();

        $segment_2 = $this->uri->segment(2);
        if (!$segment_2) {
            $segment_2 = 'settings';
        }
        $this->is_json = in_array($segment_2, $this->json_methods, true);
        if (!$this->session->has_userdata('user_id')) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ei_reload')), 401);
            }
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ei_reload')), 409);
            }
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
            redirect('Outlet/outlets');
        }
        $this->company_id = (int) $this->session->userdata('company_id');
        $this->outlet_id = (int) $this->session->userdata('outlet_id');
        $this->user_id = (int) $this->session->userdata('user_id');

        //start check access function
        $map = array(
            'index' => 'update',
            'settings' => 'update',
            'saveSettings' => 'update',
            'testEmail' => 'update',
            'log' => 'view',
            'retry' => 'send',
            'send' => 'send',
            'saleInfo' => 'send',
            'process' => null,
            'customerEmail' => null,
        );
        if (!array_key_exists($segment_2, $map)) {
            show_404();
        }
        if ($map[$segment_2] !== null && !checkAccess(self::ACCESS_EMAIL, $map[$segment_2])) {
            $this->deny();
        }
        //end check access function

        if (!$this->invoice_mailer->tablesReady()) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ei_not_ready')), 503);
            }
            $this->message(lang('ei_not_ready'));
        }
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
        $data = array('notice' => $text);
        $data['main_content'] = $this->load->view('email_invoice/message', $data, TRUE);
        $this->load->view('userHome', $data);
        $this->output->_display();
        exit;
    }

    /** posted forms and JSON calls that change something need the token and a same-site origin */
    private function requirePost() {
        if ($this->input->method() !== 'post' || !ai_csrf_ok()) {
            if ($this->is_json) {
                $this->json(array('ok' => false, 'error' => lang('ei_reload')), 400);
            }
            $this->session->set_flashdata('exception_1', lang('ei_reload'));
            redirect('Email_invoice/settings');
        }
    }

    /** long SMTP calls must not hold the session lock, or every other POS request waits */
    private function releaseSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        @set_time_limit(60);
        ignore_user_abort(true);
    }

    // ------------------------------------------------------------------
    // settings
    // ------------------------------------------------------------------

    public function index() {
        redirect('Email_invoice/settings');
    }

    public function settings() {
        $data = array();
        $data['settings'] = $this->invoice_mailer->getSettings($this->company_id);
        $data['providers'] = Invoice_mailer::$providers;
        $data['pending'] = $this->invoice_mailer->pendingCount($this->company_id);
        $data['main_content'] = $this->load->view('email_invoice/settings', $data, TRUE);
        $this->load->view('userHome', $data);
    }

    /** checked form values; $errors gets one message per bad field */
    private function readForm(&$errors) {
        $p = function ($key, $max = 150) {
            $v = $this->input->post($key, false);
            $v = is_string($v) ? trim($v) : '';
            return substr($v, 0, $max);
        };
        $d = array(
            'enabled' => $this->input->post('enabled') ? 1 : 0,
            'provider' => $p('provider', 30),
            'host' => strtolower($p('host')),
            'port' => (int) $p('port', 5),
            'encryption' => $p('encryption', 10),
            'username' => $p('username'),
            'password' => (string) $this->input->post('password', false),
            'from_email' => $p('from_email'),
            'from_name' => $p('from_name', 100),
            'reply_to' => $p('reply_to'),
            'bcc_email' => $p('bcc_email'),
            'pos_auto_tick' => $this->input->post('pos_auto_tick') ? 1 : 0,
        );
        if (strlen($d['password']) > 200 || preg_match('/[\r\n]/', $d['password'])) {
            $errors[] = lang('ei_err_password');
            $d['password'] = '';
        }
        if (!isset(Invoice_mailer::$providers[$d['provider']])) {
            $d['provider'] = 'custom';
        }
        if (!in_array($d['encryption'], array('tls', 'ssl', 'none'), true)) {
            $errors[] = lang('ei_err_encryption');
        }
        if ($d['host'] !== '' && !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $d['host'])) {
            $errors[] = lang('ei_err_host');
        }
        if ($d['port'] < 1 || $d['port'] > 65535) {
            $errors[] = lang('ei_err_port');
        }
        foreach (array('from_email' => 'ei_from_email', 'reply_to' => 'ei_reply_to', 'bcc_email' => 'ei_bcc_email') as $key => $label) {
            if ($d[$key] !== '' && !$this->invoice_mailer->validEmail($d[$key])) {
                $errors[] = sprintf(lang('ei_err_email_field'), lang($label));
            }
        }
        $d['from_name'] = preg_replace('/[\r\n]+/', ' ', $d['from_name']);
        return $d;
    }

    public function saveSettings() {
        $this->requirePost();
        $errors = array();
        $d = $this->readForm($errors);
        $saved = $this->invoice_mailer->getSettings($this->company_id);
        if ($d['enabled']) {
            if ($d['host'] === '') {
                $errors[] = lang('ei_err_host');
            }
            if ($d['username'] === '') {
                $errors[] = lang('ei_err_username');
            }
            if ($d['password'] === '' && (string) $saved->password === '') {
                $errors[] = lang('ei_err_password');
            }
            $from = $d['from_email'] !== '' ? $d['from_email'] : $d['username'];
            if (!$this->invoice_mailer->validEmail($from)) {
                $errors[] = lang('ei_from_invalid');
            }
        }
        if ($errors) {
            $this->session->set_flashdata('exception_1', implode(' ', array_unique($errors)));
            redirect('Email_invoice/settings');
        }
        if ($this->invoice_mailer->saveSettings($this->company_id, $d, $this->user_id)) {
            $this->session->set_flashdata('exception', lang('ei_saved'));
        } else {
            $this->session->set_flashdata('exception_1', lang('ei_save_error'));
        }
        redirect('Email_invoice/settings');
    }

    /** send a test with the values on the form (saved password when the field is empty) */
    public function testEmail() {
        $this->requirePost();
        $errors = array();
        $d = $this->readForm($errors);
        if ($d['host'] === '') {
            $errors[] = lang('ei_err_host');
        }
        if ($d['username'] === '') {
            $errors[] = lang('ei_err_username');
        }
        $saved = $this->invoice_mailer->getSettings($this->company_id);
        $password = $d['password'] !== '' ? $d['password'] : $this->invoice_mailer->decryptSecret($saved->password);
        if ($password === null || $password === '') {
            $errors[] = lang('ei_err_password');
        }
        $to = trim((string) $this->input->post('test_to'));
        if ($to === '') {
            $to = $d['from_email'] !== '' ? $d['from_email'] : $d['username'];
        }
        if (!$this->invoice_mailer->validEmail($to)) {
            $errors[] = lang('ei_invalid_email');
        }
        if ($errors) {
            $this->json(array('ok' => false, 'error' => implode(' ', array_unique($errors))));
        }
        $this->releaseSession();

        $s = (object) $d;
        $s->plain_password = $password;
        $sample = null;
        $sale_id = $this->invoice_mailer->latestSaleId($this->company_id);
        if ($sale_id) {
            $sample = $this->invoice_mailer->buildInvoiceEmail($sale_id, $this->company_id);
        }
        if ($sample) {
            $subject = lang('ei_test_subject') . ' - ' . $sample['subject'];
            $html = '<p style="font-family:Arial,sans-serif;font-size:14px;background:#fff8e1;padding:10px;margin:0;">' . htmlspecialchars(lang('ei_test_body'), ENT_QUOTES, 'UTF-8') . '</p>' . $sample['html'];
            $text = lang('ei_test_body') . "\n\n" . $sample['text'];
            $from_name = $d['from_name'] !== '' ? $d['from_name'] : $sample['outlet_name'];
        } else {
            $subject = lang('ei_test_subject');
            $html = '<p style="font-family:Arial,sans-serif;font-size:14px;">' . htmlspecialchars(lang('ei_test_body'), ENT_QUOTES, 'UTF-8') . '</p>';
            $text = lang('ei_test_body');
            $from_name = $d['from_name'];
        }
        $result = $this->invoice_mailer->smtpSend($s, $to, $subject, $html, $text, $from_name, $d['reply_to'], '');
        $this->invoice_mailer->recordTest($this->company_id, $result['ok'], $result['error']);
        if ($result['ok']) {
            $this->json(array('ok' => true, 'msg' => sprintf(lang('ei_test_ok'), $to)));
        }
        $this->json(array('ok' => false, 'error' => $result['error'], 'hint' => $this->hintFor($result['error'], $d)));
    }

    /** plain-language help for the errors people actually hit */
    private function hintFor($error, $d) {
        $e = strtolower((string) $error);
        if (strpos($e, 'authenticate') !== false || strpos($e, 'password not accepted') !== false || strpos($e, '535') !== false) {
            return $d['host'] === 'smtp.gmail.com' ? lang('ei_hint_gmail') : lang('ei_hint_auth');
        }
        if (strpos($e, 'connect') !== false || strpos($e, 'timed out') !== false || strpos($e, 'timeout') !== false) {
            return lang('ei_hint_connect');
        }
        if (strpos($e, 'certificate') !== false || strpos($e, 'ssl') !== false || strpos($e, 'tls') !== false) {
            return lang('ei_hint_tls');
        }
        return '';
    }

    // ------------------------------------------------------------------
    // log
    // ------------------------------------------------------------------

    public function log() {
        $data = array();
        $data['rows'] = $this->invoice_mailer->recentLog($this->company_id, null, 200);
        $data['settings'] = $this->invoice_mailer->getSettings($this->company_id);
        $data['can_send'] = checkAccess(self::ACCESS_EMAIL, 'send');
        $data['outlets'] = array();
        foreach ($this->Common_model->getAllByCustomId($this->company_id, 'company_id', 'tbl_outlets', '') as $o) {
            $data['outlets'][(int) $o->id] = $o->outlet_name;
        }
        $data['main_content'] = $this->load->view('email_invoice/log', $data, TRUE);
        $this->load->view('userHome', $data);
    }

    public function retry() {
        $this->requirePost();
        $id = (int) $this->input->post('id');
        if ($this->invoice_mailer->retry($id, $this->company_id)) {
            $this->session->set_flashdata('exception', lang('ei_retry_queued'));
        } else {
            $this->session->set_flashdata('exception_1', lang('ei_retry_error'));
        }
        redirect('Email_invoice/log');
    }

    // ------------------------------------------------------------------
    // send one bill (Sales list)
    // ------------------------------------------------------------------

    public function saleInfo() {
        $sale_id = (int) $this->input->get('sale_id');
        $sale = $this->db->select('id, sale_no, customer_id, company_id')->get_where('tbl_sales', array('id' => $sale_id, 'del_status' => 'Live'), 1)->row();
        if (!$sale || (int) $sale->company_id !== $this->company_id) {
            $this->json(array('ok' => false, 'error' => lang('ei_sale_not_found')));
        }
        $last = $this->invoice_mailer->lastForSale($sale_id, $this->company_id);
        $this->json(array(
            'ok' => true,
            'enabled' => $this->invoice_mailer->isEnabled($this->company_id),
            'sale_no' => $sale->sale_no,
            'email' => $last ? $last->to_email : $this->invoice_mailer->customerEmail($sale->customer_id, $this->company_id),
            'last' => $last ? array(
                'status' => $last->status,
                'status_text' => lang('ei_status_' . $last->status),
                'to_email' => $last->to_email,
                'at' => $last->sent_at ? $last->sent_at : $last->created_at,
                'error' => $last->last_error,
            ) : null,
        ));
    }

    public function send() {
        $this->requirePost();
        $sale_id = (int) $this->input->post('sale_id');
        $email = trim((string) $this->input->post('email'));
        $result = $this->invoice_mailer->queueSale($sale_id, $this->company_id, $email, 'resend', $this->user_id, true);
        if (!$result['ok']) {
            $this->json(array('ok' => false, 'error' => $result['msg']));
        }
        // keep a new address on a customer that had none
        $sale = $this->db->select('customer_id')->get_where('tbl_sales', array('id' => $sale_id), 1)->row();
        if ($sale) {
            $this->invoice_mailer->rememberCustomerEmail($sale->customer_id, $this->company_id, $email);
        }
        $this->releaseSession();
        $this->invoice_mailer->process($this->company_id, 3, 25);
        $row = $this->db->get_where('tbl_email_queue', array('id' => (int) $result['queue_id']), 1)->row();
        $status = $row ? $row->status : 'pending';
        if ($status === 'sent') {
            $this->json(array('ok' => true, 'status' => $status, 'msg' => sprintf(lang('ei_sent_to'), $email)));
        }
        if ($status === 'failed') {
            $this->json(array('ok' => false, 'status' => $status, 'error' => lang('ei_status_failed') . ': ' . ($row ? $row->last_error : '')));
        }
        $this->json(array('ok' => true, 'status' => $status, 'msg' => lang('ei_will_retry') . ($row && $row->last_error ? ' (' . $row->last_error . ')' : '')));
    }

    // ------------------------------------------------------------------
    // POS
    // ------------------------------------------------------------------

    /** send what is due for this company; the POS calls it after a bill with "Email bill" reaches the server */
    public function process() {
        // "Send now" on the log page also sends rows still waiting for their retry time
        $now_all = $this->input->post('now') === '1' && checkAccess(self::ACCESS_EMAIL, 'send');
        $this->releaseSession();
        $stats = $this->invoice_mailer->process($this->company_id, $now_all ? 10 : 3, 25, $now_all);
        $this->json(array('ok' => true) + $stats);
    }

    public function customerEmail() {
        $id = (int) $this->input->get('id');
        $this->json(array('ok' => true, 'email' => $this->invoice_mailer->customerEmail($id, $this->company_id)));
    }
}
