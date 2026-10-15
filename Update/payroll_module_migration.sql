-- Payroll module: database changes. Select your database in phpMyAdmin, then run once.
-- Safe to run again: rows use INSERT IGNORE and tables CREATE TABLE IF NOT EXISTS.
--
-- Monthly salary from attendance: per-employee salary setup, leaves, holidays,
-- advances / bonuses / deductions, a monthly payroll run (draft -> approved),
-- salary payments (optionally posted to Expenses) and payslips.
-- Docs: docs/payroll_module.md
--
-- The tbl_access ids are fixed on purpose: the controllers hard-code them
-- (Payroll::ACCESS_* / Payroll_setup::ACCESS_*) and userHome.php uses them in data-access.

INSERT IGNORE INTO tbl_main_modules (id, name, del_status) VALUES (13, 'payroll', 'Live');

INSERT IGNORE INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
  (412, 'payroll_runs',         '',        'payroll_runs',         0,   13,   'Live'),
  (413, '',                     'view',    'view',                 412, NULL, 'Live'),
  (414, '',                     'add',     'payroll_generate',     412, NULL, 'Live'),
  (415, '',                     'approve', 'approve',              412, NULL, 'Live'),
  (416, '',                     'pay',     'pay',                  412, NULL, 'Live'),
  (417, '',                     'delete',  'delete',               412, NULL, 'Live'),
  (418, 'payroll_salary_setup', '',        'payroll_salary_setup', 0,   13,   'Live'),
  (419, '',                     'view',    'view',                 418, NULL, 'Live'),
  (420, '',                     'update',  'update',               418, NULL, 'Live'),
  (421, 'payroll_adjustments',  '',        'payroll_adjustments',  0,   13,   'Live'),
  (422, '',                     'view',    'view',                 421, NULL, 'Live'),
  (423, '',                     'add',     'add',                  421, NULL, 'Live'),
  (424, '',                     'delete',  'delete',               421, NULL, 'Live'),
  (425, 'payroll_leaves',       '',        'payroll_leaves',       0,   13,   'Live'),
  (426, '',                     'view',    'view',                 425, NULL, 'Live'),
  (427, '',                     'add',     'add',                  425, NULL, 'Live'),
  (428, '',                     'delete',  'delete',               425, NULL, 'Live'),
  (429, 'payroll_holidays',     '',        'payroll_holidays',     0,   13,   'Live'),
  (430, '',                     'view',    'view',                 429, NULL, 'Live'),
  (431, '',                     'add',     'add',                  429, NULL, 'Live'),
  (432, '',                     'delete',  'delete',               429, NULL, 'Live'),
  (433, 'payroll_reports',      '',        'payroll_reports',      0,   13,   'Live'),
  (434, '',                     'view',    'view',                 433, NULL, 'Live'),
  (435, 'payroll_settings',     '',        'payroll_settings',     0,   13,   'Live'),
  (436, '',                     'update',  'update',               435, NULL, 'Live');

-- Kept simple for now: application/config/payroll.php switches on only Monthly Payroll
-- and Salary Setup. The other screens (421-436: advances & adjustments, leaves,
-- holidays, reports, settings) are hidden from the Role screen as well. To bring one
-- back, add its key in config/payroll.php and set its rows to 'Live' again.
UPDATE tbl_access SET del_status = 'Deleted' WHERE id BETWEEN 421 AND 436;

-- one row per company, created with defaults on first use
CREATE TABLE IF NOT EXISTS tbl_payroll_settings (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  month_days_basis VARCHAR(10) NOT NULL DEFAULT 'calendar' COMMENT 'calendar / fixed30 / working : divisor for the per-day rate of a monthly salary',
  absent_deduction_on VARCHAR(10) NOT NULL DEFAULT 'gross' COMMENT 'gross / basic : what an absent day is deducted from',
  default_weekly_off VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'ISO weekdays 1=Mon..7=Sun, comma separated; default for new salary setups',
  standard_hours DECIMAL(5,2) NOT NULL DEFAULT 9 COMMENT 'hours of one full working day',
  half_day_hours DECIMAL(5,2) NOT NULL DEFAULT 4 COMMENT 'worked less than this = half day',
  missing_checkout VARCHAR(10) NOT NULL DEFAULT 'full' COMMENT 'full / half : a day with check-in but no check-out',
  overtime_enabled TINYINT(1) NOT NULL DEFAULT 1,
  overtime_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.25,
  overtime_min_minutes INT NOT NULL DEFAULT 30 COMMENT 'daily overtime below this is ignored',
  pay_offday_work TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'work on a weekly off / holiday is paid as an extra day',
  record_expense TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'default of the "add to Expenses" box when paying',
  expense_category_id INT NULL COMMENT 'tbl_expense_items row used for salary expenses',
  payslip_footer VARCHAR(255) NULL,
  updated_by INT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_settings_company (company_id)
) ENGINE=InnoDB;

