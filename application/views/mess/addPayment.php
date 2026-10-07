<?php
$posted = $this->input->post('submit') ? true : false;
$type = $posted ? $this->input->post('type') : 'payment';
$direction = $posted ? $this->input->post('direction') : 'credit';
$pm_sel = $posted ? (int) $this->input->post('payment_id') : (isset($payment_methods[0]) ? (int) $payment_methods[0]->id : 0);
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'payments')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_receive_payment'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_collection_register_hint'); ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box mess-form">
            <?php echo form_open(base_url('Mess/addPayment'), array('id' => 'mess_payment_form', 'data-subscription' => (int) $preselect_subscription)); ?>
            <input type="hidden" name="back" value="<?php echo escape_output($back); ?>">
            <?php if (!empty($errors)): ?>
                <div class="mess-errors"><ul><?php foreach ($errors as $e): ?><li><?php echo escape_output($e); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_member'); ?> <span class="required_star">*</span></label>
                        <select name="member_id" id="member_id" class="form-control select2">
                            <option value=""><?php echo lang('select'); ?></option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo (int) $m->id; ?>" <?php echo (int) $preselect_member == $m->id ? 'selected' : ''; ?>>
                                    <?php echo escape_output($m->name . ' (' . $m->member_code . ')' . ($m->phone ? ' ' . $m->phone : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-1" id="mess_member_info" style="display:none"
                             data-due="<?php echo escape_output(lang('mess_balance_due')); ?>"
                             data-credit="<?php echo escape_output(lang('mess_advance_credit')); ?>"
                             data-zero="<?php echo escape_output(lang('balance') . ' ' . getAmtPCustom(0)); ?>"></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_against_subscription'); ?></label>
                        <select name="subscription_id" id="subscription_id" class="form-control select2">
                            <option value="0"><?php echo lang('mess_general_advance'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_payment_type'); ?></label>
                        <select name="type" class="form-control select2">
                            <option value="payment" <?php echo $type == 'payment' ? 'selected' : ''; ?>><?php echo lang('mess_type_payment'); ?></option>
                            <?php if ($can_refund): ?>
                                <option value="refund" <?php echo $type == 'refund' ? 'selected' : ''; ?>><?php echo lang('mess_type_refund'); ?></option>
                                <option value="adjustment" <?php echo $type == 'adjustment' ? 'selected' : ''; ?>><?php echo lang('mess_type_adjustment'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('amount'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>) <span class="required_star">*</span></label>
                        <input type="text" name="amount" class="form-control integerchk" onfocus="this.select();" value="<?php echo escape_output($posted ? $this->input->post('amount') : ''); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4" id="mess_method_row">
                    <div class="form-group">
                        <label><?php echo lang('payment_method'); ?> <span class="required_star">*</span></label>
                        <select name="payment_id" class="form-control select2">
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?php echo (int) $pm->id; ?>" <?php echo $pm_sel == $pm->id ? 'selected' : ''; ?>><?php echo escape_output($pm->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4" id="mess_direction_row" style="display:none">
                    <div class="form-group">
                        <label><?php echo lang('mess_adjustment'); ?></label>
                        <select name="direction" class="form-control">
                            <option value="credit" <?php echo $direction == 'credit' ? 'selected' : ''; ?>><?php echo lang('mess_adjust_credit'); ?></option>
                            <option value="debit" <?php echo $direction == 'debit' ? 'selected' : ''; ?>><?php echo lang('mess_adjust_debit'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('date'); ?></label>
                        <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo escape_output($posted ? $this->input->post('date') : date('Y-m-d')); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-8">
                    <div class="form-group">
                        <label><?php echo lang('note'); ?></label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?php echo escape_output($posted ? $this->input->post('note') : ''); ?>" placeholder="170 card, 180 card">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <div class="col-sm-12 col-md-3 mb-2">
                    <button type="submit" name="submit" value="print" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?> + <?php echo lang('mess_print_receipt'); ?></button>
                </div>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?><?php echo $preselect_member && $back != 'payments' ? 'Mess/member/' . (int) $preselect_member : 'Mess/payments'; ?>"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
