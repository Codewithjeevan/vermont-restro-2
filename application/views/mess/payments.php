<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(388, 'add');
$can_delete = checkAccess(388, 'delete');
$type_label = array('payment' => lang('payment'), 'refund' => lang('refund'), 'adjustment' => lang('mess_adjustment'));
$grand = 0;
foreach ($by_method as $bm) {
    $grand += $bm->total;
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'payments')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_payments'); ?></h3>
            <div class="mess-sub"><?php echo escape_output(date($date_format, strtotime($from)) . ' - ' . date($date_format, strtotime($to))); ?></div>
        </div>
        <div class="mess-head-actions">
            <form method="get" action="<?php echo base_url(); ?>Mess/payments" class="mess-filter">
                <input type="text" name="from" class="form-control customDatepicker" readonly value="<?php echo escape_output($from); ?>" style="width:130px">
                <input type="text" name="to" class="form-control customDatepicker" readonly value="<?php echo escape_output($to); ?>" style="width:130px">
                <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            </form>
            <?php if ($can_add): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess/addPayment?back=payments"><i class="fa fa-plus"></i> <?php echo lang('mess_receive_payment'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-tiles">
        <div class="mess-tile good">
            <div class="num"><?php echo getAmtPCustom($grand); ?></div>
            <div class="lbl"><?php echo lang('mess_collection'); ?></div>
            <div class="hint"><?php echo escape_output($this->session->userdata('currency')); ?></div>
        </div>
        <?php foreach ($by_method as $bm): ?>
            <div class="mess-tile">
                <div class="num"><?php echo getAmtPCustom($bm->total); ?></div>
                <div class="lbl"><?php echo escape_output($bm->payment_name); ?></div>
                <div class="hint"><?php echo (int) $bm->cnt; ?> <?php echo lang('mess_payments_count'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('mess_payments'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('ref_no'); ?></th>
                            <th><?php echo lang('date'); ?></th>
                            <th><?php echo lang('mess_member'); ?></th>
                            <th><?php echo lang('type'); ?></th>
                            <th><?php echo lang('payment_method'); ?></th>
                            <th class="text-end"><?php echo lang('amount'); ?></th>
                            <th><?php echo lang('note'); ?></th>
                            <th><?php echo lang('added_by'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = count($payments); foreach ($payments as $p): ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $i--; ?></td>
                            <td><?php echo escape_output($p->reference_no); ?></td>
                            <td data-order="<?php echo escape_output($p->date_time); ?>"><?php echo escape_output(date($date_format, strtotime($p->only_date))); ?>
                                <div class="mess-muted"><?php echo escape_output(date('h:i A', strtotime($p->date_time))); ?></div></td>
                            <td><a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $p->member_id; ?>"><?php echo escape_output($p->member_name); ?></a></td>
                            <td><?php echo escape_output($type_label[$p->type]); ?><?php echo $p->extra_id ? ' (' . lang('mess_extra') . ')' : ''; ?></td>
                            <td><?php echo escape_output($p->payment_name); ?></td>
                            <td class="mess-num <?php echo $p->type == 'refund' ? 'mess-due' : ''; ?>"><?php echo ($p->type == 'refund' ? '-' : '') . getAmtPCustom($p->amount); ?></td>
                            <td><?php echo escape_output($p->note); ?></td>
                            <td><?php echo escape_output($p->user_name); ?></td>
                            <td style="white-space:nowrap">
                                <a class="mess-btn sm" target="_blank" href="<?php echo base_url(); ?>Mess/paymentReceipt/<?php echo (int) $p->id; ?>" title="<?php echo lang('mess_print_receipt'); ?>"><i class="fa fa-print"></i></a>
                                <?php if ($can_delete): ?>
                                    <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess/deletePayment/<?php echo escape_output($this->custom->encrypt_decrypt($p->id, 'encrypt')); ?>"><i class="fa fa-trash"></i></a>
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
