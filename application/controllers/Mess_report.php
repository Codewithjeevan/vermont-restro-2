<?php
/*
  ###########################################################
  # This is Mess_report Controller
  ###########################################################
  # Mess module reports: attendance matrix (member x day),
  # daily served summary ("kitne ko gaya, kitne ko nahi")
  # and the collection report by day and payment method.
  # Plan: docs/mess_system_plan.html section 5.10
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Mess_report extends Cl_Controller {

    const ACCESS_REPORTS = 408;

    private $company_id;
    private $outlet_id;

    public function __construct() {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('Common_model');
        $this->load->model('Mess_model');
        $this->Common_model->setDefaultTimezone();

        if (!$this->session->has_userdata('user_id')) {
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));
            rememberClickedRedirect();
            redirect('Outlet/outlets');
        }
        if (!checkAccess(self::ACCESS_REPORTS, 'view')) {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        $this->company_id = $this->session->userdata('company_id');
        $this->outlet_id = $this->session->userdata('outlet_id');
        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

    private function render($view, $data) {
        $data['mess_settings'] = $this->Mess_model->getSettings($this->company_id);
        $data['report_tab'] = $view;
        $data['main_content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('userHome', $data);
    }

    private function cleanDate($value, $fallback) {
        $value = trim((string) $value);
        if ($value && ($t = strtotime($value)) !== false) {
            return date('Y-m-d', $t);
        }
        return $fallback;
    }

    public function index() {
        redirect('Mess_report/dailySummary');
    }

    /**
     * member x day grid of one month and slot
     * @access public
     */
    public function attendanceMatrix() {
        $month = $this->input->get('month', TRUE);
        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $slots = $this->Mess_model->getActiveSlots($this->company_id);
        $slot = (int) $this->input->get('slot', TRUE);
        if (!isset($slots[$slot])) {
            $slot = $this->Mess_model->getCurrentSlot($this->company_id);
        }
        $first = $month . '-01';
        $last = date('Y-m-t', strtotime($first));
        $rows = $this->db->query("SELECT a.member_id, a.att_date, a.status, a.persons_expected, a.persons_served, m.name, m.member_code
            FROM tbl_mess_attendance a JOIN tbl_mess_members m ON m.id = a.member_id
            WHERE a.outlet_id = ? AND a.slot = ? AND a.att_date BETWEEN ? AND ?
            ORDER BY m.name, a.att_date", array($this->outlet_id, $slot, $first, $last))->result();
        $members = array();
        foreach ($rows as $r) {
            if (!isset($members[$r->member_id])) {
                $members[$r->member_id] = (object) array('name' => $r->name, 'code' => $r->member_code, 'days' => array(), 'served' => 0, 'not_taken' => 0, 'skipped' => 0);
            }
            $m = $members[$r->member_id];
            $d = (int) substr($r->att_date, 8, 2);
            $letter = array('served' => 'S', 'partial' => 'P', 'not_taken' => 'N', 'skipped' => 'K', 'pending' => '-');
            $m->days[$d] = isset($letter[$r->status]) ? $letter[$r->status] : '';
            if ($r->status == 'served' || $r->status == 'partial') {
                $m->served += $r->persons_served;
                $m->not_taken += max(0, $r->persons_expected - $r->persons_served);
            } elseif ($r->status == 'not_taken') {
                $m->not_taken += $r->persons_expected;
            } elseif ($r->status == 'skipped') {
                $m->skipped += $r->persons_expected;
            }
        }
        $data = array();
        $data['month'] = $month;
        $data['slot'] = $slot;
        $data['slots'] = $slots;
        $data['days_in_month'] = (int) date('t', strtotime($first));
        $data['members'] = $members;
        $this->render('mess/reportAttendanceMatrix', $data);
    }

    /**
     * per date and slot: expected, served, not taken, skipped, extras
     * @access public
     */
    public function dailySummary() {
        $from = $this->cleanDate($this->input->get('from', TRUE), date('Y-m-d', strtotime('-6 days')));
        $to = $this->cleanDate($this->input->get('to', TRUE), date('Y-m-d'));
        if ($to < $from) {
            $to = $from;
        }
        $rows = $this->db->query("SELECT att_date, slot,
                SUM(CASE WHEN status <> 'skipped' THEN persons_expected ELSE 0 END) AS expected,
                SUM(persons_served) AS served,
                SUM(CASE WHEN status = 'not_taken' THEN persons_expected WHEN status IN ('served','partial') THEN persons_expected - persons_served ELSE 0 END) AS not_taken,
                SUM(CASE WHEN status = 'skipped' THEN persons_expected ELSE 0 END) AS skipped,
                SUM(CASE WHEN status = 'pending' THEN persons_expected ELSE 0 END) AS pending,
                SUM(CASE WHEN status <> 'skipped' AND delivery_mode = 'delivery' THEN persons_expected ELSE 0 END) AS delivery,
                SUM(CASE WHEN status <> 'skipped' AND diet = 'veg' THEN persons_expected ELSE 0 END) AS veg
            FROM tbl_mess_attendance WHERE outlet_id = ? AND att_date BETWEEN ? AND ?
            GROUP BY att_date, slot ORDER BY att_date DESC, slot", array($this->outlet_id, $from, $to))->result();
        $extras = $this->db->query("SELECT extra_date, slot, SUM(qty) AS qty, SUM(total) AS total FROM tbl_mess_extras
            WHERE outlet_id = ? AND del_status = 'Live' AND status <> 'cancelled' AND extra_date BETWEEN ? AND ? GROUP BY extra_date, slot",
            array($this->outlet_id, $from, $to))->result();
        $ex = array();
        foreach ($extras as $e) {
            $ex[$e->extra_date . '_' . $e->slot] = $e;
        }
        $data = array();
        $data['from'] = $from;
        $data['to'] = $to;
        $data['rows'] = $rows;
        $data['extras'] = $ex;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $this->render('mess/reportDailySummary', $data);
    }

    /**
     * closed meals: cooked vs served, food cost, cost per served meal, waste
     * @access public
     */
    public function dayCloses() {
        $from = $this->cleanDate($this->input->get('from', TRUE), date('Y-m-01'));
        $to = $this->cleanDate($this->input->get('to', TRUE), date('Y-m-d'));
        if ($to < $from) {
            $to = $from;
        }
        $data = array();
        $data['from'] = $from;
        $data['to'] = $to;
        $data['closes'] = $this->Mess_model->getDayCloses($this->outlet_id, $from, $to);
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $this->render('mess/reportDayCloses', $data);
    }

    /**
     * money by day and payment method (payment + refund, adjustments apart)
     * @access public
     */
    public function collection() {
        $from = $this->cleanDate($this->input->get('from', TRUE), date('Y-m-01'));
        $to = $this->cleanDate($this->input->get('to', TRUE), date('Y-m-d'));
        if ($to < $from) {
            $to = $from;
        }
        $rows = $this->db->query("SELECT p.only_date, p.payment_id, pm.name AS payment_name,
                SUM(CASE WHEN p.type = 'refund' THEN -p.amount ELSE p.amount END) AS total, COUNT(*) AS cnt
            FROM tbl_mess_payments p LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            WHERE p.outlet_id = ? AND p.del_status = 'Live' AND p.type IN ('payment','refund') AND p.only_date BETWEEN ? AND ?
            GROUP BY p.only_date, p.payment_id ORDER BY p.only_date DESC", array($this->outlet_id, $from, $to))->result();
        $adjust = $this->db->query("SELECT SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS credit, SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS debit
            FROM tbl_mess_payments WHERE outlet_id = ? AND del_status = 'Live' AND type = 'adjustment' AND only_date BETWEEN ? AND ?",
            array($this->outlet_id, $from, $to))->row();
        $methods = array();
        $grid = array();
        foreach ($rows as $r) {
            $methods[(int) $r->payment_id] = $r->payment_name;
            $grid[$r->only_date][(int) $r->payment_id] = (float) $r->total;
        }
        $data = array();
        $data['from'] = $from;
        $data['to'] = $to;
        $data['methods'] = $methods;
        $data['grid'] = $grid;
        $data['adjust'] = $adjust;
        $data['by_user'] = $this->db->query("SELECT u.full_name, SUM(CASE WHEN p.type = 'refund' THEN -p.amount ELSE p.amount END) AS total, COUNT(*) AS cnt
            FROM tbl_mess_payments p LEFT JOIN tbl_users u ON u.id = p.user_id
            WHERE p.outlet_id = ? AND p.del_status = 'Live' AND p.type IN ('payment','refund') AND p.only_date BETWEEN ? AND ?
            GROUP BY p.user_id ORDER BY total DESC", array($this->outlet_id, $from, $to))->result();
        $this->render('mess/reportCollection', $data);
    }
}
