<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Payroll screens that are switched on
| -------------------------------------------------------------------------
| Kept simple for now (Oct 2026): only Salary Setup and Monthly Payroll
| (generate, correct a line, approve, pay, payslips, payroll register).
|
| The other screens are built and tested but hidden. Sidebar, POS menu,
| module tabs and the routes themselves all follow this list. To show one
| again, add its key here AND set its tbl_access rows back to Live so it
| returns to the Role screen (ids in Update/payroll_module_migration.sql):
|
|   'adjustments'  Advances & Adjustments              tbl_access 421-424
|   'leaves'       Leaves                              tbl_access 425-428
|   'holidays'     Holidays                            tbl_access 429-432
|   'reports'      Attendance Sheet, Salary History,
|                  Advance Balances                    tbl_access 433-434
|   'settings'     Payroll Settings                    tbl_access 435-436
|
| While 'settings' is hidden the payroll uses the defaults of
| tbl_payroll_settings (see docs/payroll_module.md).
*/
$config['payroll_screens'] = array('runs', 'salaries');
