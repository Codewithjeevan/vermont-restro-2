<?php
$date_format = $this->session->userdata('date_format');
$can_pay = checkAccess(388, 'add');
$total = 0;
foreach ($dues as $d) {
    $total += $d->balance;
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'dues')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_dues'); ?></h3>
            <div class="mess-sub"><?php echo count($dues); ?> <?php echo lang('mess_members_with_due'); ?> &middot; <?php echo lang('mess_total_dues'); ?> <b class="mess-due"><?php echo escape_output($this->session->userdata('currency')); ?> <?php echo getAmtPCustom($total); ?></b></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('mess_dues'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('mess_member'); ?></th>
                            <th><?php echo lang('phone'); ?></th>
                            <th><?php echo lang('mess_building_room'); ?></th>
                            <th><?php echo lang('mess_last_cycle_end'); ?></th>
                            <th><?php echo lang('mess_last_payment'); ?></th>
                            <th class="text-end"><?php echo lang('mess_balance_due'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = count($dues); foreach ($dues as $d): ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $i--; ?></td>
                            <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $d->id; ?>"><?php echo escape_output($d->name); ?></a>
                                <div class="mess-muted"><?php echo escape_output($d->member_code); ?></div></td>
                            <td><?php echo escape_output($d->phone); ?></td>
                            <td><?php echo escape_output($d->building_room); ?></td>
                            <td><?php echo $d->last_end_date ? escape_output(date($date_format, strtotime($d->last_end_date))) : '-'; ?></td>
                            <td><?php echo $d->last_payment_date ? escape_output(date($date_format, strtotime($d->last_payment_date))) : '-'; ?></td>
                            <td class="mess-num mess-due" data-order="<?php echo (float) $d->balance; ?>"><?php echo getAmtPCustom($d->balance); ?></td>
                            <td style="white-space:nowrap">
                                <?php if ($can_pay): ?>
                                    <a class="mess-btn sm primary" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $d->id; ?>"><i class="fa fa-money"></i> <?php echo lang('mess_receive_payment'); ?></a>
                                <?php endif; ?>
                                <?php if (!empty($d->whatsapp)): ?>
                                    <a class="mess-btn sm whatsapp" target="_blank" rel="noopener" href="<?php echo escape_output($d->whatsapp); ?>"><i class="fa fa-whatsapp"></i></a>
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
