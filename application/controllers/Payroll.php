<?php
/*
  ###########################################################
  # This is Payroll Controller
  ###########################################################
  # Monthly payroll from attendance: generate a month (draft),
  # review / adjust the sheet, approve (locks it), pay salaries
  # (optionally posted to Expenses), payslips, reports.
  # Setup screens (salary setup, leaves, holidays, advances &
  # adjustments, settings) live in Payroll_setup.
  # Docs: docs/payroll_module.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/payroll_module_migration.sql
    const ACCESS_RUNS = 412;
    const ACCESS_SALARY = 418;
    const ACCESS_REPORTS = 433;

    private $company_id;
    private $outlet_id;
    private $user_id;

    public function __construct() {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('Common_model');
        $this->load->model('Payroll_model');
        $this->load->library('form_validation');
        $this->Common_model->setDefaultTimezone();

        if (!$this->session->has_userdata('user_id')) {
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
            rememberClickedRedirect();
            redirect('Outlet/outlets');
        }

        //start check access function
        $segment_2 = $this->uri->segment(2);
        $map = array(
            'index' => array(self::ACCESS_RUNS, 'view'),
            'runs' => array(self::ACCESS_RUNS, 'view'),
            'run' => array(self::ACCESS_RUNS, 'view'),
            'payslip' => array(self::ACCESS_RUNS, 'view'),
            'payslips' => array(self::ACCESS_RUNS, 'view'),
            'printSheet' => array(self::ACCESS_RUNS, 'view'),
            'generate' => array(self::ACCESS_RUNS, 'add'),
            'recalculate' => array(self::ACCESS_RUNS, 'add'),
            'editItem' => array(self::ACCESS_RUNS, 'add'),
            'approve' => array(self::ACCESS_RUNS, 'approve'),
            'reopen' => array(self::ACCESS_RUNS, 'approve'),
            'pay' => array(self::ACCESS_RUNS, 'pay'),
            'payAll' => array(self::ACCESS_RUNS, 'pay'),
            'deletePayment' => array(self::ACCESS_RUNS, 'pay'),
            'deleteRun' => array(self::ACCESS_RUNS, 'delete'),
            'attendanceSheet' => array(self::ACCESS_REPORTS, 'view', 'reports'),
            'salaryHistory' => array(self::ACCESS_REPORTS, 'view', 'reports'),
            'advances' => array(self::ACCESS_REPORTS, 'view', 'reports'),
        );
        if (!$segment_2) {
            $segment_2 = 'index';
        }
        if (!isset($map[$segment_2]) || !checkAccess($map[$segment_2][0], $map[$segment_2][1])) {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        // screens hidden in config/payroll.php are closed even for Admin
        $screen = isset($map[$segment_2][2]) ? $map[$segment_2][2] : 'runs';
        if (!Payroll_model::screenOn($screen)) {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect(Payroll_model::screenOn('runs') && $screen != 'runs' ? 'Payroll/runs' : 'Authentication/userProfile');
        }
        //end check access function

        $this->company_id = $this->session->userdata('company_id');
        $this->outlet_id = $this->session->userdata('outlet_id');
        $this->user_id = $this->session->userdata('user_id');

        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

    private function render($view, $data) {
        $data['payroll_settings'] = $this->Payroll_model->getSettings($this->company_id);
        $data['main_content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('userHome', $data);
    }

    private function post($name) {
        return trim((string) $this->input->post($name, TRUE));
    }

    private function money($value) {
        return round((float) str_replace(',', '', (string) $value), 3);
    }

    /** '' stays '' (no override), anything else a number >= 0 */
    private function optionalNumber($value) {
        $value = trim(str_replace(',', '', (string) $value));
        return $value === '' ? null : max(0, (float) $value);
    }

    private function cleanDate($value, $fallback = '') {
        $value = trim((string) $value);
        if ($value && ($t = strtotime($value)) !== false) {
            return date('Y-m-d', $t);
        }
        return $fallback;
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

    /** months a payroll can be generated for: this month and the 23 before it */
    private function monthOptions() {
        $months = array();
        for ($i = 0; $i < 24; $i++) {
            $m = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
            $months[$m] = $this->Payroll_model->monthLabel($m);
        }
        return $months;
    }

    private function loadRun($id) {
        $run = $this->Payroll_model->getRun($this->company_id, (int) $id);
        if (!$run) {
            $this->back('Payroll/runs', lang('payroll_not_found'));
        }
        return $run;
    }

    /* =================================================================
     * payroll months
     * ================================================================= */

    public function index() {
        $this->runs();
    }

    public function runs() {
        $data = array();
        $data['runs'] = $this->Payroll_model->getRuns($this->company_id);
        $data['month_options'] = $this->monthOptions();
        $employees = $this->Payroll_model->getEmployeesWithSalary($this->company_id);
        $data['employees_total'] = count($employees);
        $data['employees_setup'] = 0;
        $data['monthly_gross'] = 0;
        foreach ($employees as $e) {
            if ($e->salary_id && $e->salary_status == 'active') {
                $data['employees_setup']++;
                $data['monthly_gross'] += $this->Payroll_model->monthlyGross($e);
            }
        }
        $data['current_run'] = $this->Payroll_model->getRunByMonth($this->company_id, date('Y-m'));
        $data['previous_run'] = $this->Payroll_model->getRunByMonth($this->company_id, date('Y-m', strtotime(date('Y-m-01') . ' -1 month')));
        $data['advances_open'] = 0;
        foreach ($this->Payroll_model->advanceBalances($this->company_id) as $b) {
            $data['advances_open'] += max(0, $b->balance);
        }
        $this->render('payroll/runs', $data);
    }

    public function generate() {
        $month = $this->Payroll_model->cleanMonth($this->input->post('month'));
        if (!$month || $month > date('Y-m')) {
            $this->back('Payroll/runs', lang('payroll_month_invalid'));
        }
        $run = $this->Payroll_model->getRunByMonth($this->company_id, $month);
        if ($run) {
            $this->back('Payroll/run/' . $run->id, '', lang('payroll_already_generated'));
        }
        if (!$this->Payroll_model->getEligibleSalaries($this->company_id, $month)) {
            $this->back('Payroll/runs', lang('payroll_no_eligible'));
        }
        $run_id = $this->Payroll_model->createRun($this->company_id, $month, $this->user_id);
        $this->back('Payroll/run/' . $run_id, '', lang('payroll_generated'));
    }

    public function run($id = '') {
        $run = $this->loadRun($id);
        if ($run->status == 'draft') {
            // a draft always shows the latest attendance, leaves and adjustments
            $this->Payroll_model->recalculateRun($this->company_id, $run->id);
            $run = $this->Payroll_model->getRun($this->company_id, $run->id);
        }
        $data = array();
        $data['run'] = $run;
        $data['items'] = $this->Payroll_model->getItems($run->id);
        $data['payments'] = $this->Payroll_model->getPayments($this->company_id, $run->id);
        $data['payment_methods'] = getAllPaymentMethods(5);
        $in_run = array();
        foreach ($data['items'] as $i) {
            $in_run[$i->employee_id] = true;
        }
        $data['missing_setup'] = array();
        foreach ($this->Payroll_model->getEmployeesWithSalary($this->company_id) as $e) {
            if (!$e->salary_id && !isset($in_run[$e->id])) {
                $data['missing_setup'][] = $e;
            }
        }
        $data['register_open'] = $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) > 0;
        $this->render('payroll/run', $data);
    }

    public function recalculate($id = '') {
        $run = $this->loadRun($id);
        if ($run->status != 'draft') {
            $this->back('Payroll/run/' . $run->id, lang('payroll_locked'));
        }
        $this->Payroll_model->recalculateRun($this->company_id, $run->id);
        $this->back('Payroll/run/' . $run->id, '', lang('payroll_recalculated'));
    }

    public function editItem() {
        $item = $this->Payroll_model->getItem($this->company_id, (int) $this->input->post('item_id'));
        if (!$item) {
            $this->back('Payroll/runs', lang('payroll_not_found'));
        }
        if ($item->run_status != 'draft') {
            $this->back('Payroll/run/' . $item->run_id, lang('payroll_locked'));
        }
        if ($this->input->post('reset')) {
            $data = array('ovr_absent_days' => null, 'ovr_worked_hours' => null, 'ovr_overtime_hours' => null,
                'sheet_bonus' => 0, 'sheet_deduction' => 0, 'note' => null);
        } else {
            $data = array(
                'ovr_absent_days' => $this->optionalNumber($this->input->post('ovr_absent_days')),
                'ovr_worked_hours' => $this->optionalNumber($this->input->post('ovr_worked_hours')),
                'ovr_overtime_hours' => $this->optionalNumber($this->input->post('ovr_overtime_hours')),
                'sheet_bonus' => max(0, $this->money($this->input->post('sheet_bonus'))),
                'sheet_deduction' => max(0, $this->money($this->input->post('sheet_deduction'))),
                'note' => mb_substr($this->post('note'), 0, 255) ?: null,
            );
            if ($data['ovr_absent_days'] !== null && $data['ovr_absent_days'] > $item->days_in_month) {
                $this->back('Payroll/run/' . $item->run_id, lang('payroll_absent_too_many'));
            }
        }
        $this->Payroll_model->saveItemOverrides($this->company_id, $item->id, $data);
        $this->back('Payroll/run/' . $item->run_id . '#item-' . $item->id, '', lang('update_success'));
    }

    public function approve($id = '') {
        $run = $this->loadRun($id);
        if ($run->status != 'draft') {
            $this->back('Payroll/run/' . $run->id, lang('payroll_locked'));
        }
        $this->Payroll_model->recalculateRun($this->company_id, $run->id);
        if (!$this->Payroll_model->getItems($run->id)) {
            $this->back('Payroll/run/' . $run->id, lang('payroll_no_eligible'));
        }
        $this->Payroll_model->setRunStatus($run->id, 'approved', $this->user_id);
        $this->back('Payroll/run/' . $run->id, '', lang('payroll_approved_msg'));
    }

    public function reopen($id = '') {
        $run = $this->loadRun($id);
        if ($run->status != 'approved') {
            $this->back('Payroll/run/' . $run->id);
        }
        if ($this->Payroll_model->getPayments($this->company_id, $run->id)) {
            $this->back('Payroll/run/' . $run->id, lang('payroll_reopen_has_payments'));
        }
        $this->Payroll_model->setRunStatus($run->id, 'draft', $this->user_id);
        $this->back('Payroll/run/' . $run->id, '', lang('payroll_reopened_msg'));
    }

    public function deleteRun($id = '') {
        $run = $this->loadRun($id);
        if ($run->status != 'draft') {
            $this->back('Payroll/run/' . $run->id, lang('payroll_delete_only_draft'));
        }
        $this->Payroll_model->deleteRun($run->id);
        $this->back('Payroll/runs', '', lang('delete_success'));
    }

    /* =================================================================
     * salary payments
     * ================================================================= */

    /** payment options from the posted pay form, or an error string */
    private function paymentOptions() {
        $payment_id = (int) $this->input->post('payment_id');
        $method = $this->db->query("SELECT id FROM tbl_payment_methods WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($payment_id, $this->company_id))->row();
        if (!$method) {
            return lang('payroll_choose_method');
        }
        $from_till = $this->input->post('from_till') ? true : false;
        if ($from_till && $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) == 0) {
            return lang('payroll_till_needs_register');
        }
        return array(
            'payment_id' => $payment_id,
            'pay_date' => $this->cleanDate($this->input->post('pay_date'), date('Y-m-d')),
            'note' => mb_substr($this->post('note'), 0, 255),
            'record_expense' => $this->input->post('record_expense') ? true : false,
            'from_till' => $from_till,
            'user_id' => $this->user_id,
            'outlet_id' => $this->outlet_id,
            'counter_id' => (int) $this->session->userdata('counter_id'),
        );
    }

    public function pay() {
        $item = $this->Payroll_model->getItem($this->company_id, (int) $this->input->post('item_id'));
        if (!$item) {
            $this->back('Payroll/runs', lang('payroll_not_found'));
        }
        $url = 'Payroll/run/' . $item->run_id;
        if ($item->run_status != 'approved') {
            $this->back($url, lang('payroll_pay_needs_approval'));
        }
        $opt = $this->paymentOptions();
        if (!is_array($opt)) {
            $this->back($url, $opt);
        }
        $remaining = round((float) $item->net_salary - (float) $item->paid_amount, 3);
        $amount = $this->money($this->input->post('amount'));
        if ($amount <= 0 || $amount > $remaining + 0.0005) {
            $this->back($url, lang('payroll_amount_invalid'));
        }
        $opt['amount'] = $amount;
        $this->Payroll_model->addPayment($this->company_id, $item, $opt);
        $this->back($url . '#item-' . $item->id, '', lang('payroll_payment_saved'));
    }

    public function payAll() {
        $run = $this->loadRun($this->input->post('run_id'));
        $url = 'Payroll/run/' . $run->id;
        if ($run->status != 'approved') {
            $this->back($url, lang('payroll_pay_needs_approval'));
        }
        $opt = $this->paymentOptions();
        if (!is_array($opt)) {
            $this->back($url, $opt);
        }
        $count = 0;
        foreach ($this->Payroll_model->getItems($run->id) as $i) {
            $remaining = round((float) $i->net_salary - (float) $i->paid_amount, 3);
            if ($remaining <= 0) {
                continue;
            }
            $item = $this->Payroll_model->getItem($this->company_id, $i->id);
            $opt['amount'] = $remaining;
            $this->Payroll_model->addPayment($this->company_id, $item, $opt);
            $count++;
        }
        $this->back($url, $count ? '' : lang('payroll_nothing_to_pay'), $count ? sprintf(lang('payroll_paid_n'), $count) : '');
    }

    public function deletePayment($id = '') {
        $payment = $this->Payroll_model->getPayment($this->company_id, (int) $id);
        if (!$payment) {
            $this->back('Payroll/runs', lang('payroll_not_found'));
        }
        $this->Payroll_model->deletePayment($this->company_id, $payment);
        $this->back('Payroll/run/' . $payment->run_id, '', lang('delete_success'));
    }

    /* =================================================================
     * print: payslip(s), payroll sheet
     * ================================================================= */

    private function printData() {
        $company = getCompanyInfo();
        $outlet = $this->db->query("SELECT outlet_name, address, phone FROM tbl_outlets WHERE id = ?", array($this->outlet_id))->row();
        return array('company' => $company, 'outlet' => $outlet);
    }

    private function payslipBlock($item) {
        $salary = $this->Payroll_model->getSalary($this->company_id, $item->employee_id);
        $payments = $this->db->query("SELECT p.*, pm.name AS payment_name FROM tbl_payroll_payments p
            LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            WHERE p.run_item_id = ? AND p.del_status = 'Live' ORDER BY p.pay_date, p.id", array($item->id))->result();
        $adjustments = $this->Payroll_model->getAdjustments($this->company_id, $item->pay_month, $item->employee_id);
        return (object) array('item' => $item, 'salary' => $salary, 'payments' => $payments, 'adjustments' => $adjustments);
    }

    public function payslip($item_id = '') {
        $item = $this->Payroll_model->getItem($this->company_id, (int) $item_id);
        if (!$item) {
            $this->back('Payroll/runs', lang('payroll_not_found'));
        }
        $data = $this->printData();
        $data['run'] = $this->Payroll_model->getRun($this->company_id, $item->run_id);
        $data['slips'] = array($this->payslipBlock($item));
        $data['payroll_settings'] = $this->Payroll_model->getSettings($this->company_id);
        $this->load->view('payroll/payslip', $data);
    }

    public function payslips($run_id = '') {
        $run = $this->loadRun($run_id);
        $data = $this->printData();
        $data['run'] = $run;
        $data['slips'] = array();
        foreach ($this->Payroll_model->getItems($run->id) as $i) {
            $data['slips'][] = $this->payslipBlock($this->Payroll_model->getItem($this->company_id, $i->id));
        }
        $data['payroll_settings'] = $this->Payroll_model->getSettings($this->company_id);
        $this->load->view('payroll/payslip', $data);
    }

    public function printSheet($run_id = '') {
        $run = $this->loadRun($run_id);
        $data = $this->printData();
        $data['run'] = $run;
        $data['items'] = $this->Payroll_model->getItems($run->id);
        $this->load->view('payroll/sheetPrint', $data);
    }

    /* =================================================================
     * reports
     * ================================================================= */

    public function attendanceSheet() {
        $month = $this->Payroll_model->cleanMonth($this->input->get('month')) ?: date('Y-m');
        $data = $this->Payroll_model->attendanceSheet($this->company_id, $month);
        $data['month'] = $month;
        $data['month_options'] = $this->monthOptions();
        $data['print'] = $this->input->get('print') ? true : false;
        $this->render('payroll/attendanceSheet', $data);
    }

    public function salaryHistory() {
        $employees = $this->Payroll_model->getEmployeesWithSalary($this->company_id);
        $employee_id = (int) $this->input->get('employee_id');
        $year = (int) $this->input->get('year') ?: (int) date('Y');
        $data = array('employees' => $employees, 'employee_id' => $employee_id, 'year' => $year);
        $data['employee'] = $employee_id ? $this->Payroll_model->getEmployee($this->company_id, $employee_id) : null;
        if ($data['employee']) {
            $data['salary'] = $this->Payroll_model->getSalary($this->company_id, $employee_id);
            $data['lines'] = $this->Payroll_model->salaryHistory($this->company_id, $employee_id, $year);
            $data['payments'] = array();
            foreach ($this->Payroll_model->getPayments($this->company_id, '', $employee_id) as $p) {
                if (substr($p->pay_month, 0, 4) == $year) {
                    $data['payments'][] = $p;
                }
            }
            $data['advances'] = $this->Payroll_model->getAdjustments($this->company_id, '', $employee_id, 'advance');
            $balances = $this->Payroll_model->advanceBalances($this->company_id, $employee_id);
            $data['advance_balance'] = $balances ? $balances[0] : null;
        }
        $this->render('payroll/salaryHistory', $data);
    }

    public function advances() {
        $data = array();
        $data['balances'] = $this->Payroll_model->advanceBalances($this->company_id);
        $this->render('payroll/advances', $data);
    }
}
