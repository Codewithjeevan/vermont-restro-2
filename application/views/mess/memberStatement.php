<?php
$date_format = $this->session->userdata('date_format');
$currency = $this->session->userdata('currency');
$fmt = function ($d) use ($date_format) {
    return $d ? date($date_format, strtotime($d)) : '';
};
$charges = 0;
$credits = 0;
foreach ($ledger as $l) {
    $charges += $l->charge;
    $credits += $l->credit;
}
$balance = $charges - $credits;
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo lang('mess_statement'); ?> - <?php echo escape_output($member->name); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f2f5; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 13px; }
        .page { width: 210mm; max-width: 100%; margin: 14px auto; background: #fff; padding: 14mm 12mm; }
        .top { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 14px; }
        .biz { font-size: 18px; font-weight: bold; }
        h1 { font-size: 16px; margin: 0 0 4px; letter-spacing: .05em; }
        .muted { color: #666; font-size: 12px; }
        .boxes { display: flex; gap: 10px; margin: 12px 0; }
        .box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; }
        .box b { display: block; font-size: 16px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e5e5; padding: 6px 5px; text-align: left; vertical-align: top; }
        th { background: #f4f4f6; font-size: 12px; }
        .r { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; border-top: 2px solid #222; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        .actions { width: 210mm; max-width: 100%; margin: 0 auto 20px; display: flex; gap: 8px; }
        .actions button { padding: 9px 18px; border: 0; border-radius: 6px; background: #7367f0; color: #fff; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; width: 100%; padding: 0; }
            .actions { display: none; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()"><?php echo lang('print'); ?></button></div>
<div class="page">
    <div class="top">
        <div>
            <div class="biz"><?php echo escape_output($company->business_name); ?></div>
            <?php if (!empty($company->address)): ?><div class="muted"><?php echo escape_output($company->address); ?></div><?php endif; ?>
            <?php if (!empty($company->phone)): ?><div class="muted"><?php echo escape_output($company->phone); ?></div><?php endif; ?>
        </div>
        <div style="text-align:right">
            <h1><?php echo strtoupper(lang('mess_statement')); ?></h1>
            <div class="muted"><?php echo escape_output($fmt(date('Y-m-d'))); ?></div>
        </div>
    </div>

    <div><b><?php echo escape_output($member->name); ?></b> (<?php echo escape_output($member->member_code); ?>)</div>
    <div class="muted">
        <?php echo escape_output(trim(($member->phone ? $member->phone : '') . ($member->building_room ? ' · ' . $member->building_room : '') . ($member->address ? ' · ' . $member->address : ''), ' ·')); ?>
    </div>

    <div class="boxes">
        <div class="box"><?php echo lang('mess_charge'); ?><b><?php echo escape_output($currency); ?> <?php echo getAmtPCustom($charges); ?></b></div>
        <div class="box"><?php echo lang('mess_paid'); ?><b><?php echo escape_output($currency); ?> <?php echo getAmtPCustom($credits); ?></b></div>
        <div class="box"><?php echo $balance > 0.0005 ? lang('mess_balance_due') : ($balance < -0.0005 ? lang('mess_advance_credit') : lang('balance')); ?><b><?php echo escape_output($currency); ?> <?php echo getAmtPCustom(abs($balance)); ?></b></div>
    </div>

    <h2><?php echo lang('mess_ledger'); ?></h2>
    <table>
        <thead><tr>
            <th><?php echo lang('date'); ?></th>
            <th><?php echo lang('details'); ?></th>
            <th class="r"><?php echo lang('mess_charge'); ?></th>
            <th class="r"><?php echo lang('mess_paid'); ?></th>
            <th class="r"><?php echo lang('balance'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($ledger as $l): ?>
            <tr>
                <td><?php echo escape_output($fmt($l->date)); ?></td>
                <td><?php echo escape_output($l->text); ?></td>
                <td class="r"><?php echo $l->charge ? getAmtPCustom($l->charge) : ''; ?></td>
                <td class="r"><?php echo $l->credit ? getAmtPCustom($l->credit) : ''; ?></td>
                <td class="r"><?php echo getAmtPCustom($l->balance); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr>
            <td></td><td><?php echo lang('total'); ?></td>
            <td class="r"><?php echo getAmtPCustom($charges); ?></td>
            <td class="r"><?php echo getAmtPCustom($credits); ?></td>
            <td class="r"><?php echo getAmtPCustom($balance); ?></td>
        </tr></tfoot>
    </table>

    <?php
    // meals of each cycle, from the meal register
    $att_rows = $this->db->query("SELECT subscription_id, status, SUM(persons_expected) AS persons FROM tbl_mess_attendance WHERE member_id = ? GROUP BY subscription_id, status", array($member->id))->result();
    $att = array();
    foreach ($att_rows as $a) {
        $att[$a->subscription_id][$a->status] = (int) $a->persons;
    }
    if ($att): ?>
    <h2><?php echo lang('mess_meal_register'); ?></h2>
    <table>
        <thead><tr>
            <th><?php echo lang('mess_subscription'); ?></th>
            <th class="r"><?php echo lang('mess_served'); ?></th>
            <th class="r"><?php echo lang('mess_not_taken'); ?></th>
            <th class="r"><?php echo lang('mess_skipped'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($subscriptions as $s):
            if (!isset($att[$s->id])) { continue; }
            $a = $att[$s->id]; ?>
            <tr>
                <td><?php echo escape_output($fmt($s->start_date) . ' - ' . $fmt($s->end_date) . ' · ' . $s->meals_text); ?></td>
                <td class="r"><?php echo (isset($a['served']) ? $a['served'] : 0) + (isset($a['partial']) ? $a['partial'] : 0); ?></td>
                <td class="r"><?php echo isset($a['not_taken']) ? $a['not_taken'] : 0; ?></td>
                <td class="r"><?php echo isset($a['skipped']) ? $a['skipped'] : 0; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
