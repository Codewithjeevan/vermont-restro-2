<?php
/*
  ###########################################################
  # This is Mess_model Model
  ###########################################################
  # Monthly tiffin (mess) module: plans, members, subscriptions,
  # payments, attendance, daily menu and extras.
  # Plan: docs/mess_system_plan.html
  ###########################################################
 */
class Mess_model extends CI_Model {

    const SLOT_BREAKFAST = 1;
    const SLOT_LUNCH = 2;
    const SLOT_DINNER = 3;

    private $settings_cache = array();

    /* =================================================================
     * Settings
     * ================================================================= */

    /**
     * settings of a company, defaults when nothing was saved yet
     * @access public
     * @return object
     * @param int
     */
    public function getSettings($company_id) {
        if (isset($this->settings_cache[$company_id])) {
            return $this->settings_cache[$company_id];
        }
        $row = $this->db->get_where('tbl_mess_settings', array('company_id' => $company_id))->row();
        if (!$row) {
            $row = (object) array(
                'id' => '',
                'company_id' => $company_id,
                'breakfast_on' => 1, 'breakfast_from' => '06:30', 'breakfast_to' => '10:00',
                'lunch_on' => 1, 'lunch_from' => '12:00', 'lunch_to' => '15:00',
                'dinner_on' => 1, 'dinner_from' => '19:00', 'dinner_to' => '23:00',
                'cycle_days' => 30,
                'expiring_days' => 3,
                'skip_credit_policy' => 'none',
                'require_open_register' => 1,
                'member_code_prefix' => 'M-',
                'whatsapp_country_code' => '971',
                'whatsapp_menu_template' => '',
                'whatsapp_due_template' => '',
                'whatsapp_expiry_template' => '',
            );
        }
        if (!$row->whatsapp_menu_template) {
            $row->whatsapp_menu_template = "{slot} today ({date}):\n{menu}";
        }
        if (!$row->whatsapp_due_template) {
            $row->whatsapp_due_template = "Dear {name}, your mess balance is {currency} {amount}. Kindly clear it at the counter. Thank you.";
        }
        if (!$row->whatsapp_expiry_template) {
            $row->whatsapp_expiry_template = "Dear {name}, your mess subscription ends on {end_date}. Please renew to continue. Thank you.";
        }
        $this->settings_cache[$company_id] = $row;
        return $row;
    }

    /**
     * insert or update the settings row of a company
     * @access public
     * @return void
     * @param int
     * @param array
     */
    public function saveSettings($company_id, $data) {
        $exists = $this->db->get_where('tbl_mess_settings', array('company_id' => $company_id))->row();
        $data['company_id'] = $company_id;
        if ($exists) {
            $this->db->where('id', $exists->id)->update('tbl_mess_settings', $data);
        } else {
            $this->db->insert('tbl_mess_settings', $data);
        }
        unset($this->settings_cache[$company_id]);
    }

    /**
     * slots switched on in the settings, keyed by slot number
     * @access public
     * @return array  slot => (object) name, from, to
     * @param int
     */
    public function getActiveSlots($company_id) {
        $s = $this->getSettings($company_id);
        $slots = array();
        if ($s->breakfast_on) {
            $slots[self::SLOT_BREAKFAST] = (object) array('slot' => 1, 'key' => 'breakfast', 'name' => lang('mess_breakfast'), 'from' => $s->breakfast_from, 'to' => $s->breakfast_to);
        }
        if ($s->lunch_on) {
            $slots[self::SLOT_LUNCH] = (object) array('slot' => 2, 'key' => 'lunch', 'name' => lang('mess_lunch'), 'from' => $s->lunch_from, 'to' => $s->lunch_to);
        }
        if ($s->dinner_on) {
            $slots[self::SLOT_DINNER] = (object) array('slot' => 3, 'key' => 'dinner', 'name' => lang('mess_dinner'), 'from' => $s->dinner_from, 'to' => $s->dinner_to);
        }
        return $slots;
    }

    /**
     * the slot whose time window is open now, else the next one to come, else dinner
     * @access public
     * @return int
     * @param int
     */
    public function getCurrentSlot($company_id) {
        $slots = $this->getActiveSlots($company_id);
        if (!$slots) {
            return self::SLOT_DINNER;
        }
        $now = date('H:i');
        foreach ($slots as $slot) {
            if ($now >= $slot->from && $now <= $slot->to) {
                return $slot->slot;
            }
        }
        foreach ($slots as $slot) {
            if ($now < $slot->from) {
                return $slot->slot;
            }
        }
        $keys = array_keys($slots);
        return end($keys);
    }

    /* =================================================================
     * Plans
     * ================================================================= */

    public function getPlans($company_id, $only_active = false) {
        $this->db->from('tbl_mess_plans');
        $this->db->where('company_id', $company_id);
        $this->db->where('del_status', 'Live');
        if ($only_active) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    public function getPlan($id, $company_id) {
        return $this->db->get_where('tbl_mess_plans', array('id' => $id, 'company_id' => $company_id, 'del_status' => 'Live'))->row();
    }

    /**
     * rate of one meal for one person: price / (cycle days x meals a day)
     * @access public
     * @return float
     * @param object
     */
    public function perMealRate($plan) {
        if (!$plan) {
            return 0;
        }
        $meals = (int) $plan->has_breakfast + (int) $plan->has_lunch + (int) $plan->has_dinner;
        $days = (int) $plan->cycle_days;
        if ($meals < 1 || $days < 1) {
            return 0;
        }
        return (float) $plan->price_per_person / ($days * $meals);
    }

    /* =================================================================
     * Members
     * ================================================================= */

    /**
     * next free member code for the company (M-0001, M-0002 ...)
     * @access public
     * @return string
     * @param int
     */
    public function nextMemberCode($company_id) {
        $prefix = $this->getSettings($company_id)->member_code_prefix;
        $count = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_members WHERE company_id = ?", array($company_id))->row()->c;
        $n = (int) $count + 1;
        do {
            $code = $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
            $taken = $this->db->query("SELECT id FROM tbl_mess_members WHERE company_id = ? AND member_code = ?", array($company_id, $code))->row();
            $n++;
        } while ($taken);
        return $code;
    }

    /**
     * SQL snippet: member balance = charges - credits (see plan section 6)
     * @access private
     * @return string
     */
    private function balanceSelect($alias = 'm') {
        return "(
            (SELECT COALESCE(SUM(s.agreed_price),0) FROM tbl_mess_subscriptions s WHERE s.member_id = $alias.id AND s.del_status = 'Live' AND s.status <> 'cancelled')
          + (SELECT COALESCE(SUM(e.total),0) FROM tbl_mess_extras e WHERE e.member_id = $alias.id AND e.del_status = 'Live' AND e.status <> 'cancelled')
          - (SELECT COALESCE(SUM(CASE WHEN p.type = 'refund' THEN -p.amount ELSE p.amount END),0) FROM tbl_mess_payments p WHERE p.member_id = $alias.id AND p.del_status = 'Live')
        )";
    }

