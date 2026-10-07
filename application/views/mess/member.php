<?php
$date_format = $this->session->userdata('date_format');
$fmt = function ($d) use ($date_format) {
    return $d ? date($date_format, strtotime($d)) : '';
};
$today = date('Y-m-d');
$can_edit_member = checkAccess(378, 'update');
$can_sub_add = checkAccess(383, 'add');
$can_sub_update = checkAccess(383, 'update');
$can_sub_delete = checkAccess(383, 'delete');
$can_pay = checkAccess(388, 'add');
$can_pay_delete = checkAccess(388, 'delete');
$can_skip = checkAccess(395, 'update');
$can_extra = checkAccess(398, 'add');
$balance = (float) $member->balance;
$att = array();
foreach ($attendance_30 as $a) {
    $att[$a->status] = (int) $a->persons;
}
$status_label = function ($s) {
    return lang('mess_status_' . $s);
};
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'members')); ?>

    <div class="mess-card">
        <div class="mess-profile">
            <div class="mess-avatar"><?php echo escape_output(mb_strtoupper(mb_substr($member->name, 0, 1))); ?></div>
            <div class="who">
                <div class="nm"><?php echo escape_output($member->name); ?>
                    <?php if ($member->status == 'inactive'): ?><span class="mess-badge inactive"><?php echo lang('mess_status_inactive'); ?></span><?php endif; ?>
                </div>
                <div class="kv">
                    <span><?php echo escape_output($member->member_code); ?></span>
                    <?php if ($member->phone): ?><span><i class="fa fa-phone"></i> <?php echo escape_output($member->phone); ?><?php echo $member->phone2 ? ' / ' . escape_output($member->phone2) : ''; ?></span><?php endif; ?>
                    <?php if ($member->building_room): ?><span><i class="fa fa-building-o"></i> <?php echo escape_output($member->building_room); ?></span><?php endif; ?>
                    <span class="mess-badge <?php echo escape_output($member->diet_pref); ?>"><?php echo lang('mess_' . $member->diet_pref); ?></span>
                    <span><?php echo lang('mess_' . $member->delivery_mode); ?></span>
                </div>
                <?php if ($member->note): ?><div class="mess-muted mt-1"><?php echo escape_output($member->note); ?></div><?php endif; ?>
            </div>
            <div class="mess-balance">
                <?php if ($balance > 0.0005): ?>
                    <div class="amt mess-due"><?php echo getAmtPCustom($balance); ?></div>
                    <div class="mess-muted"><?php echo lang('mess_balance_due'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>)</div>
                <?php elseif ($balance < -0.0005): ?>
                    <div class="amt mess-credit"><?php echo getAmtPCustom(-$balance); ?></div>
                    <div class="mess-muted"><?php echo lang('mess_advance_credit'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>)</div>
                <?php else: ?>
                    <div class="amt"><?php echo getAmtPCustom(0); ?></div>
                    <div class="mess-muted"><?php echo lang('balance'); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mess-cycle-actions mt-3">
            <?php if ($can_pay): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $member->id; ?>"><i class="fa fa-money"></i> <?php echo lang('mess_receive_payment'); ?></a>
            <?php endif; ?>
            <?php if ($can_sub_add): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess/addEditSubscription?member_id=<?php echo (int) $member->id; ?>"><i class="fa fa-calendar-plus-o"></i> <?php echo lang('mess_new_subscription'); ?></a>
            <?php endif; ?>
            <?php if ($can_extra): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/addExtra?member_id=<?php echo (int) $member->id; ?>"><i class="fa fa-cutlery"></i> <?php echo lang('mess_add_extra'); ?></a>
            <?php endif; ?>
            <?php if ($can_skip): ?>
                <a class="mess-btn" href="#" data-mess-toggle="#mess_skip_form"><i class="fa fa-ban"></i> <?php echo lang('mess_skip_ahead'); ?></a>
            <?php endif; ?>
            <a class="mess-btn" target="_blank" href="<?php echo base_url(); ?>Mess/memberStatement/<?php echo (int) $member->id; ?>"><i class="fa fa-print"></i> <?php echo lang('mess_print_statement'); ?></a>
            <?php if ($whatsapp_due): ?>
                <a class="mess-btn whatsapp" target="_blank" rel="noopener" href="<?php echo escape_output($whatsapp_due); ?>"><i class="fa fa-whatsapp"></i> <?php echo lang('mess_whatsapp_reminder'); ?></a>
            <?php endif; ?>
            <?php if ($can_edit_member): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess/addEditMember/<?php echo escape_output($this->custom->encrypt_decrypt($member->id, 'encrypt')); ?>"><i class="fa fa-pencil"></i> <?php echo lang('edit'); ?></a>
            <?php endif; ?>
        </div>
        <?php if ($can_skip): ?>
            <form class="mess-pause-form" id="mess_skip_form" method="post" action="<?php echo base_url(); ?>Mess_daily/skip">
                <input type="hidden" name="member_id" value="<?php echo (int) $member->id; ?>">
                <div class="mess-muted mb-2"><?php echo lang('mess_skip_ahead_hint'); ?></div>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-3">
                        <label><?php echo lang('date'); ?></label>
                        <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="col-sm-6 col-md-5">
                        <label><?php echo lang('mess_slot'); ?></label>
                        <div>
                            <?php foreach ($slots as $s): ?>
                                <label class="mess-check"><input type="checkbox" name="slots[]" value="<?php echo (int) $s->slot; ?>" <?php echo $s->slot == 3 ? 'checked' : ''; ?>> <?php echo escape_output($s->name); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <button type="submit" class="mess-btn primary w-100"><?php echo lang('submit'); ?></button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="mess-grid-2">
        <div>
            <div class="mess-card">
                <h4><?php echo lang('mess_subscriptions'); ?></h4>
                <?php if (!$subscriptions): ?>
                    <div class="mess-muted"><?php echo lang('mess_no_subscription'); ?></div>
                <?php endif; ?>
                <?php foreach (array_reverse($subscriptions) as $s):
                    $due = (float) $s->agreed_price - (float) $s->paid;
                    $total_days = max(1, (strtotime($s->end_date) - strtotime($s->start_date)) / 86400 + 1);
                    $done_days = $today < $s->start_date ? 0 : min($total_days, (strtotime(min($today, $s->end_date)) - strtotime($s->start_date)) / 86400 + 1);
                    $paused_now = false;
                    foreach ($s->pauses as $pz) {
                        if ($today >= $pz->from_date && $today <= $pz->to_date) { $paused_now = true; }
                    }
                    $days_left = (strtotime($s->end_date) - strtotime($today)) / 86400;
                ?>
                <div class="mess-cycle">
                    <div class="mess-cycle-head">
                        <span class="dates"><?php echo escape_output($fmt($s->start_date)); ?> &rarr; <?php echo escape_output($fmt($s->end_date)); ?></span>
                        <span>
                            <span class="mess-badge <?php echo escape_output($s->sub_status); ?>"><?php echo $status_label($s->sub_status); ?></span>
                            <?php if ($paused_now): ?><span class="mess-badge skipped"><?php echo lang('mess_status_paused'); ?></span><?php endif; ?>
                            <?php if ($s->renewed && $s->sub_status != 'cancelled'): ?><span class="mess-badge"><?php echo lang('mess_renewed'); ?></span><?php endif; ?>
                        </span>
                    </div>
                    <div class="mess-cycle-meta">
                        <span><b><?php echo escape_output($s->meals_text); ?></b></span>
                        <span class="mess-badge <?php echo escape_output($s->diet); ?>"><?php echo lang('mess_' . $s->diet); ?></span>
                        <span><?php echo lang('mess_' . $s->delivery_mode); ?></span>
                        <?php if ($s->plan_name): ?><span><?php echo escape_output($s->plan_name); ?></span><?php endif; ?>
                        <?php if ($s->paused_days): ?><span><?php echo lang('mess_paused_days'); ?>: <?php echo (int) $s->paused_days; ?></span><?php endif; ?>
                        <?php if ($s->sub_status == 'active' || $s->sub_status == 'expiring'): ?>
                            <span><?php echo $days_left == 0 ? lang('mess_ends_today') : lang('mess_ends_in') . ' ' . (int) $days_left . ' ' . lang('days'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mess-cycle-meta">
                        <span><?php echo lang('mess_agreed_price'); ?>: <b><?php echo getAmtPCustom($s->agreed_price); ?></b><?php echo $s->price_note ? ' (' . escape_output($s->price_note) . ')' : ''; ?></span>
                        <span><?php echo lang('mess_paid'); ?>: <b><?php echo getAmtPCustom($s->paid); ?></b></span>
                        <?php if ($s->sub_status != 'cancelled'): ?>
                            <span class="<?php echo $due > 0.0005 ? 'mess-due' : 'mess-credit'; ?>"><?php echo $due > 0.0005 ? lang('due') . ': ' . getAmtPCustom($due) : lang('mess_paid'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($s->sub_status != 'cancelled' && $s->sub_status != 'upcoming'): ?>
                        <div class="mess-progress"><span style="width: <?php echo round($done_days / $total_days * 100); ?>%"></span></div>
                    <?php endif; ?>
                    <?php if ($s->pauses): ?>
                        <div class="mess-muted mt-2"><?php echo lang('mess_pauses'); ?>:
                            <?php foreach ($s->pauses as $pz): ?>
                                <span class="mess-badge skipped"><?php echo escape_output($fmt($pz->from_date)); ?> - <?php echo escape_output($fmt($pz->to_date)); ?> (<?php echo (int) $pz->days; ?>)<?php echo $pz->reason ? ' ' . escape_output($pz->reason) : ''; ?>
                                <?php if ($can_sub_update && $s->sub_status != 'cancelled'): ?>
                                    <a class="mess-confirm" href="<?php echo base_url(); ?>Mess/deletePause/<?php echo (int) $pz->id; ?>" title="<?php echo lang('delete'); ?>">&times;</a>
                                <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($s->sub_status != 'cancelled'): ?>
                    <div class="mess-cycle-actions">
                        <?php if ($can_pay && $due > 0.0005): ?>
                            <a class="mess-btn sm primary" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $member->id; ?>&subscription_id=<?php echo (int) $s->id; ?>"><i class="fa fa-money"></i> <?php echo lang('mess_receive_payment'); ?></a>
                        <?php endif; ?>
                        <?php if ($can_sub_add && !$s->renewed && in_array($s->sub_status, array('active', 'expiring', 'expired'))): ?>
                            <a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess/addEditSubscription?renew_from=<?php echo (int) $s->id; ?>"><i class="fa fa-repeat"></i> <?php echo lang('mess_renew'); ?></a>
                        <?php endif; ?>
                        <?php if ($can_sub_update): ?>
                            <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addEditSubscription/<?php echo escape_output($this->custom->encrypt_decrypt($s->id, 'encrypt')); ?>"><i class="fa fa-pencil"></i> <?php echo lang('edit'); ?></a>
                            <?php if ($s->sub_status != 'expired'): ?>
                                <a class="mess-btn sm" href="#" data-mess-toggle="#mess_pause_<?php echo (int) $s->id; ?>"><i class="fa fa-pause"></i> <?php echo lang('mess_pause'); ?></a>
                            <?php endif; ?>
                            <a class="mess-btn sm danger mess-confirm" data-confirm="<?php echo escape_output(lang('mess_cancel_confirm')); ?>" href="<?php echo base_url(); ?>Mess/cancelSubscription/<?php echo escape_output($this->custom->encrypt_decrypt($s->id, 'encrypt')); ?>"><i class="fa fa-ban"></i> <?php echo lang('mess_cancel_subscription'); ?></a>
                        <?php endif; ?>
                        <?php if ($can_sub_delete): ?>
                            <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess/deleteSubscription/<?php echo escape_output($this->custom->encrypt_decrypt($s->id, 'encrypt')); ?>"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($can_sub_update && $s->sub_status != 'expired'): ?>
                        <form class="mess-pause-form" id="mess_pause_<?php echo (int) $s->id; ?>" method="post" action="<?php echo base_url(); ?>Mess/addPause">
                            <input type="hidden" name="subscription_id" value="<?php echo (int) $s->id; ?>">
                            <div class="mess-muted mb-2"><?php echo lang('mess_pause_hint'); ?></div>
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-6 col-md-3">
                                    <label><?php echo lang('start_date'); ?></label>
                                    <input type="text" name="from_date" class="form-control customDatepicker" readonly value="<?php echo escape_output(max($today, $s->start_date)); ?>">
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label><?php echo lang('end_date'); ?></label>
                                    <input type="text" name="to_date" class="form-control customDatepicker" readonly value="<?php echo escape_output(max($today, $s->start_date)); ?>">
                                </div>
                                <div class="col-sm-8 col-md-4">
                                    <label><?php echo lang('note'); ?></label>
                                    <input type="text" name="reason" class="form-control" maxlength="150">
                                </div>
                                <div class="col-sm-4 col-md-2">
                                    <button type="submit" class="mess-btn primary w-100"><?php echo lang('submit'); ?></button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mess-card">
                <h4><?php echo lang('mess_attendance_last_30'); ?></h4>
                <div class="mess-filter">
                    <span class="mess-badge served"><?php echo lang('mess_served'); ?>: <?php echo (isset($att['served']) ? $att['served'] : 0) + (isset($att['partial']) ? $att['partial'] : 0); ?></span>
                    <span class="mess-badge not_taken"><?php echo lang('mess_not_taken'); ?>: <?php echo isset($att['not_taken']) ? $att['not_taken'] : 0; ?></span>
                    <span class="mess-badge skipped"><?php echo lang('mess_skipped'); ?>: <?php echo isset($att['skipped']) ? $att['skipped'] : 0; ?></span>
                    <span class="mess-badge pending"><?php echo lang('mess_pending'); ?>: <?php echo isset($att['pending']) ? $att['pending'] : 0; ?></span>
                </div>
            </div>
        </div>

        <div class="mess-card">
            <h4><?php echo lang('mess_ledger'); ?></h4>
            <?php if (!$ledger): ?>
                <div class="mess-muted"><?php echo lang('mess_no_entries'); ?></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="mess-table">
                    <thead>
                        <tr>
                            <th><?php echo lang('date'); ?></th>
                            <th><?php echo lang('details'); ?></th>
                            <th class="mess-num"><?php echo lang('mess_charge'); ?></th>
                            <th class="mess-num"><?php echo lang('mess_paid'); ?></th>
                            <th class="mess-num"><?php echo lang('balance'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($ledger) as $l): ?>
                        <tr>
                            <td style="white-space:nowrap"><?php echo escape_output($fmt($l->date)); ?></td>
                            <td><?php echo escape_output($l->text); ?><div class="mess-muted"><?php echo escape_output(userName($l->user_id)); ?></div></td>
                            <td class="mess-num"><?php echo $l->charge ? getAmtPCustom($l->charge) : ''; ?></td>
                            <td class="mess-num"><?php echo $l->credit ? getAmtPCustom($l->credit) : ''; ?></td>
                            <td class="mess-num <?php echo $l->balance > 0.0005 ? 'mess-due' : ''; ?>"><?php echo getAmtPCustom($l->balance); ?></td>
                            <td style="white-space:nowrap">
                                <?php if ($l->kind == 'payment'): ?>
                                    <a class="mess-btn sm" target="_blank" href="<?php echo base_url(); ?>Mess/paymentReceipt/<?php echo (int) $l->id; ?>" title="<?php echo lang('mess_print_receipt'); ?>"><i class="fa fa-print"></i></a>
                                    <?php if ($can_pay_delete): ?>
                                        <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess/deletePayment/<?php echo escape_output($this->custom->encrypt_decrypt($l->id, 'encrypt')); ?>?back=member" title="<?php echo lang('delete'); ?>"><i class="fa fa-trash"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
