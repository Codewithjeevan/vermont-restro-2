<?php
$currency = $this->session->userdata('currency');
$posted = $this->input->post('submit') ? true : false;
$v = function ($field, $default = '') use ($salary, $posted) {
    if ($posted) {
        return (string) $this->input->post($field);
    }
    return $salary && $salary->$field !== null ? $salary->$field : $default;
};
$amount = function ($field) use ($v) {
    $x = $v($field, '');
    return $x === '' || (float) $x == 0 ? '' : (string) (float) $x;
};
$type = $v('salary_type', 'monthly');
$offs = $posted ? (array) $this->input->post('weekly_off') : $this->Payroll_model->parseWeekdays($salary ? $salary->weekly_off : $default_weekly_off);
$status_on = $posted ? (bool) $this->input->post('status') : (!$salary || $salary->status == 'active');
$mode = $v('payment_mode', 'cash');
$weekdays = array(1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'salaries')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_salary_setup'); ?> &middot; <?php echo escape_output($employee->full_name); ?></h3>
            <div class="pay-sub"><?php echo escape_output($employee->designation); ?><?php echo $employee->phone ? ' &middot; ' . escape_output($employee->phone) : ''; ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box pay-form">
            <?php echo form_open(base_url('Payroll_setup/editSalary/' . (int) $employee->id), array('id' => 'paySalaryForm')); ?>
            <?php if (validation_errors() || $error): ?>
                <div class="pay-errors"><?php echo validation_errors(); ?><?php echo $error ? '<p>' . escape_output($error) . '</p>' : ''; ?></div>
            <?php endif; ?>

            <div class="pay-section-title"><?php echo lang('payroll_pay_structure'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_salary_type'); ?> <span class="required_star">*</span></label>
                        <select name="salary_type" class="form-control">
                            <?php foreach (Payroll_model::$salary_types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $type == $t ? 'selected' : ''; ?>><?php echo lang('payroll_type_' . $t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><span class="js-basic-label" data-monthly="<?php echo escape_output(lang('payroll_basic_monthly')); ?>" data-daily="<?php echo escape_output(lang('payroll_basic_daily')); ?>" data-hourly="<?php echo escape_output(lang('payroll_basic_hourly')); ?>"><?php echo lang('payroll_basic_salary'); ?></span> (<?php echo escape_output($currency); ?>) <span class="required_star">*</span></label>
                        <input type="text" name="basic_salary" class="form-control integerchk" value="<?php echo escape_output($amount('basic_salary')); ?>" placeholder="0">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_overtime_rate'); ?> (<?php echo escape_output($currency); ?>)</label>
                        <input type="text" name="overtime_rate" class="form-control integerchk" value="<?php echo escape_output($amount('overtime_rate')); ?>" placeholder="<?php echo escape_output(lang('payroll_auto')); ?>">
                        <div class="pay-muted"><?php echo lang('payroll_overtime_rate_hint'); ?></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('status'); ?></label>
                        <div class="pt-1"><label class="pay-check"><input type="checkbox" name="status" value="1" <?php echo $status_on ? 'checked' : ''; ?>> <?php echo lang('payroll_on_payroll'); ?></label></div>
                    </div>
                </div>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_monthly_allowances'); ?></div>
            <div class="row">
                <?php foreach (array('housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance') as $f): ?>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_' . $f); ?> (<?php echo escape_output($currency); ?>)</label>
                        <input type="text" name="<?php echo $f; ?>" class="form-control integerchk" value="<?php echo escape_output($amount($f)); ?>" placeholder="0">
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_fixed_deduction'); ?> (<?php echo escape_output($currency); ?>)</label>
                        <input type="text" name="fixed_deduction" class="form-control integerchk" value="<?php echo escape_output($amount('fixed_deduction')); ?>" placeholder="0">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-5">
                    <div class="form-group">
                        <label><?php echo lang('payroll_fixed_deduction_for'); ?></label>
                        <input type="text" name="fixed_deduction_note" class="form-control" maxlength="100" value="<?php echo escape_output($v('fixed_deduction_note')); ?>" placeholder="<?php echo escape_output(lang('payroll_fixed_deduction_example')); ?>">
                    </div>
                </div>
            </div>
            <div class="pay-preview mb-3">
                <span class="js-gross-note" data-type="monthly"><?php echo lang('payroll_preview_gross'); ?></span>
                <span class="js-gross-note" data-type="daily"><?php echo lang('payroll_preview_daily'); ?></span>
                <span class="js-gross-note" data-type="hourly"><?php echo lang('payroll_preview_hourly'); ?></span>
                <b><?php echo escape_output($currency); ?> <span class="js-gross"></span></b>
                &middot; <?php echo lang('payroll_preview_perday'); ?> <b><span class="js-perday"></span></b>
                &middot; <?php echo lang('payroll_preview_ot'); ?> <b><span class="js-ot"></span></b>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_work_calendar'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-lg-6">
                    <div class="form-group">
                        <label><?php echo lang('payroll_weekly_off'); ?></label>
                        <div class="pay-weekdays">
                            <?php foreach ($weekdays as $d => $name): ?>
                                <label><input type="checkbox" name="weekly_off[]" value="<?php echo $d; ?>" <?php echo in_array($d, $offs) ? 'checked' : ''; ?>><span><?php echo $name; ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="pay-muted"><?php echo lang('payroll_weekly_off_hint'); ?></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_joining_date'); ?></label>
                        <input type="text" name="joining_date" class="form-control customDatepicker" readonly value="<?php echo escape_output($v('joining_date')); ?>" placeholder="yyyy-mm-dd">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_leaving_date'); ?></label>
                        <input type="text" name="leaving_date" class="form-control customDatepicker" readonly value="<?php echo escape_output($v('leaving_date')); ?>" placeholder="yyyy-mm-dd">
                        <div class="pay-muted"><?php echo lang('payroll_leaving_hint'); ?></div>
                    </div>
                </div>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_payment_details'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('payroll_payment_mode'); ?></label>
                        <select name="payment_mode" id="pay_payment_mode" class="form-control">
                            <option value="cash" <?php echo $mode == 'cash' ? 'selected' : ''; ?>><?php echo lang('payroll_mode_cash'); ?></option>
                            <option value="bank" <?php echo $mode == 'bank' ? 'selected' : ''; ?>><?php echo lang('payroll_mode_bank'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3 js-bank-only">
                    <div class="form-group">
                        <label><?php echo lang('payroll_bank_name'); ?></label>
                        <input type="text" name="bank_name" class="form-control" maxlength="80" value="<?php echo escape_output($v('bank_name')); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3 js-bank-only">
                    <div class="form-group">
                        <label><?php echo lang('payroll_bank_account'); ?></label>
                        <input type="text" name="bank_account" class="form-control" maxlength="60" value="<?php echo escape_output($v('bank_account')); ?>">
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label><?php echo lang('note'); ?></label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?php echo escape_output($v('note')); ?>">
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?>Payroll_setup/salaries"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
