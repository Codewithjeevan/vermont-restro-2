<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Payroll screens that are switched on
| -------------------------------------------------------------------------
| All screens are on (Oct 2026). Sidebar, POS menu, module tabs and the
| routes themselves all follow this list. To hide a screen, take its key
| out here AND set its tbl_access rows to Deleted so it leaves the Role
| screen too (ids in Update/payroll_module_migration.sql):
|
|   'runs'         Monthly Payroll (generate, approve,
|                  pay, payslips, payroll register)    tbl_access 412-417
|   'salaries'     Salary Setup                        tbl_access 418-420
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
$config['payroll_screens'] = array('runs', 'salaries', 'adjustments', 'leaves', 'holidays', 'reports', 'settings');
