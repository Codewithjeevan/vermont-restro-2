<?php
$s = $payroll_settings;
$posted = $this->input->post('submit') ? true : false;
$v = function ($field) use ($s, $posted) {
    return $posted ? (string) $this->input->post($field) : $s->$field;
};
$on = function ($field) use ($s, $posted) {
    return ($posted ? $this->input->post($field) : $s->$field) ? 'checked' : '';
};
$num = function ($x) {
    return (string) (float) $x;
};
$offs = $posted ? (array) $this->input->post('default_weekly_off') : $this->Payroll_model->parseWeekdays($s->default_weekly_off);
$weekdays = array(1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'settings')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_settings'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_settings_hint'); ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box pay-form">
            <?php echo form_open(base_url('Payroll_setup/settings')); ?>
            <?php if (validation_errors()): ?>
                <div class="pay-errors"><?php echo validation_errors(); ?></div>
            <?php endif; ?>

            <div class="pay-section-title"><?php echo lang('payroll_monthly_salary_rules'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-4 form-group">
                    <label><?php echo lang('payroll_month_days_basis'); ?></label>
                    <select name="month_days_basis" class="form-control">
                        <?php foreach (array('calendar', 'fixed30', 'working') as $b): ?>
                            <option value="<?php echo $b; ?>" <?php echo $v('month_days_basis') == $b ? 'selected' : ''; ?>><?php echo lang('payroll_basis_' . $b); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pay-muted"><?php echo lang('payroll_month_days_basis_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4 form-group">
                    <label><?php echo lang('payroll_absent_deduction_on'); ?></label>
                    <select name="absent_deduction_on" class="form-control">
                        <option value="gross" <?php echo $v('absent_deduction_on') == 'gross' ? 'selected' : ''; ?>><?php echo lang('payroll_on_gross'); ?></option>
                        <option value="basic" <?php echo $v('absent_deduction_on') == 'basic' ? 'selected' : ''; ?>><?php echo lang('payroll_on_basic'); ?></option>
                    </select>
                    <div class="pay-muted"><?php echo lang('payroll_absent_deduction_on_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4 form-group">
                    <label class="d-block">&nbsp;</label>
                    <label class="pay-check"><input type="checkbox" name="pay_offday_work" value="1" <?php echo $on('pay_offday_work'); ?>> <?php echo lang('payroll_pay_offday_work'); ?></label>
                    <div class="pay-muted"><?php echo lang('payroll_pay_offday_work_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-lg-8 form-group">
                    <label><?php echo lang('payroll_default_weekly_off'); ?></label>
                    <div class="pay-weekdays">
                        <?php foreach ($weekdays as $d => $name): ?>
                            <label><input type="checkbox" name="default_weekly_off[]" value="<?php echo $d; ?>" <?php echo in_array($d, $offs) ? 'checked' : ''; ?>><span><?php echo $name; ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="pay-muted"><?php echo lang('payroll_default_weekly_off_hint'); ?></div>
                </div>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_attendance_rules'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                    <label><?php echo lang('payroll_standard_hours'); ?> <span class="required_star">*</span></label>
                    <input type="text" name="standard_hours" class="form-control integerchk" value="<?php echo escape_output($num($v('standard_hours'))); ?>">
                    <div class="pay-muted"><?php echo lang('payroll_standard_hours_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                    <label><?php echo lang('payroll_half_day_hours'); ?> <span class="required_star">*</span></label>
                    <input type="text" name="half_day_hours" class="form-control integerchk" value="<?php echo escape_output($num($v('half_day_hours'))); ?>">
                    <div class="pay-muted"><?php echo lang('payroll_half_day_hours_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-6 form-group">
                    <label><?php echo lang('payroll_missing_checkout_rule'); ?></label>
                    <select name="missing_checkout" class="form-control">
                        <option value="full" <?php echo $v('missing_checkout') == 'full' ? 'selected' : ''; ?>><?php echo lang('payroll_full_day'); ?></option>
                        <option value="half" <?php echo $v('missing_checkout') == 'half' ? 'selected' : ''; ?>><?php echo lang('payroll_half_day'); ?></option>
                    </select>
                    <div class="pay-muted"><?php echo lang('payroll_missing_checkout_rule_hint'); ?></div>
                </div>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_overtime'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                    <label class="d-block">&nbsp;</label>
                    <label class="pay-check"><input type="checkbox" name="overtime_enabled" value="1" <?php echo $on('overtime_enabled'); ?>> <?php echo lang('payroll_overtime_enabled'); ?></label>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                    <label><?php echo lang('payroll_overtime_multiplier'); ?> <span class="required_star">*</span></label>
                    <input type="text" name="overtime_multiplier" class="form-control integerchk" value="<?php echo escape_output($num($v('overtime_multiplier'))); ?>">
                    <div class="pay-muted"><?php echo lang('payroll_overtime_multiplier_hint'); ?></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                    <label><?php echo lang('payroll_overtime_min_minutes'); ?> <span class="required_star">*</span></label>
                    <input type="number" min="0" max="600" name="overtime_min_minutes" class="form-control" value="<?php echo (int) $v('overtime_min_minutes'); ?>">
                    <div class="pay-muted"><?php echo lang('payroll_overtime_min_minutes_hint'); ?></div>
                </div>
            </div>

            <div class="pay-section-title"><?php echo lang('payroll_payments_expenses'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-4 form-group">
                    <label class="d-block">&nbsp;</label>
                    <label class="pay-check"><input type="checkbox" name="record_expense" value="1" <?php echo $on('record_expense'); ?>> <?php echo lang('payroll_record_expense_default'); ?></label>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4 form-group">
                    <label><?php echo lang('payroll_expense_category'); ?></label>
                    <select name="expense_category_id" class="form-control">
                        <option value=""><?php echo lang('payroll_expense_category_auto'); ?></option>
                        <?php foreach ($expense_categories as $c): ?>
                            <option value="<?php echo (int) $c->id; ?>" <?php echo $v('expense_category_id') == $c->id ? 'selected' : ''; ?>><?php echo escape_output($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-8 form-group">
                    <label><?php echo lang('payroll_payslip_footer'); ?></label>
                    <input type="text" name="payslip_footer" class="form-control" maxlength="255" value="<?php echo escape_output($v('payslip_footer')); ?>" placeholder="<?php echo escape_output(lang('payroll_payslip_footer_example')); ?>">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
