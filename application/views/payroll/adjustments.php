<?php
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(421, 'add');
$can_delete = checkAccess(421, 'delete');
$settings = $payroll_settings;
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'adjustments')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_adjustments'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_adjustments_hint'); ?></div>
        </div>
    </div>

    <?php if ($can_add): ?>
    <div class="pay-card pay-form">
        <h4><?php echo lang('payroll_add_adjustment'); ?></h4>
        <?php echo form_open(base_url('Payroll_setup/addAdjustment')); ?>
        <div class="row">
            <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                <label><?php echo lang('employee'); ?> <span class="required_star">*</span></label>
                <select name="employee_id" class="form-control select2">
                    <option value=""><?php echo lang('select'); ?></option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?php echo (int) $e->id; ?>" <?php echo $employee_id == $e->id ? 'selected' : ''; ?>><?php echo escape_output($e->full_name); ?><?php echo $e->designation ? ' (' . escape_output($e->designation) . ')' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-2 form-group">
                <label><?php echo lang('type'); ?> <span class="required_star">*</span></label>
                <select name="adj_type" id="pay_adj_type" class="form-control">
                    <?php foreach (Payroll_model::$adjustment_types as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo lang('payroll_adj_' . $t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-2 form-group">
                <label><?php echo lang('amount'); ?> (<?php echo escape_output($currency); ?>) <span class="required_star">*</span></label>
                <input type="text" name="amount" class="form-control integerchk" placeholder="0">
            </div>
            <div class="col-sm-12 col-md-6 col-lg-2 form-group">
                <label><?php echo lang('date'); ?></label>
                <input type="text" name="adj_date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                <label><?php echo lang('payroll_pay_month'); ?> <span class="required_star">*</span></label>
                <select name="pay_month" class="form-control">
                    <?php foreach ($pay_months as $m => $label): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == ($month ?: date('Y-m')) ? 'selected' : ''; ?>><?php echo escape_output($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="pay-muted"><?php echo lang('payroll_pay_month_hint'); ?></div>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-2 form-group js-advance-only">
                <label><?php echo lang('payroll_installments'); ?></label>
                <input type="number" name="installments" class="form-control" min="1" max="24" value="1">
                <div class="pay-muted"><?php echo lang('payroll_installments_hint'); ?></div>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-3 form-group js-advance-only">
                <label><?php echo lang('payroll_given_by'); ?> <span class="required_star">*</span></label>
                <select name="payment_id" class="form-control">
                    <?php foreach ($payment_methods as $pm): ?>
                        <option value="<?php echo (int) $pm->id; ?>"><?php echo escape_output($pm->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-12 col-lg-7 form-group js-advance-only">
                <label class="d-block">&nbsp;</label>
                <label class="pay-check"><input type="checkbox" name="record_expense" value="1" class="js-record-expense" <?php echo $settings->record_expense ? 'checked' : ''; ?>> <?php echo lang('payroll_record_expense'); ?></label>
                <label class="pay-check"><input type="checkbox" name="from_till" value="1" class="js-from-till"> <?php echo lang('payroll_from_till'); ?></label>
                <div class="pay-muted"><?php echo $register_open ? lang('payroll_from_till_hint') : lang('payroll_from_till_closed'); ?></div>
            </div>
            <div class="col-sm-12 col-lg-9 form-group">
                <label><?php echo lang('note'); ?></label>
                <input type="text" name="note" class="form-control" maxlength="255">
            </div>
            <div class="col-sm-12 col-lg-3 form-group">
                <label class="d-block">&nbsp;</label>
                <button type="submit" class="pay-btn primary w-100 justify-content-center"><i class="fa fa-plus"></i> <?php echo lang('add'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
    <?php endif; ?>

    <form method="get" action="<?php echo base_url(); ?>Payroll_setup/adjustments" class="pay-filter">
        <div>
            <label><?php echo lang('payroll_pay_month'); ?></label>
            <select name="month" class="form-control js-autosubmit">
                <option value=""><?php echo lang('payroll_all_months'); ?></option>
                <?php foreach ($pay_months as $m => $label): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo escape_output($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?php echo lang('employee'); ?></label>
            <select name="employee_id" class="form-control js-autosubmit">
                <option value=""><?php echo lang('payroll_all_employees'); ?></option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?php echo (int) $e->id; ?>" <?php echo $employee_id == $e->id ? 'selected' : ''; ?>><?php echo escape_output($e->full_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?php echo lang('type'); ?></label>
            <select name="type" class="form-control js-autosubmit">
                <option value=""><?php echo lang('payroll_all_types'); ?></option>
                <?php foreach (Payroll_model::$adjustment_types as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $type == $t ? 'selected' : ''; ?>><?php echo lang('payroll_adj_' . $t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_adjustments'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th><?php echo lang('date'); ?></th>
                            <th><?php echo lang('employee'); ?></th>
                            <th><?php echo lang('type'); ?></th>
                            <th class="text-end"><?php echo lang('amount'); ?></th>
                            <th><?php echo lang('payroll_pay_month'); ?></th>
                            <th><?php echo lang('payroll_recovery'); ?></th>
                            <th><?php echo lang('note'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($adjustments as $a): ?>
                        <tr>
                            <td data-order="<?php echo escape_output($a->adj_date) . sprintf('%08d', $a->id); ?>"><?php echo escape_output(date($date_format, strtotime($a->adj_date))); ?></td>
                            <td class="name"><?php echo escape_output($a->full_name); ?></td>
                            <td><span class="pay-badge <?php echo $a->adj_type; ?>"><?php echo lang('payroll_adj_' . $a->adj_type); ?></span></td>
                            <td class="pay-num <?php echo $a->adj_type == 'deduction' ? 'pay-neg' : ''; ?>"><?php echo getAmtPCustom($a->amount); ?></td>
                            <td><?php echo escape_output($this->Payroll_model->monthLabel($a->pay_month)); ?></td>
                            <td>
                                <?php if ($a->adj_type == 'advance'): ?>
                                    <?php if ($a->installments > 1): ?>
                                        <?php echo sprintf(lang('payroll_n_installments'), (int) $a->installments, getAmtPCustom($a->amount / $a->installments)); ?>
                                        <span class="pay-muted d-block"><?php echo escape_output(date('M Y', strtotime($a->pay_month . '-01'))); ?> &ndash; <?php echo escape_output(date('M Y', strtotime($this->Payroll_model->addMonths($a->pay_month, $a->installments - 1) . '-01'))); ?></span>
                                    <?php else: ?>
                                        <?php echo lang('payroll_one_installment'); ?>
                                    <?php endif; ?>
                                    <span class="pay-muted d-block"><?php echo escape_output($a->payment_name); ?><?php echo $a->expense_id ? ' &middot; ' . lang('payroll_posted') : ''; ?></span>
                                <?php else: ?>
                                    <span class="pay-muted"><?php echo $a->adj_type == 'deduction' ? lang('payroll_deducted') : lang('payroll_added'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_output($a->note); ?></td>
                            <td>
                                <?php if ($can_delete): ?>
                                    <a class="pay-btn sm danger pay-confirm" href="<?php echo base_url(); ?>Payroll_setup/deleteAdjustment/<?php echo (int) $a->id; ?>"><i class="fa fa-trash"></i></a>
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
<?php $this->load->view('payroll/_datatable'); ?>
