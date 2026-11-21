<?php
/*
 | Bill email (Invoice_mailer::buildInvoiceEmail). Email clients ignore <style> blocks and
 | most CSS, so this is tables + inline styles only, max 600px wide.
 | Every value from the database is escaped with $e.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$c = $company;
$s = $sale;
$t = function ($key) use ($mailer) {
    return $mailer->t($key);
};
$m = function ($v) use ($mailer, $c) {
    return htmlspecialchars($mailer->money($c, $v), ENT_QUOTES, 'UTF-8');
};
$num = function ($v) {
    return rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
};
$td = 'padding:8px 6px;border-bottom:1px solid #eeeeee;font-size:14px;color:#333333;vertical-align:top;';
$tdr = $td . 'text-align:right;white-space:nowrap;';
$row = function ($label, $value, $strong = false) use ($e) {
    $style = $strong ? 'font-weight:bold;font-size:16px;color:#111111;' : 'font-size:14px;color:#555555;';
    return '<tr><td style="padding:4px 6px;' . $style . '">' . $e($label) . '</td><td style="padding:4px 6px;text-align:right;white-space:nowrap;' . $style . '">' . $value . '</td></tr>';
};
$taxes = $s->sale_vat_objects ? json_decode($s->sale_vat_objects) : array();
$outlet_address = $outlet ? $outlet->address : $c->address;
$outlet_phone = $outlet ? $outlet->phone : $c->phone;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $e($s->sale_no); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:20px 0;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:6px;overflow:hidden;">

    <tr><td style="padding:24px 24px 8px 24px;text-align:center;">
        <?php if ($logo_url): ?>
            <img src="<?php echo $e($logo_url); ?>" alt="<?php echo $e($outlet_name); ?>" style="max-width:140px;max-height:90px;border:0;margin-bottom:8px;">
        <?php endif; ?>
        <div style="font-size:20px;font-weight:bold;color:#111111;"><?php echo $e($outlet_name); ?></div>
        <?php if ($outlet_address): ?><div style="font-size:13px;color:#666666;margin-top:4px;"><?php echo $e($outlet_address); ?></div><?php endif; ?>
        <?php if ($outlet_phone): ?><div style="font-size:13px;color:#666666;"><?php echo $e($t('ei_phone')); ?>: <?php echo $e($outlet_phone); ?></div><?php endif; ?>
        <?php if ($is_tax_invoice): ?>
            <div style="font-size:13px;color:#666666;"><?php echo $e($c->tax_title ? $c->tax_title . ' ' : ''); ?><?php echo $e($t('ei_trn')); ?>: <?php echo $e($c->tax_registration_no); ?></div>
        <?php endif; ?>
    </td></tr>

    <tr><td style="padding:8px 24px;text-align:center;">
        <div style="display:inline-block;font-size:15px;font-weight:bold;letter-spacing:1px;color:#ffffff;background:#0c5889;padding:6px 14px;border-radius:4px;">
            <?php echo $e($is_tax_invoice ? $t('ei_tax_invoice') : $t('ei_invoice')); ?>
        </div>
    </td></tr>

    <tr><td style="padding:8px 24px 16px 24px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;color:#444444;">
            <tr>
                <td style="padding:2px 0;"><b><?php echo $e($t('ei_invoice_no')); ?>:</b> <?php echo $e($s->sale_no); ?></td>
                <td style="padding:2px 0;text-align:right;"><b><?php echo $e($t('ei_date')); ?>:</b> <?php echo $e($bill_date); ?></td>
            </tr>
            <tr>
                <td style="padding:2px 0;"><b><?php echo $e($t('ei_customer')); ?>:</b> <?php echo $e($s->customer_name); ?></td>
                <td style="padding:2px 0;text-align:right;"><?php if ($order_type_text): ?><b><?php echo $e($t('ei_order_type')); ?>:</b> <?php echo $e($order_type_text); ?><?php endif; ?></td>
            </tr>
            <?php if ($is_tax_invoice && trim((string) $s->customer_tax_no) !== ''): ?>
            <tr><td colspan="2" style="padding:2px 0;"><b><?php echo $e($t('ei_customer_trn')); ?>:</b> <?php echo $e($s->customer_tax_no); ?></td></tr>
            <?php endif; ?>
            <?php if ($s->orders_table_text || $s->waiter_name): ?>
            <tr>
                <td style="padding:2px 0;"><?php if ($s->orders_table_text): ?><b><?php echo $e($t('ei_table')); ?>:</b> <?php echo $e($s->orders_table_text); ?><?php endif; ?></td>
                <td style="padding:2px 0;text-align:right;"><?php if ($s->waiter_name): ?><b><?php echo $e($t('ei_waiter')); ?>:</b> <?php echo $e($s->waiter_name); ?><?php endif; ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </td></tr>

    <tr><td style="padding:0 24px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr style="background:#f7f7f7;">
                <th align="left" style="padding:8px 6px;font-size:12px;color:#666666;text-transform:uppercase;"><?php echo $e($t('ei_item')); ?></th>
                <th align="right" style="padding:8px 6px;font-size:12px;color:#666666;text-transform:uppercase;"><?php echo $e($t('ei_qty')); ?></th>
                <th align="right" style="padding:8px 6px;font-size:12px;color:#666666;text-transform:uppercase;"><?php echo $e($t('ei_price')); ?></th>
                <th align="right" style="padding:8px 6px;font-size:12px;color:#666666;text-transform:uppercase;"><?php echo $e($t('ei_amount')); ?></th>
            </tr>
            <?php $total_qty = 0; foreach ($items as $item): $total_qty += (float) $item->qty; ?>
            <tr>
                <td style="<?php echo $td; ?>">
                    <?php echo $e($item->menu_name); ?>
                    <?php if ((float) $item->discount_amount > 0): ?>
                        <div style="font-size:12px;color:#888888;"><?php echo $e($t('ei_discount')); ?>: -<?php echo $m($item->discount_amount); ?></div>
                    <?php endif; ?>
                    <?php if ($item->menu_combo_items): ?>
                        <div style="font-size:12px;color:#888888;"><?php echo $e($item->menu_combo_items); ?></div>
                    <?php endif; ?>
                    <?php if ($item->menu_note): ?>
                        <div style="font-size:12px;color:#888888;"><?php echo $e($item->menu_note); ?></div>
                    <?php endif; ?>
                </td>
                <td style="<?php echo $tdr; ?>"><?php echo $e($num($item->qty)); ?></td>
                <td style="<?php echo $tdr; ?>"><?php echo $m($item->menu_unit_price); ?></td>
                <td style="<?php echo $tdr; ?>"><?php echo $m($item->menu_price_with_discount); ?></td>
            </tr>
            <?php if (!empty($item->modifiers)):
                $mod_names = array();
                $mod_total = 0;
                foreach ($item->modifiers as $mod) {
                    $mod_names[] = $mod->name;
                    $mod_total += (float) $mod->modifier_price;
                }
            ?>
            <tr>
                <td colspan="3" style="<?php echo $td; ?>font-size:12px;color:#666666;padding-left:16px;">+ <?php echo $e(implode(', ', array_filter($mod_names))); ?></td>
                <td style="<?php echo $tdr; ?>font-size:12px;color:#666666;"><?php echo $m($mod_total); ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </td></tr>

    <tr><td style="padding:12px 24px 4px 24px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <?php
            echo $row($t('ei_total_items'), $e($num($total_qty)));
            echo $row($t('ei_sub_total'), $m($s->sub_total));
            if ((float) $s->total_discount_amount > 0) {
                echo $row($t('ei_discount'), '-' . $m($s->total_discount_amount));
            }
            if ((float) $s->delivery_charge_actual_charge > 0) {
                $charge_label = $s->charge_type === 'service' ? $t('ei_service_charge') : $t('ei_delivery_charge');
                echo $row($charge_label, $m($s->delivery_charge_actual_charge));
            }
            if ((float) $s->tips_amount_actual_charge > 0) {
                echo $row($t('ei_tips'), $m($s->tips_amount_actual_charge));
            }
            if ($c->collect_tax === 'Yes' && is_array($taxes)) {
                foreach ($taxes as $tax) {
                    if (isset($tax->tax_field_amount) && (float) $tax->tax_field_amount > 0) {
                        echo $row($tax->tax_field_type, $m($tax->tax_field_amount));
                    }
                }
            }
            if ((float) $s->rounding_amount_hidden != 0) {
                echo $row($t('ei_rounding'), $m($s->rounding_amount_hidden));
            }
            ?>
            <tr><td colspan="2" style="border-top:2px solid #111111;padding:0;height:4px;"></td></tr>
            <?php
            echo $row($t('ei_grand_total'), $m($s->total_payable), true);
            if ((float) $s->paid_amount > 0) {
                echo $row($t('ei_paid'), $m($s->paid_amount));
            }
            if ((float) $s->due_amount > 0) {
                echo $row($t('ei_due'), $m($s->due_amount));
            }
            if ((float) $s->change_amount > 0) {
                echo $row($t('ei_change'), $m($s->change_amount));
            }
            ?>
        </table>
    </td></tr>

    <?php if (!empty($payments)): ?>
    <tr><td style="padding:8px 24px;">
        <div style="font-size:12px;color:#666666;text-transform:uppercase;margin-bottom:4px;"><?php echo $e($t('ei_payment')); ?></div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <?php foreach ($payments as $p):
                $p_name = $p->method_name ? $p->method_name : $p->payment_name;
                if ((int) $p->currency_type === 1 && $p->multi_currency) {
                    $p_name .= ' (' . $p->multi_currency . ')';
                }
                echo $row($p_name, $m($p->amount));
            endforeach; ?>
        </table>
    </td></tr>
    <?php endif; ?>

    <?php if ($online_url): ?>
    <tr><td style="padding:16px 24px;text-align:center;">
        <a href="<?php echo $e($online_url); ?>" style="display:inline-block;background:#0c5889;color:#ffffff;text-decoration:none;font-size:14px;padding:10px 18px;border-radius:4px;"><?php echo $e($t('ei_view_online')); ?></a>
    </td></tr>
    <?php endif; ?>

    <?php if (trim(strip_tags((string) $c->invoice_footer)) !== ''): ?>
    <tr><td style="padding:8px 24px 20px 24px;text-align:center;font-size:13px;color:#666666;">
        <?php echo nl2br($e(strip_tags((string) $c->invoice_footer))); ?>
    </td></tr>
    <?php endif; ?>

    <tr><td style="padding:12px 24px;background:#fafafa;text-align:center;font-size:11px;color:#999999;">
        <?php echo $e(sprintf($t('ei_footer_note'), $outlet_name)); ?>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
