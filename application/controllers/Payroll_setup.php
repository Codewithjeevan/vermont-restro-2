<?php
/*
  ###########################################################
  # This is Payroll_setup Controller
  ###########################################################
  # Everything the monthly payroll reads: salary setup per
  # employee, leaves, holidays, advances / bonuses /
  # deductions, payroll settings. Runs live in Payroll.
  # Docs: docs/payroll_module.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_setup extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/payroll_module_migration.sql
    const ACCESS_SALARY = 418;
    const ACCESS_ADJUSTMENTS = 421;
    const ACCESS_LEAVES = 425;
    const ACCESS_HOLIDAYS = 429;
    const ACCESS_SETTINGS = 435;

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
            'salaries' => array(self::ACCESS_SALARY, 'view', 'salaries'),
            'editSalary' => array(self::ACCESS_SALARY, 'update', 'salaries'),
            'adjustments' => array(self::ACCESS_ADJUSTMENTS, 'view', 'adjustments'),
            'addAdjustment' => array(self::ACCESS_ADJUSTMENTS, 'add', 'adjustments'),
            'deleteAdjustment' => array(self::ACCESS_ADJUSTMENTS, 'delete', 'adjustments'),
            'leaves' => array(self::ACCESS_LEAVES, 'view', 'leaves'),
            'addLeave' => array(self::ACCESS_LEAVES, 'add', 'leaves'),
            'deleteLeave' => array(self::ACCESS_LEAVES, 'delete', 'leaves'),
            'holidays' => array(self::ACCESS_HOLIDAYS, 'view', 'holidays'),
            'addHoliday' => array(self::ACCESS_HOLIDAYS, 'add', 'holidays'),
            'deleteHoliday' => array(self::ACCESS_HOLIDAYS, 'delete', 'holidays'),
            'settings' => array(self::ACCESS_SETTINGS, 'update', 'settings'),
        );
        if (!isset($map[$segment_2]) || !checkAccess($map[$segment_2][0], $map[$segment_2][1])) {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        // screens hidden in config/payroll.php are closed even for Admin
        if (!Payroll_model::screenOn($map[$segment_2][2])) {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect(Payroll_model::screenOn('runs') ? 'Payroll/runs' : 'Authentication/userProfile');
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

    private function weekdaysFromPost($name) {
        $days = $this->input->post($name);
        return implode(',', $this->Payroll_model->parseWeekdays(is_array($days) ? implode(',', $days) : ''));
    }

    /** months for the adjustment "payroll month" box: 3 back, 12 ahead */
    private function payMonthOptions() {
        $months = array();
        for ($i = -3; $i <= 12; $i++) {
            $m = date('Y-m', strtotime(date('Y-m-01') . " $i month"));
            $months[$m] = $this->Payroll_model->monthLabel($m);
        }
        return $months;
    }

    /* =================================================================
     * salary setup
     * ================================================================= */

    public function salaries() {
        $data = array();
        $data['employees'] = $this->Payroll_model->getEmployeesWithSalary($this->company_id);
        $this->render('payroll/salaries', $data);
    }

    public function editSalary($employee_id = '') {
        $employee = $this->Payroll_model->getEmployee($this->company_id, (int) $employee_id);
        if (!$employee) {
            $this->back('Payroll_setup/salaries', lang('payroll_not_found'));
        }
        $salary = $this->Payroll_model->getSalary($this->company_id, $employee->id);
        $settings = $this->Payroll_model->getSettings($this->company_id);
        $error = '';
        if ($this->input->post('submit')) {
            $this->form_validation->set_rules('salary_type', lang('payroll_salary_type'), 'required|in_list[monthly,daily,hourly]');
            $this->form_validation->set_rules('basic_salary', lang('payroll_basic_salary'), 'required|numeric|greater_than_equal_to[0]');
            foreach (array('housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance', 'fixed_deduction', 'overtime_rate') as $f) {
                $this->form_validation->set_rules($f, lang('payroll_' . $f), 'numeric|greater_than_equal_to[0]');
            }
            $this->form_validation->set_rules('bank_name', lang('payroll_bank_name'), 'max_length[80]');
            $this->form_validation->set_rules('bank_account', lang('payroll_bank_account'), 'max_length[60]');
            $this->form_validation->set_rules('fixed_deduction_note', lang('note'), 'max_length[100]');
            $this->form_validation->set_rules('note', lang('note'), 'max_length[255]');
            $joining = $this->cleanDate($this->input->post('joining_date'));
            $leaving = $this->cleanDate($this->input->post('leaving_date'));
            if ($joining && $leaving && $leaving < $joining) {
                $error = lang('payroll_leaving_before_joining');
            }
            if ($this->form_validation->run() == TRUE && !$error) {
                $this->Payroll_model->saveSalary($this->company_id, $employee->id, array(
                    'salary_type' => $this->post('salary_type'),
                    'basic_salary' => $this->money($this->input->post('basic_salary')),
                    'housing_allowance' => $this->money($this->input->post('housing_allowance')),
                    'transport_allowance' => $this->money($this->input->post('transport_allowance')),
                    'food_allowance' => $this->money($this->input->post('food_allowance')),
                    'other_allowance' => $this->money($this->input->post('other_allowance')),
                    'fixed_deduction' => $this->money($this->input->post('fixed_deduction')),
                    'fixed_deduction_note' => $this->post('fixed_deduction_note') ?: null,
                    'overtime_rate' => $this->money($this->input->post('overtime_rate')),
                    'weekly_off' => $this->weekdaysFromPost('weekly_off'),
                    'joining_date' => $joining ?: null,
                    'leaving_date' => $leaving ?: null,
                    'payment_mode' => $this->post('payment_mode') == 'bank' ? 'bank' : 'cash',
                    'bank_name' => $this->post('bank_name') ?: null,
                    'bank_account' => $this->post('bank_account') ?: null,
                    'status' => $this->input->post('status') ? 'active' : 'inactive',
                    'note' => $this->post('note') ?: null,
                    'user_id' => $this->user_id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
                $this->back('Payroll_setup/salaries', '', lang('payroll_salary_saved'));
            }
        }
        $data = array();
        $data['employee'] = $employee;
        $data['salary'] = $salary;
        $data['error'] = $error;
        $data['default_weekly_off'] = $settings->default_weekly_off;
        $this->render('payroll/editSalary', $data);
    }

    /* =================================================================
     * advances, bonuses, deductions
     * ================================================================= */

    public function adjustments() {
        $month = $this->Payroll_model->cleanMonth($this->input->get('month'));
        $employee_id = (int) $this->input->get('employee_id');
        $type = in_array($this->input->get('type'), Payroll_model::$adjustment_types) ? $this->input->get('type') : '';
        $data = array();
        $data['month'] = $month;
        $data['employee_id'] = $employee_id;
        $data['type'] = $type;
        $data['adjustments'] = $this->Payroll_model->getAdjustments($this->company_id, $month ?: '', $employee_id ?: '', $type);
        $data['employees'] = $this->Payroll_model->getEmployeesWithSalary($this->company_id);
        $data['pay_months'] = $this->payMonthOptions();
        $data['payment_methods'] = getAllPaymentMethods(5);
        $data['register_open'] = $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) > 0;
        $this->render('payroll/adjustments', $data);
    }

    public function addAdjustment() {
        $url = 'Payroll_setup/adjustments';
        $type = $this->post('adj_type');
        $employee = $this->Payroll_model->getEmployee($this->company_id, (int) $this->input->post('employee_id'));
        $amount = $this->money($this->input->post('amount'));
        $pay_month = $this->Payroll_model->cleanMonth($this->input->post('pay_month'));
        if (!$employee || !in_array($type, Payroll_model::$adjustment_types) || $amount <= 0 || !$pay_month) {
            $this->back($url, lang('payroll_adjustment_invalid'));
        }
        $installments = $type == 'advance' ? max(1, min(24, (int) $this->input->post('installments'))) : 1;
        $row = (object) array('adj_type' => $type, 'pay_month' => $pay_month, 'installments' => $installments);
        if ($month = $this->Payroll_model->adjustmentLocked($this->company_id, $row)) {
            $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel($month)));
        }
        $date = $this->cleanDate($this->input->post('adj_date'), date('Y-m-d'));
        $note = mb_substr($this->post('note'), 0, 255);
        $insert = array(
            'company_id' => $this->company_id,
            'employee_id' => $employee->id,
            'adj_type' => $type,
            'amount' => $amount,
            'adj_date' => $date,
            'pay_month' => $pay_month,
            'installments' => $installments,
            'note' => $note ?: null,
            'outlet_id' => $this->outlet_id,
            'user_id' => $this->user_id,
            'added_date_time' => date('Y-m-d H:i:s'),
        );
        if ($type == 'advance') {
            $payment_id = (int) $this->input->post('payment_id');
            $method = $this->db->query("SELECT id FROM tbl_payment_methods WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($payment_id, $this->company_id))->row();
            if (!$method) {
                $this->back($url, lang('payroll_choose_method'));
            }
            $from_till = $this->input->post('from_till') ? true : false;
            if ($from_till && $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) == 0) {
                $this->back($url, lang('payroll_till_needs_register'));
            }
            $insert['payment_id'] = $payment_id;
            if ($this->input->post('record_expense')) {
                $insert['expense_id'] = $this->Payroll_model->postExpense($this->company_id, array(
                    'date' => $date,
                    'amount' => $amount,
                    'employee_id' => $employee->id,
                    'note' => 'Salary advance - ' . $employee->full_name . ($note ? ' (' . $note . ')' : ''),
                    'payment_id' => $payment_id,
                    'from_till' => $from_till,
                ), array('user_id' => $this->user_id, 'outlet_id' => $this->outlet_id, 'counter_id' => (int) $this->session->userdata('counter_id')));
            }
        }
        $this->db->insert('tbl_payroll_adjustments', $insert);
        $this->back($url . '?month=' . $pay_month, '', lang('insertion_success'));
    }

    public function deleteAdjustment($id = '') {
        $url = 'Payroll_setup/adjustments';
        $adj = $this->Payroll_model->getAdjustment($this->company_id, (int) $id);
        if (!$adj) {
            $this->back($url, lang('payroll_not_found'));
        }
        if ($month = $this->Payroll_model->adjustmentLocked($this->company_id, $adj)) {
            $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel($month)));
        }
        $this->db->where('id', $adj->id)->update('tbl_payroll_adjustments', array('del_status' => 'Deleted'));
        if ($adj->expense_id) {
            $this->db->where('id', $adj->expense_id)->update('tbl_expenses', array('del_status' => 'Deleted'));
        }
        $this->back($url, '', lang('delete_success'));
    }

    /* =================================================================
     * leaves
     * ================================================================= */

    public function leaves() {
        $year = (int) $this->input->get('year') ?: (int) date('Y');
        $employee_id = (int) $this->input->get('employee_id');
        $data = array();
        $data['year'] = $year;
        $data['employee_id'] = $employee_id;
        $data['leaves'] = $this->Payroll_model->getLeaves($this->company_id, $year . '-01-01', $year . '-12-31', $employee_id ?: '');
        $data['employees'] = $this->Payroll_model->getEmployeesWithSalary($this->company_id);
        $this->render('payroll/leaves', $data);
    }

    public function addLeave() {
        $url = 'Payroll_setup/leaves';
        $employee = $this->Payroll_model->getEmployee($this->company_id, (int) $this->input->post('employee_id'));
        $from = $this->cleanDate($this->input->post('from_date'));
        $to = $this->cleanDate($this->input->post('to_date'), $from);
        $type = $this->post('leave_type') == 'unpaid' ? 'unpaid' : 'paid';
        if (!$employee || !$from || !$to || $to < $from) {
            $this->back($url, lang('payroll_leave_invalid'));
        }
        if ((strtotime($to) - strtotime($from)) / 86400 > 366) {
            $this->back($url, lang('payroll_leave_invalid'));
        }
        if ($this->Payroll_model->leaveOverlaps($this->company_id, $employee->id, $from, $to)) {
            $this->back($url, lang('payroll_leave_overlap'));
        }
        foreach (array(substr($from, 0, 7), substr($to, 0, 7)) as $m) {
            $run = $this->Payroll_model->getRunByMonth($this->company_id, $m);
            if ($run && $run->status == 'approved') {
                $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel($m)));
            }
        }
        $this->db->insert('tbl_payroll_leaves', array(
            'company_id' => $this->company_id,
            'employee_id' => $employee->id,
            'from_date' => $from,
            'to_date' => $to,
            'leave_type' => $type,
            'reason' => mb_substr($this->post('reason'), 0, 255) ?: null,
            'user_id' => $this->user_id,
            'added_date_time' => date('Y-m-d H:i:s'),
        ));
        $this->back($url . '?year=' . substr($from, 0, 4), '', lang('insertion_success'));
    }

    public function deleteLeave($id = '') {
        $url = 'Payroll_setup/leaves';
        $leave = $this->db->query("SELECT * FROM tbl_payroll_leaves WHERE id = ? AND company_id = ? AND del_status = 'Live'", array((int) $id, $this->company_id))->row();
        if (!$leave) {
            $this->back($url, lang('payroll_not_found'));
        }
        foreach (array(substr($leave->from_date, 0, 7), substr($leave->to_date, 0, 7)) as $m) {
            $run = $this->Payroll_model->getRunByMonth($this->company_id, $m);
            if ($run && $run->status == 'approved') {
                $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel($m)));
            }
        }
        $this->db->where('id', $leave->id)->update('tbl_payroll_leaves', array('del_status' => 'Deleted'));
        $this->back($url, '', lang('delete_success'));
    }

    /* =================================================================
     * holidays
     * ================================================================= */

    public function holidays() {
        $year = (int) $this->input->get('year') ?: (int) date('Y');
        $data = array();
        $data['year'] = $year;
        $data['holidays'] = $this->Payroll_model->getHolidays($this->company_id, $year . '-01-01', $year . '-12-31');
        $this->render('payroll/holidays', $data);
    }

    public function addHoliday() {
        $url = 'Payroll_setup/holidays';
        $from = $this->cleanDate($this->input->post('holiday_date'));
        $to = $this->cleanDate($this->input->post('to_date'), $from);
        $name = mb_substr($this->post('name'), 0, 100);
        if (!$from || !$name || $to < $from || (strtotime($to) - strtotime($from)) / 86400 > 31) {
            $this->back($url, lang('payroll_holiday_invalid'));
        }
        $existing = array();
        foreach ($this->Payroll_model->getHolidays($this->company_id, $from, $to) as $h) {
            $existing[$h->holiday_date] = true;
        }
        for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $run = $this->Payroll_model->getRunByMonth($this->company_id, substr($d, 0, 7));
            if ($run && $run->status == 'approved') {
                $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel(substr($d, 0, 7))));
            }
        }
        for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            if (isset($existing[$d])) {
                continue;
            }
            $this->db->insert('tbl_payroll_holidays', array(
                'company_id' => $this->company_id,
                'holiday_date' => $d,
                'name' => $name,
                'user_id' => $this->user_id,
                'added_date_time' => date('Y-m-d H:i:s'),
            ));
        }
        $this->back($url . '?year=' . substr($from, 0, 4), '', lang('insertion_success'));
    }

    public function deleteHoliday($id = '') {
        $url = 'Payroll_setup/holidays';
        $holiday = $this->db->query("SELECT * FROM tbl_payroll_holidays WHERE id = ? AND company_id = ? AND del_status = 'Live'", array((int) $id, $this->company_id))->row();
        if (!$holiday) {
            $this->back($url, lang('payroll_not_found'));
        }
        $run = $this->Payroll_model->getRunByMonth($this->company_id, substr($holiday->holiday_date, 0, 7));
        if ($run && $run->status == 'approved') {
            $this->back($url, sprintf(lang('payroll_month_approved'), $this->Payroll_model->monthLabel(substr($holiday->holiday_date, 0, 7))));
        }
        $this->db->where('id', $holiday->id)->update('tbl_payroll_holidays', array('del_status' => 'Deleted'));
        $this->back($url . '?year=' . substr($holiday->holiday_date, 0, 4), '', lang('delete_success'));
    }

    /* =================================================================
     * settings
     * ================================================================= */

    public function settings() {
        if ($this->input->post('submit')) {
            $this->form_validation->set_rules('standard_hours', lang('payroll_standard_hours'), 'required|numeric|greater_than[0]|less_than_equal_to[24]');
            $this->form_validation->set_rules('half_day_hours', lang('payroll_half_day_hours'), 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[24]');
            $this->form_validation->set_rules('overtime_multiplier', lang('payroll_overtime_multiplier'), 'required|numeric|greater_than[0]|less_than_equal_to[10]');
            $this->form_validation->set_rules('overtime_min_minutes', lang('payroll_overtime_min_minutes'), 'required|integer|greater_than_equal_to[0]|less_than_equal_to[600]');
            $this->form_validation->set_rules('payslip_footer', lang('payroll_payslip_footer'), 'max_length[255]');
            if ($this->form_validation->run() == TRUE) {
                $basis = $this->post('month_days_basis');
                $this->Payroll_model->saveSettings($this->company_id, array(
                    'month_days_basis' => in_array($basis, array('calendar', 'fixed30', 'working')) ? $basis : 'calendar',
                    'absent_deduction_on' => $this->post('absent_deduction_on') == 'basic' ? 'basic' : 'gross',
                    'default_weekly_off' => $this->weekdaysFromPost('default_weekly_off'),
                    'standard_hours' => (float) $this->input->post('standard_hours'),
                    'half_day_hours' => (float) $this->input->post('half_day_hours'),
                    'missing_checkout' => $this->post('missing_checkout') == 'half' ? 'half' : 'full',
                    'overtime_enabled' => $this->input->post('overtime_enabled') ? 1 : 0,
                    'overtime_multiplier' => (float) $this->input->post('overtime_multiplier'),
                    'overtime_min_minutes' => (int) $this->input->post('overtime_min_minutes'),
                    'pay_offday_work' => $this->input->post('pay_offday_work') ? 1 : 0,
                    'record_expense' => $this->input->post('record_expense') ? 1 : 0,
                    'expense_category_id' => (int) $this->input->post('expense_category_id') ?: null,
                    'payslip_footer' => $this->post('payslip_footer') ?: null,
                    'updated_by' => $this->user_id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
                $this->back('Payroll_setup/settings', '', lang('update_success'));
            }
        }
        $data = array();
        $data['expense_categories'] = $this->db->query("SELECT id, name FROM tbl_expense_items WHERE company_id = ? AND del_status = 'Live' ORDER BY name", array($this->company_id))->result();
        $this->render('payroll/settings', $data);
    }
}
