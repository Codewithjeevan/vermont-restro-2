<?php
$date_format = $this->session->userdata('date_format');
$currency = $this->session->userdata('currency');
$type_label = array('payment' => lang('payment'), 'refund' => lang('refund'), 'adjustment' => lang('mess_adjustment'));
$autoprint = $this->input->get('autoprint') ? true : false;
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo lang('mess_receipt'); ?> <?php echo escape_output($payment->reference_no); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f2f5; font-family: Arial, Helvetica, sans-serif; color: #111; }
        .receipt { width: 80mm; max-width: 100%; margin: 12px auto; background: #fff; padding: 10px 12px 14px; font-size: 12.5px; line-height: 1.45; }
        .c { text-align: center; }
        .biz { font-size: 16px; font-weight: bold; }
        .ttl { margin: 8px 0 6px; font-weight: bold; letter-spacing: .06em; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.r { text-align: right; white-space: nowrap; }
        .amt { font-size: 18px; font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .muted { color: #555; font-size: 11.5px; }
        .actions { width: 80mm; max-width: 100%; margin: 0 auto 20px; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 9px; border: 0; border-radius: 6px; background: #7367f0; color: #fff; font-size: 14px; cursor: pointer; }
        .actions button.sec { background: #e9e8ee; color: #333; }
        @media print {
            body { background: #fff; }
            .receipt { margin: 0; width: 100%; padding: 0 2mm; }
            .actions { display: none; }
            @page { margin: 2mm; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="c">
        <div class="biz"><?php echo escape_output($company->business_name); ?></div>
        <?php if ($outlet_name && $outlet_name != $company->business_name): ?><div><?php echo escape_output($outlet_name); ?></div><?php endif; ?>
        <?php if (!empty($company->address)): ?><div class="muted"><?php echo escape_output($company->address); ?></div><?php endif; ?>
        <?php if (!empty($company->phone)): ?><div class="muted"><?php echo escape_output($company->phone); ?></div><?php endif; ?>
    </div>
    <div class="ttl c"><?php echo lang('mess_receipt_title'); ?></div>
    <table>
        <tr><td><?php echo lang('ref_no'); ?></td><td class="r"><?php echo escape_output($payment->reference_no); ?></td></tr>
        <tr><td><?php echo lang('date'); ?></td><td class="r"><?php echo escape_output(date($date_format, strtotime($payment->only_date)) . ' ' . date('h:i A', strtotime($payment->date_time))); ?></td></tr>
        <tr><td><?php echo lang('mess_received_from'); ?></td><td class="r"><b><?php echo escape_output($payment->member_name); ?></b><br><span class="muted"><?php echo escape_output($payment->member_code . ($payment->phone ? ' · ' . $payment->phone : '')); ?></span></td></tr>
        <?php if ($payment->sub_start): ?>
            <tr><td><?php echo lang('mess_subscription'); ?></td><td class="r"><?php echo escape_output(date($date_format, strtotime($payment->sub_start)) . ' - ' . date($date_format, strtotime($payment->sub_end))); ?></td></tr>
        <?php endif; ?>
        <tr><td><?php echo lang('type'); ?></td><td class="r"><?php echo escape_output($type_label[$payment->type]); ?></td></tr>
        <?php if ($payment->payment_name): ?>
            <tr><td><?php echo lang('payment_method'); ?></td><td class="r"><?php echo escape_output($payment->payment_name); ?></td></tr>
        <?php endif; ?>
    </table>
    <div class="line"></div>
    <table>
        <tr><td class="amt"><?php echo lang('amount'); ?></td><td class="r amt"><?php echo escape_output($currency); ?> <?php echo getAmtPCustom(abs($payment->amount)); ?></td></tr>
    </table>
    <?php if ($payment->note): ?><div class="muted"><?php echo escape_output($payment->note); ?></div><?php endif; ?>
    <div class="line"></div>
    <table>
        <tr>
            <td><?php echo $balance > 0.0005 ? lang('mess_balance_due') : ($balance < -0.0005 ? lang('mess_advance_credit') : lang('balance')); ?></td>
            <td class="r"><b><?php echo escape_output($currency); ?> <?php echo getAmtPCustom(abs($balance)); ?></b></td>
        </tr>
        <tr><td><?php echo lang('mess_received_by'); ?></td><td class="r"><?php echo escape_output($payment->user_name); ?></td></tr>
    </table>
    <div class="line"></div>
    <div class="c muted"><?php echo lang('mess_thank_you'); ?></div>
</div>
<div class="actions">
    <button type="button" onclick="window.print()"><?php echo lang('print'); ?></button>
    <button type="button" class="sec" onclick="window.close()"><?php echo lang('close'); ?></button>
</div>
<?php if ($autoprint): ?>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.focus(); window.print(); }, 150);
    });
</script>
<?php endif; ?>
</body>
</html>
