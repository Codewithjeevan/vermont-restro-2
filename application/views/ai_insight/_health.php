<?php
/*
 | Data Health of the outlet, checked live on every page load (not from the report),
 | so a fix shows at once. Plan 6.9.
 */
$status_label = array(
    'good' => lang('ai_status_good'),
    'watch' => lang('ai_status_watch'),
    'missing' => lang('ai_status_missing'),
    'na' => lang('ai_status_na'),
);
$check_label = array(
    'recipe_cover' => lang('ai_h_recipe_cover'),
    'menus_recipe' => lang('ai_h_menus_recipe'),
    'ing_price' => lang('ai_h_ing_price'),
    'purchases' => lang('ai_h_purchases'),
    'waste' => lang('ai_h_waste'),
    'negative' => lang('ai_h_negative'),
    'history' => lang('ai_h_history'),
    'stock_count' => lang('ai_h_count'),
);
$section_label = array(
    'menu' => lang('ai_used_menu'),
    'price' => lang('ai_used_price'),
    'purchase' => lang('ai_used_purchase'),
    'pnl' => lang('ai_used_pnl'),
    'forecast' => lang('ai_used_forecast'),
    'waste' => lang('ai_used_waste'),
    'shrink' => lang('ai_used_shrink'),
);
$gate_label = array('ready' => lang('ai_status_good'), 'limited' => lang('ai_status_watch'), 'blocked' => lang('ai_status_missing'));
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_data_health'); ?></h4>
                <div class="ai-muted"><?php echo lang('ai_data_health_hint'); ?></div>
            </div>
            <div class="ai-muted"><?php echo $f->e(sprintf(lang('ai_checked_at'), $checked_at)); ?></div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'data')); ?>

        <div class="ai-gates">
            <?php foreach ($health['gates'] as $key => $gate): ?>
                <span class="ai-gate is-<?php echo $f->e($gate); ?>"><?php echo $f->e($section_label[$key]); ?>: <?php echo $f->e($gate_label[$gate]); ?></span>
            <?php endforeach; ?>
        </div>

        <?php if ($health['stock_only'] && !$report): ?>
            <div class="ai-muted ai-mb"><?php echo $f->e(sprintf(lang('ai_warehouse_note'), $health['window']['days'])); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table ai-table">
                <thead>
                <tr>
                    <th><?php echo lang('ai_check'); ?></th>
                    <th><?php echo lang('ai_result'); ?></th>
                    <th><?php echo lang('ai_what_to_do'); ?></th>
                    <th><?php echo lang('ai_used_for'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($health['checks'] as $key => $c): ?>
                    <tr>
                        <td>
                            <span class="ai-dot is-<?php echo $f->e($c['status']); ?>" title="<?php echo $f->e($status_label[$c['status']]); ?>"></span>
                            <strong><?php echo $f->e($check_label[$key]); ?></strong>
                            <div class="ai-status is-<?php echo $f->e($c['status']); ?>"><?php echo $f->e($status_label[$c['status']]); ?></div>
                        </td>
                        <td><?php echo $f->e($c['result']); ?></td>
                        <td>
                            <?php echo $f->e($c['fix']); ?>
                            <?php if ($c['fix'] && $c['link']): ?>
                                <a class="ai-link" href="<?php echo base_url() . $f->e($c['link']); ?>"><?php echo lang('ai_open'); ?> &rarr;</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php foreach ($c['used_for'] as $u): ?>
                                <span class="ai-chip"><?php echo $f->e($section_label[$u]); ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($health['no_cost_items']): ?>
            <div class="ai-subhead"><?php echo lang('ai_items_without_recipe'); ?></div>
            <div class="table-responsive">
                <table class="table ai-table ai-table-sm">
                    <thead><tr><th><?php echo lang('ai_item'); ?></th><th class="text-end"><?php echo lang('ai_qty_sold'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($health['no_cost_items'] as $item): ?>
                        <tr>
                            <td><?php echo $f->e($item['name']); ?></td>
                            <td class="text-end"><?php echo $f->num($item['qty']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($health['negative_items']): ?>
            <div class="ai-subhead"><?php echo lang('ai_h_negative'); ?></div>
            <div class="table-responsive">
                <table class="table ai-table ai-table-sm">
                    <thead><tr><th><?php echo lang('ai_item'); ?></th><th class="text-end"><?php echo lang('ai_stock'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($health['negative_items'] as $item): ?>
                        <tr>
                            <td><?php echo $f->e($item['name']); ?></td>
                            <td class="text-end ai-neg"><?php echo $f->qty($item['stock'], $item['unit']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
