<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(398, 'add');
$can_delete = checkAccess(398, 'delete');
$total = 0;
$on_account = 0;
foreach ($extras as $e) {
    if ($e->status == 'cancelled') { continue; }
    $total += $e->total;
    if ($e->settle_type == 'on_account') { $on_account += $e->total; }
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'extras')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_extras'); ?></h3>
            <div class="mess-sub"><?php echo escape_output(date($date_format, strtotime($from)) . ($to != $from ? ' - ' . date($date_format, strtotime($to)) : '')); ?> &middot; <?php echo lang('total'); ?> <?php echo getAmtPCustom($total); ?> &middot; <?php echo lang('mess_on_account'); ?> <?php echo getAmtPCustom($on_account); ?></div>
        </div>
        <div class="mess-head-actions">
            <form method="get" action="<?php echo base_url(); ?>Mess_daily/extras" class="mess-filter">
                <input type="text" name="from" class="form-control customDatepicker" readonly value="<?php echo escape_output($from); ?>" style="width:130px">
                <input type="text" name="to" class="form-control customDatepicker" readonly value="<?php echo escape_output($to); ?>" style="width:130px">
                <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            </form>
            <?php if ($can_add): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess_daily/addExtra?date=<?php echo escape_output($from); ?>&back=extras"><i class="fa fa-plus"></i> <?php echo lang('mess_add_extra'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-card">
        <?php if (!$extras): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="mess-table">
                <thead>
                    <tr>
                        <th><?php echo lang('date'); ?></th>
                        <th><?php echo lang('mess_slot'); ?></th>
                        <th><?php echo lang('mess_member'); ?></th>
                        <th><?php echo lang('item'); ?></th>
                        <th class="mess-num"><?php echo lang('qty'); ?></th>
                        <th class="mess-num"><?php echo lang('total'); ?></th>
                        <th><?php echo lang('mess_settle'); ?></th>
                        <th><?php echo lang('status'); ?></th>
                        <th><?php echo lang('added_by'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($extras as $e): ?>
                    <tr>
                        <td><?php echo escape_output(date($date_format, strtotime($e->extra_date))); ?></td>
                        <td><?php echo isset($slots[$e->slot]) ? escape_output($slots[$e->slot]->name) : (int) $e->slot; ?></td>
                        <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $e->member_id; ?>"><?php echo escape_output($e->member_name); ?></a></td>
                        <td><?php echo escape_output($e->item_name); ?><?php echo $e->note ? '<div class="mess-muted">' . escape_output($e->note) . '</div>' : ''; ?></td>
                        <td class="mess-num"><?php echo (float) $e->qty; ?></td>
                        <td class="mess-num"><?php echo getAmtPCustom($e->total); ?></td>
                        <td><?php echo $e->settle_type == 'pay_now' ? lang('mess_pay_now') . ($e->payment_row_id ? ' <i class="fa fa-check" style="color:#1f9d55"></i>' : '') : lang('mess_on_account'); ?></td>
                        <td><span class="mess-badge <?php echo escape_output($e->status); ?>"><?php echo lang('mess_extra_status_' . $e->status); ?></span></td>
                        <td><?php echo escape_output($e->user_name); ?></td>
                        <td style="white-space:nowrap">
                            <?php if ($can_add && $e->status == 'requested'): ?>
                                <a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess_daily/extraStatus/<?php echo (int) $e->id; ?>/delivered"><i class="fa fa-check"></i> <?php echo lang('mess_mark_delivered'); ?></a>
                            <?php endif; ?>
                            <?php if ($can_add && $e->status != 'cancelled'): ?>
                                <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess_daily/extraStatus/<?php echo (int) $e->id; ?>/cancelled" title="<?php echo lang('mess_cancel_extra'); ?>"><i class="fa fa-ban"></i></a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                                <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess_daily/deleteExtra/<?php echo (int) $e->id; ?>" title="<?php echo lang('delete'); ?>"><i class="fa fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
