<?php
/*
 | Payroll module (monthly salary from attendance).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['payroll'] = 'Payroll';
$lang['payroll_runs'] = 'Monthly Payroll';
$lang['payroll_salary_setup'] = 'Salary Setup';
$lang['payroll_adjustments'] = 'Advances & Adjustments';
$lang['payroll_leaves'] = 'Leaves';
$lang['payroll_holidays'] = 'Holidays';
$lang['payroll_reports'] = 'Payroll Reports';
$lang['payroll_settings'] = 'Payroll Settings';
$lang['payroll_generate'] = 'Generate Payroll';
$lang['approve'] = 'Approve';
$lang['pay'] = 'Pay';
$lang['payroll_tab_reports'] = 'Reports';
$lang['payroll_tab_setup'] = 'Setup';
$lang['payroll_attendance_sheet'] = 'Attendance Sheet';
$lang['payroll_salary_history'] = 'Salary History';
$lang['payroll_advance_balances'] = 'Advance Balances';

// common words
$lang['payroll_attendance'] = 'Attendance';
$lang['payroll_earnings'] = 'Earnings';
$lang['payroll_deductions'] = 'Deductions';
$lang['payroll_net_salary'] = 'Net Salary';
$lang['payroll_net_pay'] = 'Net Pay';
$lang['payroll_net_payable'] = 'Net Payable';
$lang['payroll_balance'] = 'Balance';
$lang['payroll_basic'] = 'Basic';
$lang['payroll_allowances'] = 'Allowances';
$lang['payroll_bonus'] = 'Bonus';
$lang['payroll_deduction'] = 'Deduction';
$lang['payroll_absence'] = 'Absence';
$lang['payroll_advance'] = 'Advance';
$lang['payroll_advances'] = 'Advances';
$lang['payroll_other'] = 'Other';
$lang['payroll_overtime'] = 'Overtime';
$lang['payroll_ot'] = 'OT';
$lang['payroll_ot_offday'] = 'OT / Off-day';
$lang['payroll_offday_work'] = 'Work on off day / holiday';
$lang['payroll_hrs'] = 'hrs';
$lang['payroll_days_short'] = 'days';
$lang['payroll_total_earnings'] = 'Total Earnings';
$lang['payroll_total_deductions'] = 'Total Deductions';
$lang['payroll_partial'] = 'Partly paid';
$lang['payroll_unpaid'] = 'Unpaid';
$lang['payroll_status_draft'] = 'Draft';
$lang['payroll_status_approved'] = 'Approved';
$lang['payroll_status_active'] = 'Active';
$lang['payroll_status_inactive'] = 'Inactive';
$lang['payroll_type_monthly'] = 'Monthly';
$lang['payroll_type_daily'] = 'Daily wage';
$lang['payroll_type_hourly'] = 'Hourly';
$lang['payroll_per_monthly'] = 'month';
$lang['payroll_per_daily'] = 'day';
$lang['payroll_per_hourly'] = 'hour';
$lang['payroll_mode_cash'] = 'Cash';
$lang['payroll_mode_bank'] = 'Bank transfer';
$lang['payroll_auto'] = 'Auto';
$lang['payroll_off'] = 'off';
$lang['payroll_open'] = 'Open';
$lang['payroll_day'] = 'Day';
$lang['payroll_from_date'] = 'From';
$lang['payroll_to_date'] = 'To';
$lang['payroll_reason'] = 'Reason';
$lang['payroll_signature'] = 'Signature';
$lang['payroll_not_set'] = 'Not set';
$lang['payroll_set_up'] = 'Set up';
$lang['payroll_posted'] = 'In expenses';
$lang['payroll_expense'] = 'Expense';
$lang['payroll_all_employees'] = 'All employees';
$lang['payroll_all_months'] = 'All months';
$lang['payroll_all_types'] = 'All types';
$lang['payroll_choose_employee'] = 'Choose an employee to see the salary of every month, the payments and the advances.';

// day codes (attendance sheet, payslip)
$lang['payroll_code_P'] = 'Present';
$lang['payroll_code_M'] = 'Present, no check-out';
$lang['payroll_code_H'] = 'Half day';
$lang['payroll_code_A'] = 'Absent';
$lang['payroll_code_L'] = 'Paid leave';
$lang['payroll_code_U'] = 'Unpaid leave';
$lang['payroll_code_O'] = 'Weekly off';
$lang['payroll_code_HO'] = 'Holiday';
$lang['payroll_code_W'] = 'Worked on off day / holiday';
$lang['payroll_code_X'] = 'Not employed yet / left';
$lang['payroll_code_F'] = 'Upcoming day';
$lang['payroll_present_short'] = 'P';
$lang['payroll_half_short'] = 'half';
$lang['payroll_absent_short'] = 'A';
$lang['payroll_leave_short'] = 'L';
$lang['payroll_off_short'] = 'Off';
$lang['payroll_holiday_short'] = 'Hol';
$lang['payroll_offday_worked_short'] = 'W';
$lang['payroll_not_employed_short'] = 'not employed';

// attendance figures
$lang['payroll_days_in_month'] = 'Days in month';
$lang['payroll_working_days'] = 'Working days';
$lang['payroll_present_days'] = 'Present days';
$lang['payroll_half_days'] = 'Half days';
$lang['payroll_absent_days'] = 'Absent days';
$lang['payroll_leave_paid_unpaid'] = 'Leave (paid + unpaid)';
$lang['payroll_off_holidays'] = 'Off / holidays';
$lang['payroll_offday_worked'] = 'Off days worked';
$lang['payroll_worked_hours'] = 'Worked hours';
$lang['payroll_overtime_hours'] = 'Overtime hours';
$lang['payroll_missing_checkout'] = 'Days without check-out';
$lang['payroll_not_employed'] = 'Days not employed';
$lang['payroll_per_day_rate'] = 'Per-day rate';
$lang['payroll_calendar_days'] = 'Days';
$lang['payroll_full_day'] = 'Full day';
$lang['payroll_half_day'] = 'Half day';

// monthly payroll list
$lang['payroll_runs_hint'] = 'One payroll per month, calculated from attendance and the salary setup. Generate, check, approve, then pay.';
$lang['payroll_employees_on_payroll'] = 'Employees on payroll';
$lang['payroll_employees_on_payroll_hint'] = 'with an active salary setup';
$lang['payroll_monthly_gross'] = 'Monthly gross (fixed)';
$lang['payroll_monthly_gross_hint'] = 'basic + allowances of monthly staff';
$lang['payroll_not_generated'] = 'Not generated';
$lang['payroll_last_month'] = 'last month';
$lang['payroll_balance_to_pay'] = 'still to pay';
$lang['payroll_advances_open'] = 'Advances to recover';
$lang['payroll_advances_open_hint'] = 'given, not yet deducted';
$lang['payroll_no_setup_yet'] = 'No employee has a salary yet. Start with the salary setup of each employee.';
$lang['payroll_generate_title'] = 'Generate payroll';
$lang['payroll_generate_hint'] = 'A new payroll starts as a draft: it re-reads attendance every time you open it, so you can fix attendance and see the result at once. Approve it when it is right.';
$lang['payroll_running'] = 'running';
$lang['payroll_generated_by'] = 'Generated by';
$lang['payroll_approved_by'] = 'Approved by';

// payroll sheet
$lang['payroll_print_sheet'] = 'Print sheet';
$lang['payroll_all_payslips'] = 'All payslips';
$lang['payroll_recalculate'] = 'Recalculate';
$lang['payroll_pay_all'] = 'Pay all';
$lang['payroll_approve_confirm'] = 'Approve this payroll? It is locked after approval and salaries can be paid.';
$lang['payroll_draft_info'] = 'Draft: the figures are calculated again from attendance every time this page opens. Use the pencil to correct a line (absent days, overtime, bonus, deduction), then Approve.';
$lang['payroll_approved_info'] = 'Approved: this payroll is locked. Pay salaries line by line or all at once. To change it, delete its payments and Reopen.';
$lang['payroll_month_running_warn'] = 'This month is still running: %d upcoming day(s) are not absences yet (monthly staff are paid for them, daily and hourly staff are not). Approve after the month ends.';
$lang['payroll_missing_checkout_warn'] = '%d day(s) have a check-in but no check-out. They count as a %s with no overtime (Payroll Settings). Correct the out time in Attendance if needed.';
$lang['payroll_negative_warn'] = '%d employee(s) have a negative net salary. Reduce the deductions or spread the advance over more months.';
$lang['payroll_missing_setup_warn'] = 'Not in this payroll (no salary setup):';
$lang['payroll_edited'] = 'edited';
$lang['payroll_override_hint'] = 'Changed by hand on this sheet; kept when attendance is recalculated.';
$lang['payroll_no_attendance'] = 'no attendance';
$lang['payroll_salary_payments'] = 'Salary payments';
$lang['payroll_no_payments'] = 'No payment yet.';
$lang['payroll_from_till_short'] = '(till)';
$lang['payroll_delete_payment_confirm'] = 'Delete this payment? Its expense entry is deleted too.';
$lang['payroll_basis_note'] = 'Rules used';
$lang['payroll_absent_on'] = 'absence deducted from';
$lang['payroll_edit_line'] = 'Correct line';
$lang['payroll_edit_line_hint'] = 'Leave a box empty to use the value from attendance. Values typed here stay when the payroll is recalculated.';
$lang['payroll_from_attendance'] = 'From attendance';
$lang['payroll_reset_line'] = 'Reset to attendance';
$lang['payroll_pay_salary'] = 'Pay salary';
$lang['payroll_pay_all_hint'] = 'Pays the remaining balance of every employee (%s) with the method below.';
$lang['payroll_record_expense'] = 'Add to Expenses';
$lang['payroll_from_till'] = 'Paid from the cash till';
$lang['payroll_from_till_hint'] = 'Tick "paid from the cash till" only when the money leaves this counter\'s drawer: the open register then counts it.';
$lang['payroll_from_till_closed'] = 'The register of this counter is closed, so "paid from the cash till" cannot be used now.';

// salary setup
$lang['payroll_salary_setup_hint'] = 'Salary structure of each employee. Only employees with an active setup are included in the monthly payroll.';
$lang['payroll_n_of_m_set'] = '%d of %d set up';
$lang['payroll_salary_type'] = 'Salary type';
$lang['payroll_basic_rate'] = 'Basic / rate';
$lang['payroll_gross_month'] = 'Gross / month';
$lang['payroll_weekly_off'] = 'Weekly off';
$lang['payroll_joining_date'] = 'Joining date';
$lang['payroll_leaving_date'] = 'Leaving date';
$lang['payroll_left'] = 'left';
$lang['payroll_payment_mode'] = 'Paid by';
$lang['payroll_no_login'] = 'no login';
$lang['payroll_employees_from_users'] = 'Employees are the users of Account and User > Users. Add staff who do not log in there with "Will login" = No, and enter their attendance by hand.';
$lang['payroll_pay_structure'] = 'Pay structure';
$lang['payroll_basic_salary'] = 'Basic salary';
$lang['payroll_basic_monthly'] = 'Monthly basic salary';
$lang['payroll_basic_daily'] = 'Day rate';
$lang['payroll_basic_hourly'] = 'Hour rate';
$lang['payroll_overtime_rate'] = 'Overtime rate / hour';
$lang['payroll_overtime_rate_hint'] = 'Empty = calculated from the basic and the overtime multiplier.';
$lang['payroll_on_payroll'] = 'On payroll';
$lang['payroll_monthly_allowances'] = 'Monthly allowances and fixed deduction';
$lang['payroll_housing_allowance'] = 'Housing allowance';
$lang['payroll_transport_allowance'] = 'Transport allowance';
$lang['payroll_food_allowance'] = 'Food allowance';
$lang['payroll_other_allowance'] = 'Other allowance';
$lang['payroll_fixed_deduction'] = 'Fixed deduction';
$lang['payroll_fixed_deduction_for'] = 'Fixed deduction is for';
$lang['payroll_fixed_deduction_example'] = 'e.g. accommodation, insurance';
$lang['payroll_preview_gross'] = 'Gross per month';
$lang['payroll_preview_daily'] = 'About (26 days)';
$lang['payroll_preview_hourly'] = 'About (26 days x standard hours)';
$lang['payroll_preview_perday'] = 'per day';
$lang['payroll_preview_ot'] = 'overtime / hour';
$lang['payroll_work_calendar'] = 'Work calendar';
$lang['payroll_weekly_off_hint'] = 'Days off every week. Not absences; worked off days can be paid extra (Payroll Settings).';
$lang['payroll_leaving_hint'] = 'Last working day. Leave empty while employed.';
$lang['payroll_payment_details'] = 'Payment details';
$lang['payroll_bank_name'] = 'Bank name';
$lang['payroll_bank_account'] = 'Account no. / IBAN';
$lang['payroll_salary_saved'] = 'Salary setup saved.';
$lang['payroll_leaving_before_joining'] = 'The leaving date is before the joining date.';

// advances & adjustments
$lang['payroll_adjustments_hint'] = 'Advances (cash given now, recovered from salary), bonuses and extra allowances (added), deductions such as fines (subtracted). Each one goes into the payroll of the month you choose.';
$lang['payroll_add_adjustment'] = 'Add advance / bonus / deduction';
$lang['payroll_adj_advance'] = 'Salary advance';
$lang['payroll_adj_bonus'] = 'Bonus';
$lang['payroll_adj_allowance'] = 'Extra allowance';
$lang['payroll_adj_deduction'] = 'Deduction';
$lang['payroll_pay_month'] = 'Payroll month';
$lang['payroll_pay_month_hint'] = 'For an advance: the first month it is deducted.';
$lang['payroll_installments'] = 'Installments';
$lang['payroll_installments_hint'] = 'Months to recover it over.';
$lang['payroll_given_by'] = 'Given by';
$lang['payroll_recovery'] = 'Recovery';
$lang['payroll_n_installments'] = '%d x %s';
$lang['payroll_one_installment'] = 'In one go';
$lang['payroll_deducted'] = 'deducted from salary';
$lang['payroll_added'] = 'added to salary';
$lang['payroll_adjustment_invalid'] = 'Choose an employee, a type, an amount above zero and a payroll month.';
$lang['payroll_month_approved'] = 'The payroll of %s is already approved. Reopen it first.';

// leaves
$lang['payroll_leaves_hint'] = 'Paid leave is not deducted; unpaid leave is deducted like an absence. Weekly offs and holidays inside a leave are not counted.';
$lang['payroll_add_leave'] = 'Add leave';
$lang['payroll_leave_paid'] = 'Paid leave';
$lang['payroll_leave_unpaid'] = 'Unpaid leave';
$lang['payroll_leave_rule'] = 'A day with attendance stays present even inside a leave.';
$lang['payroll_leave_invalid'] = 'Choose an employee and a valid date range (at most one year).';
$lang['payroll_leave_overlap'] = 'This employee already has a leave in these dates.';

// holidays
$lang['payroll_holidays_hint'] = 'Public holidays for everyone: never an absence, and work on a holiday can be paid as an extra day.';
$lang['payroll_add_holiday'] = 'Add holiday';
$lang['payroll_same_day'] = 'same day';
$lang['payroll_holiday_example'] = 'e.g. National Day';
$lang['payroll_holiday_invalid'] = 'Give a date, a name, and at most 31 days.';

// settings
$lang['payroll_settings_hint'] = 'How attendance turns into salary. Changes apply to draft payrolls at once; approved payrolls keep their figures.';
$lang['payroll_monthly_salary_rules'] = 'Monthly salary';
$lang['payroll_month_days_basis'] = 'Per-day rate = monthly salary divided by';
$lang['payroll_basis_calendar'] = 'Days in the month (28-31)';
$lang['payroll_basis_fixed30'] = 'Always 30 days';
$lang['payroll_basis_working'] = 'Working days of the month';
$lang['payroll_month_days_basis_hint'] = 'Used to deduct an absent day and to pay a worked off day.';
$lang['payroll_absent_deduction_on'] = 'Absent day is deducted from';
$lang['payroll_on_gross'] = 'gross (basic + allowances)';
$lang['payroll_on_basic'] = 'basic only';
$lang['payroll_absent_deduction_on_hint'] = 'Per-day rate = this amount / the days above.';
$lang['payroll_pay_offday_work'] = 'Pay work on a weekly off / holiday as an extra day';
$lang['payroll_pay_offday_work_hint'] = 'Monthly staff only; daily and hourly staff are paid for every day or hour worked anyway.';
$lang['payroll_default_weekly_off'] = 'Default weekly off for new salary setups';
$lang['payroll_default_weekly_off_hint'] = 'Each employee can have their own weekly off in the salary setup.';
$lang['payroll_attendance_rules'] = 'Attendance';
$lang['payroll_standard_hours'] = 'Hours of a full day';
$lang['payroll_standard_hours_hint'] = 'Work beyond this is overtime.';
$lang['payroll_half_day_hours'] = 'Half day below (hours)';
$lang['payroll_half_day_hours_hint'] = 'Worked less than this = half day.';
$lang['payroll_missing_checkout_rule'] = 'A day with check-in but no check-out counts as';
$lang['payroll_missing_checkout_rule_hint'] = 'Staff who never log out get no out time. Their hours are taken as a full (or half) standard day, without overtime.';
$lang['payroll_overtime_enabled'] = 'Pay overtime';
$lang['payroll_overtime_multiplier'] = 'Overtime multiplier';
$lang['payroll_overtime_multiplier_hint'] = 'Hourly rate x this, e.g. 1.25';
$lang['payroll_overtime_min_minutes'] = 'Ignore overtime below (minutes / day)';
$lang['payroll_overtime_min_minutes_hint'] = 'A few extra minutes are not overtime.';
$lang['payroll_payments_expenses'] = 'Payments and expenses';
$lang['payroll_record_expense_default'] = 'Tick "Add to Expenses" by default when paying';
$lang['payroll_expense_category'] = 'Expense category for salaries';
$lang['payroll_expense_category_auto'] = 'Salary & Wages (created automatically)';
$lang['payroll_payslip_footer'] = 'Payslip footer';
$lang['payroll_payslip_footer_example'] = 'e.g. This is a computer generated payslip.';

// reports
$lang['payroll_attendance_sheet_hint'] = 'Every employee and every day of the month, read the same way as the payroll.';
$lang['payroll_attendance_sheet_note'] = 'Employees without a salary setup use the default weekly off. Correct a day in Attendance > List Attendance, add leaves and holidays here.';
$lang['payroll_not_on_payroll_hint'] = 'No salary setup: not included in the payroll.';
$lang['payroll_salary_history_hint'] = 'The salary of every month of one employee, with payments and advances.';
$lang['payroll_monthly_salaries'] = 'Monthly salaries';
$lang['payroll_no_lines'] = 'No payroll for this employee in this year.';
$lang['payroll_advance_balance'] = 'Advance balance';
$lang['payroll_given'] = 'Given';
$lang['payroll_recovered'] = 'Recovered';
$lang['payroll_no_advances'] = 'No advance.';
$lang['payroll_advance_balances_hint'] = 'Advances given and how much is still to be recovered from salary.';
$lang['payroll_advance_balances_note'] = 'Recovered = advance deductions in approved payrolls. Draft payrolls are not counted yet.';
$lang['payroll_last_advance'] = 'Last advance';
$lang['payroll_give_advance'] = 'Give advance';

// payslip, printed sheet
$lang['payroll_payslip'] = 'Payslip';
$lang['payroll_payslips'] = 'Payslips';
$lang['payroll_employee_no'] = 'Employee no.';
$lang['payroll_monthly_basic'] = 'monthly basic';
$lang['payroll_advance_recovery'] = 'Advance recovery';
$lang['payroll_no_deductions'] = 'No deductions';
$lang['payroll_employer_signature'] = 'Employer signature';
$lang['payroll_employee_signature'] = 'Employee signature';
$lang['payroll_register'] = 'Payroll Register';
$lang['payroll_printed'] = 'printed';
$lang['payroll_prepared_by'] = 'Prepared by';
$lang['payroll_checked_by'] = 'Checked by';

// messages
$lang['payroll_not_found'] = 'Not found.';
$lang['payroll_month_invalid'] = 'Choose this month or an earlier one.';
$lang['payroll_already_generated'] = 'The payroll of this month already exists; here it is.';
$lang['payroll_no_eligible'] = 'No employee with an active salary setup for this month.';
$lang['payroll_generated'] = 'Payroll generated as a draft. Check it, then approve.';
$lang['payroll_locked'] = 'This payroll is approved and locked.';
$lang['payroll_recalculated'] = 'Payroll recalculated from attendance.';
$lang['payroll_absent_too_many'] = 'Absent days cannot be more than the days of the month.';
$lang['payroll_approved_msg'] = 'Payroll approved. Salaries can be paid now.';
$lang['payroll_reopen_has_payments'] = 'Delete the salary payments of this month before reopening it.';
$lang['payroll_reopened_msg'] = 'Payroll reopened as a draft.';
$lang['payroll_delete_only_draft'] = 'Only a draft payroll can be deleted. Reopen it first.';
$lang['payroll_choose_method'] = 'Choose a payment method.';
$lang['payroll_till_needs_register'] = 'Open the register of this counter first, or untick "paid from the cash till".';
$lang['payroll_pay_needs_approval'] = 'Approve the payroll before paying salaries.';
$lang['payroll_amount_invalid'] = 'The amount must be above zero and not more than the balance.';
$lang['payroll_payment_saved'] = 'Salary payment saved.';
$lang['payroll_nothing_to_pay'] = 'Nothing left to pay.';
$lang['payroll_paid_n'] = 'Salary paid to %d employee(s).';
