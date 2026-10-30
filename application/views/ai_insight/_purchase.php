<?php
/*
 | Purchase vs usage (plan 6.5) for the chosen period ($purchase = purchase or purchase7).
 | Quantities in the consumption unit, like the inventory report. Flagged items first
 | is the engine's order; rows past 12 open with "Show all".
 */
$t = $purchase['totals'];
$limit = 12;
$max_cover = max(1, (int) $purchase['cover_days_limit'] * 2);
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_used_purchase'); ?></h4>
                <div class="ai-muted"><?php echo $period; ?></div>
            </div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'purchase')); ?>
        <?php $this->load->view('ai_insight/_gate', array('section' => 'purchase')); ?>

        <?php if ($m['health']['gates']['purchase'] != 'blocked' && $purchase['items']): ?>
            <div class="ai-kpis ai-kpis-sm">
                <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_bought'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['purchased_value']); ?></div></div>
                <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_used'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['usage_value']); ?></div></div>
                <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_wasted'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['waste_value']); ?></div>
                    <?php if ($t['high_waste_count']): ?><div class="ai-kpi-sub"><?php echo $f->e(sprintf(lang('ai_t_high_waste'), $t['high_waste_count'])); ?></div><?php endif; ?></div>
                <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_shrink'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['shrink_value']); ?></div></div>
                <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_excess'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['excess_value']); ?></div>
                    <?php if ($t['overbuy_count']): ?><div class="ai-kpi-sub"><?php echo $f->e(sprintf(lang('ai_t_overbought'), $t['overbuy_count'])); ?></div><?php endif; ?></div>
            </div>

            <div class="ai-collapsible ai-collapsed">
                <div class="table-responsive">
                    <table class="table ai-table">
                        <thead>
                        <tr>
                            <th><?php echo lang('ai_col_ingredient'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_bought'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_transfers'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_used'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_wasted'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_stock_now'); ?></th>
                            <th class="ai-col-bar"><?php echo lang('ai_col_days_cover'); ?></th>
                            <th><?php echo lang('ai_col_flags'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($purchase['items'] as $i => $row): ?>
                            <?php $u = $row['unit']; ?>
                            <tr class="<?php echo $i >= $limit ? 'ai-more' : ''; ?>">
                                <td><strong><?php echo $f->e($row['name']); ?></strong></td>
                                <td class="text-end">
                                    <?php echo $f->qty($row['purchased'], $u); ?>
                                    <div class="ai-muted"><?php echo $f->money($row['purchased_value']); ?></div>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['transfer_in'] || $row['transfer_out']): ?>
                                        +<?php echo $f->num($row['transfer_in']); ?> / &minus;<?php echo $f->num($row['transfer_out']); ?>
                                    <?php else: ?>
                                        &ndash;
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo $f->qty($row['used'], $u); ?>
                                    <div class="ai-muted"><?php echo $f->money($row['usage_value']); ?></div>
                                </td>
                                <td class="text-end">
                                    <?php echo $row['wasted'] > 0 ? $f->qty($row['wasted'], $u) : '&ndash;'; ?>
                                    <?php if ($row['wasted'] > 0 && $row['waste_pct'] !== null): ?>
                                        <div class="ai-muted"><?php echo $f->pct($row['waste_pct']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end<?php echo $row['stock'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $f->qty($row['stock'], $u); ?></td>
                                <td class="ai-col-bar">
                                    <?php if ($row['cover_days'] !== null): ?>
                                        <div class="ai-bar<?php echo $row['cover_days'] > $purchase['cover_days_limit'] ? ' is-high' : ''; ?>" title="<?php echo $f->num($row['cover_days'], 1); ?>">
                                            <span style="width: <?php echo $f->bar($row['cover_days'], $max_cover); ?>%"></span>
                                            <i class="ai-bar-target" style="left: 50%"></i>
                                        </div>
                                        <div class="ai-muted"><?php echo sprintf(lang('ai_days'), $f->num($row['cover_days'], 1)); ?></div>
                                    <?php else: ?>
                                        &ndash;
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['overbuy']): ?><span class="ai-flag is-watch"><?php echo lang('ai_f_overbuy'); ?></span><?php endif; ?>
                                    <?php if ($row['high_waste']): ?><span class="ai-flag is-bad"><?php echo lang('ai_f_waste'); ?></span><?php endif; ?>
                                    <?php if ($row['negative']): ?><span class="ai-flag is-bad"><?php echo lang('ai_f_negative'); ?></span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($purchase['items']) > $limit): ?>
                    <button type="button" class="ai-more-btn" data-less="<?php echo $f->e(lang('ai_show_less')); ?>"><?php echo $f->e(sprintf(lang('ai_show_all'), count($purchase['items']))); ?></button>
                <?php endif; ?>
            </div>
            <div class="ai-muted ai-foot"><?php echo $f->e(sprintf(lang('ai_purchase_foot'), $purchase['cover_days_limit'])); ?></div>
        <?php endif; ?>
    </div>
</div>
