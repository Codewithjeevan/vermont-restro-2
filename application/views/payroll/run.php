<?php
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
$is_draft = $run->status == 'draft';
$can_edit = $is_draft && checkAccess(412, 'add');
$can_approve = checkAccess(412, 'approve');
$can_pay = !$is_draft && checkAccess(412, 'pay');
$can_delete = $is_draft && checkAccess(412, 'delete');
$s = $payroll_settings;
$num = function ($v) {
    $v = (float) $v;
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
};

$tot = array('basic' => 0, 'allow' => 0, 'extra' => 0, 'bonus' => 0, 'earn' => 0, 'absent' => 0, 'advance' => 0, 'other' => 0, 'ded' => 0, 'net' => 0, 'paid' => 0);
$upcoming = 0;
$missing_checkout = 0;
$negative = 0;
$salary_modes = array();
foreach ($items as $i) {
    $tot['basic'] += $i->basic_amount;
    $tot['allow'] += $i->housing_allowance + $i->transport_allowance + $i->food_allowance + $i->other_allowance;
    $tot['extra'] += $i->overtime_amount + $i->offday_amount;
    $tot['bonus'] += $i->bonus_amount;
    $tot['earn'] += $i->total_earnings;
    $tot['absent'] += $i->absent_deduction;
    $tot['advance'] += $i->advance_deduction;
    $tot['other'] += $i->fixed_deduction + $i->other_deduction;
    $tot['ded'] += $i->total_deductions;
    $tot['net'] += $i->net_salary;
    $tot['paid'] += $i->paid_amount;
    $upcoming = max($upcoming, (int) $i->upcoming_days);
    $missing_checkout += (int) $i->missing_checkout_days;
    if ($i->net_salary < 0) {
        $negative++;
    }
}
foreach ($this->db->query("SELECT employee_id, payment_mode FROM tbl_payroll_salaries WHERE company_id = ?", array($run->company_id))->result() as $sm) {
    $salary_modes[$sm->employee_id] = $sm->payment_mode;
}
// the Advance column only when advances are in use (screen on, or a month that still has one)
$show_advance = Payroll_model::screenOn('adjustments') || $tot['advance'] > 0;
$balance = $tot['net'] - $tot['paid'];
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'runs')); ?>

    <div class="pay-head">
        <div>
            <h3>
                <?php echo lang('payroll'); ?> &middot; <?php echo escape_output($this->Payroll_model->monthLabel($run->pay_month)); ?>
                <span class="pay-badge <?php echo $run->status; ?>"><?php echo lang('payroll_status_' . $run->status); ?></span>
            </h3>
            <div class="pay-sub">
                <?php echo escape_output(date($date_format, strtotime($run->period_start))); ?> &ndash; <?php echo escape_output(date($date_format, strtotime($run->period_end))); ?>
                &middot; <?php echo lang('payroll_generated_by'); ?> <?php echo escape_output($run->generated_by_name); ?>
                <?php if (!$is_draft): ?>
                    &middot; <?php echo lang('payroll_approved_by'); ?> <?php echo escape_output($run->approved_by_name); ?> (<?php echo escape_output(date($date_format . ' h:i A', strtotime($run->approved_at))); ?>)
                <?php endif; ?>
            </div>
        </div>
        <div class="pay-head-actions">
            <a class="pay-btn" href="<?php echo base_url(); ?>Payroll/runs"><i class="fa fa-arrow-left"></i> <?php echo lang('back'); ?></a>
            <a class="pay-btn" target="_blank" href="<?php echo base_url(); ?>Payroll/printSheet/<?php echo (int) $run->id; ?>"><i class="fa fa-print"></i> <?php echo lang('payroll_print_sheet'); ?></a>
            <a class="pay-btn" target="_blank" href="<?php echo base_url(); ?>Payroll/payslips/<?php echo (int) $run->id; ?>"><i class="fa fa-file-text-o"></i> <?php echo lang('payroll_all_payslips'); ?></a>
            <?php if ($can_edit): ?>
                <a class="pay-btn" href="<?php echo base_url(); ?>Payroll/recalculate/<?php echo (int) $run->id; ?>"><i class="fa fa-refresh"></i> <?php echo lang('payroll_recalculate'); ?></a>
            <?php endif; ?>
            <?php if ($is_draft && $can_approve && $items): ?>
                <a class="pay-btn success pay-confirm" data-confirm="<?php echo escape_output(lang('payroll_approve_confirm')); ?>" href="<?php echo base_url(); ?>Payroll/approve/<?php echo (int) $run->id; ?>"><i class="fa fa-check"></i> <?php echo lang('approve'); ?></a>
            <?php endif; ?>
            <?php if (!$is_draft && $can_approve && $tot['paid'] == 0 && !$payments): ?>
                <a class="pay-btn pay-confirm" href="<?php echo base_url(); ?>Payroll/reopen/<?php echo (int) $run->id; ?>"><i class="fa fa-unlock"></i> <?php echo lang('reopen'); ?></a>
            <?php endif; ?>
            <?php if ($can_pay && $balance > 0.0005): ?>
                <button type="button" class="pay-btn primary" data-bs-toggle="modal" data-bs-target="#payAllModal"><i class="fa fa-money"></i> <?php echo lang('payroll_pay_all'); ?></button>
            <?php endif; ?>
            <?php if ($can_delete): ?>
                <a class="pay-btn danger pay-confirm" href="<?php echo base_url(); ?>Payroll/deleteRun/<?php echo (int) $run->id; ?>"><i class="fa fa-trash"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="pay-tiles">
        <div class="pay-tile"><div class="num"><?php echo count($items); ?></div><div class="lbl"><?php echo lang('employees'); ?></div></div>
        <div class="pay-tile"><div class="num"><?php echo getAmtPCustom($tot['earn']); ?></div><div class="lbl"><?php echo lang('payroll_earnings'); ?></div><div class="hint"><?php echo escape_output($currency); ?></div></div>
        <div class="pay-tile"><div class="num"><?php echo getAmtPCustom($tot['ded']); ?></div><div class="lbl"><?php echo lang('payroll_deductions'); ?></div><div class="hint"><?php echo escape_output($currency); ?></div></div>
        <div class="pay-tile good"><div class="num"><?php echo getAmtPCustom($tot['net']); ?></div><div class="lbl"><?php echo lang('payroll_net_payable'); ?></div><div class="hint"><?php echo escape_output($currency); ?></div></div>
        <div class="pay-tile <?php echo $balance > 0.0005 ? 'warn' : 'good'; ?>"><div class="num"><?php echo getAmtPCustom($tot['paid']); ?></div><div class="lbl"><?php echo lang('paid'); ?></div><div class="hint"><?php echo lang('payroll_balance'); ?>: <?php echo getAmtPCustom($balance); ?></div></div>
    </div>

    <?php if ($is_draft): ?>
        <div class="pay-info"><i class="fa fa-info-circle"></i> <?php echo lang('payroll_draft_info'); ?></div>
    <?php else: ?>
        <div class="pay-info"><i class="fa fa-lock"></i> <?php echo lang('payroll_approved_info'); ?></div>
    <?php endif; ?>
    <?php if ($upcoming > 0): ?>
        <div class="pay-warning"><i class="fa fa-clock-o"></i> <?php echo sprintf(lang('payroll_month_running_warn'), $upcoming); ?></div>
    <?php endif; ?>
    <?php if ($missing_checkout > 0): ?>
        <div class="pay-warning"><i class="fa fa-sign-out"></i> <?php echo sprintf(lang('payroll_missing_checkout_warn'), $missing_checkout, $s->missing_checkout == 'half' ? lang('payroll_half_day') : lang('payroll_full_day')); ?>
            <?php if (Payroll_model::screenOn('reports')): ?><a href="<?php echo base_url(); ?>Payroll/attendanceSheet?month=<?php echo escape_output($run->pay_month); ?>"><?php echo lang('payroll_attendance_sheet'); ?></a><?php endif; ?></div>
    <?php endif; ?>
    <?php if ($negative > 0): ?>
        <div class="pay-warning"><i class="fa fa-exclamation-triangle"></i> <?php echo sprintf(lang('payroll_negative_warn'), $negative); ?></div>
    <?php endif; ?>
    <?php if ($is_draft && $missing_setup): ?>
        <div class="pay-warning"><i class="fa fa-user-times"></i> <?php echo lang('payroll_missing_setup_warn'); ?>
            <?php $names = array(); foreach ($missing_setup as $m) { $names[] = escape_output($m->full_name); } echo implode(', ', $names); ?>.
            <a href="<?php echo base_url(); ?>Payroll_setup/salaries"><?php echo lang('payroll_salary_setup'); ?></a></div>
    <?php endif; ?>

    <div class="pay-card">
        <div class="table-responsive">
            <table class="pay-table">
                <thead>
                    <tr>
                        <th rowspan="2"><?php echo lang('employee'); ?></th>
                        <th rowspan="2"><?php echo lang('payroll_attendance'); ?></th>
                        <th colspan="5" class="grp"><?php echo lang('payroll_earnings'); ?></th>
                        <th colspan="<?php echo $show_advance ? 4 : 3; ?>" class="grp"><?php echo lang('payroll_deductions'); ?></th>
                        <th rowspan="2" class="pay-num"><?php echo lang('payroll_net_salary'); ?></th>
                        <th rowspan="2" class="pay-num"><?php echo lang('paid'); ?></th>
                        <th rowspan="2"></th>
                    </tr>
                    <tr>
                        <th class="pay-num"><?php echo lang('payroll_basic'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_allowances'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_ot_offday'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_bonus'); ?></th>
                        <th class="pay-num"><?php echo lang('total'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_absence'); ?></th>
                        <?php if ($show_advance): ?><th class="pay-num"><?php echo lang('payroll_advance'); ?></th><?php endif; ?>
                        <th class="pay-num"><?php echo lang('payroll_other'); ?></th>
                        <th class="pay-num"><?php echo lang('total'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="<?php echo $show_advance ? 14 : 13; ?>" class="text-center pay-muted"><?php echo lang('payroll_no_eligible'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $i):
                    $allow = $i->housing_allowance + $i->transport_allowance + $i->food_allowance + $i->other_allowance;
                    $remaining = round($i->net_salary - $i->paid_amount, 3);
                    $has_override = $i->ovr_absent_days !== null || $i->ovr_worked_hours !== null || $i->ovr_overtime_hours !== null || $i->sheet_bonus > 0 || $i->sheet_deduction > 0;
                    $no_attendance = $i->present_days == 0 && $i->offday_worked == 0 && $i->paid_leave_days == 0 && $i->upcoming_days < $i->working_days;
                    $edit_json = array(
                        'id' => (int) $i->id, 'name' => $i->employee_name, 'salary_type' => $i->salary_type,
                        'ovr_absent_days' => $i->ovr_absent_days !== null ? $num($i->ovr_absent_days) : '',
                        'ovr_worked_hours' => $i->ovr_worked_hours !== null ? $num($i->ovr_worked_hours) : '',
                        'ovr_overtime_hours' => $i->ovr_overtime_hours !== null ? $num($i->ovr_overtime_hours) : '',
                        'sheet_bonus' => $num($i->sheet_bonus), 'sheet_deduction' => $num($i->sheet_deduction), 'note' => (string) $i->note,
                        'absent_days' => $num($i->absent_days), 'worked_hours' => $num($i->worked_hours), 'overtime_hours' => $num($i->overtime_hours),
                    );
                    $pay_json = array('id' => (int) $i->id, 'name' => $i->employee_name, 'net' => (float) $i->net_salary, 'paid' => (float) $i->paid_amount,
                        'remaining' => $remaining, 'payment_mode' => isset($salary_modes[$i->employee_id]) ? $salary_modes[$i->employee_id] : '');
                ?>
                    <tr id="item-<?php echo (int) $i->id; ?>">
                        <td>
                            <span class="name"><?php echo escape_output($i->employee_name); ?></span>
                            <span class="sub"><?php echo escape_output($i->designation); ?></span>
                            <span class="pay-badge <?php echo $i->salary_type; ?>"><?php echo lang('payroll_type_' . $i->salary_type); ?></span>
                            <?php if ($has_override): ?><span class="pay-badge override" title="<?php echo escape_output(lang('payroll_override_hint')); ?>"><?php echo lang('payroll_edited'); ?></span><?php endif; ?>
                            <?php if ($no_attendance): ?><span class="pay-badge unpaid"><?php echo lang('payroll_no_attendance'); ?></span><?php endif; ?>
                            <?php if ($i->note): ?><span class="sub"><i class="fa fa-comment-o"></i> <?php echo escape_output($i->note); ?></span><?php endif; ?>
                        </td>
                        <td class="pay-days-cell">
                            <?php echo lang('payroll_present_short'); ?> <b><?php echo $num($i->present_days); ?></b><?php if ($i->half_days): ?> <span class="pay-muted">(<?php echo (int) $i->half_days; ?> <?php echo lang('payroll_half_short'); ?>)</span><?php endif; ?>
                            &middot; <?php echo lang('payroll_absent_short'); ?> <b class="<?php echo $i->absent_days > 0 ? 'pay-neg' : ''; ?>"><?php echo $num($i->absent_days); ?></b><?php echo $i->ovr_absent_days !== null ? '*' : ''; ?>
                            <?php // DECIMAL columns arrive as strings: "0.0" is truthy, so compare as numbers ?>
                            <?php if ($i->paid_leave_days > 0 || $i->unpaid_leave_days > 0): ?>&middot; <?php echo lang('payroll_leave_short'); ?> <b><?php echo $num($i->paid_leave_days); ?></b><?php if ($i->unpaid_leave_days > 0): ?>+<b class="pay-neg"><?php echo $num($i->unpaid_leave_days); ?></b><?php endif; ?><?php endif; ?>
                            <br>
                            <span class="pay-muted"><?php echo lang('payroll_off_short'); ?> <?php echo (int) $i->weekly_off_days; ?><?php if ($i->holiday_days > 0 || Payroll_model::screenOn('holidays')): ?> &middot; <?php echo lang('payroll_holiday_short'); ?> <?php echo (int) $i->holiday_days; ?><?php endif; ?>
                            <?php if ($i->offday_worked > 0): ?>&middot; <?php echo lang('payroll_offday_worked_short'); ?> <?php echo $num($i->offday_worked); ?><?php endif; ?>
                            <?php if ($i->not_employed_days): ?>&middot; <?php echo lang('payroll_not_employed_short'); ?> <?php echo (int) $i->not_employed_days; ?><?php endif; ?></span>
                            <br>
                            <span class="pay-muted"><?php echo $num($i->worked_hours); ?> <?php echo lang('payroll_hrs'); ?><?php if ($i->overtime_hours > 0): ?> &middot; <?php echo lang('payroll_ot'); ?> <?php echo $num($i->overtime_hours); ?> <?php echo lang('payroll_hrs'); ?><?php echo $i->ovr_overtime_hours !== null ? '*' : ''; ?><?php endif; ?>
                            <?php if ($i->missing_checkout_days): ?> &middot; <span title="<?php echo escape_output(lang('payroll_missing_checkout')); ?>"><i class="fa fa-sign-out"></i> <?php echo (int) $i->missing_checkout_days; ?></span><?php endif; ?></span>
                        </td>
                        <td class="pay-num"><?php echo getAmtPCustom($i->basic_amount); ?>
                            <span class="sub"><?php echo getAmtPCustom($i->rate); ?>/<?php echo lang('payroll_per_' . $i->salary_type); ?></span></td>
                        <td class="pay-num"><?php echo getAmtPCustom($allow); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($i->overtime_amount + $i->offday_amount); ?>
                            <?php if ($i->overtime_amount > 0 && $i->offday_amount > 0): ?><span class="sub"><?php echo getAmtPCustom($i->overtime_amount); ?> + <?php echo getAmtPCustom($i->offday_amount); ?></span><?php endif; ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($i->bonus_amount); ?></td>
                        <td class="pay-num"><b><?php echo getAmtPCustom($i->total_earnings); ?></b></td>
                        <td class="pay-num"><?php echo getAmtPCustom($i->absent_deduction); ?>
                            <?php if ($i->salary_type == 'monthly' && $i->absent_deduction > 0): ?><span class="sub"><?php echo getAmtPCustom($i->per_day_rate); ?>/<?php echo lang('payroll_per_daily'); ?></span><?php endif; ?></td>
                        <?php if ($show_advance): ?><td class="pay-num"><?php echo getAmtPCustom($i->advance_deduction); ?></td><?php endif; ?>
                        <td class="pay-num"><?php echo getAmtPCustom($i->fixed_deduction + $i->other_deduction); ?></td>
                        <td class="pay-num"><b><?php echo getAmtPCustom($i->total_deductions); ?></b></td>
                        <td class="pay-num"><b class="<?php echo $i->net_salary < 0 ? 'pay-neg' : ''; ?>" style="font-size:14px"><?php echo getAmtPCustom($i->net_salary); ?></b></td>
                        <td class="pay-num">
                            <?php echo getAmtPCustom($i->paid_amount); ?>
                            <?php if (!$is_draft): ?><span class="sub"><span class="pay-badge <?php echo $i->payment_status; ?>"><?php echo $i->payment_status == 'paid' ? lang('paid') : lang('payroll_' . $i->payment_status); ?></span></span><?php endif; ?>
                        </td>
                        <td>
                            <div class="pay-actions">
                                <?php if ($can_edit): ?>
                                    <button type="button" class="pay-btn sm" data-bs-toggle="modal" data-bs-target="#payEditModal" data-pay-edit="<?php echo escape_output(json_encode($edit_json)); ?>"><i class="fa fa-pencil"></i></button>
                                <?php endif; ?>
                                <?php if ($can_pay && $remaining > 0.0005): ?>
                                    <button type="button" class="pay-btn sm primary" data-bs-toggle="modal" data-bs-target="#payPayModal" data-pay-pay="<?php echo escape_output(json_encode($pay_json)); ?>"><?php echo lang('pay'); ?></button>
                                <?php endif; ?>
                                <a class="pay-btn sm" target="_blank" title="<?php echo escape_output(lang('payroll_payslip')); ?>" href="<?php echo base_url(); ?>Payroll/payslip/<?php echo (int) $i->id; ?>"><i class="fa fa-file-text-o"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($items): ?>
                <tfoot>
                    <tr>
                        <td colspan="2"><?php echo lang('total'); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['basic']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['allow']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['extra']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['bonus']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['earn']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['absent']); ?></td>
                        <?php if ($show_advance): ?><td class="pay-num"><?php echo getAmtPCustom($tot['advance']); ?></td><?php endif; ?>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['other']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['ded']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['net']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($tot['paid']); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <?php if (!$is_draft): ?>
    <div class="pay-card">
        <h4><?php echo lang('payroll_salary_payments'); ?></h4>
        <?php if (!$payments): ?>
            <div class="pay-muted"><?php echo lang('payroll_no_payments'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="pay-table">
                <thead>
                    <tr>
                        <th><?php echo lang('date'); ?></th>
                        <th><?php echo lang('employee'); ?></th>
                        <th class="pay-num"><?php echo lang('amount'); ?></th>
                        <th><?php echo lang('payment_method'); ?></th>
                        <th><?php echo lang('payroll_expense'); ?></th>
                        <th><?php echo lang('note'); ?></th>
                        <th><?php echo lang('added_by'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo escape_output(date($date_format, strtotime($p->pay_date))); ?></td>
                        <td class="name"><?php echo escape_output($p->full_name); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($p->amount); ?></td>
                        <td><?php echo escape_output($p->payment_name); ?></td>
                        <td><?php if ($p->expense_id): ?><span class="pay-badge paid"><?php echo lang('payroll_posted'); ?></span><?php echo $p->counter_id ? ' <span class="pay-muted">' . lang('payroll_from_till_short') . '</span>' : ''; ?><?php else: ?><span class="pay-muted">&ndash;</span><?php endif; ?></td>
                        <td><?php echo escape_output($p->note); ?></td>
                        <td><?php echo escape_output($p->added_by); ?></td>
                        <td>
                            <?php if (checkAccess(412, 'pay')): ?>
                                <a class="pay-btn sm danger pay-confirm" data-confirm="<?php echo escape_output(lang('payroll_delete_payment_confirm')); ?>" href="<?php echo base_url(); ?>Payroll/deletePayment/<?php echo (int) $p->id; ?>"><i class="fa fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="pay-muted mb-3">
        <?php echo lang('payroll_basis_note'); ?>:
        <b><?php echo lang('payroll_basis_' . $s->month_days_basis); ?></b>,
        <?php echo lang('payroll_absent_on'); ?> <b><?php echo lang('payroll_on_' . $s->absent_deduction_on); ?></b>,
        <?php echo lang('payroll_standard_hours'); ?> <b><?php echo $num($s->standard_hours); ?></b>,
        <?php echo lang('payroll_ot'); ?> <b><?php echo $s->overtime_enabled ? '&times;' . $num($s->overtime_multiplier) : lang('payroll_off'); ?></b>.
        <?php if (Payroll_model::screenOn('settings') && checkAccess(435, 'update')): ?><a href="<?php echo base_url(); ?>Payroll_setup/settings"><?php echo lang('payroll_settings'); ?></a><?php endif; ?>
    </div>
</section>

<?php if ($can_edit): ?>
<div class="modal fade pay-modal" id="payEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open(base_url('Payroll/editItem')); ?>
            <input type="hidden" name="item_id" value="">
            <div class="modal-header">
                <h5><?php echo lang('payroll_edit_line'); ?> &middot; <span class="js-emp"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i data-feather="x"></i></button>
            </div>
            <div class="modal-body pay-form">
                <div class="pay-hint"><?php echo lang('payroll_edit_line_hint'); ?></div>
                <div class="row">
                    <div class="col-6 form-group js-row-absent">
                        <label><?php echo lang('payroll_absent_days'); ?></label>
                        <input type="text" name="ovr_absent_days" class="form-control integerchk" placeholder="<?php echo escape_output(lang('payroll_auto')); ?>">
                        <div class="pay-computed"><?php echo lang('payroll_from_attendance'); ?>: <span class="js-calc-absent"></span></div>
                    </div>
                    <div class="col-6 form-group js-row-hours">
                        <label><?php echo lang('payroll_worked_hours'); ?></label>
                        <input type="text" name="ovr_worked_hours" class="form-control integerchk" placeholder="<?php echo escape_output(lang('payroll_auto')); ?>">
                        <div class="pay-computed"><?php echo lang('payroll_from_attendance'); ?>: <span class="js-calc-hours"></span></div>
                    </div>
                    <div class="col-6 form-group">
                        <label><?php echo lang('payroll_overtime_hours'); ?></label>
                        <input type="text" name="ovr_overtime_hours" class="form-control integerchk" placeholder="<?php echo escape_output(lang('payroll_auto')); ?>">
                        <div class="pay-computed"><?php echo lang('payroll_from_attendance'); ?>: <span class="js-calc-ot"></span></div>
                    </div>
                    <div class="col-6 form-group">
                        <label><?php echo lang('payroll_bonus'); ?> (<?php echo escape_output($currency); ?>)</label>
                        <input type="text" name="sheet_bonus" class="form-control integerchk" placeholder="0">
                    </div>
                    <div class="col-6 form-group">
                        <label><?php echo lang('payroll_deduction'); ?> (<?php echo escape_output($currency); ?>)</label>
                        <input type="text" name="sheet_deduction" class="form-control integerchk" placeholder="0">
                    </div>
                    <div class="col-12 form-group">
                        <label><?php echo lang('note'); ?></label>
                        <input type="text" name="note" class="form-control" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php /* save first: Enter in a field submits with the first submit button */ ?>
                <button type="submit" name="save" value="1" class="pay-btn primary" style="order:2"><?php echo lang('submit'); ?></button>
                <button type="submit" name="reset" value="1" class="pay-btn pay-confirm" style="order:1"><?php echo lang('payroll_reset_line'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($can_pay): ?>
<?php
$pay_fields = function () use ($payment_methods, $s, $register_open) {
    ob_start(); ?>
    <div class="row">
        <div class="col-6 form-group">
            <label><?php echo lang('payment_method'); ?> <span class="required_star">*</span></label>
            <select name="payment_id" class="form-control">
                <?php foreach ($payment_methods as $pm): ?>
                    <option value="<?php echo (int) $pm->id; ?>"><?php echo escape_output($pm->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 form-group">
            <label><?php echo lang('date'); ?></label>
            <input type="text" name="pay_date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-12 form-group">
            <label><?php echo lang('note'); ?></label>
            <input type="text" name="note" class="form-control" maxlength="255">
        </div>
        <div class="col-12">
            <label class="pay-check"><input type="checkbox" name="record_expense" value="1" class="js-record-expense" <?php echo $s->record_expense ? 'checked' : ''; ?>> <?php echo lang('payroll_record_expense'); ?></label>
            <label class="pay-check"><input type="checkbox" name="from_till" value="1" class="js-from-till"> <?php echo lang('payroll_from_till'); ?></label>
            <div class="pay-muted"><?php echo $register_open ? lang('payroll_from_till_hint') : lang('payroll_from_till_closed'); ?></div>
        </div>
    </div>
    <?php return ob_get_clean();
};
?>
<div class="modal fade pay-modal" id="payPayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open(base_url('Payroll/pay')); ?>
            <input type="hidden" name="item_id" value="">
            <div class="modal-header">
                <h5><?php echo lang('payroll_pay_salary'); ?> &middot; <span class="js-emp"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i data-feather="x"></i></button>
            </div>
            <div class="modal-body pay-form">
                <div class="pay-hint">
                    <?php echo lang('payroll_net_salary'); ?> <b class="js-net"></b> &middot; <?php echo lang('paid'); ?> <b class="js-paid"></b> &middot; <?php echo lang('payroll_balance'); ?> <b class="js-remaining"></b>
                </div>
                <div class="form-group">
                    <label><?php echo lang('amount'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>) <span class="required_star">*</span></label>
                    <input type="text" name="amount" class="form-control integerchk" value="">
                </div>
                <?php echo $pay_fields(); ?>
            </div>
            <div class="modal-footer">
                <button type="submit" class="pay-btn primary"><i class="fa fa-money"></i> <?php echo lang('pay'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<div class="modal fade pay-modal" id="payAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open(base_url('Payroll/payAll')); ?>
            <input type="hidden" name="run_id" value="<?php echo (int) $run->id; ?>">
            <div class="modal-header">
                <h5><?php echo lang('payroll_pay_all'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i data-feather="x"></i></button>
            </div>
            <div class="modal-body pay-form">
                <div class="pay-hint"><?php echo sprintf(lang('payroll_pay_all_hint'), escape_output($this->session->userdata('currency')) . ' ' . getAmtPCustom($balance)); ?></div>
                <?php echo $pay_fields(); ?>
            </div>
            <div class="modal-footer">
                <button type="submit" class="pay-btn primary"><i class="fa fa-money"></i> <?php echo lang('payroll_pay_all'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php endif; ?>
