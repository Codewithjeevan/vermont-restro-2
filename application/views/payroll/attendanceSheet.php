<?php
$dim = (int) date('t', strtotime($month . '-01'));
$today = date('Y-m-d');
$codes = array(
    'P' => lang('payroll_code_P'), 'M' => lang('payroll_code_M'), 'H' => lang('payroll_code_H'), 'A' => lang('payroll_code_A'),
    'L' => lang('payroll_code_L'), 'U' => lang('payroll_code_U'), 'O' => lang('payroll_code_O'), 'HO' => lang('payroll_code_HO'),
    'W' => lang('payroll_code_W'), 'X' => lang('payroll_code_X'), 'F' => lang('payroll_code_F'),
);
$num = function ($v) {
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
};
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'attendance')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_attendance_sheet'); ?> &middot; <?php echo escape_output($this->Payroll_model->monthLabel($month)); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_attendance_sheet_hint'); ?></div>
        </div>
        <div class="pay-head-actions">
            <button type="button" class="pay-btn" onclick="window.print()"><i class="fa fa-print"></i> <?php echo lang('print'); ?></button>
            <?php if (checkAccess(155, 'add')): ?>
                <a class="pay-btn" href="<?php echo base_url(); ?>Attendance/addEditAttendance"><i class="fa fa-plus"></i> <?php echo lang('add_attendance'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" action="<?php echo base_url(); ?>Payroll/attendanceSheet" class="pay-filter">
        <div>
            <label><?php echo lang('month'); ?></label>
            <select name="month" class="form-control js-autosubmit">
                <?php foreach ($month_options as $m => $label): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo escape_output($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="pay-legend">
        <?php foreach ($codes as $c => $label): ?>
            <span><span class="d d-<?php echo $c; ?>"><?php echo $c; ?></span><?php echo escape_output($label); ?></span>
        <?php endforeach; ?>
    </div>

    <div class="pay-card">
        <div class="pay-att-wrap">
            <table class="pay-att">
                <thead>
                    <tr>
                        <th class="emp"><?php echo lang('employee'); ?></th>
                        <?php for ($d = 1; $d <= $dim; $d++):
                            $date = sprintf('%s-%02d', $month, $d);
                            $dow = date('N', strtotime($date));
                        ?>
                            <th class="<?php echo $dow >= 6 ? 'we' : ''; ?>" title="<?php echo escape_output(isset($holidays[$date]) ? $holidays[$date] : date('l', strtotime($date))); ?>"><?php echo $d; ?><br><span class="pay-muted"><?php echo substr(date('D', strtotime($date)), 0, 2); ?></span></th>
                        <?php endfor; ?>
                        <th title="<?php echo escape_output(lang('payroll_present_days')); ?>"><?php echo lang('payroll_present_short'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_half_days')); ?>"><?php echo lang('payroll_half_short'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_absent_days')); ?>"><?php echo lang('payroll_absent_short'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_leaves')); ?>"><?php echo lang('payroll_leave_short'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_offday_worked')); ?>"><?php echo lang('payroll_offday_worked_short'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_worked_hours')); ?>"><?php echo lang('payroll_hrs'); ?></th>
                        <th title="<?php echo escape_output(lang('payroll_overtime_hours')); ?>"><?php echo lang('payroll_ot'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): $st = $r->status; $e = $r->employee; ?>
                    <tr>
                        <td class="emp">
                            <b><?php echo escape_output($e->full_name); ?></b>
                            <?php if (!$e->salary_id): ?><span class="pay-badge notset" title="<?php echo escape_output(lang('payroll_not_on_payroll_hint')); ?>"><?php echo lang('payroll_not_set'); ?></span><?php endif; ?>
                            <span class="pay-muted d-block"><?php echo escape_output($e->designation); ?></span>
                        </td>
                        <?php for ($d = 1; $d <= $dim; $d++): $c = $st['days'][$d]; ?>
                            <td><span class="d d-<?php echo $c; ?>" title="<?php echo escape_output($codes[$c]); ?>"><?php echo $c == 'X' || $c == 'F' ? '&middot;' : $c; ?></span></td>
                        <?php endfor; ?>
                        <td class="tot"><?php echo $num($st['present_days']); ?></td>
                        <td class="tot"><?php echo (int) $st['half_days']; ?></td>
                        <td class="tot" style="<?php echo $st['absent_days'] > 0 ? 'color:#d63939' : ''; ?>"><?php echo $num($st['absent_days'] + $st['unpaid_leave_days']); ?></td>
                        <td class="tot"><?php echo $num($st['paid_leave_days']); ?></td>
                        <td class="tot"><?php echo $num($st['offday_worked']); ?></td>
                        <td class="tot"><?php echo $num($st['worked_hours']); ?></td>
                        <td class="tot"><?php echo $num($st['overtime_hours']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="pay-muted"><?php echo lang('payroll_attendance_sheet_note'); ?></div>
</section>
