<?php
/*
  ###########################################################
  # This is Payroll_model
  ###########################################################
  # Monthly payroll from attendance.
  # dayStatus() turns tbl_attendance + weekly offs + holidays +
  # leaves into one status code per day; computeItem() turns
  # those days + the salary setup + adjustments into money.
  # Docs: docs/payroll_module.md
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends CI_Model {

    /** day status codes, also used by the attendance sheet and the payslip */
    const D_PRESENT = 'P';
    const D_HALF = 'H';
    const D_NO_CHECKOUT = 'M';
    const D_ABSENT = 'A';
    const D_WEEKLY_OFF = 'O';
    const D_HOLIDAY = 'HO';
    const D_PAID_LEAVE = 'L';
    const D_UNPAID_LEAVE = 'U';
    const D_OFFDAY_WORK = 'W';
    const D_NOT_EMPLOYED = 'X';
    const D_UPCOMING = 'F';

    public static $salary_types = array('monthly', 'daily', 'hourly');
    public static $adjustment_types = array('advance', 'bonus', 'allowance', 'deduction');

    /* =================================================================
     * small helpers
     * ================================================================= */

    /** is this payroll screen switched on in config/payroll.php? */
    public static function screenOn($key) {
        $screens = get_instance()->config->item('payroll_screens');
        return in_array($key, is_array($screens) ? $screens : array(), true);
    }

    /** 'YYYY-MM' or false */
    public function cleanMonth($month) {
        $month = trim((string) $month);
        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            return $month;
        }
        return false;
    }

    public function monthStart($month) {
        return $month . '-01';
    }

    public function monthEnd($month) {
        return date('Y-m-t', strtotime($month . '-01'));
    }

    public function monthLabel($month) {
        return date('F Y', strtotime($month . '-01'));
    }

    /** whole months from $from to $to (both 'YYYY-MM'), negative when $to is earlier */
    public function monthDiff($from, $to) {
        list($fy, $fm) = explode('-', $from);
        list($ty, $tm) = explode('-', $to);
        return ((int) $ty - (int) $fy) * 12 + ((int) $tm - (int) $fm);
    }

    public function addMonths($month, $n) {
        return date('Y-m', strtotime($month . '-01 +' . (int) $n . ' month'));
    }

    public function precision() {
        $company = getCompanyInfo();
        return isset($company->precision) && $company->precision !== '' ? (int) $company->precision : 2;
    }

    /** "1,3,7" -> array(1,3,7) of ISO weekdays */
    public function parseWeekdays($csv) {
        $out = array();
        foreach (explode(',', (string) $csv) as $d) {
            $d = (int) trim($d);
            if ($d >= 1 && $d <= 7) {
                $out[] = $d;
            }
        }
        return array_values(array_unique($out));
    }

    public function weekdayNames($csv) {
        $names = array();
        foreach ($this->parseWeekdays($csv) as $d) {
            $names[] = date('D', strtotime('Monday this week +' . ($d - 1) . ' day'));
        }
        return implode(', ', $names);
    }

    /** seconds since midnight of a TIME / DATETIME string, null when empty or 00:00:00 */
    private function clockSeconds($value) {
        $value = trim((string) $value);
        if ($value === '' || $value === '00:00:00' || $value === '0000-00-00 00:00:00') {
            return null;
        }
        if (strlen($value) > 8) {
            $value = substr($value, -8);
        }
        $p = explode(':', $value);
        if (count($p) < 2) {
            return null;
        }
        return (int) $p[0] * 3600 + (int) $p[1] * 60 + (isset($p[2]) ? (int) $p[2] : 0);
    }

    /* =================================================================
     * settings
     * ================================================================= */

    public function getSettings($company_id) {
        $row = $this->db->query("SELECT * FROM tbl_payroll_settings WHERE company_id = ?", array($company_id))->row();
        if (!$row) {
            $this->db->insert('tbl_payroll_settings', array('company_id' => $company_id));
            $row = $this->db->query("SELECT * FROM tbl_payroll_settings WHERE company_id = ?", array($company_id))->row();
        }
        return $row;
    }

    public function saveSettings($company_id, $data) {
        $this->getSettings($company_id);
        $this->db->where('company_id', $company_id)->update('tbl_payroll_settings', $data);
    }

    /** tbl_expense_items row for salaries; created on first use */
    public function expenseCategoryId($company_id, $user_id) {
        $settings = $this->getSettings($company_id);
        if ($settings->expense_category_id) {
            $row = $this->db->query("SELECT id FROM tbl_expense_items WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($settings->expense_category_id, $company_id))->row();
            if ($row) {
                return (int) $row->id;
            }
        }
        $row = $this->db->query("SELECT id FROM tbl_expense_items WHERE company_id = ? AND del_status = 'Live' AND name = 'Salary & Wages' LIMIT 1", array($company_id))->row();
        if ($row) {
            $id = (int) $row->id;
        } else {
            $this->db->insert('tbl_expense_items', array(
                'name' => 'Salary & Wages',
                'description' => 'Payroll',
                'user_id' => $user_id,
                'company_id' => $company_id,
                'del_status' => 'Live',
            ));
            $id = (int) $this->db->insert_id();
        }
        $this->db->where('company_id', $company_id)->update('tbl_payroll_settings', array('expense_category_id' => $id));
        return $id;
    }

    /* =================================================================
     * employees + salary setup
     * ================================================================= */

    /** live employees of the company, with their salary setup (s_* columns, NULL when not set) */
    public function getEmployeesWithSalary($company_id) {
        return $this->db->query("SELECT u.id, u.full_name, u.designation, u.role, u.phone, u.will_login, u.outlet_id,
                s.id AS salary_id, s.salary_type, s.basic_salary, s.housing_allowance, s.transport_allowance,
                s.food_allowance, s.other_allowance, s.fixed_deduction, s.fixed_deduction_note, s.overtime_rate,
                s.weekly_off, s.joining_date, s.leaving_date, s.payment_mode, s.bank_name, s.bank_account,
                s.status AS salary_status, s.note AS salary_note
            FROM tbl_users u
            LEFT JOIN tbl_payroll_salaries s ON s.employee_id = u.id AND s.company_id = u.company_id AND s.del_status = 'Live'
            WHERE u.company_id = ? AND u.del_status = 'Live'
            ORDER BY u.full_name", array($company_id))->result();
    }

    public function getEmployee($company_id, $employee_id) {
        return $this->db->query("SELECT * FROM tbl_users WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($employee_id, $company_id))->row();
    }

    public function getSalary($company_id, $employee_id) {
        return $this->db->query("SELECT * FROM tbl_payroll_salaries WHERE company_id = ? AND employee_id = ? AND del_status = 'Live'", array($company_id, $employee_id))->row();
    }

    public function saveSalary($company_id, $employee_id, $data) {
        $existing = $this->db->query("SELECT id FROM tbl_payroll_salaries WHERE company_id = ? AND employee_id = ?", array($company_id, $employee_id))->row();
        $data['del_status'] = 'Live';
        if ($existing) {
            $this->db->where('id', $existing->id)->update('tbl_payroll_salaries', $data);
        } else {
            $data['company_id'] = $company_id;
            $data['employee_id'] = $employee_id;
            $this->db->insert('tbl_payroll_salaries', $data);
        }
    }

    public function monthlyGross($salary) {
        if (!$salary) {
            return 0;
        }
        $allowances = (float) $salary->housing_allowance + (float) $salary->transport_allowance + (float) $salary->food_allowance + (float) $salary->other_allowance;
        return $salary->salary_type == 'monthly' ? (float) $salary->basic_salary + $allowances : $allowances;
    }

    /** salary setups that belong in the payroll of $month */
    public function getEligibleSalaries($company_id, $month) {
        return $this->db->query("SELECT s.*, u.full_name, u.designation
            FROM tbl_payroll_salaries s
            JOIN tbl_users u ON u.id = s.employee_id AND u.del_status = 'Live'
            WHERE s.company_id = ? AND s.del_status = 'Live' AND s.status = 'active'
              AND (s.joining_date IS NULL OR s.joining_date <= ?)
              AND (s.leaving_date IS NULL OR s.leaving_date >= ?)
            ORDER BY u.full_name", array($company_id, $this->monthEnd($month), $this->monthStart($month)))->result();
    }

    /* =================================================================
     * holidays, leaves
     * ================================================================= */

    public function getHolidays($company_id, $from, $to) {
        return $this->db->query("SELECT * FROM tbl_payroll_holidays WHERE company_id = ? AND del_status = 'Live' AND holiday_date BETWEEN ? AND ? ORDER BY holiday_date", array($company_id, $from, $to))->result();
    }

    /** date => name */
    public function holidayMap($company_id, $from, $to) {
        $map = array();
        foreach ($this->getHolidays($company_id, $from, $to) as $h) {
            $map[$h->holiday_date] = $h->name;
        }
        return $map;
    }

    public function getLeaves($company_id, $from, $to, $employee_id = '') {
        $sql = "SELECT l.*, u.full_name FROM tbl_payroll_leaves l
                LEFT JOIN tbl_users u ON u.id = l.employee_id
                WHERE l.company_id = ? AND l.del_status = 'Live' AND l.from_date <= ? AND l.to_date >= ?";
        $params = array($company_id, $to, $from);
        if ($employee_id) {
            $sql .= " AND l.employee_id = ?";
            $params[] = $employee_id;
        }
        return $this->db->query($sql . " ORDER BY l.from_date DESC, u.full_name", $params)->result();
    }

    /** employee_id => date => 'paid' / 'unpaid' (paid wins when two leaves overlap) */
    public function leaveMap($company_id, $from, $to) {
        $map = array();
        foreach ($this->getLeaves($company_id, $from, $to) as $l) {
            $d = max($l->from_date, $from);
            $end = min($l->to_date, $to);
            while ($d <= $end) {
                if (!isset($map[$l->employee_id][$d]) || $l->leave_type == 'paid') {
                    $map[$l->employee_id][$d] = $l->leave_type;
                }
                $d = date('Y-m-d', strtotime($d . ' +1 day'));
            }
        }
        return $map;
    }

    /** does a live leave of this employee overlap the range? */
    public function leaveOverlaps($company_id, $employee_id, $from, $to) {
        return (bool) $this->db->query("SELECT id FROM tbl_payroll_leaves WHERE company_id = ? AND employee_id = ? AND del_status = 'Live' AND from_date <= ? AND to_date >= ? LIMIT 1", array($company_id, $employee_id, $to, $from))->row();
    }

    /* =================================================================
     * attendance
     * ================================================================= */

    /** employee_id => date => rows (in_time, out_time) */
    public function attendanceMap($company_id, $from, $to, $employee_ids = null) {
        $sql = "SELECT employee_id, `date`, in_time, out_time FROM tbl_attendance
                WHERE company_id = ? AND del_status = 'Live' AND `date` BETWEEN ? AND ?";
        $params = array($company_id, $from, $to);
        if (is_array($employee_ids)) {
            if (!$employee_ids) {
                return array();
            }
            $sql .= " AND employee_id IN (" . implode(',', array_map('intval', $employee_ids)) . ")";
        }
        $map = array();
        foreach ($this->db->query($sql . " ORDER BY `date`, id", $params)->result() as $r) {
            $map[$r->employee_id][$r->date][] = $r;
        }
        return $map;
    }

    /**
     * One status code per day of the month for one employee, plus the counters
     * the salary needs. Money-free, so the attendance sheet uses it too.
     *
     * @param object $settings tbl_payroll_settings row
     * @param object|null $salary tbl_payroll_salaries row (weekly off, joining / leaving date)
     * @param string $month YYYY-MM
     * @param array $attendance date => rows
     * @param array $holidays date => name
     * @param array $leaves date => paid / unpaid
     */
    public function dayStatus($settings, $salary, $month, $attendance, $holidays, $leaves) {
        $dim = (int) date('t', strtotime($month . '-01'));
        $today = date('Y-m-d');
        $offs = $this->parseWeekdays($salary ? $salary->weekly_off : $settings->default_weekly_off);
        $join = $salary && $salary->joining_date ? $salary->joining_date : null;
        $leave = $salary && $salary->leaving_date ? $salary->leaving_date : null;
        $std = (float) $settings->standard_hours > 0 ? (float) $settings->standard_hours : 8;
        $half_limit = (float) $settings->half_day_hours;
        $ot_min_hours = max(0, (int) $settings->overtime_min_minutes) / 60;

        $r = array(
            'days' => array(),
            'days_in_month' => $dim,
            'working_days' => 0, 'weekly_off_days' => 0, 'holiday_days' => 0,
            'present_days' => 0, 'half_days' => 0, 'offday_worked' => 0,
            'paid_leave_days' => 0, 'unpaid_leave_days' => 0, 'absent_days' => 0,
            'not_employed_days' => 0, 'not_employed_working' => 0, 'upcoming_days' => 0,
            'missing_checkout_days' => 0, 'worked_hours' => 0, 'overtime_hours' => 0,
        );

        for ($d = 1; $d <= $dim; $d++) {
            $date = sprintf('%s-%02d', $month, $d);
            $dow = (int) date('N', strtotime($date));
            $is_holiday = isset($holidays[$date]);
            $is_off = !$is_holiday && in_array($dow, $offs);
            if ($is_holiday) {
                $r['holiday_days']++;
            } elseif ($is_off) {
                $r['weekly_off_days']++;
            } else {
                $r['working_days']++;
            }

            if (($join && $date < $join) || ($leave && $date > $leave)) {
                $r['days'][$d] = self::D_NOT_EMPLOYED;
                $r['not_employed_days']++;
                if (!$is_holiday && !$is_off) {
                    $r['not_employed_working']++;
                }
                continue;
            }

            if (!empty($attendance[$date])) {
                $seconds = 0;
                $closed = false;
                foreach ($attendance[$date] as $row) {
                    $in = $this->clockSeconds($row->in_time);
                    $out = $this->clockSeconds($row->out_time);
                    if ($in === null || $out === null) {
                        continue;
                    }
                    $diff = $out - $in;
                    if ($diff < 0) {
                        $diff += 86400; // shift past midnight
                    }
                    $seconds += $diff;
                    $closed = true;
                }
                $ot = 0;
                if (!$closed) {
                    // checked in, never checked out: count by the setting, no overtime
                    $r['missing_checkout_days']++;
                    $credit = $settings->missing_checkout == 'half' ? 0.5 : 1;
                    $hours = $credit * $std;
                    $code = self::D_NO_CHECKOUT;
                } else {
                    $hours = round($seconds / 3600, 2);
                    $credit = $hours < $half_limit ? 0.5 : 1;
                    if ($settings->overtime_enabled && $hours > $std && ($hours - $std) >= $ot_min_hours) {
                        $ot = round($hours - $std, 2);
                    }
                    $code = $credit < 1 ? self::D_HALF : self::D_PRESENT;
                }
                $r['worked_hours'] += $hours;
                $r['overtime_hours'] += $ot;
                if ($is_holiday || $is_off) {
                    $r['offday_worked'] += $credit;
                    $r['days'][$d] = self::D_OFFDAY_WORK;
                } else {
                    $r['present_days'] += $credit;
                    if ($credit < 1) {
                        $r['half_days']++;
                        $r['absent_days'] += 0.5;
                    }
                    $r['days'][$d] = $code;
                }
                continue;
            }

            if ($is_holiday) {
                $r['days'][$d] = self::D_HOLIDAY;
            } elseif ($is_off) {
                $r['days'][$d] = self::D_WEEKLY_OFF;
            } elseif (isset($leaves[$date])) {
                if ($leaves[$date] == 'paid') {
                    $r['days'][$d] = self::D_PAID_LEAVE;
                    $r['paid_leave_days']++;
                } else {
                    $r['days'][$d] = self::D_UNPAID_LEAVE;
                    $r['unpaid_leave_days']++;
                }
            } elseif ($date > $today) {
                $r['days'][$d] = self::D_UPCOMING;
                $r['upcoming_days']++;
            } else {
                $r['days'][$d] = self::D_ABSENT;
                $r['absent_days']++;
            }
        }
        $r['worked_hours'] = round($r['worked_hours'], 2);
        $r['overtime_hours'] = round($r['overtime_hours'], 2);
        return $r;
    }

    /* =================================================================
     * adjustments (advance / bonus / allowance / deduction)
     * ================================================================= */

    public function getAdjustments($company_id, $month = '', $employee_id = '', $type = '') {
        $sql = "SELECT a.*, u.full_name, pm.name AS payment_name
                FROM tbl_payroll_adjustments a
                LEFT JOIN tbl_users u ON u.id = a.employee_id
                LEFT JOIN tbl_payment_methods pm ON pm.id = a.payment_id
                WHERE a.company_id = ? AND a.del_status = 'Live'";
        $params = array($company_id);
        if ($month) {
            // an advance shows in every month it is being recovered
            $sql .= " AND (a.pay_month = ? OR (a.adj_type = 'advance' AND a.pay_month <= ? AND PERIOD_DIFF(REPLACE(?, '-', ''), REPLACE(a.pay_month, '-', '')) < a.installments))";
            $params[] = $month;
            $params[] = $month;
            $params[] = $month;
        }
        if ($employee_id) {
            $sql .= " AND a.employee_id = ?";
            $params[] = $employee_id;
        }
        if ($type) {
            $sql .= " AND a.adj_type = ?";
            $params[] = $type;
        }
        return $this->db->query($sql . " ORDER BY a.adj_date DESC, a.id DESC", $params)->result();
    }

    public function getAdjustment($company_id, $id) {
        return $this->db->query("SELECT * FROM tbl_payroll_adjustments WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($id, $company_id))->row();
    }

    /** share of one advance recovered in $month (0 when the month is outside its schedule) */
    public function advanceShare($adv, $month, $precision) {
        $n = max(1, (int) $adv->installments);
        $k = $this->monthDiff($adv->pay_month, $month);
        if ($k < 0 || $k >= $n) {
            return 0;
        }
        $share = round((float) $adv->amount / $n, $precision);
        if ($k == $n - 1) {
            $share = round((float) $adv->amount - $share * ($n - 1), $precision);
        }
        return $share;
    }

    /** employee_id => array(bonus, allowance, deduction, advance) for $month */
    public function adjustmentTotals($company_id, $month, $precision) {
        $totals = array();
        foreach ($this->getAdjustments($company_id, $month) as $a) {
            if (!isset($totals[$a->employee_id])) {
                $totals[$a->employee_id] = array('bonus' => 0, 'allowance' => 0, 'deduction' => 0, 'advance' => 0);
            }
            if ($a->adj_type == 'advance') {
                $totals[$a->employee_id]['advance'] += $this->advanceShare($a, $month, $precision);
            } else {
                $totals[$a->employee_id][$a->adj_type] += (float) $a->amount;
            }
        }
        return $totals;
    }

    /** months whose payroll an adjustment touches */
    public function adjustmentMonths($adj) {
        $months = array();
        $n = $adj->adj_type == 'advance' ? max(1, (int) $adj->installments) : 1;
        for ($i = 0; $i < $n; $i++) {
            $months[] = $this->addMonths($adj->pay_month, $i);
        }
        return $months;
    }

    /** an approved run already contains this adjustment -> it may not change any more */
    public function adjustmentLocked($company_id, $adj) {
        foreach ($this->adjustmentMonths($adj) as $m) {
            $run = $this->getRunByMonth($company_id, $m);
            if ($run && $run->status == 'approved') {
                return $m;
            }
        }
        return false;
    }

    /** advances given, recovered in approved payrolls, still open; one row per employee */
    public function advanceBalances($company_id, $employee_id = '') {
        $params = array($company_id);
        $where = '';
        if ($employee_id) {
            $where = ' AND a.employee_id = ?';
            $params[] = $employee_id;
        }
        $rows = $this->db->query("SELECT a.employee_id, u.full_name, u.designation, SUM(a.amount) AS given, COUNT(*) AS advances, MAX(a.adj_date) AS last_date
            FROM tbl_payroll_adjustments a LEFT JOIN tbl_users u ON u.id = a.employee_id
            WHERE a.company_id = ? AND a.del_status = 'Live' AND a.adj_type = 'advance' $where
            GROUP BY a.employee_id, u.full_name, u.designation ORDER BY u.full_name", $params)->result();
        foreach ($rows as $r) {
            $r->recovered = (float) $this->db->query("SELECT IFNULL(SUM(i.advance_deduction), 0) AS v
                FROM tbl_payroll_run_items i JOIN tbl_payroll_runs r ON r.id = i.run_id
                WHERE r.company_id = ? AND r.del_status = 'Live' AND r.status = 'approved' AND i.employee_id = ?", array($company_id, $r->employee_id))->row()->v;
            $r->balance = (float) $r->given - $r->recovered;
        }
        return $rows;
    }

    /* =================================================================
     * payroll runs
     * ================================================================= */

    public function getRuns($company_id) {
        return $this->db->query("SELECT r.*, u.full_name AS generated_by_name, a.full_name AS approved_by_name
            FROM tbl_payroll_runs r
            LEFT JOIN tbl_users u ON u.id = r.generated_by
            LEFT JOIN tbl_users a ON a.id = r.approved_by
            WHERE r.company_id = ? AND r.del_status = 'Live' ORDER BY r.pay_month DESC", array($company_id))->result();
    }

    public function getRun($company_id, $id) {
        return $this->db->query("SELECT r.*, u.full_name AS generated_by_name, a.full_name AS approved_by_name
            FROM tbl_payroll_runs r
            LEFT JOIN tbl_users u ON u.id = r.generated_by
            LEFT JOIN tbl_users a ON a.id = r.approved_by
            WHERE r.id = ? AND r.company_id = ? AND r.del_status = 'Live'", array($id, $company_id))->row();
    }

    public function getRunByMonth($company_id, $month) {
        return $this->db->query("SELECT * FROM tbl_payroll_runs WHERE company_id = ? AND pay_month = ? AND del_status = 'Live' LIMIT 1", array($company_id, $month))->row();
    }

    public function getItems($run_id) {
        return $this->db->query("SELECT * FROM tbl_payroll_run_items WHERE run_id = ? ORDER BY employee_name", array($run_id))->result();
    }

    public function getItem($company_id, $item_id) {
        return $this->db->query("SELECT i.*, r.pay_month, r.status AS run_status, r.period_start, r.period_end
            FROM tbl_payroll_run_items i JOIN tbl_payroll_runs r ON r.id = i.run_id AND r.del_status = 'Live'
            WHERE i.id = ? AND i.company_id = ?", array($item_id, $company_id))->row();
    }

    public function createRun($company_id, $month, $user_id) {
        $this->db->insert('tbl_payroll_runs', array(
            'company_id' => $company_id,
            'pay_month' => $month,
            'period_start' => $this->monthStart($month),
            'period_end' => $this->monthEnd($month),
            'status' => 'draft',
            'generated_by' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
        ));
        $run_id = (int) $this->db->insert_id();
        $this->recalculateRun($company_id, $run_id);
        return $run_id;
    }

    /**
     * Read attendance, leaves, holidays and adjustments again and rebuild every
     * line of a draft run. Manual overrides typed on the sheet are kept. New
     * salary setups are added; lines of employees no longer eligible go away.
     */
    public function recalculateRun($company_id, $run_id) {
        $run = $this->getRun($company_id, $run_id);
        if (!$run || $run->status != 'draft') {
            return false;
        }
        $month = $run->pay_month;
        $from = $run->period_start;
        $to = $run->period_end;
        $precision = $this->precision();
        $settings = $this->getSettings($company_id);
        $salaries = $this->getEligibleSalaries($company_id, $month);
        $employee_ids = array();
        foreach ($salaries as $s) {
            $employee_ids[] = (int) $s->employee_id;
        }
        $attendance = $this->attendanceMap($company_id, $from, $to, $employee_ids);
        $holidays = $this->holidayMap($company_id, $from, $to);
        $leaves = $this->leaveMap($company_id, $from, $to);
        $adjustments = $this->adjustmentTotals($company_id, $month, $precision);

        $existing = array();
        foreach ($this->getItems($run_id) as $item) {
            $existing[$item->employee_id] = $item;
        }

        $this->db->trans_start();
        foreach ($salaries as $salary) {
            $eid = (int) $salary->employee_id;
            $item = isset($existing[$eid]) ? $existing[$eid] : null;
            $days = $this->dayStatus($settings, $salary, $month,
                isset($attendance[$eid]) ? $attendance[$eid] : array(), $holidays,
                isset($leaves[$eid]) ? $leaves[$eid] : array());
            $row = $this->computeItem($settings, $salary, $days,
                isset($adjustments[$eid]) ? $adjustments[$eid] : array(), $item, $precision);
            $row['employee_name'] = $salary->full_name;
            $row['designation'] = $salary->designation;
            if ($item) {
                $this->db->where('id', $item->id)->update('tbl_payroll_run_items', $row);
                unset($existing[$eid]);
            } else {
                $row['run_id'] = $run_id;
                $row['company_id'] = $company_id;
                $row['employee_id'] = $eid;
                $this->db->insert('tbl_payroll_run_items', $row);
            }
        }
        // employees who left the payroll (setup removed / inactive / dates): drop unpaid lines
        foreach ($existing as $item) {
            if ((float) $item->paid_amount == 0) {
                $this->db->where('id', $item->id)->delete('tbl_payroll_run_items');
            }
        }
        $this->db->where('id', $run_id)->update('tbl_payroll_runs', array('calculated_at' => date('Y-m-d H:i:s')));
        $this->refreshRunTotals($run_id);
        $this->db->trans_complete();
        return true;
    }

    /**
     * Money of one employee for one month.
     *
     * monthly: full basic + allowances, minus absent days x per-day rate
     *          (per-day = gross or basic / basis days); work on a weekly off or
     *          holiday adds an extra day.
     * daily:   day rate x paid days (present + off-day work + paid leave).
     * hourly:  hour rate x (regular hours + paid leave x standard hours).
     * all:     + overtime, bonus / allowance adjustments, sheet bonus;
     *          - advance installment, fixed deduction, deduction adjustments, sheet deduction.
     */
    public function computeItem($settings, $salary, $days, $adj, $item, $precision) {
        $type = in_array($salary->salary_type, self::$salary_types) ? $salary->salary_type : 'monthly';
        $rate = (float) $salary->basic_salary;
        $std = (float) $settings->standard_hours > 0 ? (float) $settings->standard_hours : 8;
        $multiplier = (float) $settings->overtime_multiplier > 0 ? (float) $settings->overtime_multiplier : 1;
        $housing = (float) $salary->housing_allowance;
        $transport = (float) $salary->transport_allowance;
        $food = (float) $salary->food_allowance;
        $other = (float) $salary->other_allowance;
        $allowances = $housing + $transport + $food + $other;

        // manual overrides typed on the sheet survive a recalculation
        $computed_absent = $days['absent_days'];
        $absent = $item && $item->ovr_absent_days !== null ? (float) $item->ovr_absent_days : $computed_absent;
        $worked_hours = $item && $item->ovr_worked_hours !== null ? (float) $item->ovr_worked_hours : $days['worked_hours'];
        $ot_hours = $item && $item->ovr_overtime_hours !== null ? (float) $item->ovr_overtime_hours : ($settings->overtime_enabled ? $days['overtime_hours'] : 0);
        $sheet_bonus = $item ? (float) $item->sheet_bonus : 0;
        $sheet_deduction = $item ? (float) $item->sheet_deduction : 0;

        $basis = $days['days_in_month'];
        $absent_deduction = 0;
        $offday_amount = 0;
        if ($type == 'monthly') {
            if ($settings->month_days_basis == 'fixed30') {
                $basis = 30;
            } elseif ($settings->month_days_basis == 'working') {
                $basis = max(1, $days['working_days']);
            }
            $deduct_base = $settings->absent_deduction_on == 'basic' ? $rate : $rate + $allowances;
            $per_day = $deduct_base / $basis;
            // days not employed are unpaid: all of them on a calendar / 30-day basis, working days on a working-day basis
            $not_employed = $settings->month_days_basis == 'working' ? $days['not_employed_working'] : $days['not_employed_days'];
            $unpaid_days = $absent + $days['unpaid_leave_days'] + $not_employed;
            $earned_anything = ($days['present_days'] + $days['paid_leave_days'] + $days['offday_worked'] + $days['upcoming_days']) > 0
                || ($item && $item->ovr_absent_days !== null && $absent < $computed_absent);
            $absent_deduction = $earned_anything ? min($deduct_base, $per_day * $unpaid_days) : $deduct_base;
            $basic_amount = $rate;
            if ($settings->pay_offday_work) {
                $offday_amount = $per_day * $days['offday_worked'];
            }
            $ot_rate = (float) $salary->overtime_rate > 0 ? (float) $salary->overtime_rate : ($rate / $basis / $std) * $multiplier;
        } elseif ($type == 'daily') {
            $per_day = $rate;
            // an absent-days override hands the difference back as paid days
            $paid_days = max(0, $days['present_days'] + $days['offday_worked'] + $days['paid_leave_days'] + ($computed_absent - $absent));
            $basic_amount = $rate * $paid_days;
            $ot_rate = (float) $salary->overtime_rate > 0 ? (float) $salary->overtime_rate : ($rate / $std) * $multiplier;
        } else {
            $per_day = $rate * $std;
            $regular_hours = max(0, $worked_hours - $ot_hours);
            $basic_amount = $rate * ($regular_hours + $days['paid_leave_days'] * $std);
            $ot_rate = (float) $salary->overtime_rate > 0 ? (float) $salary->overtime_rate : $rate * $multiplier;
        }
        $overtime_amount = $ot_hours * $ot_rate;

        $bonus = (isset($adj['bonus']) ? $adj['bonus'] : 0) + (isset($adj['allowance']) ? $adj['allowance'] : 0) + $sheet_bonus;
        $advance = isset($adj['advance']) ? $adj['advance'] : 0;
        $other_deduction = (isset($adj['deduction']) ? $adj['deduction'] : 0) + $sheet_deduction;
        $fixed_deduction = (float) $salary->fixed_deduction;

        $p = $precision;
        $row = array(
            'salary_type' => $type,
            'rate' => round($rate, 3),
            'days_in_month' => $days['days_in_month'],
            'basis_days' => $basis,
            'working_days' => $days['working_days'],
            'weekly_off_days' => $days['weekly_off_days'],
            'holiday_days' => $days['holiday_days'],
            'present_days' => $days['present_days'],
            'half_days' => $days['half_days'],
            'offday_worked' => $days['offday_worked'],
            'paid_leave_days' => $days['paid_leave_days'],
            'unpaid_leave_days' => $days['unpaid_leave_days'],
            'absent_days' => $absent,
            'not_employed_days' => $days['not_employed_days'],
            'upcoming_days' => $days['upcoming_days'],
            'missing_checkout_days' => $days['missing_checkout_days'],
            'worked_hours' => round($worked_hours, 2),
            'overtime_hours' => round($ot_hours, 2),
            'per_day_rate' => round($per_day, 3),
            'overtime_rate' => round($ot_rate, 3),
            'basic_amount' => round($basic_amount, $p),
            'housing_allowance' => round($housing, $p),
            'transport_allowance' => round($transport, $p),
            'food_allowance' => round($food, $p),
            'other_allowance' => round($other, $p),
            'overtime_amount' => round($overtime_amount, $p),
            'offday_amount' => round($offday_amount, $p),
            'bonus_amount' => round($bonus, $p),
            'absent_deduction' => round($absent_deduction, $p),
            'advance_deduction' => round($advance, $p),
            'fixed_deduction' => round($fixed_deduction, $p),
            'other_deduction' => round($other_deduction, $p),
            'days_json' => json_encode($days['days']),
        );
        $row['total_earnings'] = round($row['basic_amount'] + $row['housing_allowance'] + $row['transport_allowance']
            + $row['food_allowance'] + $row['other_allowance'] + $row['overtime_amount'] + $row['offday_amount'] + $row['bonus_amount'], $p);
        $row['total_deductions'] = round($row['absent_deduction'] + $row['advance_deduction'] + $row['fixed_deduction'] + $row['other_deduction'], $p);
        $row['net_salary'] = round($row['total_earnings'] - $row['total_deductions'], $p);
        $paid = $item ? (float) $item->paid_amount : 0;
        $row['payment_status'] = $this->paymentStatus($paid, $row['net_salary']);
        return $row;
    }

    public function paymentStatus($paid, $net) {
        if ($paid <= 0) {
            return $net <= 0 ? 'paid' : 'unpaid';
        }
        return $paid + 0.0001 >= $net ? 'paid' : 'partial';
    }

    public function refreshRunTotals($run_id) {
        $t = $this->db->query("SELECT COUNT(*) AS employees, IFNULL(SUM(total_earnings),0) AS e, IFNULL(SUM(total_deductions),0) AS d,
                IFNULL(SUM(net_salary),0) AS n, IFNULL(SUM(paid_amount),0) AS p
            FROM tbl_payroll_run_items WHERE run_id = ?", array($run_id))->row();
        $this->db->where('id', $run_id)->update('tbl_payroll_runs', array(
            'employees' => $t->employees,
            'total_earnings' => $t->e,
            'total_deductions' => $t->d,
            'total_net' => $t->n,
            'total_paid' => $t->p,
        ));
    }

    /** sheet edits on one draft line, then recompute that line */
    public function saveItemOverrides($company_id, $item_id, $data) {
        $item = $this->getItem($company_id, $item_id);
        if (!$item || $item->run_status != 'draft') {
            return false;
        }
        $this->db->where('id', $item_id)->update('tbl_payroll_run_items', $data);
        $this->recalculateRun($company_id, $item->run_id);
        return true;
    }

    public function setRunStatus($run_id, $status, $user_id) {
        $data = array('status' => $status);
        if ($status == 'approved') {
            $data['approved_by'] = $user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
        } else {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }
        $this->db->where('id', $run_id)->update('tbl_payroll_runs', $data);
    }

    public function deleteRun($run_id) {
        $this->db->where('id', $run_id)->update('tbl_payroll_runs', array('del_status' => 'Deleted'));
        $this->db->where('run_id', $run_id)->delete('tbl_payroll_run_items');
    }

    /* =================================================================
     * payments
     * ================================================================= */

    public function getPayments($company_id, $run_id = '', $employee_id = '') {
        $sql = "SELECT p.*, pm.name AS payment_name, u.full_name, r.pay_month, b.full_name AS added_by
                FROM tbl_payroll_payments p
                LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
                LEFT JOIN tbl_users u ON u.id = p.employee_id
                LEFT JOIN tbl_users b ON b.id = p.user_id
                LEFT JOIN tbl_payroll_runs r ON r.id = p.run_id
                WHERE p.company_id = ? AND p.del_status = 'Live'";
        $params = array($company_id);
        if ($run_id) {
            $sql .= " AND p.run_id = ?";
            $params[] = $run_id;
        }
        if ($employee_id) {
            $sql .= " AND p.employee_id = ?";
            $params[] = $employee_id;
        }
        return $this->db->query($sql . " ORDER BY p.pay_date DESC, p.id DESC", $params)->result();
    }

    public function getPayment($company_id, $id) {
        return $this->db->query("SELECT * FROM tbl_payroll_payments WHERE id = ? AND company_id = ? AND del_status = 'Live'", array($id, $company_id))->row();
    }

    /**
     * Pay (part of) one approved line. $opt: amount, payment_id, pay_date, note,
     * record_expense (bool), from_till (bool), user_id, outlet_id, counter_id.
     */
    public function addPayment($company_id, $item, $opt) {
        $expense_id = null;
        if ($opt['record_expense']) {
            $expense_id = $this->postExpense($company_id, array(
                'date' => $opt['pay_date'],
                'amount' => $opt['amount'],
                'employee_id' => $item->employee_id,
                'note' => 'Salary ' . $this->monthLabel($item->pay_month) . ' - ' . $item->employee_name . ($opt['note'] ? ' (' . $opt['note'] . ')' : ''),
                'payment_id' => $opt['payment_id'],
                'from_till' => $opt['from_till'],
            ), $opt);
        }
        $this->db->insert('tbl_payroll_payments', array(
            'company_id' => $company_id,
            'run_id' => $item->run_id,
            'run_item_id' => $item->id,
            'employee_id' => $item->employee_id,
            'amount' => $opt['amount'],
            'payment_id' => $opt['payment_id'],
            'pay_date' => $opt['pay_date'],
            'note' => $opt['note'],
            'expense_id' => $expense_id,
            'outlet_id' => $opt['outlet_id'],
            'counter_id' => $opt['from_till'] ? (int) $opt['counter_id'] : 0,
            'user_id' => $opt['user_id'],
            'added_date_time' => date('Y-m-d H:i:s'),
        ));
        $this->refreshItemPaid($item->id);
        return (int) $this->db->insert_id();
    }

    public function deletePayment($company_id, $payment) {
        $this->db->where('id', $payment->id)->update('tbl_payroll_payments', array('del_status' => 'Deleted'));
        if ($payment->expense_id) {
            $this->db->where('id', $payment->expense_id)->update('tbl_expenses', array('del_status' => 'Deleted'));
        }
        $this->refreshItemPaid($payment->run_item_id);
    }

    public function refreshItemPaid($item_id) {
        $item = $this->db->query("SELECT * FROM tbl_payroll_run_items WHERE id = ?", array($item_id))->row();
        if (!$item) {
            return;
        }
        $paid = (float) $this->db->query("SELECT IFNULL(SUM(amount),0) AS v FROM tbl_payroll_payments WHERE run_item_id = ? AND del_status = 'Live'", array($item_id))->row()->v;
        $this->db->where('id', $item_id)->update('tbl_payroll_run_items', array(
            'paid_amount' => $paid,
            'payment_status' => $this->paymentStatus($paid, (float) $item->net_salary),
        ));
        $this->refreshRunTotals($item->run_id);
    }

    /**
     * A tbl_expenses row, same shape as Expense::addEditExpense. counter_id only
     * when the cash left the till, so the open register counts it.
     */
    public function postExpense($company_id, $e, $opt) {
        $this->db->insert('tbl_expenses', array(
            'date' => $e['date'],
            'amount' => $e['amount'],
            'category_id' => $this->expenseCategoryId($company_id, $opt['user_id']),
            'employee_id' => $e['employee_id'],
            'note' => mb_substr($e['note'], 0, 200),
            'user_id' => $opt['user_id'],
            'outlet_id' => $opt['outlet_id'],
            'payment_id' => $e['payment_id'],
            'counter_id' => $e['from_till'] ? (int) $opt['counter_id'] : 0,
            'added_date_time' => date('Y-m-d H:i:s'),
            'del_status' => 'Live',
        ));
        return (int) $this->db->insert_id();
    }

    /* =================================================================
     * reports
     * ================================================================= */

    /** attendance sheet: every live employee, with or without a salary setup */
    public function attendanceSheet($company_id, $month) {
        $from = $this->monthStart($month);
        $to = $this->monthEnd($month);
        $settings = $this->getSettings($company_id);
        $employees = $this->getEmployeesWithSalary($company_id);
        $attendance = $this->attendanceMap($company_id, $from, $to);
        $holidays = $this->holidayMap($company_id, $from, $to);
        $leaves = $this->leaveMap($company_id, $from, $to);
        $rows = array();
        foreach ($employees as $e) {
            $salary = $e->salary_id ? (object) array('weekly_off' => $e->weekly_off, 'joining_date' => $e->joining_date, 'leaving_date' => $e->leaving_date) : null;
            $status = $this->dayStatus($settings, $salary, $month,
                isset($attendance[$e->id]) ? $attendance[$e->id] : array(), $holidays,
                isset($leaves[$e->id]) ? $leaves[$e->id] : array());
            $rows[] = (object) array('employee' => $e, 'status' => $status, 'has_attendance' => isset($attendance[$e->id]));
        }
        return array('rows' => $rows, 'holidays' => $holidays);
    }

    /** one employee's payroll lines for a year */
    public function salaryHistory($company_id, $employee_id, $year) {
        return $this->db->query("SELECT i.*, r.pay_month, r.status AS run_status
            FROM tbl_payroll_run_items i JOIN tbl_payroll_runs r ON r.id = i.run_id AND r.del_status = 'Live'
            WHERE i.company_id = ? AND i.employee_id = ? AND r.pay_month LIKE ?
            ORDER BY r.pay_month", array($company_id, $employee_id, $year . '-%'))->result();
    }
}
