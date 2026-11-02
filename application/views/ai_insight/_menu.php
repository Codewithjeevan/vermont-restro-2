<?php
/*
 | Menu performance (menu engineering, plan 6.3) for the chosen period ($menu = menu or menu7).
 | Rows are sorted by total margin; items past the first 10 open with "Show all".
 */
$class_label = array(
    'star' => lang('ai_c_star'),
    'plowhorse' => lang('ai_c_plowhorse'),
    'puzzle' => lang('ai_c_puzzle'),
    'dog' => lang('ai_c_dog'),
);
$limit = 10;
$max_share = 0;
foreach ($menu['items'] as $row) {
    $max_share = max($max_share, $row['qty_share_pct']);
}
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_used_menu'); ?></h4>
                <div class="ai-muted"><?php echo $period; ?></div>
            </div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'menu')); ?>
        <?php $this->load->view('ai_insight/_gate', array('section' => 'menu')); ?>

        <?php if ($m['health']['gates']['menu'] != 'blocked' && $menu['items']): ?>
            <div class="ai-classes">
                <?php foreach ($class_label as $key => $label): ?>
                    <div class="ai-class is-<?php echo $key; ?>">
                        <div class="ai-class-count"><?php echo (int) $menu['class_counts'][$key]; ?></div>
                        <div class="ai-class-label"><?php echo $f->e($label); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="ai-muted ai-mb"><?php echo sprintf(lang('ai_menu_avg_margin'), $f->money($menu['avg_cm'])); ?></div>

            <div class="ai-collapsible ai-collapsed">
                <div class="table-responsive">
                    <table class="table ai-table">
                        <thead>
                        <tr>
                            <th><?php echo lang('ai_item'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_sold'); ?></th>
                            <th class="ai-col-bar"><?php echo lang('ai_col_share'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_avg_price'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_plate_cost'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_margin'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_total_margin'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_vs_prev'); ?></th>
                            <th><?php echo lang('ai_col_group'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_values($menu['items']) as $i => $row): ?>
                            <tr class="<?php echo $i >= $limit ? 'ai-more' : ''; ?>">
                                <td><strong><?php echo $f->e($row['name']); ?></strong></td>
                                <td class="text-end">
                                    <?php echo $f->num($row['qty']); ?>
                                    <?php if ($row['giveaway_qty'] > 0): ?>
                                        <div class="ai-muted"><?php echo sprintf(lang('ai_free'), $f->num($row['giveaway_qty'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="ai-col-bar">
                                    <?php if ($row['has_cost']): ?>
                                        <div class="ai-bar" title="<?php echo $f->pct($row['qty_share_pct']); ?>"><span style="width: <?php echo $f->bar($row['qty_share_pct'], $max_share); ?>%"></span></div>
                                        <div class="ai-muted"><?php echo $f->pct($row['qty_share_pct']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo $f->money($row['avg_price']); ?></td>
                                <td class="text-end"><?php echo $row['has_cost'] ? $f->money($row['plate_cost']) : '&ndash;'; ?></td>
                                <td class="text-end<?php echo $row['has_cost'] && $row['cm'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $row['has_cost'] ? $f->money($row['cm']) : '&ndash;'; ?></td>
                                <td class="text-end<?php echo $row['has_cost'] && $row['total_cm'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $row['has_cost'] ? $f->money($row['total_cm']) : '&ndash;'; ?></td>
                                <td class="text-end">
                                    <?php if ($row['prev_qty'] > 0): ?>
                                        <?php echo $f->change(array('value' => $row['qty'] - $row['prev_qty'], 'pct' => $row['qty_change_pct'])); ?>
                                    <?php else: ?>
                                        <span class="ai-chg"><?php echo lang('ai_new'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['class']): ?>
                                        <span class="ai-group is-<?php echo $f->e($row['class']); ?>"><?php echo $f->e($class_label[$row['class']]); ?></span>
                                    <?php else: ?>
                                        <span class="ai-group"><?php echo lang('ai_c_none'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($menu['items']) > $limit): ?>
                    <button type="button" class="ai-more-btn" data-less="<?php echo $f->e(lang('ai_show_less')); ?>"><?php echo $f->e(sprintf(lang('ai_show_all'), count($menu['items']))); ?></button>
                <?php endif; ?>
            </div>

            <?php if ($menu['unsold']): ?>
                <div class="ai-subhead"><?php echo $f->e(sprintf(lang('ai_unsold'), $menu['unsold_count'])); ?></div>
                <div>
                    <?php foreach ($menu['unsold'] as $u): ?>
                        <span class="ai-chip"><?php echo $f->e($u['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
