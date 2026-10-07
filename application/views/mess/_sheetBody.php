<?php
/*
 | Body of the daily sheet (menu, headcount, production plan with stock,
 | member tick sheet, extras). Used by dailySheet.php (admin page) and
 | dailySheetPrint.php (A4 print). $plan = Mess_model::buildProduction().
 */
$date_format = $this->session->userdata('date_format');
$slot_name = isset($slots[$slot]) ? $slots[$slot]->name : '';
$c = $plan->counts;
$fmtq = function ($v) {
    $v = (float) $v;
    return abs($v - round($v)) < 0.005 ? number_format($v, 0) : number_format($v, 2);
};
// split the headcount by diet and mode, skipped kept apart
$hc = array('expected' => array('nonveg' => 0, 'veg' => 0, 'pickup' => 0, 'delivery' => 0), 'skipped' => array('nonveg' => 0, 'veg' => 0, 'pickup' => 0, 'delivery' => 0));
$list = array();
$skipped_rows = array();
foreach ($plan->rows as $r) {
    $k = $r->status == 'skipped' ? 'skipped' : 'expected';
    $hc[$k][$r->diet == 'veg' ? 'veg' : 'nonveg'] += $r->persons_expected;
    $hc[$k][$r->delivery_mode == 'delivery' ? 'delivery' : 'pickup'] += $r->persons_expected;
    if ($r->status == 'skipped') {
        $skipped_rows[] = $r;
    } else {
        $list[] = $r;
    }
}
usort($list, function ($a, $b) {
    if ($a->delivery_mode != $b->delivery_mode) {
        return strcmp($a->delivery_mode, $b->delivery_mode);
    }
    return strcasecmp($a->member_name, $b->member_name);
});
$extra_qty = 0;
foreach ($plan->extras as $e) {
    if ($e->status != 'cancelled') { $extra_qty += $e->qty; }
}
?>
<div class="mess-sheet">
    <div class="mess-sheet-title">
        <div><b><?php echo lang('mess_sheet_title'); ?> &middot; <?php echo escape_output(mb_strtoupper($slot_name)); ?></b></div>
        <div class="mess-muted"><?php echo escape_output(date('l', strtotime($date)) . ' ' . date($date_format, strtotime($date))); ?> &middot; <?php echo escape_output($outlet_name); ?> &middot; <?php echo lang('mess_printed_by'); ?> <?php echo escape_output(userName($this->session->userdata('user_id'))); ?> <?php echo escape_output(date('h:i A')); ?></div>
    </div>

    <h5><?php echo lang('menu'); ?></h5>
    <?php foreach (array('nonveg', 'veg') as $diet):
        $dishes = isset($plan->menu[$diet]) ? $plan->menu[$diet] : array(); ?>
        <div><b><?php echo lang('mess_' . $diet); ?>:</b>
            <?php if ($dishes) {
                $n = array();
                foreach ($dishes as $d) { $n[] = $d->dish_name; }
                echo escape_output(implode(' · ', $n));
            } elseif (in_array($diet, $plan->menu_missing)) {
                echo '<span class="mess-due">' . lang('mess_menu_missing') . '</span>';
            } else {
                echo '-';
            } ?>
        </div>
    <?php endforeach; ?>

    <h5><?php echo lang('mess_headcount'); ?></h5>
    <table class="mess-table mess-sheet-table">
        <thead><tr><th></th><th class="mess-num"><?php echo lang('mess_nonveg'); ?></th><th class="mess-num"><?php echo lang('mess_veg'); ?></th><th class="mess-num"><?php echo lang('total'); ?></th><th class="mess-num"><?php echo lang('mess_pickup'); ?></th><th class="mess-num"><?php echo lang('mess_delivery'); ?></th></tr></thead>
        <tbody>
            <tr><td><?php echo lang('mess_expected_persons'); ?></td><td class="mess-num"><?php echo $hc['expected']['nonveg']; ?></td><td class="mess-num"><?php echo $hc['expected']['veg']; ?></td><td class="mess-num"><b><?php echo $hc['expected']['nonveg'] + $hc['expected']['veg']; ?></b></td><td class="mess-num"><?php echo $hc['expected']['pickup']; ?></td><td class="mess-num"><?php echo $hc['expected']['delivery']; ?></td></tr>
            <tr><td><?php echo lang('mess_skipped_advance'); ?></td><td class="mess-num"><?php echo $hc['skipped']['nonveg']; ?></td><td class="mess-num"><?php echo $hc['skipped']['veg']; ?></td><td class="mess-num"><?php echo $hc['skipped']['nonveg'] + $hc['skipped']['veg']; ?></td><td class="mess-num"><?php echo $hc['skipped']['pickup']; ?></td><td class="mess-num"><?php echo $hc['skipped']['delivery']; ?></td></tr>
            <tr><td><?php echo lang('mess_extras'); ?> (<?php echo lang('mess_portions'); ?>)</td><td colspan="2"></td><td class="mess-num"><?php echo $fmtq($extra_qty); ?></td><td colspan="2"></td></tr>
            <?php if ($c->served || $c->not_taken): ?>
                <tr><td><?php echo lang('mess_served'); ?> / <?php echo lang('mess_not_taken'); ?></td><td colspan="2"></td><td class="mess-num"><?php echo (int) $c->served; ?> / <?php echo (int) $c->not_taken; ?></td><td colspan="2"></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h5><?php echo lang('mess_production_plan'); ?></h5>
    <?php if (!$plan->dishes): ?>
        <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
    <?php else: ?>
    <table class="mess-table mess-sheet-table">
        <thead><tr><th><?php echo lang('mess_dish'); ?></th><th class="mess-num"><?php echo lang('mess_portions'); ?></th><th><?php echo lang('mess_ingredient_need'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($plan->dishes as $d): ?>
            <tr>
                <td><b><?php echo escape_output($d->dish_name); ?></b>
                    <?php if ($d->food_menu_id && !$d->recipe_lines): ?><div class="mess-muted"><?php echo lang('mess_no_recipe'); ?></div><?php endif; ?></td>
                <td class="mess-num"><b><?php echo $fmtq($d->portions); ?></b>
                    <?php if ($d->extras || $d->buffer): ?><div class="mess-muted"><?php echo $fmtq($d->from_menu); ?><?php echo $d->extras ? ' +' . $fmtq($d->extras) . ' ' . lang('mess_extra') : ''; ?><?php echo $d->buffer ? ' +' . $fmtq($d->buffer) . ' ' . lang('mess_buffer') : ''; ?></div><?php endif; ?></td>
                <td>
                    <?php $parts = array();
                    foreach ($d->needs as $n) {
                        $ing = $plan->ingredients[$n->ingredient_id];
                        $parts[] = '<span class="' . ($ing->short > 0 ? 'mess-due' : '') . '">' . escape_output($n->name) . ' ' . $fmtq($n->need) . ' ' . escape_output($n->unit) . '</span>';
                    }
                    echo $parts ? implode(' · ', $parts) : '<span class="mess-muted">-</span>'; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($plan->ingredients): ?>
    <h5><?php echo lang('mess_stock'); ?> <?php if ($plan->short_count): ?><span class="mess-badge expired"><?php echo (int) $plan->short_count; ?> <?php echo lang('mess_short'); ?></span><?php endif; ?></h5>
    <table class="mess-table mess-sheet-table">
        <thead><tr><th><?php echo lang('ingredient'); ?></th><th class="mess-num"><?php echo lang('mess_ingredient_need'); ?></th><th class="mess-num"><?php echo lang('mess_stock'); ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($plan->ingredients as $ing): ?>
            <tr>
                <td><?php echo escape_output($ing->name); ?></td>
                <td class="mess-num"><?php echo $fmtq($ing->need); ?> <?php echo escape_output($ing->unit); ?></td>
                <td class="mess-num"><?php echo $fmtq($ing->stock); ?> <?php echo escape_output($ing->unit); ?></td>
                <td><?php if ($ing->short > 0): ?><span class="mess-badge expired"><?php echo lang('mess_short'); ?> <?php echo $fmtq($ing->short); ?> <?php echo escape_output($ing->unit); ?></span><?php else: ?><span class="mess-badge active"><?php echo lang('mess_ok'); ?></span><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h5><?php echo lang('mess_member_checklist'); ?> (<?php echo count($list); ?>)</h5>
    <table class="mess-table mess-sheet-table mess-ticks">
        <thead><tr><th>#</th><th><?php echo lang('mess_member'); ?></th><th class="mess-num"><?php echo lang('qty'); ?></th><th><?php echo lang('mess_diet'); ?></th><th><?php echo lang('mess_mode'); ?> &middot; <?php echo lang('phone'); ?></th><th><?php echo lang('mess_served'); ?></th><th><?php echo lang('mess_not_taken'); ?></th><th><?php echo lang('mess_sign'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($list as $i => $r): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><b><?php echo escape_output($r->member_name); ?></b><?php echo $r->building_room ? '<div class="mess-muted">' . escape_output($r->building_room) . '</div>' : ''; ?>
                    <?php if ($r->delivery_mode == 'delivery' && ($r->delivery_address || $r->address)): ?><div class="mess-muted"><?php echo escape_output($r->delivery_address ? $r->delivery_address : $r->address); ?></div><?php endif; ?></td>
                <td class="mess-num"><b><?php echo (int) $r->persons_expected; ?></b></td>
                <td><?php echo $r->diet == 'veg' ? 'V' : 'NV'; ?></td>
                <td><?php echo lang('mess_' . $r->delivery_mode); ?><?php echo $r->phone ? ' · ' . escape_output($r->phone) : ''; ?></td>
                <td class="box"><?php echo $r->status == 'served' || $r->status == 'partial' ? '&#10003;' . ($r->status == 'partial' ? ' ' . (int) $r->persons_served : '') : ''; ?></td>
                <td class="box"><?php echo $r->status == 'not_taken' ? '&#10007;' : ''; ?></td>
                <td class="box wide"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($skipped_rows): ?>
        <div class="mess-muted mt-1"><?php echo lang('mess_skipped'); ?>:
            <?php $n = array(); foreach ($skipped_rows as $r) { $n[] = $r->member_name . ' (' . (int) $r->persons_expected . ')'; } echo escape_output(implode(', ', $n)); ?></div>
    <?php endif; ?>

    <?php
    $live_extras = array();
    foreach ($plan->extras as $e) { if ($e->status != 'cancelled') { $live_extras[] = $e; } }
    if ($live_extras): ?>
        <h5><?php echo lang('mess_extras_today'); ?></h5>
        <div>
            <?php $parts = array();
            foreach ($live_extras as $e) {
                $parts[] = '<b>' . escape_output($e->member_name) . '</b>: ' . (float) $e->qty . ' x ' . escape_output($e->item_name) . ' (' . ($e->settle_type == 'pay_now' ? lang('mess_pay_now') : lang('mess_on_account')) . ', ' . getAmtPCustom($e->total) . ')';
            }
            echo implode(' &middot; ', $parts); ?>
        </div>
    <?php endif; ?>
</div>