-- salary structure of one employee (tbl_users row)
CREATE TABLE IF NOT EXISTS tbl_payroll_salaries (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  employee_id INT NOT NULL,
  salary_type VARCHAR(10) NOT NULL DEFAULT 'monthly' COMMENT 'monthly / daily / hourly',
  basic_salary DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly basic, or the day rate, or the hour rate',
  housing_allowance DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly',
  transport_allowance DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly',
  food_allowance DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly',
  other_allowance DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly',
  fixed_deduction DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly, e.g. accommodation',
  fixed_deduction_note VARCHAR(100) NULL,
  overtime_rate DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'per hour; 0 = from the salary x multiplier',
  weekly_off VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'ISO weekdays 1=Mon..7=Sun, comma separated',
  joining_date DATE NULL,
  leaving_date DATE NULL,
  payment_mode VARCHAR(10) NOT NULL DEFAULT 'cash' COMMENT 'cash / bank',
  bank_name VARCHAR(80) NULL,
  bank_account VARCHAR(60) NULL COMMENT 'account number or IBAN',
  status VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active / inactive',
  note VARCHAR(255) NULL,
  user_id INT NULL,
  updated_at DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_salary_employee (company_id, employee_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_payroll_holidays (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  holiday_date DATE NOT NULL,
  name VARCHAR(100) NOT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_payroll_holiday_date (company_id, holiday_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_payroll_leaves (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  employee_id INT NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  leave_type VARCHAR(10) NOT NULL DEFAULT 'paid' COMMENT 'paid / unpaid',
  reason VARCHAR(255) NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_payroll_leave_emp (company_id, employee_id, from_date, to_date)
) ENGINE=InnoDB;

-- one-off money per employee: advance (cash given now, recovered from salary),
-- bonus / allowance (added), deduction (fine, damage, ...)
CREATE TABLE IF NOT EXISTS tbl_payroll_adjustments (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  employee_id INT NOT NULL,
  adj_type VARCHAR(12) NOT NULL COMMENT 'advance / bonus / allowance / deduction',
  amount DECIMAL(14,3) NOT NULL DEFAULT 0,
  adj_date DATE NOT NULL,
  pay_month CHAR(7) NOT NULL COMMENT 'YYYY-MM of the payroll that adds / deducts it (first installment for an advance)',
  installments INT NOT NULL DEFAULT 1 COMMENT 'advance only: recovered over this many months',
  note VARCHAR(255) NULL,
  payment_id INT NULL COMMENT 'advance only: how the cash was given',
  expense_id INT NULL COMMENT 'advance only: tbl_expenses row, if posted',
  outlet_id INT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_payroll_adj_emp (company_id, employee_id, pay_month),
  KEY idx_payroll_adj_month (company_id, pay_month)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_payroll_runs (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  pay_month CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'draft' COMMENT 'draft / approved',
  employees INT NOT NULL DEFAULT 0,
  total_earnings DECIMAL(14,3) NOT NULL DEFAULT 0,
  total_deductions DECIMAL(14,3) NOT NULL DEFAULT 0,
  total_net DECIMAL(14,3) NOT NULL DEFAULT 0,
  total_paid DECIMAL(14,3) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  calculated_at DATETIME NULL COMMENT 'last time attendance was read',
  generated_by INT NULL,
  generated_at DATETIME NULL,
  approved_by INT NULL,
  approved_at DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_payroll_run_month (company_id, pay_month, del_status)
) ENGINE=InnoDB;

-- one employee's salary in one run; days / amounts are a snapshot, recomputed while draft
CREATE TABLE IF NOT EXISTS tbl_payroll_run_items (
  id INT NOT NULL AUTO_INCREMENT,
  run_id INT NOT NULL,
  company_id INT NOT NULL,
  employee_id INT NOT NULL,
  employee_name VARCHAR(100) NULL,
  designation VARCHAR(100) NULL,
  salary_type VARCHAR(10) NOT NULL DEFAULT 'monthly',
  rate DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'basic salary / day rate / hour rate at calculation time',
  days_in_month INT NOT NULL DEFAULT 0,
  basis_days DECIMAL(6,2) NOT NULL DEFAULT 0 COMMENT 'divisor of the per-day rate',
  working_days INT NOT NULL DEFAULT 0,
  weekly_off_days INT NOT NULL DEFAULT 0,
  holiday_days INT NOT NULL DEFAULT 0,
  present_days DECIMAL(6,1) NOT NULL DEFAULT 0 COMMENT 'half day = 0.5',
  half_days INT NOT NULL DEFAULT 0,
  offday_worked DECIMAL(6,1) NOT NULL DEFAULT 0 COMMENT 'days worked on a weekly off / holiday',
  paid_leave_days DECIMAL(6,1) NOT NULL DEFAULT 0,
  unpaid_leave_days DECIMAL(6,1) NOT NULL DEFAULT 0,
  absent_days DECIMAL(6,1) NOT NULL DEFAULT 0 COMMENT 'incl. unpaid halves of half days',
  not_employed_days INT NOT NULL DEFAULT 0 COMMENT 'before joining / after leaving',
  upcoming_days INT NOT NULL DEFAULT 0 COMMENT 'days after today when the month is still running',
  missing_checkout_days INT NOT NULL DEFAULT 0,
  worked_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  per_day_rate DECIMAL(14,3) NOT NULL DEFAULT 0,
  overtime_rate DECIMAL(14,3) NOT NULL DEFAULT 0,
  basic_amount DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'monthly basic, or days x day rate, or hours x hour rate',
  housing_allowance DECIMAL(14,3) NOT NULL DEFAULT 0,
  transport_allowance DECIMAL(14,3) NOT NULL DEFAULT 0,
  food_allowance DECIMAL(14,3) NOT NULL DEFAULT 0,
  other_allowance DECIMAL(14,3) NOT NULL DEFAULT 0,
  overtime_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
  offday_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
  bonus_amount DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'bonus + allowance adjustments + sheet bonus',
  absent_deduction DECIMAL(14,3) NOT NULL DEFAULT 0,
  advance_deduction DECIMAL(14,3) NOT NULL DEFAULT 0,
  fixed_deduction DECIMAL(14,3) NOT NULL DEFAULT 0,
  other_deduction DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'deduction adjustments + sheet deduction',
  total_earnings DECIMAL(14,3) NOT NULL DEFAULT 0,
  total_deductions DECIMAL(14,3) NOT NULL DEFAULT 0,
  net_salary DECIMAL(14,3) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(14,3) NOT NULL DEFAULT 0,
  payment_status VARCHAR(10) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid / partial / paid',
  ovr_absent_days DECIMAL(6,1) NULL COMMENT 'manual override, kept on recalculation',
  ovr_worked_hours DECIMAL(8,2) NULL COMMENT 'manual override (hourly staff)',
  ovr_overtime_hours DECIMAL(8,2) NULL COMMENT 'manual override',
  sheet_bonus DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'typed on the payroll sheet',
  sheet_deduction DECIMAL(14,3) NOT NULL DEFAULT 0 COMMENT 'typed on the payroll sheet',
  note VARCHAR(255) NULL,
  days_json TEXT NULL COMMENT 'per-day status codes, for the payslip and the attendance sheet',
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_item (run_id, employee_id),
  KEY idx_payroll_item_emp (company_id, employee_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_payroll_payments (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  run_id INT NOT NULL,
  run_item_id INT NOT NULL,
  employee_id INT NOT NULL,
  amount DECIMAL(14,3) NOT NULL DEFAULT 0,
  payment_id INT NULL COMMENT 'tbl_payment_methods',
  pay_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  expense_id INT NULL COMMENT 'tbl_expenses row, if posted',
  outlet_id INT NULL,
  counter_id INT NOT NULL DEFAULT 0,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_payroll_pay_item (run_item_id),
  KEY idx_payroll_pay_emp (company_id, employee_id)
) ENGINE=InnoDB;
