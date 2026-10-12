<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(383, 'add');
$today = date('Y-m-d');
$row = function ($s, $expired) use ($date_format, $can_add, $today) {
    $days = (int) round((strtotime($s->end_date) - strtotime($today)) / 86400);
    $due = $s->agreed_price - $s->paid;
    ob_start(); ?>
    <tr data-search="<?php echo escape_output($s->member_name . ' ' . $s->phone . ' ' . $s->member_code); ?>">
        <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $s->member_id; ?>"><?php echo escape_output($s->member_name); ?></a>
            <div class="mess-muted"><?php echo escape_output($s->member_code . ($s->phone ? ' · ' . $s->phone : '')); ?></div></td>
        <td><?php echo escape_output($s->meals_text); ?></td>
        <td><?php echo escape_output(date($date_format, strtotime($s->end_date))); ?>
            <div class="mess-muted"><?php echo $expired ? lang('mess_ended') . ' ' . abs($days) . ' ' . lang('days') : ($days == 0 ? lang('mess_ends_today') : lang('mess_ends_in') . ' ' . $days . ' ' . lang('days')); ?></div></td>
        <td class="mess-num"><?php echo getAmtPCustom($s->agreed_price); ?></td>
        <td class="mess-num <?php echo $due > 0.0005 ? 'mess-due' : ''; ?>"><?php echo getAmtPCustom(max(0, $due)); ?></td>
        <td style="white-space:nowrap">
            <?php if ($can_add): ?>
                <a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess/addEditSubscription?renew_from=<?php echo (int) $s->id; ?>"><i class="fa fa-repeat"></i> <?php echo lang('mess_renew'); ?></a>
            <?php endif; ?>
            <?php if (!empty($s->whatsapp)): ?>
                <a class="mess-btn sm whatsapp" target="_blank" rel="noopener" href="<?php echo escape_output($s->whatsapp); ?>"><i class="fa fa-whatsapp"></i> <?php echo lang('mess_whatsapp'); ?></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php return ob_get_clean();
};
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'expiring')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_expiring'); ?></h3>
        </div>
        <div class="mess-head-actions">
            <form method="get" action="<?php echo base_url(); ?>Mess/expiring" class="mess-filter">
                <label class="mess-muted m-0"><?php echo lang('mess_within_days'); ?></label>
                <input type="number" min="0" max="60" name="days" value="<?php echo (int) $days; ?>" class="form-control" style="width:80px">
                <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            </form>
        </div>
    </div>

    <div class="mess-card">
        <h4><?php echo lang('mess_expiring_list'); ?> <span class="mess-badge expiring"><?php echo count($expiring); ?></span></h4>
        <?php if (!$expiring): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="mess-table">
                <thead><tr>
                    <th><?php echo lang('mess_member'); ?></th><th><?php echo lang('mess_meals'); ?></th><th><?php echo lang('end_date'); ?></th>
                    <th class="mess-num"><?php echo lang('mess_agreed_price'); ?></th><th class="mess-num"><?php echo lang('due'); ?></th><th></th>
                </tr></thead>
                <tbody><?php foreach ($expiring as $s) { echo $row($s, false); } ?></tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="mess-card">
        <h4><?php echo lang('mess_expired_not_renewed'); ?> <span class="mess-badge expired"><?php echo count($expired); ?></span></h4>
        <div class="mess-muted mb-2"><?php echo lang('mess_expired_not_renewed_hint'); ?></div>
        <?php if ($expired): ?>
        <div class="table-responsive">
            <table class="mess-table">
                <thead><tr>
                    <th><?php echo lang('mess_member'); ?></th><th><?php echo lang('mess_meals'); ?></th><th><?php echo lang('end_date'); ?></th>
                    <th class="mess-num"><?php echo lang('mess_agreed_price'); ?></th><th class="mess-num"><?php echo lang('due'); ?></th><th></th>
                </tr></thead>
                <tbody><?php foreach (array_reverse($expired) as $s) { echo $row($s, true); } ?></tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
