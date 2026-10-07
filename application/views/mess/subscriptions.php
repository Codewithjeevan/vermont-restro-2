<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(383, 'add');
$can_update = checkAccess(383, 'update');
$can_pay = checkAccess(388, 'add');
$filters = array(
    'current' => lang('mess_current'),
    'expiring' => lang('mess_status_expiring'),
    'upcoming' => lang('mess_status_upcoming'),
    'expired' => lang('mess_status_expired'),
    'cancelled' => lang('mess_status_cancelled'),
    '' => lang('all'),
);
$totals = array('persons' => 0, 'agreed' => 0, 'paid' => 0, 'due' => 0);
foreach ($subscriptions as $s) {
    if ($s->sub_status == 'cancelled') { continue; }
    $totals['agreed'] += $s->agreed_price;
    $totals['paid'] += $s->paid;
    $totals['due'] += max(0, $s->agreed_price - $s->paid);
    $totals['persons'] += $s->persons_total;
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'subscriptions')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_subscriptions'); ?></h3>
            <div class="mess-sub"><?php echo count($subscriptions); ?> &middot; <?php echo lang('mess_agreed_price'); ?> <?php echo getAmtPCustom($totals['agreed']); ?> &middot; <?php echo lang('mess_paid'); ?> <?php echo getAmtPCustom($totals['paid']); ?> &middot; <?php echo lang('due'); ?> <?php echo getAmtPCustom($totals['due']); ?></div>
        </div>
        <div class="mess-head-actions">
            <div class="mess-filter">
                <?php foreach ($filters as $k => $label): ?>
                    <a class="mess-btn <?php echo $filter === $k ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess/subscriptions?filter=<?php echo $k; ?>"><?php echo escape_output($label); ?></a>
                <?php endforeach; ?>
            </div>
            <?php if ($can_add): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess/addEditSubscription"><i class="fa fa-plus"></i> <?php echo lang('mess_new_subscription'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('mess_subscriptions'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('mess_member'); ?></th>
                            <th><?php echo lang('mess_meals'); ?></th>
                            <th><?php echo lang('mess_diet'); ?></th>
                            <th><?php echo lang('mess_mode'); ?></th>
                            <th><?php echo lang('start_date'); ?></th>
                            <th><?php echo lang('end_date'); ?></th>
                            <th class="text-end"><?php echo lang('mess_agreed_price'); ?></th>
                            <th class="text-end"><?php echo lang('mess_paid'); ?></th>
                            <th class="text-end"><?php echo lang('due'); ?></th>
                            <th><?php echo lang('status'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = count($subscriptions); foreach ($subscriptions as $s):
                            $due = $s->agreed_price - $s->paid; ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $i--; ?></td>
                            <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $s->member_id; ?>"><?php echo escape_output($s->member_name); ?></a>
                                <div class="mess-muted"><?php echo escape_output($s->member_code . ($s->phone ? ' · ' . $s->phone : '')); ?></div></td>
                            <td><?php echo escape_output($s->meals_text); ?></td>
                            <td><span class="mess-badge <?php echo escape_output($s->diet); ?>"><?php echo lang('mess_' . $s->diet); ?></span></td>
                            <td><?php echo lang('mess_' . $s->delivery_mode); ?></td>
                            <td data-order="<?php echo escape_output($s->start_date); ?>"><?php echo escape_output(date($date_format, strtotime($s->start_date))); ?></td>
                            <td data-order="<?php echo escape_output($s->end_date); ?>"><?php echo escape_output(date($date_format, strtotime($s->end_date))); ?></td>
                            <td class="mess-num"><?php echo getAmtPCustom($s->agreed_price); ?></td>
                            <td class="mess-num"><?php echo getAmtPCustom($s->paid); ?></td>
                            <td class="mess-num <?php echo $due > 0.0005 && $s->sub_status != 'cancelled' ? 'mess-due' : ''; ?>"><?php echo $s->sub_status == 'cancelled' ? '-' : getAmtPCustom(max(0, $due)); ?></td>
                            <td><span class="mess-badge <?php echo escape_output($s->sub_status); ?>"><?php echo lang('mess_status_' . $s->sub_status); ?></span>
                                <?php if ($s->renewed && $s->sub_status != 'cancelled'): ?> <span class="mess-badge"><?php echo lang('mess_renewed'); ?></span><?php endif; ?></td>
                            <td style="white-space:nowrap">
                                <?php if ($can_pay && $due > 0.0005 && $s->sub_status != 'cancelled'): ?>
                                    <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $s->member_id; ?>&subscription_id=<?php echo (int) $s->id; ?>&back=payments" title="<?php echo lang('mess_receive_payment'); ?>"><i class="fa fa-money"></i></a>
                                <?php endif; ?>
                                <?php if ($can_add && !$s->renewed && in_array($s->sub_status, array('active', 'expiring', 'expired'))): ?>
                                    <a class="mess-btn sm success" href="<?php echo base_url(); ?>Mess/addEditSubscription?renew_from=<?php echo (int) $s->id; ?>"><i class="fa fa-repeat"></i> <?php echo lang('mess_renew'); ?></a>
                                <?php endif; ?>
                                <?php if ($can_update && $s->sub_status != 'cancelled'): ?>
                                    <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addEditSubscription/<?php echo escape_output($this->custom->encrypt_decrypt($s->id, 'encrypt')); ?>"><i class="fa fa-pencil"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php $this->load->view('mess/_datatable'); ?>