    /**
     * members of a company with balance, current subscription end and last payment
     * @access public
     * @return array
     * @param int
     * @param string
     */
    public function getMembers($company_id, $status = '') {
        $today = date('Y-m-d');
        $where = '';
        $binds = array($today, $today, $company_id);
        if ($status == 'active' || $status == 'inactive') {
            $where .= ' AND m.status = ?';
            $binds[] = $status;
        }
        return $this->db->query("SELECT m.*,
                " . $this->balanceSelect('m') . " AS balance,
                (SELECT MAX(s.end_date) FROM tbl_mess_subscriptions s WHERE s.member_id = m.id AND s.del_status = 'Live' AND s.status <> 'cancelled') AS last_end_date,
                (SELECT COUNT(*) FROM tbl_mess_subscriptions s WHERE s.member_id = m.id AND s.del_status = 'Live' AND s.status <> 'cancelled' AND s.start_date <= ? AND s.end_date >= ?) AS current_subscriptions,
                (SELECT MAX(p.only_date) FROM tbl_mess_payments p WHERE p.member_id = m.id AND p.del_status = 'Live' AND p.type = 'payment') AS last_payment_date
            FROM tbl_mess_members m
            WHERE m.company_id = ? AND m.del_status = 'Live' $where
            ORDER BY m.name ASC", $binds)->result();
    }

    /**
     * active members for dropdowns (id, code, name, phone, balance)
     * @access public
     * @return array
     * @param int
     */
    public function getMembersForDropdown($company_id) {
        return $this->db->query("SELECT m.id, m.member_code, m.name, m.phone, m.building_room, m.diet_pref, m.delivery_mode, m.address, m.status
            FROM tbl_mess_members m
            WHERE m.company_id = ? AND m.del_status = 'Live'
            ORDER BY m.status ASC, m.name ASC", array($company_id))->result();
    }

    public function getMember($id, $company_id) {
        $row = $this->db->query("SELECT m.*, " . $this->balanceSelect('m') . " AS balance
            FROM tbl_mess_members m WHERE m.id = ? AND m.company_id = ? AND m.del_status = 'Live'", array($id, $company_id))->row();
        return $row;
    }

    public function getMemberBalance($member_id) {
        $row = $this->db->query("SELECT " . $this->balanceSelect('m') . " AS balance FROM tbl_mess_members m WHERE m.id = ?", array($member_id))->row();
        return $row ? (float) $row->balance : 0;
    }

    /**
     * other members of the company already using a phone number
     * @access public
     * @return array
     */
    public function findMembersByPhone($company_id, $phone, $except_id = 0) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) < 6) {
            return array();
        }
        $tail = substr($digits, -8);
        return $this->db->query("SELECT id, member_code, name, phone FROM tbl_mess_members
            WHERE company_id = ? AND del_status = 'Live' AND id <> ?
            AND (REPLACE(REPLACE(REPLACE(phone,'-',''),' ',''),'+','') LIKE ? OR REPLACE(REPLACE(REPLACE(phone2,'-',''),' ',''),'+','') LIKE ?)",
            array($company_id, (int) $except_id, '%' . $tail, '%' . $tail))->result();
    }

    /* =================================================================
     * Subscriptions
     * ================================================================= */

    /**
     * SQL CASE giving the display status of a subscription row alias s
     * @access private
     * @return string
     */
    private function statusCase($today, $expiring_days) {
        $today = $this->db->escape($today);
        $expiring_days = (int) $expiring_days;
        return "CASE
            WHEN s.status = 'cancelled' THEN 'cancelled'
            WHEN s.start_date > $today THEN 'upcoming'
            WHEN s.end_date < $today THEN 'expired'
            WHEN DATEDIFF(s.end_date, $today) < $expiring_days THEN 'expiring'
            ELSE 'active' END";
    }

    /**
     * subscriptions of an outlet (or of one member) with member, meals, paid and status
     * $filter: '' all, current (active+expiring), active, expiring, expired, upcoming, cancelled
     * @access public
     * @return array
     */
    public function getSubscriptions($company_id, $outlet_id = '', $filter = '', $member_id = '') {
        $today = date('Y-m-d');
        $settings = $this->getSettings($company_id);
        $status_case = $this->statusCase($today, $settings->expiring_days);
        $where = " s.company_id = " . (int) $company_id . " AND s.del_status = 'Live'";
        if ($outlet_id) {
            $where .= " AND s.outlet_id = " . (int) $outlet_id;
        }
        if ($member_id) {
            $where .= " AND s.member_id = " . (int) $member_id;
        }
        $having = '';
        if ($filter == 'current') {
            $having = " HAVING sub_status IN ('active','expiring')";
        } elseif (in_array($filter, array('active', 'expiring', 'expired', 'upcoming', 'cancelled'))) {
            $having = " HAVING sub_status = " . $this->db->escape($filter);
        }
        $rows = $this->db->query("SELECT s.*, m.name AS member_name, m.member_code, m.phone, m.building_room,
                p.name AS plan_name,
                $status_case AS sub_status,
                (SELECT COALESCE(SUM(CASE WHEN py.type = 'refund' THEN -py.amount ELSE py.amount END),0) FROM tbl_mess_payments py WHERE py.subscription_id = s.id AND py.del_status = 'Live') AS paid,
                (SELECT COUNT(*) FROM tbl_mess_subscriptions n WHERE n.member_id = s.member_id AND n.id <> s.id AND n.del_status = 'Live' AND n.status <> 'cancelled' AND n.start_date > s.start_date) AS renewed
            FROM tbl_mess_subscriptions s
            JOIN tbl_mess_members m ON m.id = s.member_id
            LEFT JOIN tbl_mess_plans p ON p.id = s.plan_id
            WHERE $where
            $having
            ORDER BY s.end_date ASC, m.name ASC")->result();
        $this->attachMeals($rows);
        return $rows;
    }

    /**
     * adds ->meals (slot => persons) and ->meals_text to subscription rows
     * @access public
     * @return void
     * @param array
     */
    public function attachMeals(&$rows) {
        if (!$rows) {
            return;
        }
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r->id;
        }
        $meals = $this->db->query("SELECT subscription_id, slot, persons FROM tbl_mess_subscription_meals WHERE subscription_id IN (" . implode(',', $ids) . ") ORDER BY slot")->result();
        $map = array();
        foreach ($meals as $ml) {
            $map[$ml->subscription_id][(int) $ml->slot] = (int) $ml->persons;
        }
        foreach ($rows as $r) {
            $r->meals = isset($map[$r->id]) ? $map[$r->id] : array();
            $r->meals_text = $this->mealsText($r->meals);
        }
    }

    /**
     * "Lunch + 2 Dinner" style text, like the client's Excel
     * @access public
     * @return string
     * @param array slot => persons
     */
    public function mealsText($meals) {
        $names = array(1 => lang('mess_breakfast'), 2 => lang('mess_lunch'), 3 => lang('mess_dinner'));
        $parts = array();
        foreach ($meals as $slot => $persons) {
            if ($persons < 1 || !isset($names[$slot])) {
                continue;
            }
            $parts[] = ($persons > 1 ? $persons . ' ' : '') . $names[$slot];
        }
        return implode(' + ', $parts);
    }

