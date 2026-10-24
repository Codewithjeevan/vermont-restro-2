<?php
/*
 | Standalone print page: one payslip (Payroll/payslip/<item>) or all payslips
 | of a month (Payroll/payslips/<run>). Payslips flow two per A4 page and never
 | break inside.
 */
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
$month_label = $this->Payroll_model->monthLabel($run->pay_month);
$num = function ($v) {
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
};
$biz = isset($company->business_name) ? $company->business_name : '';
$title = count($slips) == 1 ? lang('payroll_payslip') . ' ' . $slips[0]->item->employee_name . ' ' . $month_label : lang('payroll_payslips') . ' ' . $month_label;
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title><?php echo escape_output($title); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f2f5; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12px; }
        .bar { max-width: 210mm; margin: 12px auto; display: flex; gap: 8px; }
        .bar button { padding: 9px 16px; border: 0; border-radius: 6px; background: #7367f0; color: #fff; font-size: 14px; cursor: pointer; }
        .bar button.sec { background: #e9e8ee; color: #333; }
        .slip { position: relative; max-width: 210mm; margin: 0 auto 12px; background: #fff; padding: 9mm 10mm; page-break-inside: avoid; break-inside: avoid; overflow: hidden; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 6px; margin-bottom: 8px; }
        .biz { font-size: 17px; font-weight: bold; }
        .muted { color: #666; }
        .ttl { text-align: right; }
        .ttl .big { font-size: 18px; font-weight: bold; letter-spacing: .08em; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 18px; margin-bottom: 8px; }
        .kv { display: flex; justify-content: space-between; border-bottom: 1px dotted #ccc; padding: 2px 0; }
        .kv span:last-child { font-weight: bold; text-align: right; }
        h5 { margin: 8px 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #555; }
        .att { display: grid; grid-template-columns: repeat(6, 1fr); border: 1px solid #ddd; margin-bottom: 6px; }
        .att div { padding: 4px 5px; border-right: 1px solid #eee; text-align: center; }
        .att div:last-child { border-right: 0; }
        .att b { display: block; font-size: 13px; }
        .att small { color: #666; font-size: 10px; }
        .strip { display: flex; flex-wrap: wrap; gap: 1px; margin-bottom: 8px; }
        .strip span { width: 22px; text-align: center; font-size: 9px; border: 1px solid #e5e5e5; padding: 1px 0; }
        .strip span i { display: block; font-style: normal; font-weight: bold; font-size: 10px; }
        .c-A, .c-U { background: #fce8e8; color: #c62828; }
        .c-H { background: #fdf3dc; color: #9a6a12; }
        .c-L { background: #e4f0fa; color: #1e6aa8; }
        .c-O, .c-HO { background: #f0f0f0; color: #888; }
        .c-P, .c-M, .c-W { background: #e8f6ee; color: #1b7d46; }
        .c-X, .c-F { color: #bbb; }
        .money { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; align-items: start; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 5px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
        th { background: #f4f4f6; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        td.r, th.r { text-align: right; white-space: nowrap; }
        td small { color: #777; display: block; font-size: 10px; }
        tr.tot td { border-top: 2px solid #222; font-weight: bold; }
        .net { margin-top: 8px; display: flex; justify-content: space-between; align-items: center; border: 2px solid #222; padding: 6px 10px; }
        .net .amt { font-size: 18px; font-weight: bold; }
        .pays { margin-top: 6px; }
        .sign { display: flex; justify-content: space-between; margin-top: 26px; gap: 40px; }
        .sign div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 3px; color: #555; }
        .foot { margin-top: 8px; color: #666; font-size: 10.5px; text-align: center; }
        .draft { position: absolute; top: 40%; left: 0; right: 0; text-align: center; font-size: 64px; color: rgba(214, 57, 57, .12); font-weight: bold; transform: rotate(-18deg); pointer-events: none; }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .slip { margin: 0 0 6mm; padding: 4mm 2mm; max-width: none; }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" onclick="window.print()"><?php echo lang('print'); ?></button>
    <button type="button" class="sec" onclick="window.close()"><?php echo lang('close'); ?></button>
</div>
<?php foreach ($slips as $slip):
    $i = $slip->item;
    $sal = $slip->salary;
    $days = json_decode((string) $i->days_json, true) ?: array();
    $rate_unit = lang('payroll_per_' . $i->salary_type);
    if ($i->salary_type == 'daily') {
        $paid_days = $i->rate > 0 ? $i->basic_amount / $i->rate : 0;
        $basic_note = $num($paid_days) . ' ' . lang('payroll_days_short') . ' &times; ' . getAmtPCustom($i->rate);
    } elseif ($i->salary_type == 'hourly') {
        $hours = $i->rate > 0 ? $i->basic_amount / $i->rate : 0;
        $basic_note = $num($hours) . ' ' . lang('payroll_hrs') . ' &times; ' . getAmtPCustom($i->rate);
    } else {
        $basic_note = lang('payroll_monthly_basic');
    }
    $bonus_lines = array();
    $deduction_lines = array();
    $adv_lines = array();
    foreach ($slip->adjustments as $a) {
        if ($a->adj_type == 'bonus' || $a->adj_type == 'allowance') {
            $bonus_lines[] = array(lang('payroll_adj_' . $a->adj_type) . ($a->note ? ' &ndash; ' . escape_output($a->note) : ''), $a->amount);
        } elseif ($a->adj_type == 'deduction') {
            $deduction_lines[] = array(lang('payroll_adj_deduction') . ($a->note ? ' &ndash; ' . escape_output($a->note) : ''), $a->amount);
        } else {
            $share = $this->Payroll_model->advanceShare($a, $i->pay_month, $this->Payroll_model->precision());
            if ($share > 0) {
                $adv_lines[] = escape_output(date($date_format, strtotime($a->adj_date))) . ($a->installments > 1 ? ' (' . ($this->Payroll_model->monthDiff($a->pay_month, $i->pay_month) + 1) . '/' . (int) $a->installments . ')' : '');
            }
        }
    }
    if ($i->sheet_bonus > 0) {
        $bonus_lines[] = array(lang('payroll_bonus') . ($i->note ? ' &ndash; ' . escape_output($i->note) : ''), $i->sheet_bonus);
    }
    if ($i->sheet_deduction > 0) {
        $deduction_lines[] = array(lang('payroll_deduction') . ($i->note ? ' &ndash; ' . escape_output($i->note) : ''), $i->sheet_deduction);
    }
    $paid_total = 0;
    foreach ($slip->payments as $p) {
        $paid_total += $p->amount;
    }
?>
<div class="slip">
    <?php if ($run->status == 'draft'): ?><div class="draft"><?php echo strtoupper(lang('payroll_status_draft')); ?></div><?php endif; ?>
    <div class="head">
        <div>
            <div class="biz"><?php echo escape_output($biz); ?></div>
            <?php if ($outlet && $outlet->outlet_name && $outlet->outlet_name != $biz): ?><div><?php echo escape_output($outlet->outlet_name); ?></div><?php endif; ?>
            <?php $addr = $outlet && $outlet->address ? $outlet->address : (isset($company->address) ? $company->address : ''); ?>
            <?php if ($addr): ?><div class="muted"><?php echo escape_output($addr); ?></div><?php endif; ?>
            <?php $phone = $outlet && $outlet->phone ? $outlet->phone : (isset($company->phone) ? $company->phone : ''); ?>
            <?php if ($phone): ?><div class="muted"><?php echo escape_output($phone); ?></div><?php endif; ?>
        </div>
        <div class="ttl">
            <div class="big"><?php echo strtoupper(lang('payroll_payslip')); ?></div>
            <div><b><?php echo escape_output($month_label); ?></b></div>
            <div class="muted"><?php echo escape_output(date($date_format, strtotime($run->period_start))); ?> &ndash; <?php echo escape_output(date($date_format, strtotime($run->period_end))); ?></div>
        </div>
    </div>

    <div class="grid">
        <div>
            <div class="kv"><span><?php echo lang('employee'); ?></span><span><?php echo escape_output($i->employee_name); ?></span></div>
            <div class="kv"><span><?php echo lang('designation'); ?></span><span><?php echo escape_output($i->designation) ?: '&ndash;'; ?></span></div>
            <div class="kv"><span><?php echo lang('payroll_employee_no'); ?></span><span>#<?php echo (int) $i->employee_id; ?></span></div>
        </div>
        <div>
            <div class="kv"><span><?php echo lang('payroll_salary_type'); ?></span><span><?php echo lang('payroll_type_' . $i->salary_type); ?> (<?php echo getAmtPCustom($i->rate); ?>/<?php echo $rate_unit; ?>)</span></div>
            <div class="kv"><span><?php echo lang('payroll_joining_date'); ?></span><span><?php echo $sal && $sal->joining_date ? escape_output(date($date_format, strtotime($sal->joining_date))) : '&ndash;'; ?></span></div>
            <div class="kv"><span><?php echo lang('payroll_payment_mode'); ?></span><span><?php
                if ($sal && $sal->payment_mode == 'bank') {
                    echo escape_output(trim($sal->bank_name . ' ' . $sal->bank_account)) ?: lang('payroll_mode_bank');
                } else {
                    echo lang('payroll_mode_cash');
                } ?></span></div>
        </div>
    </div>

    <h5><?php echo lang('payroll_attendance'); ?></h5>
    <div class="att">
        <div><b><?php echo (int) $i->days_in_month; ?></b><small><?php echo lang('payroll_days_in_month'); ?></small></div>
        <div><b><?php echo (int) $i->working_days; ?></b><small><?php echo lang('payroll_working_days'); ?></small></div>
        <div><b><?php echo $num($i->present_days); ?></b><small><?php echo lang('payroll_present_days'); ?><?php echo $i->half_days ? ' (' . (int) $i->half_days . ' ' . lang('payroll_half_short') . ')' : ''; ?></small></div>
        <div><b><?php echo $num($i->paid_leave_days); ?><?php echo $i->unpaid_leave_days > 0 ? ' + ' . $num($i->unpaid_leave_days) : ''; ?></b><small><?php echo lang('payroll_leave_paid_unpaid'); ?></small></div>
        <div><b><?php echo $num($i->absent_days); ?></b><small><?php echo lang('payroll_absent_days'); ?></small></div>
        <div><b><?php echo (int) $i->weekly_off_days; ?> / <?php echo (int) $i->holiday_days; ?></b><small><?php echo lang('payroll_off_holidays'); ?></small></div>
    </div>
    <div class="att">
        <div><b><?php echo $num($i->offday_worked); ?></b><small><?php echo lang('payroll_offday_worked'); ?></small></div>
        <div><b><?php echo $num($i->worked_hours); ?></b><small><?php echo lang('payroll_worked_hours'); ?></small></div>
        <div><b><?php echo $num($i->overtime_hours); ?></b><small><?php echo lang('payroll_overtime_hours'); ?></small></div>
        <div><b><?php echo (int) $i->missing_checkout_days; ?></b><small><?php echo lang('payroll_missing_checkout'); ?></small></div>
        <div><b><?php echo (int) $i->not_employed_days; ?></b><small><?php echo lang('payroll_not_employed'); ?></small></div>
        <div><b><?php echo getAmtPCustom($i->per_day_rate); ?></b><small><?php echo lang('payroll_per_day_rate'); ?></small></div>
    </div>
    <div class="strip">
        <?php foreach ($days as $d => $c): ?>
            <span class="c-<?php echo escape_output($c); ?>"><?php echo (int) $d; ?><i><?php echo $c == 'X' || $c == 'F' ? '&middot;' : escape_output($c); ?></i></span>
        <?php endforeach; ?>
    </div>

    <div class="money">
        <table>
            <thead><tr><th><?php echo lang('payroll_earnings'); ?></th><th class="r"><?php echo escape_output($currency); ?></th></tr></thead>
            <tbody>
                <tr><td><?php echo lang('payroll_basic'); ?><small><?php echo $basic_note; ?></small></td><td class="r"><?php echo getAmtPCustom($i->basic_amount); ?></td></tr>
                <?php foreach (array('housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance') as $f): if ($i->$f > 0): ?>
                    <tr><td><?php echo lang('payroll_' . $f); ?></td><td class="r"><?php echo getAmtPCustom($i->$f); ?></td></tr>
                <?php endif; endforeach; ?>
                <?php if ($i->overtime_amount > 0): ?>
                    <tr><td><?php echo lang('payroll_overtime'); ?><small><?php echo $num($i->overtime_hours); ?> <?php echo lang('payroll_hrs'); ?> &times; <?php echo getAmtPCustom($i->overtime_rate); ?></small></td><td class="r"><?php echo getAmtPCustom($i->overtime_amount); ?></td></tr>
                <?php endif; ?>
                <?php if ($i->offday_amount > 0): ?>
                    <tr><td><?php echo lang('payroll_offday_work'); ?><small><?php echo $num($i->offday_worked); ?> <?php echo lang('payroll_days_short'); ?> &times; <?php echo getAmtPCustom($i->per_day_rate); ?></small></td><td class="r"><?php echo getAmtPCustom($i->offday_amount); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($bonus_lines as $b): ?>
                    <tr><td><?php echo $b[0]; ?></td><td class="r"><?php echo getAmtPCustom($b[1]); ?></td></tr>
                <?php endforeach; ?>
                <tr class="tot"><td><?php echo lang('payroll_total_earnings'); ?></td><td class="r"><?php echo getAmtPCustom($i->total_earnings); ?></td></tr>
            </tbody>
        </table>
        <table>
            <thead><tr><th><?php echo lang('payroll_deductions'); ?></th><th class="r"><?php echo escape_output($currency); ?></th></tr></thead>
            <tbody>
                <?php if ($i->absent_deduction > 0): ?>
                    <tr><td><?php echo lang('payroll_absence'); ?><small><?php
                        $unpaid = (float) $i->absent_days + (float) $i->unpaid_leave_days;
                        echo $num($unpaid) . ' ' . lang('payroll_days_short');
                        echo $i->not_employed_days ? ' + ' . (int) $i->not_employed_days . ' ' . lang('payroll_not_employed_short') : '';
                        echo ' &times; ' . getAmtPCustom($i->per_day_rate); ?></small></td><td class="r"><?php echo getAmtPCustom($i->absent_deduction); ?></td></tr>
                <?php endif; ?>
                <?php if ($i->advance_deduction > 0): ?>
                    <tr><td><?php echo lang('payroll_advance_recovery'); ?><?php if ($adv_lines): ?><small><?php echo implode(', ', $adv_lines); ?></small><?php endif; ?></td><td class="r"><?php echo getAmtPCustom($i->advance_deduction); ?></td></tr>
                <?php endif; ?>
                <?php if ($i->fixed_deduction > 0): ?>
                    <tr><td><?php echo $sal && $sal->fixed_deduction_note ? escape_output($sal->fixed_deduction_note) : lang('payroll_fixed_deduction'); ?></td><td class="r"><?php echo getAmtPCustom($i->fixed_deduction); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($deduction_lines as $b): ?>
                    <tr><td><?php echo $b[0]; ?></td><td class="r"><?php echo getAmtPCustom($b[1]); ?></td></tr>
                <?php endforeach; ?>
                <?php if ($i->total_deductions == 0): ?>
                    <tr><td class="muted"><?php echo lang('payroll_no_deductions'); ?></td><td class="r">&ndash;</td></tr>
                <?php endif; ?>
                <tr class="tot"><td><?php echo lang('payroll_total_deductions'); ?></td><td class="r"><?php echo getAmtPCustom($i->total_deductions); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="net">
        <span><b><?php echo lang('payroll_net_pay'); ?></b></span>
        <span class="amt"><?php echo escape_output($currency); ?> <?php echo getAmtPCustom($i->net_salary); ?></span>
    </div>

    <?php if ($slip->payments): ?>
    <div class="pays">
        <h5><?php echo lang('payroll_salary_payments'); ?></h5>
        <table>
            <?php foreach ($slip->payments as $p): ?>
                <tr><td><?php echo escape_output(date($date_format, strtotime($p->pay_date))); ?></td><td><?php echo escape_output($p->payment_name); ?><?php echo $p->note ? ' &ndash; ' . escape_output($p->note) : ''; ?></td><td class="r"><?php echo getAmtPCustom($p->amount); ?></td></tr>
            <?php endforeach; ?>
            <tr class="tot"><td colspan="2"><?php echo lang('payroll_balance'); ?></td><td class="r"><?php echo getAmtPCustom($i->net_salary - $paid_total); ?></td></tr>
        </table>
    </div>
    <?php endif; ?>

    <div class="sign">
        <div><?php echo lang('payroll_employer_signature'); ?></div>
        <div><?php echo lang('payroll_employee_signature'); ?></div>
    </div>
    <?php if (!empty($payroll_settings->payslip_footer)): ?><div class="foot"><?php echo escape_output($payroll_settings->payslip_footer); ?></div><?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (!$slips): ?><div class="slip"><?php echo lang('payroll_no_eligible'); ?></div><?php endif; ?>
</body>
</html>
