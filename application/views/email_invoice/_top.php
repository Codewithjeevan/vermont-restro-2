<?php
/*
 | Shared top of the Email Invoice pages: small stylesheet, tabs, flash messages.
 | Tabs carry data-access + menu_assign_class, so user_home_buttom.js removes the
 | ones the role cannot open (same rule as the sidebar).
 | $ei_tab = key of the active tab.
 */
$ei_tab = isset($ei_tab) ? $ei_tab : '';
$tabs = array(
    array('settings', 'update-443', 'Email_invoice/settings', lang('ei_email_settings')),
    array('log', 'view-443', 'Email_invoice/log', lang('ei_email_log')),
);
?>
<style>
    .ei-page .ei-tabs { display:flex; gap:6px; margin-bottom:14px; flex-wrap:wrap; }
    .ei-page .ei-tab { padding:7px 14px; border-radius:6px; background:#eef2f6; color:#334; text-decoration:none; font-size:14px; }
    .ei-page .ei-tab.active { background:#0c5889; color:#fff; }
    .ei-page .ei-head h3 { margin:0 0 4px 0; }
    .ei-page .ei-sub { color:#6b7280; font-size:13px; margin-bottom:14px; }
    .ei-page .ei-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:18px; margin-bottom:16px; }
    .ei-page .ei-card h4 { font-size:16px; margin:0 0 12px 0; }
    .ei-page .ei-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:14px 18px; }
    .ei-page .ei-field label { display:block; font-weight:600; font-size:13px; margin-bottom:4px; }
    .ei-page .ei-muted { color:#6b7280; font-size:12px; margin-top:4px; }
    .ei-page .ei-help { background:#f0f7ff; border-left:3px solid #0c5889; padding:8px 12px; font-size:13px; border-radius:4px; margin:10px 0 0 0; }
    .ei-page .ei-status { display:inline-block; padding:2px 9px; border-radius:10px; font-size:12px; font-weight:600; white-space:nowrap; }
    .ei-page .ei-status.sent, .ei-page .ei-status.on { background:#dcfce7; color:#166534; }
    .ei-page .ei-status.failed, .ei-page .ei-status.off { background:#fee2e2; color:#991b1b; }
    .ei-page .ei-status.pending, .ei-page .ei-status.sending { background:#fef3c7; color:#92400e; }
    .ei-page .ei-switch { display:flex; align-items:center; gap:10px; font-weight:600; cursor:pointer; }
    .ei-page .ei-switch input { width:20px; height:20px; }
    .ei-page .ei-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .ei-page .ei-result { margin-top:10px; font-size:13px; }
    .ei-page .ei-result.ok { color:#166534; }
    .ei-page .ei-result.err { color:#991b1b; }
    .ei-page .ei-error-text { color:#991b1b; font-size:12px; max-width:380px; word-break:break-word; }
    .ei-page details summary { cursor:pointer; font-weight:600; margin-bottom:10px; }
</style>

<div class="ei-tabs">
    <?php foreach ($tabs as $t): ?>
        <a class="menu_assign_class ei-tab<?php echo $t[0] == $ei_tab ? ' active' : ''; ?>" data-access="<?php echo $t[1]; ?>" data-menu__cid="irp_32" href="<?php echo base_url() . $t[2]; ?>"><?php echo escape_output($t[3]); ?></a>
    <?php endforeach; ?>
</div>

<?php
// CI3 on PHP 8 never expires flashdata, so every view unsets a message once shown.
$flash_ok = $this->session->flashdata('exception');
$flash_er = $this->session->flashdata('exception_1');
unset($_SESSION['exception'], $_SESSION['exception_1']);
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
