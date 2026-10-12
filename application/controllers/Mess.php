<?php
/*
  ###########################################################
  # This is Mess Controller
  ###########################################################
  # Mess (monthly tiffin) module, part 1: people and money.
  # Today dashboard, plans, members, subscriptions (pause,
  # renew, cancel), payments with the open-register rule,
  # member ledger / statement, dues, expiring list, settings.
  # Daily food lives in Mess_daily, reports in Mess_report.
  # Plan: docs/mess_system_plan.html
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Mess extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/mess_module_migration.sql
    const ACCESS_DASHBOARD = 371;
    const ACCESS_PLANS = 373;
    const ACCESS_MEMBERS = 378;
    const ACCESS_SUBSCRIPTIONS = 383;
    const ACCESS_PAYMENTS = 388;
    const ACCESS_SETTINGS = 410;

    private $company_id;
    private $outlet_id;
    private $user_id;

    public function __construct() {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('Common_model');
        $this->load->model('Mess_model');
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
        $segment_3 = $this->uri->segment(3);
        $map = array(
            'index' => array(self::ACCESS_DASHBOARD, 'view'),
            'today' => array(self::ACCESS_DASHBOARD, 'view'),
            'plans' => array(self::ACCESS_PLANS, 'view'),
            'addEditPlan' => array(self::ACCESS_PLANS, $segment_3 ? 'update' : 'add'),
            'deletePlan' => array(self::ACCESS_PLANS, 'delete'),
            'members' => array(self::ACCESS_MEMBERS, 'view'),
            'member' => array(self::ACCESS_MEMBERS, 'view'),
            'memberStatement' => array(self::ACCESS_MEMBERS, 'view'),
            'memberInfoAjax' => array(self::ACCESS_MEMBERS, 'view'),
            'addEditMember' => array(self::ACCESS_MEMBERS, $segment_3 ? 'update' : 'add'),
            'checkPhoneAjax' => array(self::ACCESS_MEMBERS, 'view'),
            'deleteMember' => array(self::ACCESS_MEMBERS, 'delete'),
            'subscriptions' => array(self::ACCESS_SUBSCRIPTIONS, 'view'),
            'expiring' => array(self::ACCESS_SUBSCRIPTIONS, 'view'),
            'addEditSubscription' => array(self::ACCESS_SUBSCRIPTIONS, $segment_3 ? 'update' : 'add'),
            'addPause' => array(self::ACCESS_SUBSCRIPTIONS, 'update'),
            'deletePause' => array(self::ACCESS_SUBSCRIPTIONS, 'update'),
            'cancelSubscription' => array(self::ACCESS_SUBSCRIPTIONS, 'update'),
            'deleteSubscription' => array(self::ACCESS_SUBSCRIPTIONS, 'delete'),
            'payments' => array(self::ACCESS_PAYMENTS, 'view'),
            'dues' => array(self::ACCESS_PAYMENTS, 'view'),
            'paymentReceipt' => array(self::ACCESS_PAYMENTS, 'view'),
            'addPayment' => array(self::ACCESS_PAYMENTS, 'add'),
            'deletePayment' => array(self::ACCESS_PAYMENTS, 'delete'),
            'settings' => array(self::ACCESS_SETTINGS, 'update'),
        );
        if (!$segment_2) {
            $segment_2 = 'index';
        }
        if (!isset($map[$segment_2]) || !checkAccess($map[$segment_2][0], $map[$segment_2][1])) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 0, 'msg' => lang('menu_not_permit_access')));
                exit;
            }
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        //end check access function

        $this->company_id = $this->session->userdata('company_id');
        $this->outlet_id = $this->session->userdata('outlet_id');
        $this->user_id = $this->session->userdata('user_id');

        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

    /**
     * render a view inside the admin layout
     * @access private
     */
    private function render($view, $data) {
        $data['mess_settings'] = $this->Mess_model->getSettings($this->company_id);
        $data['main_content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('userHome', $data);
    }

    private function post($name) {
        return trim((string) $this->input->post($name, TRUE));
    }

    private function isAdmin() {
        return $this->session->userdata('role') == 'Admin';
    }

    /**
     * a date typed by the user, as Y-m-d, or the fallback
     * @access private
     */
    private function cleanDate($value, $fallback = '') {
        $value = trim((string) $value);
        if ($value && ($t = strtotime($value)) !== false) {
            return date('Y-m-d', $t);
        }
        return $fallback;
    }

    private function money($value) {
        return round((float) str_replace(',', '', (string) $value), 3);
    }

    /* =================================================================
     * Today dashboard
     * ================================================================= */

    public function index() {
        $this->today();
    }

    public function today() {
        $today = date('Y-m-d');
        $data = array();
        $data['today'] = $today;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['current_slot'] = $this->Mess_model->getCurrentSlot($this->company_id);
        $data['headcount'] = $this->Mess_model->expectedHeadcount($this->outlet_id, $today, $this->company_id);
        $data['menu'] = $this->Mess_model->getMenuGrouped($this->outlet_id, $today);
        $data['current_subscriptions'] = $this->Mess_model->getSubscriptions($this->company_id, $this->outlet_id, 'current');
        $data['expiring'] = $this->Mess_model->getExpiring($this->company_id, $this->outlet_id);
        $data['expired_not_renewed'] = $this->Mess_model->getExpiredNotRenewed($this->company_id, $this->outlet_id);
        $data['dues'] = $this->Mess_model->getDues($this->company_id);
        $data['collection_today'] = $this->Mess_model->getCollectionByMethod($this->outlet_id, $today, $today);
        $data['extras_today'] = $this->Mess_model->getExtras($this->outlet_id, $today, $today);
        // meals of yesterday and of today (window over) that fed people but were not closed
        $data['not_closed'] = array();
        if (checkAccess(404, 'view')) {
            $now = date('H:i');
            foreach (array(date('Y-m-d', strtotime('-1 day')), $today) as $day) {
                foreach ($data['slots'] as $slot) {
                    if ($day == $today && $now <= $slot->to) {
                        continue;
                    }
                    $close = $this->Mess_model->getDayClose($this->outlet_id, $day, $slot->slot);
                    if ($close && $close->status == 'closed') {
                        continue;
                    }
                    $fed = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_attendance WHERE outlet_id = ? AND att_date = ? AND slot = ? AND status IN ('served','partial')", array($this->outlet_id, $day, $slot->slot))->row()->c;
                    if ($fed) {
                        $data['not_closed'][] = (object) array('date' => $day, 'slot' => $slot->slot, 'name' => $slot->name);
                    }
                }
            }
        }
        $this->render('mess/today', $data);
    }

    /* =================================================================
     * Plans
     * ================================================================= */

    public function plans() {
        $data = array();
        $data['plans'] = $this->Mess_model->getPlans($this->company_id);
        $this->render('mess/plans', $data);
    }

    public function addEditPlan($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $plan = $id ? $this->Mess_model->getPlan($id, $this->company_id) : null;
        if ($id && !$plan) {
            redirect('Mess/plans');
        }
        if ($this->input->post('submit')) {
            $this->form_validation->set_rules('name', lang('mess_plan_name'), 'required|max_length[100]');
            $this->form_validation->set_rules('price_per_person', lang('mess_price_per_person'), 'required|numeric');
            $this->form_validation->set_rules('cycle_days', lang('mess_cycle_days'), 'required|integer|greater_than[0]|less_than[367]');
            $slots_ok = $this->input->post('has_breakfast') || $this->input->post('has_lunch') || $this->input->post('has_dinner');
            if ($this->form_validation->run() == TRUE && $slots_ok) {
                $row = array(
                    'name' => $this->post('name'),
                    'diet' => in_array($this->post('diet'), array('veg', 'nonveg', 'both')) ? $this->post('diet') : 'nonveg',
                    'has_breakfast' => $this->input->post('has_breakfast') ? 1 : 0,
                    'has_lunch' => $this->input->post('has_lunch') ? 1 : 0,
                    'has_dinner' => $this->input->post('has_dinner') ? 1 : 0,
                    'price_per_person' => $this->money($this->post('price_per_person')),
                    'cycle_days' => (int) $this->post('cycle_days'),
                    'description' => $this->post('description'),
                    'is_active' => $this->input->post('is_active') ? 1 : 0,
                );
                if ($plan) {
                    $this->Common_model->updateInformation($row, $plan->id, 'tbl_mess_plans');
                    $this->session->set_flashdata('exception', lang('update_success'));
                } else {
                    $row['company_id'] = $this->company_id;
                    $row['user_id'] = $this->user_id;
                    $row['added_date_time'] = date('Y-m-d H:i:s');
                    $this->Common_model->insertInformation($row, 'tbl_mess_plans');
                    $this->session->set_flashdata('exception', lang('insertion_success'));
                }
                redirect('Mess/plans');
            }
            $data['slot_error'] = $slots_ok ? '' : lang('mess_plan_need_slot');
        }
        $data['plan'] = $plan;
        $data['encrypted_id'] = $encrypted_id;
        $this->render('mess/addEditPlan', $data);
    }

    public function deletePlan($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $plan = $this->Mess_model->getPlan($id, $this->company_id);
        if ($plan) {
            $used = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_subscriptions WHERE plan_id = ? AND del_status = 'Live'", array($plan->id))->row()->c;
            if ($used) {
                $this->Common_model->updateInformation(array('is_active' => 0), $plan->id, 'tbl_mess_plans');
                $this->session->set_flashdata('exception_er', lang('mess_plan_in_use'));
            } else {
                $this->Common_model->deleteStatusChange($plan->id, 'tbl_mess_plans');
                $this->session->set_flashdata('exception', lang('delete_success'));
            }
        }
        redirect('Mess/plans');
    }

    /* =================================================================
     * Members
     * ================================================================= */

    public function members() {
        $status = $this->input->get('status', TRUE);
        if ($status === null) {
            $status = 'active';
        }
        $data = array();
        $data['status'] = $status;
        $data['members'] = $this->Mess_model->getMembers($this->company_id, $status);
        $this->render('mess/members', $data);
    }

    public function addEditMember($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $member = $id ? $this->Mess_model->getMember($id, $this->company_id) : null;
        if ($id && !$member) {
            redirect('Mess/members');
        }
        $data = array();
        if ($this->input->post('submit')) {
            $this->form_validation->set_rules('name', lang('name'), 'required|max_length[150]');
            $this->form_validation->set_rules('phone', lang('phone'), 'max_length[30]');
            $this->form_validation->set_rules('phone2', lang('mess_phone2'), 'max_length[30]');
            $this->form_validation->set_rules('building_room', lang('mess_building_room'), 'max_length[150]');
            $this->form_validation->set_rules('address', lang('address'), 'max_length[255]');
            $this->form_validation->set_rules('note', lang('note'), 'max_length[255]');
            if ($this->form_validation->run() == TRUE) {
                $row = array(
                    'name' => $this->post('name'),
                    'phone' => $this->post('phone'),
                    'phone2' => $this->post('phone2'),
                    'building_room' => $this->post('building_room'),
                    'address' => $this->post('address'),
                    'diet_pref' => in_array($this->post('diet_pref'), array('veg', 'nonveg', 'both')) ? $this->post('diet_pref') : 'nonveg',
                    'delivery_mode' => $this->post('delivery_mode') == 'delivery' ? 'delivery' : 'pickup',
                    'note' => $this->post('note'),
                    'status' => $this->post('status') == 'inactive' ? 'inactive' : 'active',
                );
                if ($member) {
                    $this->Common_model->updateInformation($row, $member->id, 'tbl_mess_members');
                    $this->session->set_flashdata('exception', lang('update_success'));
                    $member_id = $member->id;
                } else {
                    $row['member_code'] = $this->Mess_model->nextMemberCode($this->company_id);
                    $row['company_id'] = $this->company_id;
                    $row['outlet_id'] = $this->outlet_id;
                    $row['user_id'] = $this->user_id;
                    $row['added_date_time'] = date('Y-m-d H:i:s');
                    $member_id = $this->Common_model->insertInformation($row, 'tbl_mess_members');
                    $this->session->set_flashdata('exception', lang('insertion_success'));
                }
                $next = $this->input->post('submit');
                if ($next == 'subscribe' && checkAccess(self::ACCESS_SUBSCRIPTIONS, 'add')) {
                    redirect('Mess/addEditSubscription?member_id=' . $member_id);
                }
                redirect('Mess/member/' . $member_id);
            }
        }
        $data['member'] = $member;
        $data['encrypted_id'] = $encrypted_id;
        $data['next_code'] = $member ? $member->member_code : $this->Mess_model->nextMemberCode($this->company_id);
        $this->render('mess/addEditMember', $data);
    }

    /**
     * warn about a phone number already used by another member
     * @access public
     */
    public function checkPhoneAjax() {
        $phone = $this->input->get('phone', TRUE);
        $except = (int) $this->input->get('except_id', TRUE);
        $rows = $this->Mess_model->findMembersByPhone($this->company_id, $phone, $except);
        $names = array();
        foreach ($rows as $r) {
            $names[] = $r->name . ' (' . $r->member_code . ')';
        }
        echo json_encode(array('status' => 1, 'used_by' => $names));
    }

    public function deleteMember($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $member = $this->Mess_model->getMember($id, $this->company_id);
        if ($member) {
            $subs = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_subscriptions WHERE member_id = ? AND del_status = 'Live'", array($member->id))->row()->c;
            $pays = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_payments WHERE member_id = ? AND del_status = 'Live'", array($member->id))->row()->c;
            if ($subs || $pays) {
                $this->Common_model->updateInformation(array('status' => 'inactive'), $member->id, 'tbl_mess_members');
                $this->session->set_flashdata('exception_er', lang('mess_member_has_records'));
            } else {
                $this->Common_model->deleteStatusChange($member->id, 'tbl_mess_members');
                $this->session->set_flashdata('exception', lang('delete_success'));
            }
        }
        redirect('Mess/members');
    }

    /**
     * member profile: cycles, pauses, ledger, attendance summary, actions
     * @access public
     */
    public function member($id = '') {
        $member = $this->Mess_model->getMember((int) $id, $this->company_id);
        if (!$member) {
            redirect('Mess/members');
        }
        $data = array();
        $data['member'] = $member;
        $data['subscriptions'] = $this->Mess_model->getSubscriptions($this->company_id, '', '', $member->id);
        foreach ($data['subscriptions'] as $s) {
            $s->pauses = $this->Mess_model->getPauses($s->id);
        }
        $data['ledger'] = $this->Mess_model->getLedger($member->id);
        $data['attendance_30'] = $this->db->query("SELECT status, SUM(persons_expected) AS persons, COUNT(*) AS c FROM tbl_mess_attendance
            WHERE member_id = ? AND att_date BETWEEN ? AND ? GROUP BY status",
            array($member->id, date('Y-m-d', strtotime('-30 days')), date('Y-m-d')))->result();
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $settings = $this->Mess_model->getSettings($this->company_id);
        $data['whatsapp_due'] = '';
        if ($member->balance > 0) {
            $text = $this->Mess_model->fillTemplate($settings->whatsapp_due_template, array(
                'name' => $member->name,
                'amount' => getAmtPCustom($member->balance),
                'currency' => $this->session->userdata('currency'),
            ));
            $data['whatsapp_due'] = $this->Mess_model->whatsappLink($member->phone, $text, $settings->whatsapp_country_code);
        }
        $this->render('mess/member', $data);
    }

    /**
     * printable ledger (A4) for disputes
     * @access public
     */
    public function memberStatement($id = '') {
        $member = $this->Mess_model->getMember((int) $id, $this->company_id);
        if (!$member) {
            redirect('Mess/members');
        }
        $data = array();
        $data['member'] = $member;
        $data['ledger'] = $this->Mess_model->getLedger($member->id);
        $data['subscriptions'] = $this->Mess_model->getSubscriptions($this->company_id, '', '', $member->id);
        $data['company'] = getCompanyInfo();
        $this->load->view('mess/memberStatement', $data);
    }

    /**
     * member defaults + balance + open subscriptions, for the subscription and payment forms
     * @access public
     */
    public function memberInfoAjax() {
        $member = $this->Mess_model->getMember((int) $this->input->get('member_id', TRUE), $this->company_id);
        if (!$member) {
            echo json_encode(array('status' => 0));
            return;
        }
        $subs = array();
        foreach ($this->Mess_model->getUnpaidSubscriptions($member->id) as $s) {
            $subs[] = array(
                'id' => $s->id,
                'text' => $this->Mess_model->shortDate($s->start_date) . ' - ' . $this->Mess_model->shortDate($s->end_date) . ' | ' . $s->meals_text . ' | ' . lang('mess_agreed_price') . ' ' . getAmtPCustom($s->agreed_price) . ' | ' . lang('due') . ' ' . getAmtPCustom($s->agreed_price - $s->paid),
                'due' => round($s->agreed_price - $s->paid, 3),
                'end_date' => $s->end_date,
            );
        }
        $last = $this->db->query("SELECT id, end_date FROM tbl_mess_subscriptions WHERE member_id = ? AND del_status = 'Live' AND status <> 'cancelled' ORDER BY end_date DESC LIMIT 1", array($member->id))->row();
        echo json_encode(array(
            'status' => 1,
            'name' => $member->name,
            'phone' => $member->phone,
            'diet' => $member->diet_pref,
            'delivery_mode' => $member->delivery_mode,
            'address' => trim($member->building_room . ($member->address ? ', ' . $member->address : ''), ', '),
            'balance' => round($member->balance, 3),
            'balance_text' => getAmtPCustom(abs($member->balance)),
            'subscriptions' => $subs,
            'last_end_date' => $last ? $last->end_date : '',
        ));
    }

    /* =================================================================
     * Subscriptions
     * ================================================================= */

    public function subscriptions() {
        $filter = $this->input->get('filter', TRUE);
        if ($filter === null) {
            $filter = 'current';
        }
        $data = array();
        $data['filter'] = $filter;
        $data['subscriptions'] = $this->Mess_model->getSubscriptions($this->company_id, $this->outlet_id, $filter);
        $this->render('mess/subscriptions', $data);
    }

    public function expiring() {
        $settings = $this->Mess_model->getSettings($this->company_id);
        $days = $this->input->get('days', TRUE);
        $days = ($days !== null && $days !== '') ? max(0, (int) $days) : (int) $settings->expiring_days;
        $data = array();
        $data['days'] = $days;
        $data['expiring'] = $this->Mess_model->getExpiring($this->company_id, $this->outlet_id, $days);
        $data['expired'] = $this->Mess_model->getExpiredNotRenewed($this->company_id, $this->outlet_id);
        foreach (array_merge($data['expiring'], $data['expired']) as $s) {
            $text = $this->Mess_model->fillTemplate($settings->whatsapp_expiry_template, array(
                'name' => $s->member_name,
                'end_date' => date($this->session->userdata('date_format'), strtotime($s->end_date)),
            ));
            $s->whatsapp = $this->Mess_model->whatsappLink($s->phone, $text, $settings->whatsapp_country_code);
        }
        $this->render('mess/expiring', $data);
    }

    /**
     * new / edit / renew. GET member_id preselects a member, renew_from prefills from a cycle.
     * @access public
     */
    public function addEditSubscription($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $sub = $id ? $this->Mess_model->getSubscription($id, $this->company_id) : null;
        if ($id && !$sub) {
            redirect('Mess/subscriptions');
        }
        $settings = $this->Mess_model->getSettings($this->company_id);
        $data = array();
        $data['errors'] = array();
        $renew_from = null;
        if (!$sub && $this->input->get('renew_from', TRUE)) {
            $renew_from = $this->Mess_model->getSubscription((int) $this->input->get('renew_from', TRUE), $this->company_id);
        }

        if ($this->input->post('submit')) {
            $member = $this->Mess_model->getMember((int) $this->post('member_id'), $this->company_id);
            $plan = $this->post('plan_id') ? $this->Mess_model->getPlan((int) $this->post('plan_id'), $this->company_id) : null;
            $start_date = $this->cleanDate($this->post('start_date'));
            $end_date = $this->cleanDate($this->post('end_date'));
            $meals = array();
            $persons_total = 0;
            foreach (array(1 => 'persons_breakfast', 2 => 'persons_lunch', 3 => 'persons_dinner') as $slot => $field) {
                $p = max(0, (int) $this->post($field));
                if ($p > 0) {
                    $meals[$slot] = $p;
                    $persons_total += $p;
                }
            }
            $agreed = $this->post('agreed_price');
            $advance = $this->money($this->post('advance_amount'));
            $advance_method = (int) $this->post('advance_payment_id');

            if (!$member) {
                $data['errors'][] = lang('mess_member') . ': ' . lang('select');
            }
            if (!$start_date || !$end_date) {
                $data['errors'][] = lang('start_date') . ' / ' . lang('end_date');
            } elseif ($end_date < $start_date) {
                $data['errors'][] = lang('mess_dates_invalid');
            }
            if (!$meals) {
                $data['errors'][] = lang('mess_meals_required');
            }
            if ($agreed === '' || !is_numeric(str_replace(',', '', $agreed)) || $this->money($agreed) < 0) {
                $data['errors'][] = lang('mess_agreed_price_required');
            }
            if ($advance > 0) {
                if (!$advance_method) {
                    $data['errors'][] = lang('mess_payment_method_required');
                } elseif ($settings->require_open_register && $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) == 0) {
                    $data['errors'][] = lang('mess_register_needed');
                }
            }

            if (!$data['errors']) {
                $days = (int) ((strtotime($end_date) - strtotime($start_date)) / 86400) + 1;
                $paused_days = $sub ? (int) $sub->paused_days : 0;
                $cycle_days = max(1, $days - $paused_days);
                $rate = $this->Mess_model->perMealRate($plan);
                $row = array(
                    'member_id' => $member->id,
                    'plan_id' => $plan ? $plan->id : null,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'cycle_days' => $cycle_days,
                    'diet' => in_array($this->post('diet'), array('veg', 'nonveg', 'both')) ? $this->post('diet') : 'nonveg',
                    'delivery_mode' => $this->post('delivery_mode') == 'delivery' ? 'delivery' : 'pickup',
                    'delivery_address' => $this->post('delivery_address'),
                    'persons_total' => $persons_total,
                    'suggested_price' => round($rate * $persons_total * $cycle_days, 3),
                    'agreed_price' => $this->money($agreed),
                    'price_note' => $this->post('price_note'),
                    'note' => $this->post('note'),
                );
                if ($sub) {
                    $sub_id = $this->Mess_model->saveSubscription($row, $meals, $sub->id);
                    $this->session->set_flashdata('exception', lang('update_success'));
                } else {
                    $row['renewed_from_id'] = $this->post('renewed_from_id') ? (int) $this->post('renewed_from_id') : null;
                    $row['status'] = 'active';
                    $row['outlet_id'] = $this->outlet_id;
                    $row['company_id'] = $this->company_id;
                    $row['user_id'] = $this->user_id;
                    $row['added_date_time'] = date('Y-m-d H:i:s');
                    $sub_id = $this->Mess_model->saveSubscription($row, $meals);
                    $this->session->set_flashdata('exception', lang('insertion_success'));
                }
                $payment_row_id = 0;
                if ($advance > 0) {
                    $payment_row_id = $this->Mess_model->insertPayment(array(
                        'member_id' => $member->id,
                        'subscription_id' => $sub_id,
                        'type' => 'payment',
                        'amount' => $advance,
                        'payment_id' => $advance_method,
                        'date_time' => date('Y-m-d H:i:s'),
                        'only_date' => date('Y-m-d'),
                        'note' => lang('mess_advance_now'),
                        'counter_id' => (int) $this->session->userdata('counter_id'),
                        'user_id' => $this->user_id,
                        'outlet_id' => $this->outlet_id,
                        'company_id' => $this->company_id,
                    ));
                }
                if ($payment_row_id && $this->input->post('submit') == 'print') {
                    $this->session->set_flashdata('mess_print_receipt', $payment_row_id);
                }
                redirect('Mess/member/' . $member->id);
            }
        }

        $data['sub'] = $sub;
        $data['renew_from'] = $renew_from;
        $data['encrypted_id'] = $encrypted_id;
        $data['members'] = $this->Mess_model->getMembersForDropdown($this->company_id);
        $data['plans'] = $this->Mess_model->getPlans($this->company_id);
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['payment_methods'] = getAllPaymentMethods(5);
        $data['preselect_member'] = $sub ? $sub->member_id : ($renew_from ? $renew_from->member_id : (int) $this->input->get('member_id', TRUE));
        if ($sub) {
            $data['overlaps'] = $this->Mess_model->getOverlappingSubscriptions($sub->member_id, $sub->start_date, $sub->end_date, $sub->id);
        } else {
            $data['overlaps'] = array();
        }
        $data['register_open'] = $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) > 0;
        $this->render('mess/addEditSubscription', $data);
    }

    public function addPause() {
        $sub = $this->Mess_model->getSubscription((int) $this->input->post('subscription_id'), $this->company_id);
        if (!$sub) {
            redirect('Mess/subscriptions');
        }
        $from = $this->cleanDate($this->input->post('from_date'));
        $to = $this->cleanDate($this->input->post('to_date'));
        $error = (!$from || !$to) ? 'mess_pause_dates_invalid' : $this->Mess_model->addPause($sub, $from, $to, $this->post('reason'), $this->user_id);
        if ($error) {
            $this->session->set_flashdata('exception_er', lang($error));
        } else {
            $this->session->set_flashdata('exception', lang('mess_pause_added'));
        }
        redirect('Mess/member/' . $sub->member_id);
    }

    public function deletePause($id = '') {
        $pause = $this->db->get_where('tbl_mess_pauses', array('id' => (int) $id, 'del_status' => 'Live'))->row();
        if ($pause) {
            $sub = $this->Mess_model->getSubscription($pause->subscription_id, $this->company_id);
            if ($sub) {
                $this->Mess_model->deletePause($pause);
                $this->session->set_flashdata('exception', lang('delete_success'));
                redirect('Mess/member/' . $sub->member_id);
            }
        }
        redirect('Mess/subscriptions');
    }

    public function cancelSubscription($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $sub = $this->Mess_model->getSubscription($id, $this->company_id);
        if (!$sub) {
            redirect('Mess/subscriptions');
        }
        $this->Common_model->updateInformation(array('status' => 'cancelled'), $sub->id, 'tbl_mess_subscriptions');
        $this->db->query("DELETE FROM tbl_mess_attendance WHERE subscription_id = ? AND status = 'pending'", array($sub->id));
        $this->session->set_flashdata('exception', lang('update_success'));
        redirect('Mess/member/' . $sub->member_id);
    }

    public function deleteSubscription($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $sub = $this->Mess_model->getSubscription($id, $this->company_id);
        if (!$sub) {
            redirect('Mess/subscriptions');
        }
        $pays = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_payments WHERE subscription_id = ? AND del_status = 'Live'", array($sub->id))->row()->c;
        $marked = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_attendance WHERE subscription_id = ? AND status <> 'pending'", array($sub->id))->row()->c;
        if ($pays || $marked) {
            $this->Common_model->updateInformation(array('status' => 'cancelled'), $sub->id, 'tbl_mess_subscriptions');
            $this->db->query("DELETE FROM tbl_mess_attendance WHERE subscription_id = ? AND status = 'pending'", array($sub->id));
            $this->session->set_flashdata('exception_er', lang('mess_sub_has_payments'));
        } else {
            $this->Common_model->deleteStatusChange($sub->id, 'tbl_mess_subscriptions');
            $this->db->query("DELETE FROM tbl_mess_attendance WHERE subscription_id = ?", array($sub->id));
            $this->session->set_flashdata('exception', lang('delete_success'));
        }
        redirect('Mess/member/' . $sub->member_id);
    }

    /* =================================================================
     * Payments
     * ================================================================= */

    public function payments() {
        $from = $this->cleanDate($this->input->get('from', TRUE), date('Y-m-d', strtotime('-30 days')));
        $to = $this->cleanDate($this->input->get('to', TRUE), date('Y-m-d'));
        $data = array();
        $data['from'] = $from;
        $data['to'] = $to;
        $data['payments'] = $this->Mess_model->getPayments($this->outlet_id, $from, $to);
        $data['by_method'] = $this->Mess_model->getCollectionByMethod($this->outlet_id, $from, $to);
        $this->render('mess/payments', $data);
    }

    public function dues() {
        $settings = $this->Mess_model->getSettings($this->company_id);
        $data = array();
        $data['dues'] = $this->Mess_model->getDues($this->company_id);
        foreach ($data['dues'] as $d) {
            $text = $this->Mess_model->fillTemplate($settings->whatsapp_due_template, array(
                'name' => $d->name,
                'amount' => getAmtPCustom($d->balance),
                'currency' => $this->session->userdata('currency'),
            ));
            $d->whatsapp = $this->Mess_model->whatsappLink($d->phone, $text, $settings->whatsapp_country_code);
        }
        $this->render('mess/dues', $data);
    }

    /**
     * can this user enter refunds / adjustments
     * @access private
     */
    private function canRefund() {
        return $this->isAdmin() || checkAccess(self::ACCESS_PAYMENTS, 'delete');
    }

    public function addPayment() {
        $settings = $this->Mess_model->getSettings($this->company_id);
        if ($settings->require_open_register && $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) == 0) {
            $this->session->set_flashdata('exception_3', lang('mess_register_needed'));
            rememberClickedRedirect();
            redirect('Register/openRegister');
        }
        $data = array();
        $data['errors'] = array();
        if ($this->input->post('submit')) {
            $member = $this->Mess_model->getMember((int) $this->post('member_id'), $this->company_id);
            $type = in_array($this->post('type'), array('payment', 'refund', 'adjustment')) ? $this->post('type') : 'payment';
            $amount = $this->money($this->post('amount'));
            $payment_id = (int) $this->post('payment_id');
            $subscription_id = (int) $this->post('subscription_id');
            $only_date = $this->cleanDate($this->post('date'), date('Y-m-d'));
            if (!$member) {
                $data['errors'][] = lang('mess_member') . ': ' . lang('select');
            }
            if ($amount <= 0) {
                $data['errors'][] = lang('mess_amount_positive');
            }
            if ($type != 'adjustment' && !$payment_id) {
                $data['errors'][] = lang('mess_payment_method_required');
            }
            if ($type != 'payment' && !$this->canRefund()) {
                $data['errors'][] = lang('mess_refund_adjust_admin');
            }
            if ($subscription_id && $member) {
                $sub = $this->Mess_model->getSubscription($subscription_id, $this->company_id);
                if (!$sub || $sub->member_id != $member->id) {
                    $subscription_id = 0;
                }
            }
            if (!$data['errors']) {
                if ($type == 'adjustment' && $this->post('direction') == 'debit') {
                    $amount = -$amount;
                }
                $pid = $this->Mess_model->insertPayment(array(
                    'member_id' => $member->id,
                    'subscription_id' => $subscription_id ? $subscription_id : null,
                    'type' => $type,
                    'amount' => $amount,
                    'payment_id' => $type == 'adjustment' ? null : $payment_id,
                    'date_time' => date('Y-m-d H:i:s'),
                    'only_date' => $only_date,
                    'note' => $this->post('note'),
                    'counter_id' => $type == 'adjustment' ? 0 : (int) $this->session->userdata('counter_id'),
                    'user_id' => $this->user_id,
                    'outlet_id' => $this->outlet_id,
                    'company_id' => $this->company_id,
                ));
                $this->session->set_flashdata('exception', lang('mess_payment_saved'));
                if ($this->input->post('submit') == 'print') {
                    $this->session->set_flashdata('mess_print_receipt', $pid);
                }
                $back = $this->post('back');
                if ($back == 'payments') {
                    redirect('Mess/payments');
                }
                redirect('Mess/member/' . $member->id);
            }
        }
        $data['members'] = $this->Mess_model->getMembersForDropdown($this->company_id);
        $data['payment_methods'] = getAllPaymentMethods(5);
        $data['preselect_member'] = (int) ($this->input->post('member_id') ? $this->input->post('member_id') : $this->input->get('member_id', TRUE));
        $data['preselect_subscription'] = (int) ($this->input->post('subscription_id') ? $this->input->post('subscription_id') : $this->input->get('subscription_id', TRUE));
        $data['can_refund'] = $this->canRefund();
        $data['back'] = $this->input->get('back', TRUE) ? $this->input->get('back', TRUE) : $this->post('back');
        $this->render('mess/addPayment', $data);
    }

    public function deletePayment($encrypted_id = '') {
        $id = $this->custom->encrypt_decrypt($encrypted_id, 'decrypt');
        $payment = $this->Mess_model->getPayment($id, $this->company_id);
        $back = $this->input->get('back', TRUE) == 'member' && $payment ? 'Mess/member/' . $payment->member_id : 'Mess/payments';
        if (!$payment) {
            redirect($back);
        }
        // money of a closed register must not change under it
        if (!$this->isAdmin() && $payment->type != 'adjustment' && $payment->counter_id && !$this->Mess_model->getOpenRegisterOfPayment($payment)) {
            $this->session->set_flashdata('exception_er', lang('mess_payment_closed_register'));
            redirect($back);
        }
        $this->Common_model->deleteStatusChange($payment->id, 'tbl_mess_payments');
        $this->session->set_flashdata('exception', lang('delete_success'));
        redirect($back);
    }

    /**
     * 80mm receipt, prints itself
     * @access public
     */
    public function paymentReceipt($id = '') {
        $payment = $this->Mess_model->getPayment((int) $id, $this->company_id);
        if (!$payment) {
            show_404();
        }
        $data = array();
        $data['payment'] = $payment;
        $data['balance'] = $this->Mess_model->getMemberBalance($payment->member_id);
        $data['company'] = getCompanyInfo();
        $data['outlet_name'] = getOutletNameById($payment->outlet_id);
        $this->load->view('mess/paymentReceipt', $data);
    }

    /* =================================================================
     * Settings
     * ================================================================= */

    public function settings() {
        $data = array();
        if ($this->input->post('submit')) {
            $time = function ($v, $d) {
                return preg_match('/^\d{2}:\d{2}$/', (string) $v) ? $v : $d;
            };
            $policy = $this->post('skip_credit_policy');
            $row = array(
                'breakfast_on' => $this->input->post('breakfast_on') ? 1 : 0,
                'breakfast_from' => $time($this->post('breakfast_from'), '06:30'),
                'breakfast_to' => $time($this->post('breakfast_to'), '10:00'),
                'lunch_on' => $this->input->post('lunch_on') ? 1 : 0,
                'lunch_from' => $time($this->post('lunch_from'), '12:00'),
                'lunch_to' => $time($this->post('lunch_to'), '15:00'),
                'dinner_on' => $this->input->post('dinner_on') ? 1 : 0,
                'dinner_from' => $time($this->post('dinner_from'), '19:00'),
                'dinner_to' => $time($this->post('dinner_to'), '23:00'),
                'cycle_days' => max(1, min(366, (int) $this->post('cycle_days'))),
                'expiring_days' => max(0, min(60, (int) $this->post('expiring_days'))),
                'skip_credit_policy' => in_array($policy, array('none', 'informed', 'always')) ? $policy : 'none',
                'require_open_register' => $this->input->post('require_open_register') ? 1 : 0,
                'member_code_prefix' => substr(preg_replace('/[^A-Za-z0-9\-_\/]/', '', $this->post('member_code_prefix')), 0, 10),
                'whatsapp_country_code' => substr(preg_replace('/\D/', '', $this->post('whatsapp_country_code')), 0, 6),
                'whatsapp_menu_template' => $this->input->post('whatsapp_menu_template', TRUE),
                'whatsapp_due_template' => $this->input->post('whatsapp_due_template', TRUE),
                'whatsapp_expiry_template' => $this->input->post('whatsapp_expiry_template', TRUE),
                'updated_by' => $this->user_id,
                'updated_at' => date('Y-m-d H:i:s'),
            );
            if (!$row['breakfast_on'] && !$row['lunch_on'] && !$row['dinner_on']) {
                $row['dinner_on'] = 1;
            }
            $this->Mess_model->saveSettings($this->company_id, $row);
            $this->session->set_flashdata('exception', lang('mess_settings_saved'));
            redirect('Mess/settings');
        }
        $this->render('mess/settings', $data);
    }
}
