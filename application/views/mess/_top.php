<?php
/*
 | Shared top of every Mess page: stylesheet, flash messages, module tabs.
 | Tabs carry data-access + menu_assign_class, so user_home_buttom.js removes
 | the ones the role cannot open (same rule as the sidebar).
 | $mess_tab = key of the active tab.
 */
$mess_tab = isset($mess_tab) ? $mess_tab : '';
$css_ver = @filemtime(FCPATH . 'frequent_changing/css/mess.css');
$js_ver = @filemtime(FCPATH . 'frequent_changing/js/mess.js');
$tabs = array(
    array('today', 'view-371', 'Mess/today', lang('mess_today')),
    array('register', 'view-395', 'Mess_daily/register', lang('mess_meal_register')),
    array('menu', 'view-392', 'Mess_daily/menu', lang('mess_daily_menu')),
    array('sheet', 'view-402', 'Mess_daily/sheet', lang('mess_daily_sheet')),
    array('close', 'view-404', 'Mess_daily/dayClose', lang('mess_day_close')),
    array('extras', 'view-398', 'Mess_daily/extras', lang('mess_extras')),
    array('members', 'view-378', 'Mess/members', lang('mess_tab_members')),
    array('subscriptions', 'view-383', 'Mess/subscriptions', lang('mess_subscriptions')),
    array('expiring', 'view-383', 'Mess/expiring', lang('mess_tab_renewals')),
    array('payments', 'view-388', 'Mess/payments', lang('mess_tab_payments')),
    array('dues', 'view-388', 'Mess/dues', lang('mess_dues')),
    array('plans', 'view-373', 'Mess/plans', lang('mess_tab_plans')),
    array('reports', 'view-408', 'Mess_report/index', lang('mess_tab_reports')),
    array('settings', 'update-410', 'Mess/settings', lang('mess_tab_settings')),
);
?>
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/mess.css?v=<?php echo escape_output($css_ver); ?>">
<script src="<?php echo base_url(); ?>frequent_changing/js/mess.js?v=<?php echo escape_output($js_ver); ?>"></script>
<input type="hidden" id="mess_base_url" value="<?php echo base_url(); ?>">
<input type="hidden" id="mess_currency" value="<?php echo escape_output($this->session->userdata('currency')); ?>">
<input type="hidden" id="mess_precision" value="<?php echo escape_output(getCompanyInfo()->precision); ?>">
<input type="hidden" id="mess_txt_are_you_sure" value="<?php echo escape_output(lang('are_you_sure')); ?>">
<input type="hidden" id="mess_txt_copied" value="<?php echo escape_output(lang('mess_copied_to_clipboard')); ?>">

<ul class="mess-tabs">
    <?php foreach ($tabs as $t): ?>
        <li class="menu_assign_class<?php echo $t[0] == $mess_tab ? ' active' : ''; ?>" data-access="<?php echo $t[1]; ?>" data-menu__cid="irp_mess">
            <a href="<?php echo base_url() . $t[2]; ?>"><?php echo escape_output($t[3]); ?></a>
        </li>
    <?php endforeach; ?>
    <li><a href="<?php echo base_url(); ?>docs/mess_how_to_use.html" target="_blank" rel="noopener"><i class="fa fa-question-circle"></i> How to use</a></li>
</ul>

<?php
// CI3 on PHP 8 never expires flashdata ('old' < time() is false for a string), so every
// view in this app unsets a flash message once it is shown. Same here.
$flash_ok = $this->session->flashdata('exception');
$flash_er = $this->session->flashdata('exception_er');
$receipt_id = $this->session->flashdata('mess_print_receipt');
unset($_SESSION['exception'], $_SESSION['exception_er'], $_SESSION['mess_print_receipt']);
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
<?php if ($receipt_id): ?>
    <iframe class="mess-print-frame" title="receipt" style="position:absolute;width:0;height:0;border:0;left:-9999px"
            src="<?php echo base_url(); ?>Mess/paymentReceipt/<?php echo (int) $receipt_id; ?>?autoprint=1"></iframe>
<?php endif; ?>
