<?php
/*
 | Profit & loss (plan 6.6) for the chosen period against the one before ($pnl = pnl or pnl7).
 | Lines in the order net profit is worked out; discounts, complimentary items and staff
 | meals are shown for information (they are already out of net sales).
 | Each line: lang key, field, which way is good (up / down / '' neutral), sign shown.
 */
$cur = $pnl['cur'];
$prev = $pnl['prev'];
$lines = array(
    array('ai_l_gross', 'gross_sales', 'up', ''),
    array('ai_l_vat', 'vat', '', '&minus;'),
    array('ai_l_tips', 'tips', '', '&minus;'),
    array('ai_l_net_sales', 'net_sales', 'up', '=', 'ai-total'),
    array('ai_l_mess', 'mess_revenue', 'up', '+'),
    array('ai_l_refunds', 'refunds', 'down', '&minus;'),
    array('ai_l_food', 'food_cost', 'down', '&minus;'),
    array('ai_l_waste', 'waste', 'down', '&minus;'),
    array('ai_l_salaries', 'salaries', 'down', '&minus;'),
    array('ai_l_expenses', 'expenses_total', 'down', '&minus;'),
);
$prev_expenses = array();
foreach ($prev['expenses'] as $e) {
    $prev_expenses[$e['category']] = $e['amount'];
}
$info = array(
    array('ai_l_discounts', 'discounts'),
    array('ai_l_complimentary', 'complimentary'),
    array('ai_l_staff', 'staff_meals'),
);
$hide_zero = array('tips', 'mess_revenue', 'refunds', 'salaries');
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_used_pnl'); ?></h4>
                <div class="ai-muted"><?php echo $period; ?> <?php echo lang('ai_against'); ?> <?php echo $period_prev; ?></div>
            </div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'pnl')); ?>
        <?php $this->load->view('ai_insight/_gate', array('section' => 'pnl')); ?>

        <?php if ($m['health']['gates']['pnl'] != 'blocked'): ?>
            <div class="table-responsive">
                <table class="table ai-table ai-pnl">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="text-end"><?php echo lang('ai_col_this'); ?></th>
                        <th class="text-end"><?php echo lang('ai_col_prev'); ?></th>
                        <th class="text-end"><?php echo lang('ai_col_change'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo lang('ai_l_orders'); ?></td>
                        <td class="text-end"><?php echo $f->num($cur['orders'], 0); ?></td>
                        <td class="text-end"><?php echo $f->num($prev['orders'], 0); ?></td>
                        <td class="text-end"><?php echo $f->change($pnl['change']['orders'], 'up', false); ?></td>
                    </tr>
                    <?php foreach ($lines as $l): ?>
                        <?php if (in_array($l[1], $hide_zero) && (float) $cur[$l[1]] == 0 && (float) $prev[$l[1]] == 0) continue; ?>
                        <tr class="<?php echo isset($l[4]) ? $l[4] : ''; ?>">
                            <td><span class="ai-sign"><?php echo $l[3]; ?></span><?php echo lang($l[0]); ?>
                                <?php if ($l[1] == 'food_cost' && $cur['food_cost_pct'] !== null): ?>
                                    <span class="ai-muted">(<?php echo $f->pct($cur['food_cost_pct']); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo $f->money($cur[$l[1]]); ?></td>
                            <td class="text-end"><?php echo $f->money($prev[$l[1]]); ?></td>
                            <td class="text-end"><?php echo $f->change($pnl['change'][$l[1]], $l[2]); ?></td>
                        </tr>
                        <?php if ($l[1] == 'expenses_total'): ?>
                            <?php foreach ($cur['expenses'] as $e): ?>
                                <tr class="ai-sub-row">
                                    <td><?php echo $f->e($e['category']); ?></td>
                                    <td class="text-end"><?php echo $f->money($e['amount']); ?></td>
                                    <td class="text-end"><?php echo isset($prev_expenses[$e['category']]) ? $f->money($prev_expenses[$e['category']]) : '&ndash;'; ?></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <tr class="ai-total ai-grand">
                        <td><span class="ai-sign">=</span><?php echo lang('ai_l_net_profit'); ?></td>
                        <td class="text-end<?php echo $cur['net_profit'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $f->money($cur['net_profit']); ?></td>
                        <td class="text-end<?php echo $prev['net_profit'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $f->money($prev['net_profit']); ?></td>
                        <td class="text-end"><?php echo $f->change($pnl['change']['net_profit']); ?></td>
                    </tr>
                    <tr class="ai-info-head"><td colspan="4"><?php echo lang('ai_l_included'); ?></td></tr>
                    <?php foreach ($info as $l): ?>
                        <tr class="ai-sub-row">
                            <td><?php echo lang($l[0]); ?></td>
                            <td class="text-end"><?php echo $f->money($cur[$l[1]]); ?></td>
                            <td class="text-end"><?php echo $f->money($prev[$l[1]]); ?></td>
                            <td class="text-end"><?php echo $f->change($pnl['change'][$l[1]], 'down'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ai-sub-row">
                        <td><?php echo lang('ai_l_avg_ticket'); ?></td>
                        <td class="text-end"><?php echo $f->money($cur['avg_ticket']); ?></td>
                        <td class="text-end"><?php echo $f->money($prev['avg_ticket']); ?></td>
                        <td class="text-end"><?php echo $f->change(isset($pnl['change']['avg_ticket']) ? $pnl['change']['avg_ticket'] : null); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ai-muted ai-foot"><?php echo lang('ai_pnl_foot'); ?></div>
        <?php endif; ?>
    </div>
</div>