    public function getSubscription($id, $company_id) {
        $settings = $this->getSettings($company_id);
        $status_case = $this->statusCase(date('Y-m-d'), $settings->expiring_days);
        $row = $this->db->query("SELECT s.*, m.name AS member_name, m.member_code, m.phone, m.building_room, p.name AS plan_name,
                $status_case AS sub_status,
                (SELECT COALESCE(SUM(CASE WHEN py.type = 'refund' THEN -py.amount ELSE py.amount END),0) FROM tbl_mess_payments py WHERE py.subscription_id = s.id AND py.del_status = 'Live') AS paid
            FROM tbl_mess_subscriptions s
            JOIN tbl_mess_members m ON m.id = s.member_id
            LEFT JOIN tbl_mess_plans p ON p.id = s.plan_id
            WHERE s.id = ? AND s.company_id = ? AND s.del_status = 'Live'", array($id, $company_id))->row();
        if ($row) {
            $rows = array($row);
            $this->attachMeals($rows);
            $row->pauses = $this->getPauses($row->id);
        }
        return $row;
    }

    /**
     * insert/update a subscription and replace its meal rows
     * @access public
     * @return int subscription id
     * @param array
     * @param array slot => persons
     * @param int
     */
    public function saveSubscription($data, $meals, $id = '') {
        $this->db->trans_start();
        if ($id) {
            $this->db->where('id', $id)->update('tbl_mess_subscriptions', $data);
        } else {
            $this->db->insert('tbl_mess_subscriptions', $data);
            $id = $this->db->insert_id();
        }
        $this->db->where('subscription_id', $id)->delete('tbl_mess_subscription_meals');
        foreach ($meals as $slot => $persons) {
            if ((int) $persons > 0) {
                $this->db->insert('tbl_mess_subscription_meals', array('subscription_id' => $id, 'slot' => (int) $slot, 'persons' => (int) $persons));
            }
        }
        $this->db->trans_complete();
        return $id;
    }

    /**
     * other live subscriptions of the member whose dates overlap
     * @access public
     * @return array
     */
    public function getOverlappingSubscriptions($member_id, $start_date, $end_date, $except_id = 0) {
        return $this->db->query("SELECT id, start_date, end_date FROM tbl_mess_subscriptions
            WHERE member_id = ? AND id <> ? AND del_status = 'Live' AND status <> 'cancelled'
            AND start_date <= ? AND end_date >= ?", array($member_id, (int) $except_id, $end_date, $start_date))->result();
    }

    public function getPauses($subscription_id) {
        return $this->db->query("SELECT * FROM tbl_mess_pauses WHERE subscription_id = ? AND del_status = 'Live' ORDER BY from_date", array($subscription_id))->result();
    }

    /**
     * add a leave period and move the end date forward by the same number of days
     * @access public
     * @return string '' on success, else the error lang key
     */
    public function addPause($subscription, $from_date, $to_date, $reason, $user_id) {
        if ($to_date < $from_date) {
            return 'mess_pause_dates_invalid';
        }
        if ($from_date < $subscription->start_date || $from_date > $subscription->end_date) {
            return 'mess_pause_outside_cycle';
        }
        foreach ($this->getPauses($subscription->id) as $p) {
            if ($from_date <= $p->to_date && $to_date >= $p->from_date) {
                return 'mess_pause_overlaps';
            }
        }
        $days = (int) ((strtotime($to_date) - strtotime($from_date)) / 86400) + 1;
        $this->db->trans_start();
        $this->db->insert('tbl_mess_pauses', array(
            'subscription_id' => $subscription->id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'days' => $days,
            'reason' => $reason,
            'user_id' => $user_id,
            'added_date_time' => date('Y-m-d H:i:s'),
        ));
        $this->db->query("UPDATE tbl_mess_subscriptions SET end_date = DATE_ADD(end_date, INTERVAL ? DAY), paused_days = paused_days + ? WHERE id = ?", array($days, $days, $subscription->id));
        // pending rows inside the pause are no longer expected
        $this->db->query("DELETE FROM tbl_mess_attendance WHERE subscription_id = ? AND status = 'pending' AND att_date BETWEEN ? AND ?", array($subscription->id, $from_date, $to_date));
        $this->db->trans_complete();
        return '';
    }

    public function deletePause($pause) {
        $this->db->trans_start();
        $this->db->where('id', $pause->id)->update('tbl_mess_pauses', array('del_status' => 'Deleted'));
        $this->db->query("UPDATE tbl_mess_subscriptions SET end_date = DATE_SUB(end_date, INTERVAL ? DAY), paused_days = GREATEST(paused_days - ?, 0) WHERE id = ?", array($pause->days, $pause->days, $pause->subscription_id));
        $this->db->trans_complete();
    }

    /**
     * is the subscription paused on a date
     * @access public
     * @return bool
     */
    public function isPausedOn($subscription_id, $date) {
        return (bool) $this->db->query("SELECT id FROM tbl_mess_pauses WHERE subscription_id = ? AND del_status = 'Live' AND ? BETWEEN from_date AND to_date", array($subscription_id, $date))->row();
    }

    /**
     * subscriptions ending within the alert window that were not renewed yet
     * @access public
     * @return array
     */
    public function getExpiring($company_id, $outlet_id, $days = null) {
        $settings = $this->getSettings($company_id);
        if ($days === null) {
            $days = $settings->expiring_days;
        }
        $rows = $this->getSubscriptions($company_id, $outlet_id, '');
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime($today . ' +' . ((int) $days) . ' days'));
        $out = array();
        foreach ($rows as $r) {
            if ($r->status == 'cancelled' || $r->renewed) {
                continue;
            }
            if ($r->end_date >= $today && $r->end_date <= $limit && $r->start_date <= $today) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /**
     * cycles that ended in the last $days days with no newer cycle for the member
     * @access public
     * @return array
     */
    public function getExpiredNotRenewed($company_id, $outlet_id, $days = 45) {
        $rows = $this->getSubscriptions($company_id, $outlet_id, 'expired');
        $from = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $out = array();
        foreach ($rows as $r) {
            if (!$r->renewed && $r->end_date >= $from) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /* =================================================================
     * Payments and ledger
     * ================================================================= */

    public function nextPaymentReference($company_id) {
        $count = $this->db->query("SELECT COUNT(*) AS c FROM tbl_mess_payments WHERE company_id = ?", array($company_id))->row()->c;
        return 'MP-' . str_pad((int) $count + 1, 6, '0', STR_PAD_LEFT);
    }

    public function insertPayment($data) {
        if (empty($data['reference_no'])) {
            $data['reference_no'] = $this->nextPaymentReference($data['company_id']);
        }
        $this->db->insert('tbl_mess_payments', $data);
        return $this->db->insert_id();
    }

    public function getPayment($id, $company_id) {
        return $this->db->query("SELECT p.*, m.name AS member_name, m.member_code, m.phone, pm.name AS payment_name, u.full_name AS user_name,
                s.start_date AS sub_start, s.end_date AS sub_end
            FROM tbl_mess_payments p
            JOIN tbl_mess_members m ON m.id = p.member_id
            LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            LEFT JOIN tbl_users u ON u.id = p.user_id
            LEFT JOIN tbl_mess_subscriptions s ON s.id = p.subscription_id
            WHERE p.id = ? AND p.company_id = ? AND p.del_status = 'Live'", array($id, $company_id))->row();
    }

    /**
     * payments of an outlet in a date range (only_date)
     * @access public
     * @return array
     */
    public function getPayments($outlet_id, $from_date, $to_date, $member_id = '', $payment_id = '') {
        $binds = array($outlet_id, $from_date, $to_date);
        $where = '';
        if ($member_id) {
            $where .= ' AND p.member_id = ?';
            $binds[] = $member_id;
        }
        if ($payment_id) {
            $where .= ' AND p.payment_id = ?';
            $binds[] = $payment_id;
        }
        return $this->db->query("SELECT p.*, m.name AS member_name, m.member_code, pm.name AS payment_name, u.full_name AS user_name
            FROM tbl_mess_payments p
            JOIN tbl_mess_members m ON m.id = p.member_id
            LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            LEFT JOIN tbl_users u ON u.id = p.user_id
            WHERE p.outlet_id = ? AND p.del_status = 'Live' AND p.only_date BETWEEN ? AND ? $where
            ORDER BY p.id DESC", $binds)->result();
    }

    /**
     * money actually received per payment method (refund negative, adjustments excluded)
     * @access public
     * @return array
     */
    public function getCollectionByMethod($outlet_id, $from_date, $to_date) {
        return $this->db->query("SELECT p.payment_id, pm.name AS payment_name,
                SUM(CASE WHEN p.type = 'refund' THEN -p.amount ELSE p.amount END) AS total, COUNT(*) AS cnt
            FROM tbl_mess_payments p
            LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            WHERE p.outlet_id = ? AND p.del_status = 'Live' AND p.type IN ('payment','refund') AND p.only_date BETWEEN ? AND ?
            GROUP BY p.payment_id ORDER BY pm.order_by ASC", array($outlet_id, $from_date, $to_date))->result();
    }

    /**
     * the register (tbl_register) row a payment belongs to, if that register is still open
     * @access public
     * @return object|null
     */
    public function getOpenRegisterOfPayment($payment) {
        if (!$payment->counter_id) {
            return null;
        }
        return $this->db->query("SELECT id, opening_balance_date_time FROM tbl_register
            WHERE counter_id = ? AND outlet_id = ? AND register_status = 1 AND opening_balance_date_time <= ?
            ORDER BY id DESC LIMIT 1", array($payment->counter_id, $payment->outlet_id, $payment->date_time))->row();
    }

    /**
     * member ledger lines, oldest first, with running balance
     * @access public
     * @return array
     */
    public function getLedger($member_id) {
        $lines = array();
        $subs = $this->db->query("SELECT * FROM tbl_mess_subscriptions WHERE member_id = ? AND del_status = 'Live' ORDER BY start_date, id", array($member_id))->result();
        $this->attachMeals($subs);
        foreach ($subs as $s) {
            $cancelled = $s->status == 'cancelled';
            $lines[] = (object) array(
                'date' => $s->start_date,
                'sort' => 1,
                'id' => $s->id,
                'kind' => 'subscription',
                'text' => lang('mess_subscription') . ': ' . $s->meals_text . ', ' . $this->shortDate($s->start_date) . ' - ' . $this->shortDate($s->end_date) . ($cancelled ? ' (' . lang('mess_status_cancelled') . ')' : '') . ($s->price_note ? ' - ' . $s->price_note : ''),
                'charge' => $cancelled ? 0 : (float) $s->agreed_price,
                'credit' => 0,
                'user_id' => $s->user_id,
            );
        }
        $extras = $this->db->query("SELECT * FROM tbl_mess_extras WHERE member_id = ? AND del_status = 'Live' ORDER BY extra_date, id", array($member_id))->result();
        foreach ($extras as $e) {
            $cancelled = $e->status == 'cancelled';
            $lines[] = (object) array(
                'date' => $e->extra_date,
                'sort' => 2,
                'id' => $e->id,
                'kind' => 'extra',
                'text' => lang('mess_extra') . ': ' . (float) $e->qty . ' x ' . $e->item_name . ' (' . ($e->settle_type == 'pay_now' ? lang('mess_pay_now') : lang('mess_on_account')) . ')' . ($cancelled ? ' (' . lang('mess_status_cancelled') . ')' : ''),
                'charge' => $cancelled ? 0 : (float) $e->total,
                'credit' => 0,
                'user_id' => $e->user_id,
            );
        }
        $pays = $this->db->query("SELECT p.*, pm.name AS payment_name FROM tbl_mess_payments p LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
            WHERE p.member_id = ? AND p.del_status = 'Live' ORDER BY p.only_date, p.id", array($member_id))->result();
        foreach ($pays as $p) {
            $amount = (float) $p->amount;
            if ($p->type == 'refund') {
                $text = lang('refund') . ' (' . $p->payment_name . ') ' . $p->reference_no;
                $charge = $amount;
                $credit = 0;
            } elseif ($p->type == 'adjustment') {
                $text = lang('mess_adjustment') . ' ' . $p->reference_no;
                $charge = $amount < 0 ? -$amount : 0;
                $credit = $amount > 0 ? $amount : 0;
            } else {
                $text = lang('payment') . ' (' . $p->payment_name . ') ' . $p->reference_no;
                $charge = 0;
                $credit = $amount;
            }
            if ($p->note) {
                $text .= ' - ' . $p->note;
            }
            $lines[] = (object) array(
                'date' => $p->only_date,
                'sort' => 3,
                'id' => $p->id,
                'kind' => 'payment',
                'text' => $text,
                'charge' => $charge,
                'credit' => $credit,
                'user_id' => $p->user_id,
            );
        }
        usort($lines, function ($a, $b) {
            if ($a->date != $b->date) {
                return strcmp($a->date, $b->date);
            }
            if ($a->sort != $b->sort) {
                return $a->sort - $b->sort;
            }
            return $a->id - $b->id;
        });
        $balance = 0;
        foreach ($lines as $l) {
            $balance += $l->charge - $l->credit;
            $l->balance = $balance;
        }
        return $lines;
    }

    /**
     * members with something to pay, highest first
     * @access public
     * @return array
     */
    public function getDues($company_id, $min_balance = 0.001) {
        $rows = $this->getMembers($company_id);
        $out = array();
        foreach ($rows as $r) {
            if ((float) $r->balance >= $min_balance) {
                $out[] = $r;
            }
        }
        usort($out, function ($a, $b) {
            return $b->balance <=> $a->balance;
        });
        return $out;
    }

    /* =================================================================
     * Attendance (meal register)
     * ================================================================= */

    /**
     * subscriptions that expect food on a date and slot (outlet scoped, pauses excluded)
     * @access public
     * @return array rows of subscription + persons for the slot
     */
    public function getExpectedSubscriptions($outlet_id, $date, $slot) {
        return $this->db->query("SELECT s.id AS subscription_id, s.member_id, s.diet, s.delivery_mode, s.company_id, sm.persons
            FROM tbl_mess_subscriptions s
            JOIN tbl_mess_subscription_meals sm ON sm.subscription_id = s.id AND sm.slot = ?
            WHERE s.outlet_id = ? AND s.del_status = 'Live' AND s.status <> 'cancelled'
              AND s.start_date <= ? AND s.end_date >= ? AND sm.persons > 0
              AND NOT EXISTS (SELECT 1 FROM tbl_mess_pauses pz WHERE pz.subscription_id = s.id AND pz.del_status = 'Live' AND ? BETWEEN pz.from_date AND pz.to_date)",
            array($slot, $outlet_id, $date, $date, $date))->result();
    }

    /**
     * create the missing "pending" rows of a date and slot. Marked rows are never touched;
     * pending rows whose subscription no longer expects food (edited / cancelled / paused)
     * are removed so the headcount stays right.
     * @access public
     * @return void
     */
    public function ensureAttendance($outlet_id, $date, $slot) {
        $expected = $this->getExpectedSubscriptions($outlet_id, $date, $slot);
        $existing = $this->db->query("SELECT id, subscription_id, status, persons_expected FROM tbl_mess_attendance WHERE outlet_id = ? AND att_date = ? AND slot = ?", array($outlet_id, $date, $slot))->result();
        $by_sub = array();
        foreach ($existing as $e) {
            $by_sub[$e->subscription_id] = $e;
        }
        $expected_ids = array();
        foreach ($expected as $x) {
            $expected_ids[$x->subscription_id] = true;
            if (isset($by_sub[$x->subscription_id])) {
                $row = $by_sub[$x->subscription_id];
                if ($row->status == 'pending' && (int) $row->persons_expected != (int) $x->persons) {
                    $this->db->where('id', $row->id)->update('tbl_mess_attendance', array('persons_expected' => (int) $x->persons));
                }
                continue;
            }
            $this->db->query("INSERT IGNORE INTO tbl_mess_attendance (att_date, slot, subscription_id, member_id, persons_expected, persons_served, status, diet, delivery_mode, outlet_id, company_id)
                VALUES (?, ?, ?, ?, ?, 0, 'pending', ?, ?, ?, ?)",
                array($date, $slot, $x->subscription_id, $x->member_id, (int) $x->persons, $x->diet == 'veg' ? 'veg' : 'nonveg', $x->delivery_mode, $outlet_id, $x->company_id));
        }
        foreach ($existing as $e) {
            if ($e->status == 'pending' && !isset($expected_ids[$e->subscription_id])) {
                $this->db->where('id', $e->id)->delete('tbl_mess_attendance');
            }
        }
    }

    /**
     * attendance rows of a date and slot with member info
     * @access public
     * @return array
     */
    public function getAttendance($outlet_id, $date, $slot) {
        return $this->db->query("SELECT a.*, m.name AS member_name, m.member_code, m.phone, m.building_room, m.address, s.diet AS sub_diet, s.delivery_address,
                u.full_name AS marked_by_name
            FROM tbl_mess_attendance a
            JOIN tbl_mess_members m ON m.id = a.member_id
            JOIN tbl_mess_subscriptions s ON s.id = a.subscription_id
            LEFT JOIN tbl_users u ON u.id = a.marked_by
            WHERE a.outlet_id = ? AND a.att_date = ? AND a.slot = ?
            ORDER BY FIELD(a.status,'pending','partial','served','not_taken','skipped'), a.delivery_mode, m.name", array($outlet_id, $date, $slot))->result();
    }

    public function getAttendanceRow($id, $outlet_id) {
        return $this->db->get_where('tbl_mess_attendance', array('id' => $id, 'outlet_id' => $outlet_id))->row();
    }

    /**
     * counts for the header tiles of the meal register
     * @access public
     * @return object
     */
    public function attendanceCounts($rows) {
        $c = (object) array('members' => 0, 'expected' => 0, 'served' => 0, 'pending' => 0, 'not_taken' => 0, 'skipped' => 0,
            'veg' => 0, 'nonveg' => 0, 'pickup' => 0, 'delivery' => 0, 'served_members' => 0);
        foreach ($rows as $r) {
            if ($r->status == 'skipped') {
                $c->skipped += $r->persons_expected;
                continue;
            }
            $c->members++;
            $c->expected += $r->persons_expected;
            if ($r->diet == 'veg') {
                $c->veg += $r->persons_expected;
            } else {
                $c->nonveg += $r->persons_expected;
            }
            if ($r->delivery_mode == 'delivery') {
                $c->delivery += $r->persons_expected;
            } else {
                $c->pickup += $r->persons_expected;
            }
            if ($r->status == 'pending') {
                $c->pending += $r->persons_expected;
            } elseif ($r->status == 'not_taken') {
                $c->not_taken += $r->persons_expected;
            } else {
                $c->served += $r->persons_served;
                $c->served_members++;
                $c->not_taken += max(0, $r->persons_expected - $r->persons_served);
            }
        }
        return $c;
    }

    /**
     * persons expected per slot and diet on a date (skipped excluded), after generating rows
     * @access public
     * @return array slot => ['veg'=>n,'nonveg'=>n,'total'=>n,'pickup'=>n,'delivery'=>n,'skipped'=>n]
     */
    public function expectedHeadcount($outlet_id, $date, $company_id) {
        $out = array();
        foreach ($this->getActiveSlots($company_id) as $slot) {
            $this->ensureAttendance($outlet_id, $date, $slot->slot);
            $rows = $this->db->query("SELECT status, diet, delivery_mode, persons_expected, persons_served FROM tbl_mess_attendance WHERE outlet_id = ? AND att_date = ? AND slot = ?", array($outlet_id, $date, $slot->slot))->result();
            $c = $this->attendanceCounts($rows);
            $out[$slot->slot] = $c;
        }
        return $out;
    }

    /* =================================================================
     * Daily menu
     * ================================================================= */

    public function getMenu($outlet_id, $date) {
        return $this->db->query("SELECT mm.*, fm.name AS food_menu_name, fm.total_cost,
                (SELECT COUNT(*) FROM tbl_food_menus_ingredients fi WHERE fi.food_menu_id = mm.food_menu_id AND fi.del_status = 'Live') AS recipe_lines
            FROM tbl_mess_menus mm
            LEFT JOIN tbl_food_menus fm ON fm.id = mm.food_menu_id
            WHERE mm.outlet_id = ? AND mm.menu_date = ? AND mm.del_status = 'Live'
            ORDER BY mm.slot, mm.diet DESC, mm.sort_order, mm.id", array($outlet_id, $date))->result();
    }

    /**
     * menu rows grouped: [slot][diet] => rows
     * @access public
     * @return array
     */
    public function getMenuGrouped($outlet_id, $date) {
        $out = array();
        foreach ($this->getMenu($outlet_id, $date) as $r) {
            $out[(int) $r->slot][$r->diet][] = $r;
        }
        return $out;
    }

    /**
     * replace the dishes of one date + slot + diet
     * @access public
     * @return void
     */
    public function saveMenuCell($outlet_id, $company_id, $user_id, $date, $slot, $diet, $dishes) {
        $this->db->trans_start();
        $this->db->where(array('outlet_id' => $outlet_id, 'menu_date' => $date, 'slot' => $slot, 'diet' => $diet))->delete('tbl_mess_menus');
        $i = 0;
        foreach ($dishes as $d) {
            $this->db->insert('tbl_mess_menus', array(
                'menu_date' => $date,
                'slot' => $slot,
                'diet' => $diet,
                'food_menu_id' => $d['food_menu_id'] ? (int) $d['food_menu_id'] : null,
                'dish_name' => $d['dish_name'],
                'portion_per_person' => $d['portion_per_person'],
                'buffer_portions' => $d['buffer_portions'],
                'sort_order' => $i++,
                'outlet_id' => $outlet_id,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'added_date_time' => date('Y-m-d H:i:s'),
            ));
        }
        $this->db->trans_complete();
    }

    /**
     * copy every dish of one date to another date (target cells are replaced)
     * @access public
     * @return int dishes copied
     */
    public function copyMenu($outlet_id, $company_id, $user_id, $from_date, $to_date, $slot = '') {
        $rows = $this->getMenu($outlet_id, $from_date);
        $this->db->trans_start();
        $where = array('outlet_id' => $outlet_id, 'menu_date' => $to_date);
        if ($slot) {
            $where['slot'] = $slot;
        }
        $this->db->where($where)->delete('tbl_mess_menus');
        $n = 0;
        foreach ($rows as $r) {
            if ($slot && (int) $r->slot != (int) $slot) {
                continue;
            }
            $this->db->insert('tbl_mess_menus', array(
                'menu_date' => $to_date,
                'slot' => $r->slot,
                'diet' => $r->diet,
                'food_menu_id' => $r->food_menu_id,
                'dish_name' => $r->dish_name,
                'portion_per_person' => $r->portion_per_person,
                'buffer_portions' => $r->buffer_portions,
                'sort_order' => $r->sort_order,
                'outlet_id' => $outlet_id,
                'company_id' => $company_id,
                'user_id' => $user_id,
                'added_date_time' => date('Y-m-d H:i:s'),
            ));
            $n++;
        }
        $this->db->trans_complete();
        return $n;
    }

    /**
     * food menus that can be picked as mess dishes or extras
     * @access public
     * @return array
     */
    public function getFoodMenusForPicker($company_id) {
        return $this->db->query("SELECT fm.id, fm.name, fm.code, fm.sale_price, fm.veg_item, fm.total_cost, c.category_name,
                (SELECT COUNT(*) FROM tbl_food_menus_ingredients fi WHERE fi.food_menu_id = fm.id AND fi.del_status = 'Live') AS recipe_lines
            FROM tbl_food_menus fm
            LEFT JOIN tbl_food_menu_categories c ON c.id = fm.category_id
            WHERE fm.company_id = ? AND fm.del_status = 'Live' AND (fm.parent_id = 0 OR fm.parent_id IS NULL)
            ORDER BY c.category_name, fm.name", array($company_id))->result();
    }

    /* =================================================================
     * Extras
     * ================================================================= */

    public function getExtras($outlet_id, $from_date, $to_date, $slot = '', $member_id = '') {
        $binds = array($outlet_id, $from_date, $to_date);
        $where = '';
        if ($slot) {
            $where .= ' AND e.slot = ?';
            $binds[] = $slot;
        }
        if ($member_id) {
            $where .= ' AND e.member_id = ?';
            $binds[] = $member_id;
        }
        return $this->db->query("SELECT e.*, m.name AS member_name, m.member_code, m.phone, u.full_name AS user_name,
                (SELECT py.id FROM tbl_mess_payments py WHERE py.extra_id = e.id AND py.del_status = 'Live' LIMIT 1) AS payment_row_id
            FROM tbl_mess_extras e
            JOIN tbl_mess_members m ON m.id = e.member_id
            LEFT JOIN tbl_users u ON u.id = e.user_id
            WHERE e.outlet_id = ? AND e.del_status = 'Live' AND e.extra_date BETWEEN ? AND ? $where
            ORDER BY e.extra_date DESC, e.slot, e.id DESC", $binds)->result();
    }

    public function getExtra($id, $outlet_id) {
        return $this->db->get_where('tbl_mess_extras', array('id' => $id, 'outlet_id' => $outlet_id, 'del_status' => 'Live'))->row();
    }

    /**
     * the latest live subscription of a member covering a date (for linking extras / payments)
     * @access public
     * @return object|null
     */
    public function getSubscriptionCovering($member_id, $date) {
        return $this->db->query("SELECT * FROM tbl_mess_subscriptions WHERE member_id = ? AND del_status = 'Live' AND status <> 'cancelled' AND start_date <= ? AND end_date >= ? ORDER BY id DESC LIMIT 1", array($member_id, $date, $date))->row();
    }

    /**
     * subscriptions of a member that still have something to pay, oldest first
     * @access public
     * @return array
     */
    public function getUnpaidSubscriptions($member_id) {
        $rows = $this->db->query("SELECT s.*,
                (SELECT COALESCE(SUM(CASE WHEN py.type = 'refund' THEN -py.amount ELSE py.amount END),0) FROM tbl_mess_payments py WHERE py.subscription_id = s.id AND py.del_status = 'Live') AS paid
            FROM tbl_mess_subscriptions s
            WHERE s.member_id = ? AND s.del_status = 'Live' AND s.status <> 'cancelled'
            ORDER BY s.start_date DESC, s.id DESC", array($member_id))->result();
        $this->attachMeals($rows);
        return $rows;
    }

    /* =================================================================
     * Marking the meal register
     * ================================================================= */

    /**
     * apply one action to an attendance row
     * $action: served / partial / not_taken / skip / undo
     * @access public
     * @return string '' on success, else error lang key
     */
    public function markAttendance($row, $action, $persons, $user_id) {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        if ($row->att_date > $today && !in_array($action, array('skip', 'undo'))) {
            return 'mess_future_only_skip';
        }
        $data = array('marked_by' => $user_id, 'marked_at' => $now);
        switch ($action) {
            case 'served':
                $data['status'] = 'served';
                $data['persons_served'] = (int) $row->persons_expected;
                break;
            case 'partial':
                $persons = max(0, min((int) $persons, (int) $row->persons_expected));
                $data['persons_served'] = $persons;
                $data['status'] = $persons == 0 ? 'not_taken' : ($persons >= (int) $row->persons_expected ? 'served' : 'partial');
                break;
            case 'not_taken':
                $data['status'] = 'not_taken';
                $data['persons_served'] = 0;
                break;
            case 'skip':
                if ($row->status != 'pending') {
                    return 'mess_skip_already_marked';
                }
                $data['status'] = 'skipped';
                $data['persons_served'] = 0;
                $data['informed_at'] = $now;
                break;
            case 'undo':
                $data = array('status' => 'pending', 'persons_served' => 0, 'marked_by' => null, 'marked_at' => null, 'informed_at' => null);
                break;
            default:
                return 'mess_invalid_action';
        }
        $this->db->where('id', $row->id)->update('tbl_mess_attendance', $data);
        return '';
    }

    /**
     * "kal nahi aaunga": mark a member's meal of a date and slot as skipped ahead of time
     * @access public
     * @return string '' on success, else error lang key
     */
    public function skipMeal($member_id, $outlet_id, $date, $slot, $user_id) {
        $found = null;
        foreach ($this->getExpectedSubscriptions($outlet_id, $date, $slot) as $x) {
            if ((int) $x->member_id == (int) $member_id) {
                $found = $x;
                break;
            }
        }
        if (!$found) {
            return 'mess_skip_not_expected';
        }
        $this->ensureAttendance($outlet_id, $date, $slot);
        $row = $this->db->get_where('tbl_mess_attendance', array('att_date' => $date, 'slot' => $slot, 'subscription_id' => $found->subscription_id))->row();
        if (!$row) {
            return 'mess_skip_not_expected';
        }
        return $this->markAttendance($row, 'skip', 0, $user_id);
    }

    /* =================================================================
     * Production plan and stock (daily sheet)
     * ================================================================= */

    /**
     * recipe lines of food menus: food_menu_id => list of (ingredient_id, consumption, name, unit, unit_cost)
     * @access public
     * @return array
     */
    public function getRecipes($food_menu_ids) {
        $ids = array();
        foreach ($food_menu_ids as $id) {
            if ((int) $id > 0) {
                $ids[] = (int) $id;
            }
        }
        if (!$ids) {
            return array();
        }
        $rows = $this->db->query("SELECT fi.food_menu_id, fi.ingredient_id, fi.consumption, i.name, i.consumption_unit_cost, i.alert_quantity, u.unit_name
            FROM tbl_food_menus_ingredients fi
            JOIN tbl_ingredients i ON i.id = fi.ingredient_id
            LEFT JOIN tbl_units u ON u.id = i.unit_id
            WHERE fi.del_status = 'Live' AND fi.food_menu_id IN (" . implode(',', array_unique($ids)) . ")")->result();
        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r->food_menu_id][] = $r;
        }
        return $out;
    }

    /**
     * current stock of ingredients in their consumption unit, same ledger as Inventory_model::getCurrentInventory
     * @access public
     * @return array ingredient_id => float
     */
    public function getIngredientStock($ingredient_ids, $outlet_id) {
        $ids = array();
        foreach ($ingredient_ids as $id) {
            if ((int) $id > 0) {
                $ids[] = (int) $id;
            }
        }
        if (!$ids) {
            return array();
        }
        $o = (int) $outlet_id;
        $rows = $this->db->query("SELECT i.id, i.conversion_rate,
            (SELECT SUM(quantity_amount) FROM tbl_purchase_ingredients WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live') AS purchase,
            (SELECT SUM(consumption) FROM tbl_sale_consumptions_of_menus WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live') AS consumption,
            (SELECT SUM(consumption) FROM tbl_sale_consumptions_of_modifiers_of_menus WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live') AS modifiers,
            (SELECT SUM(waste_amount) FROM tbl_waste_ingredients WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live') AS waste,
            (SELECT SUM(consumption_amount) FROM tbl_inventory_adjustment_ingredients WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live' AND consumption_status = 'Plus') AS adj_plus,
            (SELECT SUM(consumption_amount) FROM tbl_inventory_adjustment_ingredients WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live' AND consumption_status = 'Minus') AS adj_minus,
            (SELECT SUM(quantity_amount) FROM tbl_production_ingredients WHERE ingredient_id = i.id AND outlet_id = $o AND del_status = 'Live' AND status = 1) AS production,
            (SELECT SUM(quantity_amount) FROM tbl_transfer_ingredients WHERE ingredient_id = i.id AND to_outlet_id = $o AND del_status = 'Live' AND status = 1 AND transfer_type = 1) AS t_plus,
            (SELECT SUM(quantity_amount) FROM tbl_transfer_ingredients WHERE ingredient_id = i.id AND from_outlet_id = $o AND del_status = 'Live' AND status = 1 AND transfer_type = 1) AS t_minus,
            (SELECT SUM(quantity_amount) FROM tbl_transfer_received_ingredients WHERE ingredient_id = i.id AND to_outlet_id = $o AND del_status = 'Live' AND status = 1) AS t_plus2,
            (SELECT SUM(quantity_amount) FROM tbl_transfer_received_ingredients WHERE ingredient_id = i.id AND from_outlet_id = $o AND del_status = 'Live' AND status = 1) AS t_minus2
            FROM tbl_ingredients i WHERE i.id IN (" . implode(',', array_unique($ids)) . ")")->result();
        $out = array();
        foreach ($rows as $r) {
            $cr = (float) $r->conversion_rate > 0 ? (float) $r->conversion_rate : 1;
            $out[(int) $r->id] = ((float) $r->purchase * $cr) - (float) $r->consumption - (float) $r->modifiers - (float) $r->waste
                + (float) $r->adj_plus - (float) $r->adj_minus
                + ((float) $r->t_plus - (float) $r->t_minus + (float) $r->t_plus2 - (float) $r->t_minus2) * $cr
                + (float) $r->production * $cr;
        }
        return $out;
    }

    /**
     * what to cook for a date and slot: dishes with portions, ingredient need vs stock
     * @access public
     * @return object dishes[], ingredients[], counts, menu_missing[]
     */
    public function buildProduction($outlet_id, $date, $slot) {
        $this->ensureAttendance($outlet_id, $date, $slot);
        $rows = $this->getAttendance($outlet_id, $date, $slot);
        $counts = $this->attendanceCounts($rows);
        $expected = array('veg' => $counts->veg, 'nonveg' => $counts->nonveg);
        $menu = $this->getMenuGrouped($outlet_id, $date);
        $cell = isset($menu[(int) $slot]) ? $menu[(int) $slot] : array();

        $dishes = array();
        $menu_missing = array();
        foreach (array('nonveg', 'veg') as $diet) {
            if (empty($cell[$diet])) {
                if ($expected[$diet] > 0) {
                    $menu_missing[] = $diet;
                }
                continue;
            }
            foreach ($cell[$diet] as $d) {
                $key = $d->food_menu_id ? 'f' . $d->food_menu_id : 'n' . mb_strtolower(trim($d->dish_name));
                if (!isset($dishes[$key])) {
                    $dishes[$key] = (object) array(
                        'key' => $key,
                        'food_menu_id' => $d->food_menu_id ? (int) $d->food_menu_id : 0,
                        'dish_name' => $d->dish_name,
                        'diets' => array(),
                        'from_menu' => 0,
                        'extras' => 0,
                        'buffer' => 0,
                        'portions' => 0,
                        'recipe_lines' => (int) $d->recipe_lines,
                        'unit_cost' => (float) $d->total_cost,
                        'portion_by_diet' => array(),
                    );
                }
                $dishes[$key]->diets[] = $diet;
                $dishes[$key]->portion_by_diet[$diet] = (float) $d->portion_per_person;
                $dishes[$key]->from_menu += $expected[$diet] * (float) $d->portion_per_person;
                $dishes[$key]->buffer += (float) $d->buffer_portions;
            }
        }
        // extras of the slot: a dish on the menu gets its qty added, anything else is its own line
        $extras = $this->getExtras($outlet_id, $date, $date, $slot);
        foreach ($extras as $e) {
            if ($e->status == 'cancelled') {
                continue;
            }
            $key = $e->food_menu_id ? 'f' . $e->food_menu_id : 'x' . mb_strtolower(trim($e->item_name));
            if (!isset($dishes[$key])) {
                $dishes[$key] = (object) array(
                    'key' => $key,
                    'food_menu_id' => $e->food_menu_id ? (int) $e->food_menu_id : 0,
                    'dish_name' => $e->item_name,
                    'diets' => array(),
                    'from_menu' => 0,
                    'extras' => 0,
                    'buffer' => 0,
                    'portions' => 0,
                    'recipe_lines' => 0,
                    'unit_cost' => 0,
                    'extra_only' => true,
                    'portion_by_diet' => array(),
                );
            }
            $dishes[$key]->extras += (float) $e->qty;
        }
        $recipes = $this->getRecipes(array_map(function ($d) {
            return $d->food_menu_id;
        }, array_values($dishes)));
        // persons actually served per diet, for the day close "served portions" estimate
        $served_by_diet = array('veg' => 0, 'nonveg' => 0);
        foreach ($rows as $r) {
            if ($r->status == 'served' || $r->status == 'partial') {
                $served_by_diet[$r->diet == 'veg' ? 'veg' : 'nonveg'] += (int) $r->persons_served;
            }
        }
        $ingredients = array();
        foreach ($dishes as $d) {
            $d->portions = $d->from_menu + $d->extras + $d->buffer;
            $d->served = $d->extras;
            foreach ($d->portion_by_diet as $diet => $portion) {
                $d->served += $served_by_diet[$diet] * $portion;
            }
            $d->needs = array();
            if ($d->food_menu_id && isset($recipes[$d->food_menu_id])) {
                $d->recipe_lines = count($recipes[$d->food_menu_id]);
                $cost = 0;
                foreach ($recipes[$d->food_menu_id] as $r) {
                    $need = (float) $r->consumption * $d->portions;
                    $cost += (float) $r->consumption * (float) $r->consumption_unit_cost;
                    $d->needs[] = (object) array('ingredient_id' => (int) $r->ingredient_id, 'name' => $r->name, 'unit' => $r->unit_name, 'need' => $need);
                    $iid = (int) $r->ingredient_id;
                    if (!isset($ingredients[$iid])) {
                        $ingredients[$iid] = (object) array('id' => $iid, 'name' => $r->name, 'unit' => $r->unit_name, 'need' => 0, 'stock' => 0, 'alert' => (float) $r->alert_quantity, 'unit_cost' => (float) $r->consumption_unit_cost);
                    }
                    $ingredients[$iid]->need += $need;
                }
                if ($cost > 0) {
                    $d->unit_cost = $cost;
                }
            } else {
                $d->recipe_lines = 0;
            }
        }
        $stock = $this->getIngredientStock(array_keys($ingredients), $outlet_id);
        $short = 0;
        foreach ($ingredients as $iid => $ing) {
            $ing->stock = isset($stock[$iid]) ? $stock[$iid] : 0;
            // a negative book stock is a bookkeeping gap, not chicken the kitchen owes: buy for today's need only
            $ing->short = max(0, $ing->need - max(0, $ing->stock));
            if ($ing->short > 0) {
                $short++;
            }
        }
        return (object) array(
            'rows' => $rows,
            'counts' => $counts,
            'dishes' => array_values($dishes),
            'ingredients' => $ingredients,
            'short_count' => $short,
            'menu_missing' => $menu_missing,
            'extras' => $extras,
            'menu' => $cell,
        );
    }

    /* =================================================================
     * Day close: cooked portions move the stock
     * ================================================================= */

    public function getDayClose($outlet_id, $date, $slot) {
        return $this->db->query("SELECT c.*, u.full_name AS closed_by_name, r.full_name AS reopened_by_name
            FROM tbl_mess_day_close c
            LEFT JOIN tbl_users u ON u.id = c.closed_by
            LEFT JOIN tbl_users r ON r.id = c.reopened_by
            WHERE c.outlet_id = ? AND c.close_date = ? AND c.slot = ?", array($outlet_id, $date, $slot))->row();
    }

    public function getDayCloseItems($close_id) {
        return $this->db->query("SELECT * FROM tbl_mess_day_close_items WHERE day_close_id = ? ORDER BY id", array($close_id))->result();
    }

    /**
     * closes of a date range (for the report / dashboard)
     * @access public
     * @return array
     */
    public function getDayCloses($outlet_id, $from, $to) {
        return $this->db->query("SELECT c.*, u.full_name AS closed_by_name,
                (SELECT SUM(cooked) FROM tbl_mess_day_close_items i WHERE i.day_close_id = c.id) AS cooked,
                (SELECT SUM(leftover) FROM tbl_mess_day_close_items i WHERE i.day_close_id = c.id) AS leftover
            FROM tbl_mess_day_close c LEFT JOIN tbl_users u ON u.id = c.closed_by
            WHERE c.outlet_id = ? AND c.close_date BETWEEN ? AND ? AND c.status = 'closed'
            ORDER BY c.close_date DESC, c.slot", array($outlet_id, $from, $to))->result();
    }

    /**
     * close a slot: store what was cooked and left, post consumption (cooked - wasted) to the
     * stock ledger and the wasted leftover as a waste entry. Consumption + waste = cooked,
     * so the ingredients leave the stock exactly once.
     * $input[dishFormKey] = ['cooked' => n, 'leftover' => n, 'action' => waste|carry|staff]
     * (dish key from buildProduction, so a menu edited in between cannot shift the rows)
     * @access public
     * @return int|string close id, or an error lang key
     */
    public function closeDay($outlet_id, $company_id, $user_id, $date, $slot, $input, $note) {
        $existing = $this->getDayClose($outlet_id, $date, $slot);
        if ($existing && $existing->status == 'closed') {
            return 'mess_already_closed';
        }
        $plan = $this->buildProduction($outlet_id, $date, $slot);
        $recipes = $this->getRecipes(array_map(function ($d) {
            return $d->food_menu_id;
        }, $plan->dishes));
        $now = date('Y-m-d H:i:s');
        $c = $plan->counts;
        $extras_qty = 0;
        foreach ($plan->extras as $e) {
            if ($e->status != 'cancelled') {
                $extras_qty += (float) $e->qty;
            }
        }

        $this->db->trans_start();
        $head = array(
            'close_date' => $date,
            'slot' => $slot,
            'expected' => (int) $c->expected,
            'served' => (int) $c->served,
            'not_taken' => (int) $c->not_taken,
            'skipped' => (int) $c->skipped,
            'pending' => (int) $c->pending,
            'extras_qty' => $extras_qty,
            'status' => 'closed',
            'note' => $note,
            'closed_by' => $user_id,
            'closed_at' => $now,
            'waste_id' => null,
            'outlet_id' => $outlet_id,
            'company_id' => $company_id,
        );
        if ($existing) {
            $close_id = $existing->id;
            $this->db->where('id', $close_id)->update('tbl_mess_day_close', $head);
            $this->db->where('day_close_id', $close_id)->delete('tbl_mess_day_close_items');
        } else {
            $this->db->insert('tbl_mess_day_close', $head);
            $close_id = $this->db->insert_id();
        }

        $food_cost = 0;
        $waste_cost = 0;
        $waste_lines = array();
        foreach ($plan->dishes as $d) {
            $k = $this->dishFormKey($d);
            $in = isset($input[$k]) && is_array($input[$k]) ? $input[$k] : array();
            $cooked = isset($in['cooked']) && $in['cooked'] !== '' ? max(0, (float) $in['cooked']) : (float) $d->portions;
            $leftover = isset($in['leftover']) ? max(0, min($cooked, (float) $in['leftover'])) : 0;
            $action = isset($in['action']) && in_array($in['action'], array('waste', 'carry', 'staff')) ? $in['action'] : 'waste';
            $wasted = $action == 'waste' ? $leftover : 0;
            $lines = ($d->food_menu_id && isset($recipes[$d->food_menu_id])) ? $recipes[$d->food_menu_id] : array();
            $unit_cost = 0;
            foreach ($lines as $r) {
                $unit_cost += (float) $r->consumption * (float) $r->consumption_unit_cost;
                $used = ($cooked - $wasted) * (float) $r->consumption;
                if ($used > 0) {
                    $this->db->insert('tbl_sale_consumptions_of_menus', array(
                        'ingredient_id' => (int) $r->ingredient_id,
                        'consumption' => $used,
                        'sale_consumption_id' => null,
                        'sales_id' => 0,
                        'order_status' => 3,
                        'food_menu_id' => (int) $d->food_menu_id,
                        'user_id' => $user_id,
                        'outlet_id' => $outlet_id,
                        'del_status' => 'Live',
                        'cost' => round($used * (float) $r->consumption_unit_cost, 4),
                        'status' => 1,
                        'mess_close_id' => $close_id,
                    ));
                }
                $waste_amount = $wasted * (float) $r->consumption;
                if ($waste_amount > 0) {
                    $loss = $waste_amount * (float) $r->consumption_unit_cost;
                    $waste_cost += $loss;
                    $waste_lines[] = array('ingredient_id' => (int) $r->ingredient_id, 'waste_amount' => $waste_amount, 'last_purchase_price' => (float) $r->consumption_unit_cost, 'loss_amount' => $loss);
                }
            }
            $food_cost += $cooked * $unit_cost;
            $this->db->insert('tbl_mess_day_close_items', array(
                'day_close_id' => $close_id,
                'food_menu_id' => $d->food_menu_id ? (int) $d->food_menu_id : null,
                'dish_name' => $d->dish_name,
                'planned' => round($d->portions, 2),
                'served' => round($d->served, 2),
                'cooked' => $cooked,
                'leftover' => $leftover,
                'leftover_action' => $action,
                'unit_cost' => round($unit_cost, 4),
                'food_cost' => round($cooked * $unit_cost, 3),
                'has_recipe' => $lines ? 1 : 0,
            ));
        }

        $waste_id = null;
        if ($waste_lines) {
            $slots = $this->getActiveSlots($company_id);
            $this->db->insert('tbl_wastes', array(
                'reference_no' => 'MW-' . str_pad($close_id, 5, '0', STR_PAD_LEFT) . '-' . date('His'),
                'date' => $date,
                'total_loss' => round($waste_cost, 2),
                'note' => lang('mess') . ' ' . lang('mess_day_close') . ': ' . $date . ' ' . (isset($slots[$slot]) ? $slots[$slot]->name : $slot),
                'employee_id' => $user_id,
                'user_id' => $user_id,
                'outlet_id' => $outlet_id,
                'del_status' => 'Live',
            ));
            $waste_id = $this->db->insert_id();
            foreach ($waste_lines as $w) {
                $w['waste_id'] = $waste_id;
                $w['outlet_id'] = $outlet_id;
                $w['del_status'] = 'Live';
                $this->db->insert('tbl_waste_ingredients', $w);
            }
        }
        $this->db->where('id', $close_id)->update('tbl_mess_day_close', array(
            'food_cost' => round($food_cost, 3),
            'waste_cost' => round($waste_cost, 3),
            'waste_id' => $waste_id,
        ));
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return 'mess_close_failed';
        }
        return (int) $close_id;
    }

    /**
     * undo a close: the posted consumption and waste are soft-deleted, stock comes back
     * @access public
     * @return void
     */
    /**
     * short, form-safe key of a production dish (names may hold spaces or Arabic)
     * @access public
     * @return string
     */
    public function dishFormKey($dish) {
        return substr(md5($dish->key), 0, 12);
    }

    public function reopenDay($close, $user_id) {
        $this->db->trans_start();
        $this->db->where('mess_close_id', $close->id)->update('tbl_sale_consumptions_of_menus', array('del_status' => 'Deleted'));
        if ($close->waste_id) {
            $this->db->where('id', $close->waste_id)->update('tbl_wastes', array('del_status' => 'Deleted'));
            $this->db->where('waste_id', $close->waste_id)->update('tbl_waste_ingredients', array('del_status' => 'Deleted'));
        }
        $this->db->where('id', $close->id)->update('tbl_mess_day_close', array(
            'status' => 'reopened',
            'reopened_by' => $user_id,
            'reopened_at' => date('Y-m-d H:i:s'),
            'waste_id' => null,
        ));
        $this->db->trans_complete();
    }

    /* =================================================================
     * Helpers
     * ================================================================= */

    public function shortDate($date) {
        if (!$date || $date == '0000-00-00') {
            return '';
        }
        return date('d M', strtotime($date));
    }

    /**
     * wa.me link with a prefilled message; local 05x numbers get the country code
     * @access public
     * @return string ('' when the phone is unusable)
     */
    public function whatsappLink($phone, $text, $country_code) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) < 7) {
            return '';
        }
        if (substr($digits, 0, 2) == '00') {
            $digits = substr($digits, 2);
        } elseif (substr($digits, 0, 1) == '0') {
            $digits = $country_code . substr($digits, 1);
        } elseif (strlen($digits) <= 9) {
            $digits = $country_code . $digits;
        }
        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }

    public function fillTemplate($template, $vars) {
        foreach ($vars as $k => $v) {
            $template = str_replace('{' . $k . '}', $v, $template);
        }
        return $template;
    }
}
