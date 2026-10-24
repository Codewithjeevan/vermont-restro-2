<?php
/*
 | Shared top of every Payroll page: stylesheet, script, flash messages, module tabs.
 | Tabs carry data-access + menu_assign_class, so user_home_buttom.js removes the
 | ones the role cannot open (same rule as the sidebar); a group left without a
 | tab is removed too (check_main_menu).
 | $pay_tab = key of the active tab.
 */
$pay_tab = isset($pay_tab) ? $pay_tab : '';
$css_ver = @filemtime(FCPATH . 'frequent_changing/css/payroll.css');
$js_ver = @filemtime(FCPATH . 'frequent_changing/js/payroll.js');
// tab: key, data-access, url, label, screen key of config/payroll.php
$tab_groups = array(
    array('work', lang('payroll'), array(
        array('runs', 'view-412', 'Payroll/runs', lang('payroll_runs'), 'runs'),
        array('adjustments', 'view-421', 'Payroll_setup/adjustments', lang('payroll_adjustments'), 'adjustments'),
        array('leaves', 'view-425', 'Payroll_setup/leaves', lang('payroll_leaves'), 'leaves'),
    )),
    array('reports', lang('payroll_tab_reports'), array(
        array('attendance', 'view-433', 'Payroll/attendanceSheet', lang('payroll_attendance_sheet'), 'reports'),
        array('history', 'view-433', 'Payroll/salaryHistory', lang('payroll_salary_history'), 'reports'),
        array('advances', 'view-433', 'Payroll/advances', lang('payroll_advance_balances'), 'reports'),
    )),
    array('setup', lang('payroll_tab_setup'), array(
        array('salaries', 'view-418', 'Payroll_setup/salaries', lang('payroll_salary_setup'), 'salaries'),
        array('holidays', 'view-429', 'Payroll_setup/holidays', lang('payroll_holidays'), 'holidays'),
        array('settings', 'update-435', 'Payroll_setup/settings', lang('payroll_settings'), 'settings'),
    )),
);
// drop the screens switched off in config/payroll.php, then the groups left empty
foreach ($tab_groups as $gi => $g) {
    $tab_groups[$gi][2] = array_values(array_filter($g[2], function ($t) {
        return Payroll_model::screenOn($t[4]);
    }));
    if (!$tab_groups[$gi][2]) {
        unset($tab_groups[$gi]);
    }
}
$pay_settings = isset($payroll_settings) ? $payroll_settings : null;
?>
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/payroll.css?v=<?php echo escape_output($css_ver); ?>">
<script src="<?php echo base_url(); ?>frequent_changing/js/payroll.js?v=<?php echo escape_output($js_ver); ?>"></script>
<input type="hidden" id="pay_precision" value="<?php echo escape_output(getCompanyInfo()->precision); ?>">
<input type="hidden" id="pay_txt_are_you_sure" value="<?php echo escape_output(lang('are_you_sure')); ?>">
<?php if ($pay_settings): ?>
<input type="hidden" id="pay_standard_hours" value="<?php echo escape_output((float) $pay_settings->standard_hours); ?>">
<input type="hidden" id="pay_ot_multiplier" value="<?php echo escape_output((float) $pay_settings->overtime_multiplier); ?>">
<?php endif; ?>

<div class="pay-tabs">
    <?php foreach ($tab_groups as $g): ?>
        <ul class="pay-tab-group is-<?php echo $g[0]; ?> check_main_menu">
            <li class="pay-tab-label"><?php echo escape_output($g[1]); ?></li>
            <?php foreach ($g[2] as $t): ?>
                <li class="menu_assign_class<?php echo $t[0] == $pay_tab ? ' active' : ''; ?>" data-access="<?php echo $t[1]; ?>" data-menu__cid="irp_payroll">
                    <a href="<?php echo base_url() . $t[2]; ?>"><?php echo escape_output($t[3]); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>

<?php
// CI3 on PHP 8 never expires flashdata, so every view unsets a message once shown.
$flash_ok = $this->session->flashdata('exception');
$flash_er = $this->session->flashdata('exception_er');
unset($_SESSION['exception'], $_SESSION['exception_er']);
?>
<?php if ($flash_ok): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="alert-body"><p class="m-0"><i class="m-right fa fa-check"></i><?php echo escape_output($flash_ok); ?></p></div>
    </div>
<?php endif; ?>
<?php if ($flash_er): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="alert-body"><p class="m-0"><i class="m-right fa fa-times"></i><?php echo escape_output($flash_er); ?></p></div>
    </div>
<?php endif; ?>
