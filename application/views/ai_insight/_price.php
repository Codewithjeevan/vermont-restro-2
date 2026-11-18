<?php
/*
 | Price check (plan 6.4): food cost % of each costed item against the company target,
 | with a suggested price. Always the last 28 days. Items above the target come first;
 | items on target open with "Show all".
 */
$price = $m['price'];
$band_label = array(
    'loss' => lang('ai_b_loss'),
    'critical' => lang('ai_b_critical'),
    'high' => lang('ai_b_high'),
    'watch' => lang('ai_b_watch'),
    'good' => lang('ai_b_good'),
);
$limit = max(10, (int) $price['above_target_count']);
$target = (float) $price['target_pct'];
$inclusive = !empty($m['meta']['tax_inclusive']);
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_used_price'); ?></h4>
                <div class="ai-muted"><?php echo sprintf(lang('ai_price_hint'), $f->num($target), $f->num($price['rounding'])); ?> &middot; <?php echo $period_28; ?></div>
            </div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'price')); ?>
        <?php $this->load->view('ai_insight/_gate', array('section' => 'price')); ?>

        <?php if ($m['health']['gates']['price'] != 'blocked' && $price['items']): ?>
            <div class="ai-gates">
                <?php foreach ($band_label as $key => $label): ?>
                    <?php if ($price['band_counts'][$key] > 0): ?>
                        <span class="ai-band is-<?php echo $key; ?>"><?php echo $f->e($label); ?>: <?php echo (int) $price['band_counts'][$key]; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="ai-collapsible ai-collapsed">
                <div class="table-responsive">
                    <table class="table ai-table">
                        <thead>
                        <tr>
                            <th><?php echo lang('ai_item'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_price'); ?></th>
                            <?php if ($inclusive): ?>
                                <th class="text-end"><?php echo lang('ai_col_net_price'); ?></th>
                            <?php endif; ?>
                            <th class="text-end"><?php echo lang('ai_col_plate_cost'); ?></th>
                            <th class="ai-col-bar"><?php echo lang('ai_col_food_cost'); ?></th>
                            <th><?php echo lang('ai_col_status'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_sold_28'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_suggested'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_gain'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($price['items'] as $i => $row): ?>
                            <tr class="<?php echo $i >= $limit ? 'ai-more' : ''; ?>">
                                <td><strong><?php echo $f->e($row['name']); ?></strong></td>
                                <td class="text-end"><?php echo $f->money($row['list_price']); ?></td>
                                <?php if ($inclusive): ?>
                                    <td class="text-end"><?php echo $f->money($row['net_price']); ?></td>
                                <?php endif; ?>
                                <td class="text-end"><?php echo $f->money($row['plate_cost']); ?></td>
                                <td class="ai-col-bar">
                                    <div class="ai-bar is-<?php echo $f->e($row['band']); ?>" title="<?php echo $f->pct($row['food_cost_pct']); ?>">
                                        <span style="width: <?php echo $f->bar($row['food_cost_pct'], 100); ?>%"></span>
                                        <i class="ai-bar-target" style="left: <?php echo $f->bar($target, 100); ?>%"></i>
                                    </div>
                                    <div class="ai-muted"><?php echo $f->pct($row['food_cost_pct']); ?></div>
                                </td>
                                <td><span class="ai-band is-<?php echo $f->e($row['band']); ?>"><?php echo $f->e($band_label[$row['band']]); ?></span></td>
                                <td class="text-end"><?php echo $f->num($row['qty']); ?></td>
                                <td class="text-end"><strong><?php echo $row['suggested_price'] === null ? '&ndash;' : $f->money($row['suggested_price']); ?></strong></td>
                                <td class="text-end"><?php echo $row['impact'] > 0 ? $f->money($row['impact']) : '&ndash;'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($price['items']) > $limit): ?>
                    <button type="button" class="ai-more-btn" data-less="<?php echo $f->e(lang('ai_show_less')); ?>"><?php echo $f->e(sprintf(lang('ai_show_all'), count($price['items']))); ?></button>
                <?php endif; ?>
            </div>
            <div class="ai-muted ai-foot"><?php echo lang('ai_price_foot'); ?></div>
        <?php endif; ?>
    </div>
</div>
