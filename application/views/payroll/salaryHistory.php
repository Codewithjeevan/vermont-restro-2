<?php
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'history')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_salary_history'); ?><?php echo $employee ? ' &middot; ' . escape_output($employee->full_name) : ''; ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_salary_history_hint'); ?></div>
        </div>
        <?php if ($employee): ?>
        <div class="pay-head-actions">
            <button type="button" class="pay-btn" onclick="window.print()"><i class="fa fa-print"></i> <?php echo lang('print'); ?></button>
        </div>
        <?php endif; ?>
    </div>

    <form method="get" action="<?php echo base_url(); ?>Payroll/salaryHistory" class="pay-filter">
        <div>
            <label><?php echo lang('employee'); ?></label>
            <select name="employee_id" class="form-control js-autosubmit">
                <option value=""><?php echo lang('select'); ?></option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?php echo (int) $e->id; ?>" <?php echo $employee_id == $e->id ? 'selected' : ''; ?>><?php echo escape_output($e->full_name); ?><?php echo $e->salary_id ? '' : ' (' . lang('payroll_not_set') . ')'; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?php echo lang('year'); ?></label>
            <select name="year" class="form-control js-autosubmit">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </form>

    <?php if (!$employee): ?>
        <div class="pay-info"><?php echo lang('payroll_choose_employee'); ?></div>
    <?php else:
        $t = array('earn' => 0, 'ded' => 0, 'net' => 0, 'paid' => 0, 'ot' => 0, 'absent' => 0);
        foreach ($lines as $l) {
            $t['earn'] += $l->total_earnings;
            $t['ded'] += $l->total_deductions;
            $t['net'] += $l->net_salary;
            $t['paid'] += $l->paid_amount;
            $t['ot'] += $l->overtime_hours;
            $t['absent'] += $l->absent_days;
        }
    ?>
    <div class="pay-tiles">
        <div class="pay-tile"><div class="num"><?php echo $salary ? getAmtPCustom($this->Payroll_model->monthlyGross($salary)) : '&ndash;'; ?></div><div class="lbl"><?php echo $salary ? lang('payroll_type_' . $salary->salary_type) . ' &middot; ' . getAmtPCustom($salary->basic_salary) . '/' . lang('payroll_per_' . $salary->salary_type) : lang('payroll_not_set'); ?></div><div class="hint"><?php echo lang('payroll_gross_month'); ?></div></div>
        <div class="pay-tile good"><div class="num"><?php echo getAmtPCustom($t['net']); ?></div><div class="lbl"><?php echo lang('payroll_net_salary'); ?> <?php echo (int) $year; ?></div><div class="hint"><?php echo escape_output($currency); ?></div></div>
        <div class="pay-tile <?php echo $t['net'] - $t['paid'] > 0.0005 ? 'warn' : ''; ?>"><div class="num"><?php echo getAmtPCustom($t['paid']); ?></div><div class="lbl"><?php echo lang('paid'); ?></div><div class="hint"><?php echo lang('payroll_balance'); ?> <?php echo getAmtPCustom($t['net'] - $t['paid']); ?></div></div>
        <div class="pay-tile <?php echo $advance_balance && $advance_balance->balance > 0 ? 'warn' : ''; ?>"><div class="num"><?php echo getAmtPCustom($advance_balance ? $advance_balance->balance : 0); ?></div><div class="lbl"><?php echo lang('payroll_advance_balance'); ?></div><div class="hint"><?php echo lang('payroll_given'); ?> <?php echo getAmtPCustom($advance_balance ? $advance_balance->given : 0); ?></div></div>
    </div>

    <div class="pay-card">
        <h4><?php echo lang('payroll_monthly_salaries'); ?> <?php echo (int) $year; ?></h4>
        <div class="table-responsive">
            <table class="pay-table">
                <thead>
                    <tr>
                        <th><?php echo lang('month'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_present_days'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_absent_days'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_overtime_hours'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_earnings'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_deductions'); ?></th>
                        <th class="pay-num"><?php echo lang('payroll_net_salary'); ?></th>
                        <th class="pay-num"><?php echo lang('paid'); ?></th>
                        <th><?php echo lang('status'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$lines): ?>
                    <tr><td colspan="10" class="text-center pay-muted"><?php echo lang('payroll_no_lines'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($lines as $l): ?>
                    <tr>
                        <td class="name"><?php echo escape_output($this->Payroll_model->monthLabel($l->pay_month)); ?></td>
                        <td class="pay-num"><?php echo (float) $l->present_days; ?></td>
                        <td class="pay-num"><?php echo (float) $l->absent_days; ?></td>
                        <td class="pay-num"><?php echo (float) $l->overtime_hours; ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($l->total_earnings); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($l->total_deductions); ?></td>
                        <td class="pay-num"><b><?php echo getAmtPCustom($l->net_salary); ?></b></td>
                        <td class="pay-num"><?php echo getAmtPCustom($l->paid_amount); ?></td>
                        <td>
                            <span class="pay-badge <?php echo $l->run_status; ?>"><?php echo lang('payroll_status_' . $l->run_status); ?></span>
                            <?php if ($l->run_status == 'approved'): ?><span class="pay-badge <?php echo $l->payment_status; ?>"><?php echo $l->payment_status == 'paid' ? lang('paid') : lang('payroll_' . $l->payment_status); ?></span><?php endif; ?>
                        </td>
                        <td>
                            <a class="pay-btn sm" target="_blank" href="<?php echo base_url(); ?>Payroll/payslip/<?php echo (int) $l->id; ?>"><i class="fa fa-file-text-o"></i></a>
                            <?php if (checkAccess(412, 'view')): ?><a class="pay-btn sm" href="<?php echo base_url(); ?>Payroll/run/<?php echo (int) $l->run_id; ?>#item-<?php echo (int) $l->id; ?>"><i class="fa fa-eye"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($lines): ?>
                <tfoot>
                    <tr>
                        <td><?php echo lang('total'); ?></td>
                        <td></td>
                        <td class="pay-num"><?php echo (float) $t['absent']; ?></td>
                        <td class="pay-num"><?php echo (float) $t['ot']; ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($t['earn']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($t['ded']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($t['net']); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($t['paid']); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="pay-grid-2">
        <div class="pay-card">
            <h4><?php echo lang('payroll_salary_payments'); ?> <?php echo (int) $year; ?></h4>
            <?php if (!$payments): ?>
                <div class="pay-muted"><?php echo lang('payroll_no_payments'); ?></div>
            <?php else: ?>
            <table class="pay-table">
                <thead><tr><th><?php echo lang('date'); ?></th><th><?php echo lang('month'); ?></th><th class="pay-num"><?php echo lang('amount'); ?></th><th><?php echo lang('payment_method'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo escape_output(date($date_format, strtotime($p->pay_date))); ?></td>
                        <td><?php echo escape_output(date('M Y', strtotime($p->pay_month . '-01'))); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($p->amount); ?></td>
                        <td><?php echo escape_output($p->payment_name); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="pay-card">
            <h4><?php echo lang('payroll_advances'); ?></h4>
            <?php if (!$advances): ?>
                <div class="pay-muted"><?php echo lang('payroll_no_advances'); ?></div>
            <?php else: ?>
            <table class="pay-table">
                <thead><tr><th><?php echo lang('date'); ?></th><th class="pay-num"><?php echo lang('amount'); ?></th><th><?php echo lang('payroll_recovery'); ?></th><th><?php echo lang('note'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($advances as $a): ?>
                    <tr>
                        <td><?php echo escape_output(date($date_format, strtotime($a->adj_date))); ?></td>
                        <td class="pay-num"><?php echo getAmtPCustom($a->amount); ?></td>
                        <td><?php echo escape_output(date('M Y', strtotime($a->pay_month . '-01'))); ?><?php echo $a->installments > 1 ? ' &ndash; ' . escape_output(date('M Y', strtotime($this->Payroll_model->addMonths($a->pay_month, $a->installments - 1) . '-01'))) . ' (' . (int) $a->installments . ')' : ''; ?></td>
                        <td><?php echo escape_output($a->note); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td><?php echo lang('payroll_recovered'); ?></td><td class="pay-num"><?php echo getAmtPCustom($advance_balance ? $advance_balance->recovered : 0); ?></td><td><?php echo lang('payroll_balance'); ?></td><td class="pay-num"><?php echo getAmtPCustom($advance_balance ? $advance_balance->balance : 0); ?></td></tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
