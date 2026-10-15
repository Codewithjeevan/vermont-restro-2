# Payroll module

Monthly salary calculated from employee attendance. Sidebar menu **Payroll** (after Attendance).

## Simple mode (current)

For now only **Monthly Payroll** and **Salary Setup** are switched on. Monthly
Payroll covers generate, correcting a line, approve, pay, payslips and the register.
The switch is `application/config/payroll.php` (`$config['payroll_screens']`).
Sidebar, POS menu, module tabs and routes all follow it, and a hidden screen is
closed even for Admin. The migration also sets the hidden screens' `tbl_access`
rows (421-436) to `Deleted`, so they are not on the Role screen either.

While hidden:
- **Advances & Adjustments, Leaves, Holidays**: nothing is recorded, so nothing
  is added or deducted. For a one-off bonus or fine, use the pencil on the payroll
  line (sheet bonus / sheet deduction).
- **Payroll Settings**: the defaults of `tbl_payroll_settings` apply.
  - Per-day rate = salary / days in the month.
  - Absence is deducted from gross.
  - A full day is 9 h; under 4 h is a half day.
  - A day without check-out counts as a full day.
  - Overtime is paid at x1.25 above 9 h; under 30 min is ignored.
  - Work on a weekly off is paid as an extra day.
- **Reports** (attendance sheet, salary history, advance balances): not shown.
  The Advance column is left out of the sheet and the register.

To switch a screen back on, add its key to `config/payroll.php` and set its
`tbl_access` rows back to `'Live'`. The keys and ids are listed in that file.

## Deploy

1. Run `Update/payroll_module_migration.sql` once on the database (phpMyAdmin). It adds
   `tbl_access` rows 412-436 (main module 13 `payroll`) and the `tbl_payroll_*` tables.
2. Admin sees the menu at once. Other roles: tick the Payroll permissions in
   **Account and User > Role**, then the user logs out and in again (`function_access`
   is built at login).

## Daily use (once set up)

1. **Salary Setup**: per employee, salary type, basic / rate, monthly allowances
   (housing, transport, food, other), fixed deduction, overtime rate (empty = auto),
   weekly off days, joining / leaving date, cash or bank. Only employees with an
   active setup are on the payroll. Employees are `tbl_users`; staff without a login
   are users with *Will login = No*, and their attendance is entered by hand.
2. Through the month: **Leaves** (paid / unpaid), **Holidays**, **Advances & Adjustments**
   (salary advance with installments, bonus, extra allowance, deduction/fine).
3. **Monthly Payroll > Generate** for a month. It starts as a **draft**. A draft is
   recalculated from attendance every time it is opened, so fixing attendance shows
   at once. The pencil on a line overrides absent days / worked hours / overtime hours
   and adds a sheet bonus or deduction. Overrides survive recalculation; *Reset to
   attendance* clears them.
4. **Approve**. This locks the month: leaves, holidays and adjustments of an approved
   month cannot be added or deleted.
5. **Pay**, per line (partial payments allowed) or **Pay all**. *Add to Expenses* writes
   a `tbl_expenses` row (category *Salary & Wages*, created automatically).
   *Paid from the cash till* also stamps the counter, so the open register counts it.
   That option needs an open register. Deleting a payment deletes its expense.
   **Reopen** works only while the month has no payments.
6. Print: payslip per employee, all payslips, payroll register (landscape).

Reports: **Attendance Sheet** (employee x day grid, same reading as the payroll),
**Salary History** (one employee, one year), **Advance Balances**.

## How a day is read (`Payroll_model::dayStatus`)

Each day of the month gets one code:

| Code | Meaning | Rule |
|---|---|---|
| X | not employed | before the joining date or after the leaving date |
| P / H | present / half day | there are `tbl_attendance` rows. Hours = out - in (shift past midnight adds 24h). Under *half day hours* it is a half day |
| M | present, no check-out | there is an in time but no out time (`00:00:00`). Counted as a full or half day per Settings, standard hours, no overtime |
| W | worked on a weekly off or holiday | attendance on such a day |
| HO / O | holiday / weekly off | no attendance |
| L / U | paid / unpaid leave | no attendance |
| F | upcoming | the day is after today (current month) |
| A | absent | everything else |

A half day adds 0.5 to absent days. Overtime is the hours above *hours of a full day*
on that day, when it is at least *ignore overtime below* minutes.

## Money (`Payroll_model::computeItem`)

- **Monthly**: basic + allowances in full, then minus **absence** = per-day rate x
  (absent + unpaid leave + days not employed). The per-day rate is (gross or basic, per
  Settings) / (days in month, 30, or working days, per Settings). Work on an off day
  or holiday adds one per-day rate (Settings). Overtime rate = basic / basis days /
  standard hours x multiplier, unless the employee has a fixed overtime rate.
  A month with no present, leave or upcoming day deducts the full base.
- **Daily**: day rate x (present + off-day work + paid leave). Allowances are added as
  monthly amounts. Overtime rate = day rate / standard hours x multiplier.
- **Hourly**: hour rate x (worked hours - overtime hours + paid leave days x standard
  hours). Overtime rate = hour rate x multiplier.
- All types: + bonus / allowance adjustments + sheet bonus; - advance installment,
  fixed deduction, deduction adjustments, sheet deduction.
- An advance of N installments deducts amount / N per month from its payroll month.
  The last installment takes the rounding.
  *Recovered* = advance deductions in **approved** payrolls only.

## Code

| Part | File |
|---|---|
| Migration | `Update/payroll_module_migration.sql` |
| Engine + data | `application/models/Payroll_model.php` |
| Runs, payments, print, reports | `application/controllers/Payroll.php` |
| Salary setup, leaves, holidays, adjustments, settings | `application/controllers/Payroll_setup.php` |
| Views | `application/views/payroll/` (`_top.php` = tabs + flash) |
| CSS / JS | `frequent_changing/css/payroll.css`, `frequent_changing/js/payroll.js` (plain JS, no jQuery plugins) |
| Language | `application/language/*/payroll_lang.php` (autoloaded; Common_model loads the user's language) |
| Menu | `application/views/userHome.php` (both layouts), `application/views/sale/POS/main_screen.php` |

Permissions (`tbl_access` parent ids, hard-coded in the controllers):
412 Monthly Payroll (view / add = generate+edit / approve / pay / delete),
418 Salary Setup (view / update), 421 Advances & Adjustments (view / add / delete),
425 Leaves, 429 Holidays (view / add / delete), 433 Payroll Reports (view),
435 Payroll Settings (update).

## Known limits

- Attendance comes from login / logout (`Authentication`) and *Add Attendance*. A logout
  after midnight updates nothing, because `logOut()` looks for *today's* row. Such a
  day shows as **M** (no check-out). Fix the out time in Attendance if the hours matter.
- There is no late-arrival deduction, because there is no shift start time. Use a
  sheet deduction or a deduction adjustment.
- Payroll is per company, not per outlet. Expenses are posted to the outlet that is
  selected when paying.
