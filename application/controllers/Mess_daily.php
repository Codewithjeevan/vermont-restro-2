<?php
/*
  ###########################################################
  # This is Mess_daily Controller
  ###########################################################
  # Mess (monthly tiffin) module, part 2: the daily loop.
  # Daily menu planner, meal register (who got food, who did
  # not), advance skips, extras ("aaj mujhe ye chahiye") and
  # the printable daily sheet with the stock check.
  # Plan: docs/mess_system_plan.html
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Mess_daily extends Cl_Controller {

    // tbl_access parent ids, mirrored in Update/mess_module_migration.sql
    const ACCESS_DAILY_MENU = 392;
    const ACCESS_MEAL_REGISTER = 395;
    const ACCESS_EXTRAS = 398;
    const ACCESS_DAILY_SHEET = 402;
    const ACCESS_DAY_CLOSE = 404;
    const ACCESS_PAYMENTS = 388;

    private $company_id;
    private $outlet_id;
    private $user_id;

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

        //start check access function
        $segment_2 = $this->uri->segment(2);
        $map = array(
            'index' => array(self::ACCESS_MEAL_REGISTER, 'view'),
            'menu' => array(self::ACCESS_DAILY_MENU, 'view'),
            'saveMenu' => array(self::ACCESS_DAILY_MENU, 'update'),
            'copyMenu' => array(self::ACCESS_DAILY_MENU, 'update'),
            'register' => array(self::ACCESS_MEAL_REGISTER, 'view'),
            'mark' => array(self::ACCESS_MEAL_REGISTER, 'update'),
            'bulkMark' => array(self::ACCESS_MEAL_REGISTER, 'update'),
            'flipDiet' => array(self::ACCESS_MEAL_REGISTER, 'update'),
            'skip' => array(self::ACCESS_MEAL_REGISTER, 'update'),
            'extras' => array(self::ACCESS_EXTRAS, 'view'),
            'addExtra' => array(self::ACCESS_EXTRAS, 'add'),
            'extraStatus' => array(self::ACCESS_EXTRAS, 'add'),
            'deleteExtra' => array(self::ACCESS_EXTRAS, 'delete'),
            'sheet' => array(self::ACCESS_DAILY_SHEET, 'view'),
            'dayClose' => array(self::ACCESS_DAY_CLOSE, 'view'),
            'closeDay' => array(self::ACCESS_DAY_CLOSE, 'update'),
            'reopenDay' => array(self::ACCESS_DAY_CLOSE, 'reopen'),
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

    private function render($view, $data) {
        $data['mess_settings'] = $this->Mess_model->getSettings($this->company_id);
        $data['main_content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('userHome', $data);
    }

    private function post($name) {
        return trim((string) $this->input->post($name, TRUE));
    }

    private function cleanDate($value, $fallback = '') {
        $value = trim((string) $value);
        if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && ($t = strtotime($value)) !== false) {
            return date('Y-m-d', $t);
        }
        if ($value && ($t = strtotime($value)) !== false) {
            return date('Y-m-d', $t);
        }
        return $fallback;
    }

    /**
     * a slot that is switched on, else the slot of the moment
     * @access private
     */
    private function cleanSlot($slot) {
        $slots = $this->Mess_model->getActiveSlots($this->company_id);
        $slot = (int) $slot;
        if (isset($slots[$slot])) {
            return $slot;
        }
        return $this->Mess_model->getCurrentSlot($this->company_id);
    }

    public function index() {
        redirect('Mess_daily/register');
    }

    /* =================================================================
     * Daily menu planner
     * ================================================================= */

    public function menu($date = '') {
        $date = $this->cleanDate($date ? $date : $this->input->get('date', TRUE), date('Y-m-d'));
        $settings = $this->Mess_model->getSettings($this->company_id);
        $data = array();
        $data['date'] = $date;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['menu'] = $this->Mess_model->getMenuGrouped($this->outlet_id, $date);
        $data['headcount'] = $this->Mess_model->expectedHeadcount($this->outlet_id, $date, $this->company_id);
        $data['food_menus'] = $this->Mess_model->getFoodMenusForPicker($this->company_id);
        $data['can_edit'] = checkAccess(self::ACCESS_DAILY_MENU, 'update');
        // extras per food menu and slot, they add to the planned portions
        $extra_qty = array();
        foreach ($this->Mess_model->getExtras($this->outlet_id, $date, $date) as $e) {
            if ($e->status != 'cancelled' && $e->food_menu_id) {
                $k = $e->slot . '_' . $e->food_menu_id;
                $extra_qty[$k] = (isset($extra_qty[$k]) ? $extra_qty[$k] : 0) + (float) $e->qty;
            }
        }
        $data['extra_qty'] = $extra_qty;
        // WhatsApp text per slot
        $texts = array();
        foreach ($data['slots'] as $slot) {
            $lines = array();
            foreach (array('nonveg', 'veg') as $diet) {
                if (empty($data['menu'][$slot->slot][$diet])) {
                    continue;
                }
                $names = array();
                foreach ($data['menu'][$slot->slot][$diet] as $d) {
                    $names[] = $d->dish_name;
                }
                $lines[] = lang('mess_' . $diet) . ': ' . implode(', ', $names);
            }
            if ($lines) {
                $texts[$slot->slot] = $this->Mess_model->fillTemplate($settings->whatsapp_menu_template, array(
                    'slot' => $slot->name,
                    'date' => date('D d M', strtotime($date)),
                    'menu' => implode("\n", $lines),
                ));
            }
        }
        $data['menu_texts'] = $texts;
        $this->render('mess/dailyMenu', $data);
    }

    /**
     * save every dish of one day. POST dish[slot][diet][i][food_menu_id|dish_name|portion|buffer]
     * @access public
     */
    public function saveMenu() {
        $date = $this->cleanDate($this->input->post('date'));
        if (!$date) {
            redirect('Mess_daily/menu');
        }
        $posted = $this->input->post('dish');
        $slots = $this->Mess_model->getActiveSlots($this->company_id);
        $food_names = array();
        foreach ($this->Mess_model->getFoodMenusForPicker($this->company_id) as $fm) {
            $food_names[(int) $fm->id] = $fm->name;
        }
        foreach ($slots as $slot) {
            foreach (array('nonveg', 'veg') as $diet) {
                $dishes = array();
                $cell = isset($posted[$slot->slot][$diet]) && is_array($posted[$slot->slot][$diet]) ? $posted[$slot->slot][$diet] : array();
                foreach ($cell as $d) {
                    $fid = isset($d['food_menu_id']) ? (int) $d['food_menu_id'] : 0;
                    if ($fid && !isset($food_names[$fid])) {
                        $fid = 0;
                    }
                    $name = isset($d['dish_name']) ? trim(strip_tags($d['dish_name'])) : '';
                    if ($fid) {
                        $name = $food_names[$fid];
                    }
                    if ($name === '') {
                        continue;
                    }
                    $portion = isset($d['portion']) ? (float) $d['portion'] : 1;
                    $buffer = isset($d['buffer']) ? (float) $d['buffer'] : 0;
                    $dishes[] = array(
                        'food_menu_id' => $fid,
                        'dish_name' => mb_substr($name, 0, 150),
                        'portion_per_person' => $portion > 0 ? min($portion, 99) : 1,
                        'buffer_portions' => max(0, min($buffer, 9999)),
                    );
                }
                $this->Mess_model->saveMenuCell($this->outlet_id, $this->company_id, $this->user_id, $date, $slot->slot, $diet, $dishes);
            }
        }
        $this->session->set_flashdata('exception', lang('mess_menu_saved'));
        redirect('Mess_daily/menu/' . $date);
    }

    public function copyMenu() {
        $to = $this->cleanDate($this->input->post('to_date'));
        $from = $this->cleanDate($this->input->post('from_date'));
        if ($to && $from && $to != $from) {
            $n = $this->Mess_model->copyMenu($this->outlet_id, $this->company_id, $this->user_id, $from, $to);
            $this->session->set_flashdata('exception', $n . ' ' . lang('mess_copy_done'));
        }
        redirect('Mess_daily/menu/' . ($to ? $to : ''));
    }

    /* =================================================================
     * Meal register
     * ================================================================= */

    public function register($date = '', $slot = '') {
        $date = $this->cleanDate($date ? $date : $this->input->get('date', TRUE), date('Y-m-d'));
        $slot = $this->cleanSlot($slot ? $slot : $this->input->get('slot', TRUE));
        $this->Mess_model->ensureAttendance($this->outlet_id, $date, $slot);
        $rows = $this->Mess_model->getAttendance($this->outlet_id, $date, $slot);
        $extras = array();
        foreach ($this->Mess_model->getExtras($this->outlet_id, $date, $date, $slot) as $e) {
            if ($e->status != 'cancelled') {
                $extras[$e->member_id][] = $e;
            }
        }
        $data = array();
        $data['date'] = $date;
        $data['slot'] = $slot;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['rows'] = $rows;
        $data['counts'] = $this->Mess_model->attendanceCounts($rows);
        $data['extras'] = $extras;
        $data['can_mark'] = checkAccess(self::ACCESS_MEAL_REGISTER, 'update');
        $data['is_future'] = $date > date('Y-m-d');
        $this->render('mess/mealRegister', $data);
    }

    /**
     * AJAX: one tap on the register. Returns the new row html and the header counts.
     * @access public
     */
    public function mark() {
        $row = $this->Mess_model->getAttendanceRow((int) $this->input->post('id'), $this->outlet_id);
        if (!$row) {
            echo json_encode(array('status' => 0, 'msg' => lang('mess_nothing_to_show')));
            return;
        }
        $error = $this->Mess_model->markAttendance($row, $this->input->post('action', TRUE), (int) $this->input->post('persons'), $this->user_id);
        if ($error) {
            echo json_encode(array('status' => 0, 'msg' => lang($error)));
            return;
        }
        echo json_encode($this->rowResponse($row->id, $row->att_date, $row->slot));
    }

    /**
     * AJAX: veg / non-veg switch of a "Both" member for one meal
     * @access public
     */
    public function flipDiet() {
        $row = $this->Mess_model->getAttendanceRow((int) $this->input->post('id'), $this->outlet_id);
        if (!$row) {
            echo json_encode(array('status' => 0));
            return;
        }
        $this->db->where('id', $row->id)->update('tbl_mess_attendance', array('diet' => $row->diet == 'veg' ? 'nonveg' : 'veg'));
        echo json_encode($this->rowResponse($row->id, $row->att_date, $row->slot));
    }

    private function rowResponse($id, $date, $slot) {
        $rows = $this->Mess_model->getAttendance($this->outlet_id, $date, $slot);
        $counts = $this->Mess_model->attendanceCounts($rows);
        $html = '';
        foreach ($rows as $r) {
            if ((int) $r->id == (int) $id) {
                $extras = array();
                foreach ($this->Mess_model->getExtras($this->outlet_id, $date, $date, $slot, $r->member_id) as $e) {
                    if ($e->status != 'cancelled') {
                        $extras[] = $e;
                    }
                }
                $html = $this->load->view('mess/_registerRow', array(
                    'r' => $r,
                    'row_extras' => $extras,
                    'can_mark' => true,
                    'is_future' => $date > date('Y-m-d'),
                ), TRUE);
            }
        }
        return array('status' => 1, 'html' => $html, 'counts' => $counts);
    }

    public function bulkMark() {
        $date = $this->cleanDate($this->input->post('date'));
        $slot = $this->cleanSlot($this->input->post('slot'));
        $action = $this->input->post('action', TRUE) == 'not_taken' ? 'not_taken' : 'served';
        if ($date && $date <= date('Y-m-d')) {
            $this->Mess_model->ensureAttendance($this->outlet_id, $date, $slot);
            $pending = $this->db->get_where('tbl_mess_attendance', array('outlet_id' => $this->outlet_id, 'att_date' => $date, 'slot' => $slot, 'status' => 'pending'))->result();
            foreach ($pending as $row) {
                $this->Mess_model->markAttendance($row, $action, 0, $this->user_id);
            }
            $this->session->set_flashdata('exception', lang('update_success') . ' (' . count($pending) . ')');
        }
        redirect('Mess_daily/register/' . $date . '/' . $slot);
    }

    /**
     * advance skip from the member page: member_id, date, slots[]
     * @access public
     */
    public function skip() {
        $member_id = (int) $this->input->post('member_id');
        $member = $this->Mess_model->getMember($member_id, $this->company_id);
        $date = $this->cleanDate($this->input->post('date'));
        $slots = $this->input->post('slots');
        if (!$member || !$date || !is_array($slots) || !$slots) {
            redirect($member ? 'Mess/member/' . $member_id : 'Mess/members');
        }
        $ok = 0;
        $errors = array();
        foreach ($slots as $slot) {
            $err = $this->Mess_model->skipMeal($member_id, $this->outlet_id, $date, (int) $slot, $this->user_id);
            if ($err) {
                $errors[$err] = lang($err);
            } else {
                $ok++;
            }
        }
        if ($ok) {
            $this->session->set_flashdata('exception', lang('mess_skip_saved') . ' (' . $ok . ')');
        }
        if ($errors) {
            $this->session->set_flashdata('exception_er', implode(' ', $errors));
        }
        redirect('Mess/member/' . $member_id);
    }

    /* =================================================================
     * Extras
     * ================================================================= */

    public function extras() {
        $from = $this->cleanDate($this->input->get('from', TRUE), date('Y-m-d'));
        $to = $this->cleanDate($this->input->get('to', TRUE), $from);
        if ($to < $from) {
            $to = $from;
        }
        $data = array();
        $data['from'] = $from;
        $data['to'] = $to;
        $data['extras'] = $this->Mess_model->getExtras($this->outlet_id, $from, $to);
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $this->render('mess/extras', $data);
    }

    public function addExtra() {
        $settings = $this->Mess_model->getSettings($this->company_id);
        $data = array();
        $data['errors'] = array();
        $register_open = $this->Common_model->isOpenRegister($this->user_id, $this->outlet_id) > 0;
        if ($this->input->post('submit')) {
            $member = $this->Mess_model->getMember((int) $this->post('member_id'), $this->company_id);
            $date = $this->cleanDate($this->post('extra_date'), date('Y-m-d'));
            $slot = $this->cleanSlot($this->post('slot'));
            $settle = $this->post('settle_type') == 'pay_now' ? 'pay_now' : 'on_account';
            $payment_id = (int) $this->post('payment_id');
            $items = $this->input->post('items');
            $food = array();
            foreach ($this->Mess_model->getFoodMenusForPicker($this->company_id) as $fm) {
                $food[(int) $fm->id] = $fm;
            }
            $lines = array();
            if (is_array($items)) {
                foreach ($items as $it) {
                    $fid = isset($it['food_menu_id']) ? (int) $it['food_menu_id'] : 0;
                    $name = isset($it['item_name']) ? trim(strip_tags($it['item_name'])) : '';
                    if ($fid && isset($food[$fid])) {
                        $name = $food[$fid]->name;
                    } else {
                        $fid = 0;
                    }
                    $qty = isset($it['qty']) ? (float) $it['qty'] : 0;
                    $price = isset($it['unit_price']) ? round((float) str_replace(',', '', $it['unit_price']), 3) : 0;
                    if ($name === '' || $qty <= 0) {
                        continue;
                    }
                    $lines[] = array('food_menu_id' => $fid ? $fid : null, 'item_name' => mb_substr($name, 0, 150), 'qty' => $qty, 'unit_price' => max(0, $price), 'total' => round($qty * max(0, $price), 3));
                }
            }
            if (!$member) {
                $data['errors'][] = lang('mess_member') . ': ' . lang('select');
            }
            if (!$lines) {
                $data['errors'][] = lang('mess_item_name') . ' / ' . lang('qty');
            }
            if ($settle == 'pay_now') {
                if (!checkAccess(self::ACCESS_PAYMENTS, 'add')) {
                    $data['errors'][] = lang('menu_not_permit_access');
                } elseif (!$payment_id) {
                    $data['errors'][] = lang('mess_payment_method_required');
                } elseif ($settings->require_open_register && !$register_open) {
                    $data['errors'][] = lang('mess_register_needed');
                }
            }
            if (!$data['errors']) {
                $sub = $this->Mess_model->getSubscriptionCovering($member->id, $date);
                $now = date('Y-m-d H:i:s');
                foreach ($lines as $l) {
                    $row = array_merge($l, array(
                        'extra_date' => $date,
                        'slot' => $slot,
                        'member_id' => $member->id,
                        'subscription_id' => $sub ? $sub->id : null,
                        'settle_type' => $settle,
                        'status' => 'requested',
                        'note' => $this->post('note'),
                        'outlet_id' => $this->outlet_id,
                        'company_id' => $this->company_id,
                        'user_id' => $this->user_id,
                        'added_date_time' => $now,
                    ));
                    $extra_id = $this->Common_model->insertInformation($row, 'tbl_mess_extras');
                    if ($settle == 'pay_now' && $l['total'] > 0) {
                        $this->Mess_model->insertPayment(array(
                            'member_id' => $member->id,
                            'subscription_id' => null,
                            'extra_id' => $extra_id,
                            'type' => 'payment',
                            'amount' => $l['total'],
                            'payment_id' => $payment_id,
                            'date_time' => $now,
                            'only_date' => date('Y-m-d'),
                            'note' => lang('mess_extra') . ': ' . (float) $l['qty'] . ' x ' . $l['item_name'],
                            'counter_id' => (int) $this->session->userdata('counter_id'),
                            'user_id' => $this->user_id,
                            'outlet_id' => $this->outlet_id,
                            'company_id' => $this->company_id,
                        ));
                    }
                }
                $this->session->set_flashdata('exception', lang('mess_extra_saved'));
                $back = $this->post('back');
                if ($back == 'register') {
                    redirect('Mess_daily/register/' . $date . '/' . $slot);
                } elseif ($back == 'extras') {
                    redirect('Mess_daily/extras?from=' . $date);
                }
                redirect('Mess/member/' . $member->id);
            }
        }
        $data['members'] = $this->Mess_model->getMembersForDropdown($this->company_id);
        $data['food_menus'] = $this->Mess_model->getFoodMenusForPicker($this->company_id);
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['current_slot'] = $this->Mess_model->getCurrentSlot($this->company_id);
        $data['payment_methods'] = getAllPaymentMethods(5);
        $data['can_pay_now'] = checkAccess(self::ACCESS_PAYMENTS, 'add');
        $data['register_open'] = $register_open;
        $data['preselect_member'] = (int) ($this->input->post('member_id') ? $this->input->post('member_id') : $this->input->get('member_id', TRUE));
        $data['preselect_date'] = $this->cleanDate($this->input->get('date', TRUE), date('Y-m-d'));
        $data['preselect_slot'] = $this->input->get('slot', TRUE) ? (int) $this->input->get('slot', TRUE) : $data['current_slot'];
        $data['back'] = $this->input->get('back', TRUE) ? $this->input->get('back', TRUE) : $this->post('back');
        $this->render('mess/addExtra', $data);
    }

    public function extraStatus($id = '', $status = '') {
        $extra = $this->Mess_model->getExtra((int) $id, $this->outlet_id);
        $back = $this->input->get('back', TRUE);
        if ($extra && in_array($status, array('requested', 'delivered', 'cancelled'))) {
            $this->Common_model->updateInformation(array('status' => $status), $extra->id, 'tbl_mess_extras');
            $paid = $this->db->get_where('tbl_mess_payments', array('extra_id' => $extra->id, 'del_status' => 'Live'))->row();
            if ($status == 'cancelled' && $paid) {
                $this->session->set_flashdata('exception_er', lang('mess_extra_paid_cancel_note'));
            } else {
                $this->session->set_flashdata('exception', lang('update_success'));
            }
        }
        if ($extra && $back == 'register') {
            redirect('Mess_daily/register/' . $extra->extra_date . '/' . $extra->slot);
        }
        if ($extra && $back == 'member') {
            redirect('Mess/member/' . $extra->member_id);
        }
        redirect('Mess_daily/extras' . ($extra ? '?from=' . $extra->extra_date : ''));
    }

    public function deleteExtra($id = '') {
        $extra = $this->Mess_model->getExtra((int) $id, $this->outlet_id);
        if ($extra) {
            $paid = $this->db->get_where('tbl_mess_payments', array('extra_id' => $extra->id, 'del_status' => 'Live'))->row();
            if ($paid && $this->session->userdata('role') != 'Admin' && $paid->counter_id && !$this->Mess_model->getOpenRegisterOfPayment($paid)) {
                $this->session->set_flashdata('exception_er', lang('mess_payment_closed_register'));
            } else {
                $this->Common_model->deleteStatusChange($extra->id, 'tbl_mess_extras');
                if ($paid) {
                    $this->Common_model->deleteStatusChange($paid->id, 'tbl_mess_payments');
                }
                $this->session->set_flashdata('exception', lang('delete_success'));
            }
        }
        redirect('Mess_daily/extras' . ($extra ? '?from=' . $extra->extra_date : ''));
    }

    /* =================================================================
     * Day close: cooked / leftover per dish, posts stock
     * ================================================================= */

    public function dayClose($date = '', $slot = '') {
        $date = $this->cleanDate($date ? $date : $this->input->get('date', TRUE), date('Y-m-d'));
        $slot = $this->cleanSlot($slot ? $slot : $this->input->get('slot', TRUE));
        $data = array();
        $data['date'] = $date;
        $data['slot'] = $slot;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['close'] = $this->Mess_model->getDayClose($this->outlet_id, $date, $slot);
        $data['closed'] = $data['close'] && $data['close']->status == 'closed';
        if ($data['closed']) {
            $data['items'] = $this->Mess_model->getDayCloseItems($data['close']->id);
        } else {
            $data['plan'] = $this->Mess_model->buildProduction($this->outlet_id, $date, $slot);
        }
        // closed / open state of every slot of the day, for the tabs
        $states = array();
        foreach ($data['slots'] as $s) {
            $c = $this->Mess_model->getDayClose($this->outlet_id, $date, $s->slot);
            $states[$s->slot] = $c && $c->status == 'closed';
        }
        $data['slot_closed'] = $states;
        $data['can_close'] = checkAccess(self::ACCESS_DAY_CLOSE, 'update');
        $data['can_reopen'] = checkAccess(self::ACCESS_DAY_CLOSE, 'reopen');
        $this->render('mess/dayClose', $data);
    }

    public function closeDay() {
        $date = $this->cleanDate($this->input->post('date'));
        $slot = $this->cleanSlot($this->input->post('slot'));
        if (!$date) {
            redirect('Mess_daily/dayClose');
        }
        if ($date > date('Y-m-d')) {
            $this->session->set_flashdata('exception_er', lang('mess_close_future'));
            redirect('Mess_daily/dayClose/' . $date . '/' . $slot);
        }
        $input = $this->input->post('close');
        $result = $this->Mess_model->closeDay($this->outlet_id, $this->company_id, $this->user_id, $date, $slot, is_array($input) ? $input : array(), trim((string) $this->input->post('note', TRUE)));
        if (is_int($result)) {
            $this->session->set_flashdata('exception', lang('mess_closed_ok'));
        } else {
            $this->session->set_flashdata('exception_er', lang($result));
        }
        redirect('Mess_daily/dayClose/' . $date . '/' . $slot);
    }

    public function reopenDay() {
        $date = $this->cleanDate($this->input->post('date'));
        $slot = $this->cleanSlot($this->input->post('slot'));
        $close = $date ? $this->Mess_model->getDayClose($this->outlet_id, $date, $slot) : null;
        if ($close && $close->status == 'closed') {
            $this->Mess_model->reopenDay($close, $this->user_id);
            $this->session->set_flashdata('exception', lang('mess_reopened_ok'));
        }
        redirect('Mess_daily/dayClose/' . $date . '/' . $slot);
    }

    /* =================================================================
     * Daily sheet (A4 print)
     * ================================================================= */

    public function sheet($date = '', $slot = '') {
        $date = $this->cleanDate($date ? $date : $this->input->get('date', TRUE), date('Y-m-d'));
        $slot = $this->cleanSlot($slot ? $slot : $this->input->get('slot', TRUE));
        $data = array();
        $data['date'] = $date;
        $data['slot'] = $slot;
        $data['slots'] = $this->Mess_model->getActiveSlots($this->company_id);
        $data['plan'] = $this->Mess_model->buildProduction($this->outlet_id, $date, $slot);
        $data['company'] = getCompanyInfo();
        $data['outlet_name'] = getOutletNameById($this->outlet_id);
        $data['print'] = $this->input->get('print', TRUE) ? true : false;
        if ($data['print']) {
            $this->load->view('mess/dailySheetPrint', $data);
            return;
        }
        $this->render('mess/dailySheet', $data);
    }
}
