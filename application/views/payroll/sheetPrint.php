<?php
/* Standalone print page: the payroll register of one month (landscape). */
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
$month_label = $this->Payroll_model->monthLabel($run->pay_month);
$num = function ($v) {
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
};
$biz = isset($company->business_name) ? $company->business_name : '';
$cols = array('basic_amount', 'allowances', 'extra', 'bonus_amount', 'total_earnings', 'absent_deduction', 'advance_deduction', 'other', 'total_deductions', 'net_salary', 'paid_amount');
// the Advance column only when advances are in use (screen on, or a month that still has one)
$show_advance = Payroll_model::screenOn('adjustments');
foreach ($items as $i) {
    $show_advance = $show_advance || $i->advance_deduction > 0;
}
if (!$show_advance) {
    $cols = array_values(array_diff($cols, array('advance_deduction')));
}
$tot = array_fill_keys($cols, 0);
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title><?php echo lang('payroll_register'); ?> <?php echo escape_output($month_label); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f2f5; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 11px; }
        .bar { max-width: 297mm; margin: 12px auto; display: flex; gap: 8px; }
        .bar button { padding: 9px 16px; border: 0; border-radius: 6px; background: #7367f0; color: #fff; font-size: 14px; cursor: pointer; }
        .bar button.sec { background: #e9e8ee; color: #333; }
        .page { max-width: 297mm; margin: 0 auto 20px; background: #fff; padding: 8mm; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #222; padding-bottom: 6px; margin-bottom: 8px; }
        .biz { font-size: 16px; font-weight: bold; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 3px 4px; vertical-align: top; }
        th { background: #f4f4f6; font-size: 10px; text-transform: uppercase; }
        td.r, th.r { text-align: right; white-space: nowrap; }
        td small { display: block; color: #777; font-size: 9.5px; }
        td.att { white-space: nowrap; }
        tfoot td { font-weight: bold; border-top: 2px solid #222; }
        .sign { display: flex; justify-content: space-between; gap: 40px; margin-top: 34px; }
        .sign div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 3px; color: #555; }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .page { margin: 0; padding: 0; max-width: none; }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" onclick="window.print()"><?php echo lang('print'); ?></button>
    <button type="button" class="sec" onclick="window.close()"><?php echo lang('close'); ?></button>
</div>
<div class="page">
    <div class="head">
        <div>
            <div class="biz"><?php echo escape_output($biz); ?></div>
            <?php if ($outlet && $outlet->outlet_name && $outlet->outlet_name != $biz): ?><div class="muted"><?php echo escape_output($outlet->outlet_name); ?></div><?php endif; ?>
        </div>
        <div style="text-align:right">
            <div class="biz"><?php echo lang('payroll_register'); ?> &middot; <?php echo escape_output($month_label); ?></div>
            <div class="muted"><?php echo lang('status'); ?>: <?php echo lang('payroll_status_' . $run->status); ?><?php echo $run->approved_at ? ' &middot; ' . escape_output(date($date_format, strtotime($run->approved_at))) : ''; ?> &middot; <?php echo lang('payroll_printed'); ?> <?php echo escape_output(date($date_format . ' h:i A')); ?></div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo lang('employee'); ?></th>
                <th><?php echo lang('payroll_attendance'); ?></th>
                <th class="r"><?php echo lang('payroll_basic'); ?></th>
                <th class="r"><?php echo lang('payroll_allowances'); ?></th>
                <th class="r"><?php echo lang('payroll_ot_offday'); ?></th>
                <th class="r"><?php echo lang('payroll_bonus'); ?></th>
                <th class="r"><?php echo lang('payroll_total_earnings'); ?></th>
                <th class="r"><?php echo lang('payroll_absence'); ?></th>
                <?php if ($show_advance): ?><th class="r"><?php echo lang('payroll_advance'); ?></th><?php endif; ?>
                <th class="r"><?php echo lang('payroll_other'); ?></th>
                <th class="r"><?php echo lang('payroll_total_deductions'); ?></th>
                <th class="r"><?php echo lang('payroll_net_salary'); ?></th>
                <th class="r"><?php echo lang('paid'); ?></th>
                <th style="width:90px"><?php echo lang('payroll_signature'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($items as $i):
            $row = array(
                'basic_amount' => $i->basic_amount,
                'allowances' => $i->housing_allowance + $i->transport_allowance + $i->food_allowance + $i->other_allowance,
                'extra' => $i->overtime_amount + $i->offday_amount,
                'bonus_amount' => $i->bonus_amount,
                'total_earnings' => $i->total_earnings,
                'absent_deduction' => $i->absent_deduction,
                'advance_deduction' => $i->advance_deduction,
                'other' => $i->fixed_deduction + $i->other_deduction,
                'total_deductions' => $i->total_deductions,
                'net_salary' => $i->net_salary,
                'paid_amount' => $i->paid_amount,
            );
            if (!$show_advance) {
                unset($row['advance_deduction']);
            }
            foreach ($row as $k => $x) {
                $tot[$k] += $x;
            }
        ?>
            <tr>
                <td><?php echo $n++; ?></td>
                <td><b><?php echo escape_output($i->employee_name); ?></b><small><?php echo escape_output($i->designation); ?> &middot; <?php echo lang('payroll_type_' . $i->salary_type); ?></small></td>
                <td class="att"><?php echo lang('payroll_present_short'); ?> <?php echo $num($i->present_days); ?> &middot; <?php echo lang('payroll_absent_short'); ?> <?php echo $num($i->absent_days); ?> &middot; <?php echo lang('payroll_leave_short'); ?> <?php echo $num($i->paid_leave_days); ?>
                    <small><?php echo $num($i->worked_hours); ?> <?php echo lang('payroll_hrs'); ?><?php echo $i->overtime_hours > 0 ? ' &middot; ' . lang('payroll_ot') . ' ' . $num($i->overtime_hours) : ''; ?></small></td>
                <?php foreach ($row as $k => $x): ?>
                    <td class="r"><?php echo $k == 'net_salary' ? '<b>' . getAmtPCustom($x) . '</b>' : getAmtPCustom($x); ?></td>
                <?php endforeach; ?>
                <td></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><?php echo lang('total'); ?> (<?php echo escape_output($currency); ?>)</td>
                <?php foreach ($cols as $k): ?>
                    <td class="r"><?php echo getAmtPCustom($tot[$k]); ?></td>
                <?php endforeach; ?>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="sign">
        <div><?php echo lang('payroll_prepared_by'); ?></div>
        <div><?php echo lang('payroll_checked_by'); ?></div>
        <div><?php echo lang('payroll_approved_by'); ?></div>
    </div>
</div>
</body>
</html>
