<?php
/*
 | Shared top of every AI Insight page: stylesheet, flash messages, module tabs.
 | Tabs carry data-access + menu_assign_class, so user_home_buttom.js removes the
 | ones the role cannot open (same rule as the sidebar).
 | $ai_tab = key of the active tab.
 */
$ai_tab = isset($ai_tab) ? $ai_tab : '';
$css_ver = @filemtime(FCPATH . 'frequent_changing/css/ai_insight.css');
$tabs = array();
if (ai_company_enabled()) {
    $tabs[] = array('insight', 'view-437', 'Ai_insight/index', lang('ai_insight'));
    if (checkAccess(441, 'update')) {
        $tabs[] = array('settings', 'update-441', 'Ai_insight/settings', lang('ai_settings'));
    }
}
if (ai_is_main_admin()) {
    $tabs[] = array('companies', 'view-437', 'Ai_insight/companies', lang('ai_companies'));
}
?>
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/ai_insight.css?v=<?php echo escape_output($css_ver); ?>">

<?php if (count($tabs) > 1): ?>
<div class="ai-tabs">
    <?php foreach ($tabs as $t): ?>
        <a class="menu_assign_class ai-tab<?php echo $t[0] == $ai_tab ? ' active' : ''; ?>" data-access="<?php echo $t[1]; ?>" data-menu__cid="irp_ai" href="<?php echo base_url() . $t[2]; ?>"><?php echo escape_output($t[3]); ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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
