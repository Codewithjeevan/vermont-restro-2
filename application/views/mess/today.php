<?php
$date_format = $this->session->userdata('date_format');
$currency = $this->session->userdata('currency');
$dues_total = 0;
foreach ($dues as $d) {
    $dues_total += $d->balance;
}
$collected = 0;
$collected_parts = array();
foreach ($collection_today as $c) {
    $collected += $c->total;
    $collected_parts[] = $c->payment_name . ' ' . getAmtPCustom($c->total);
}
$extras_count = 0;
$extras_account = 0;
foreach ($extras_today as $e) {
    if ($e->status == 'cancelled') { continue; }
    $extras_count++;
    if ($e->settle_type == 'on_account') { $extras_account += $e->total; }
}
$persons_active = 0;
foreach ($current_subscriptions as $s) {
    $persons_active += $s->persons_total;
}
$can_register = checkAccess(395, 'view');
$can_menu = checkAccess(392, 'view');
$cur = isset($headcount[$current_slot]) ? $headcount[$current_slot] : null;
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'today')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess'); ?> &middot; <?php echo escape_output(date('l', strtotime($today))); ?> <?php echo escape_output(date($date_format, strtotime($today))); ?></h3>
            <div class="mess-sub"><?php echo lang('outlet'); ?>: <?php echo escape_output(getOutletNameById($this->session->userdata('outlet_id'))); ?></div>
        </div>
        <div class="mess-head-actions">
            <?php if ($can_register): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $today; ?>/<?php echo (int) $current_slot; ?>"><i class="fa fa-check-square-o"></i> <?php echo lang('mess_open_register'); ?></a>
            <?php endif; ?>
            <?php if (checkAccess(388, 'add')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess/addPayment"><i class="fa fa-money"></i> <?php echo lang('mess_receive_payment'); ?></a>
            <?php endif; ?>
            <?php if (checkAccess(383, 'add')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess/addEditSubscription"><i class="fa fa-calendar-plus-o"></i> <?php echo lang('mess_new_subscription'); ?></a>
            <?php endif; ?>
            <?php if (checkAccess(398, 'add')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/addExtra"><i class="fa fa-cutlery"></i> <?php echo lang('mess_add_extra'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-tiles">
        <?php foreach ($slots as $slot):
            $h = $headcount[$slot->slot]; ?>
            <a class="mess-tile <?php echo $slot->slot == $current_slot ? 'good' : ''; ?>" href="<?php echo $can_register ? base_url() . 'Mess_daily/register/' . $today . '/' . (int) $slot->slot : '#'; ?>">
                <div class="num"><?php echo (int) $h->expected; ?></div>
                <div class="lbl"><?php echo escape_output($slot->name); ?> <?php echo lang('mess_expected_today'); ?></div>
                <div class="hint"><?php echo lang('mess_nonveg'); ?> <?php echo (int) $h->nonveg; ?> &middot; <?php echo lang('mess_veg'); ?> <?php echo (int) $h->veg; ?>
                    <?php if ($h->served || $h->not_taken): ?><br><?php echo lang('mess_served'); ?> <?php echo (int) $h->served; ?> &middot; <?php echo lang('mess_pending'); ?> <?php echo (int) $h->pending; ?><?php endif; ?>
                    <?php if ($h->skipped): ?><br><?php echo lang('mess_skipped'); ?> <?php echo (int) $h->skipped; ?><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if ($cur): ?>
            <div class="mess-tile">
                <div class="num"><?php echo (int) $cur->pickup; ?> / <?php echo (int) $cur->delivery; ?></div>
                <div class="lbl"><?php echo lang('mess_pickup'); ?> / <?php echo lang('mess_delivery'); ?></div>
                <div class="hint"><?php echo escape_output($slots[$current_slot]->name); ?></div>
            </div>
        <?php endif; ?>
        <a class="mess-tile" href="<?php echo base_url(); ?>Mess_daily/extras">
            <div class="num"><?php echo $extras_count; ?></div>
            <div class="lbl"><?php echo lang('mess_extras_today'); ?></div>
            <div class="hint"><?php echo lang('mess_on_account'); ?> <?php echo escape_output($currency); ?> <?php echo getAmtPCustom($extras_account); ?></div>
        </a>
        <a class="mess-tile <?php echo $expiring ? 'warn' : ''; ?>" href="<?php echo base_url(); ?>Mess/expiring">
            <div class="num"><?php echo count($expiring); ?></div>
            <div class="lbl"><?php echo lang('mess_expiring_list'); ?></div>
            <div class="hint"><?php echo $expiring ? escape_output($expiring[0]->member_name) . ' ' . escape_output(date($date_format, strtotime($expiring[0]->end_date))) : '&nbsp;'; ?>
                <?php if ($expired_not_renewed): ?><br><?php echo count($expired_not_renewed); ?> <?php echo lang('mess_expired_not_renewed'); ?><?php endif; ?></div>
        </a>
        <a class="mess-tile <?php echo $dues_total > 0 ? 'bad' : ''; ?>" href="<?php echo base_url(); ?>Mess/dues">
            <div class="num"><?php echo getAmtPCustom($dues_total); ?></div>
            <div class="lbl"><?php echo lang('mess_total_dues'); ?> (<?php echo escape_output($currency); ?>)</div>
            <div class="hint"><?php echo count($dues); ?> <?php echo lang('mess_members_with_due'); ?></div>
        </a>
        <a class="mess-tile good" href="<?php echo base_url(); ?>Mess/payments?from=<?php echo $today; ?>&to=<?php echo $today; ?>">
            <div class="num"><?php echo getAmtPCustom($collected); ?></div>
            <div class="lbl"><?php echo lang('mess_collection_today'); ?> (<?php echo escape_output($currency); ?>)</div>
            <div class="hint"><?php echo $collected_parts ? escape_output(implode(' · ', $collected_parts)) : '&nbsp;'; ?></div>
        </a>
        <a class="mess-tile" href="<?php echo base_url(); ?>Mess/subscriptions">
            <div class="num"><?php echo count($current_subscriptions); ?></div>
            <div class="lbl"><?php echo lang('mess_subscriptions'); ?> (<?php echo lang('mess_current'); ?>)</div>
            <div class="hint"><?php echo (int) $persons_active; ?> <?php echo lang('mess_persons'); ?></div>
        </a>
    </div>

    <div class="mess-grid-2">
        <div class="mess-card">
            <h4><?php echo lang('mess_menu_today'); ?>
                <?php if ($can_menu): ?><a class="mess-btn sm" href="<?php echo base_url(); ?>Mess_daily/menu/<?php echo $today; ?>"><i class="fa fa-pencil"></i> <?php echo lang('mess_daily_menu'); ?></a><?php endif; ?>
            </h4>
            <?php foreach ($slots as $slot):
                $h = $headcount[$slot->slot];
                foreach (array('nonveg', 'veg') as $diet):
                    $expected = $diet == 'veg' ? $h->veg : $h->nonveg;
                    $dishes = isset($menu[$slot->slot][$diet]) ? $menu[$slot->slot][$diet] : array();
                    if (!$dishes && !$expected) { continue; } ?>
                    <div class="mb-2">
                        <span class="name"><?php echo escape_output($slot->name); ?></span>
                        <span class="mess-badge <?php echo $diet; ?>"><?php echo lang('mess_' . $diet); ?></span>
                        <?php if ($dishes):
                            $names = array();
                            foreach ($dishes as $d) { $names[] = $d->dish_name; } ?>
                            <?php echo escape_output(implode(' · ', $names)); ?>
                        <?php else: ?>
                            <span class="mess-due"><?php echo lang('mess_menu_missing'); ?> (<?php echo (int) $expected; ?> <?php echo lang('mess_expected_today'); ?>)</span>
                        <?php endif; ?>
                    </div>
            <?php endforeach; endforeach; ?>
        </div>

        <div class="mess-card">
            <h4><?php echo lang('mess_needs_attention'); ?></h4>
            <?php if (!$expiring && !$expired_not_renewed && !$dues && !$not_closed): ?>
                <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
            <?php endif; ?>
            <table class="mess-table">
                <tbody>
                <?php foreach ($not_closed as $nc): ?>
                    <tr>
                        <td class="name"><?php echo escape_output($nc->name); ?> &middot; <?php echo escape_output(date($date_format, strtotime($nc->date))); ?></td>
                        <td><span class="mess-badge pending"><?php echo lang('mess_not_closed'); ?></span></td>
                        <td class="mess-num"><a class="mess-btn sm primary" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo $nc->date; ?>/<?php echo (int) $nc->slot; ?>"><?php echo lang('mess_day_close'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach (array_slice($expiring, 0, 5) as $s): ?>
                    <tr>
                        <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $s->member_id; ?>"><?php echo escape_output($s->member_name); ?></a></td>
                        <td><span class="mess-badge expiring"><?php echo lang('mess_status_expiring'); ?></span> <?php echo escape_output(date($date_format, strtotime($s->end_date))); ?></td>
                        <td class="mess-num"><?php if (checkAccess(383, 'add')): ?><a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess/addEditSubscription?renew_from=<?php echo (int) $s->id; ?>"><?php echo lang('mess_renew'); ?></a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach (array_slice(array_reverse($expired_not_renewed), 0, 5) as $s): ?>
                    <tr>
                        <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $s->member_id; ?>"><?php echo escape_output($s->member_name); ?></a></td>
                        <td><span class="mess-badge expired"><?php echo lang('mess_status_expired'); ?></span> <?php echo escape_output(date($date_format, strtotime($s->end_date))); ?></td>
                        <td class="mess-num"><?php if (checkAccess(383, 'add')): ?><a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess/addEditSubscription?renew_from=<?php echo (int) $s->id; ?>"><?php echo lang('mess_renew'); ?></a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach (array_slice($dues, 0, 5) as $d): ?>
                    <tr>
                        <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $d->id; ?>"><?php echo escape_output($d->name); ?></a></td>
                        <td><?php echo lang('due'); ?> <span class="mess-due"><?php echo getAmtPCustom($d->balance); ?></span></td>
                        <td class="mess-num"><?php if (checkAccess(388, 'add')): ?><a class="mess-btn sm primary" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $d->id; ?>"><?php echo lang('payment'); ?></a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
