<?php
/*
 | Sales prediction for the next 7 days and the suggested purchase (plan 6.7).
 | Not shown when the outlet has under 4 weeks of sales (the gate says what is missing).
 */
$fc = $m['forecast'];
$max_qty = 0;
foreach ($fc['days'] as $d) {
    $max_qty = max($max_qty, $d['qty']);
}
$limit = 10;
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_forecast_title'); ?></h4>
                <?php if ($fc['available'] && $fc['days']): ?>
                    <div class="ai-muted"><?php echo $f->date($fc['days'][0]['date']); ?> &ndash; <?php echo $f->date($fc['days'][count($fc['days']) - 1]['date']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php $this->load->view('ai_insight/_note', array('key' => 'forecast')); ?>
        <?php $this->load->view('ai_insight/_gate', array('section' => 'forecast')); ?>

        <?php if ($fc['available'] && $fc['days']): ?>
            <div class="ai-fc-line">
                <?php echo sprintf(lang('ai_fc_line'), $f->num($fc['total_qty'], 0), $f->money($fc['total_sales_value']), $f->e($fc['busiest_day'])); ?>
                <?php if ($fc['accuracy_pct'] !== null): ?>
                    <span class="ai-muted"><?php echo sprintf(lang('ai_fc_accuracy'), $f->pct($fc['accuracy_pct'])); ?></span>
                <?php endif; ?>
            </div>

            <div class="ai-days">
                <?php foreach ($fc['days'] as $d): ?>
                    <div class="ai-day">
                        <div class="ai-day-name"><?php echo $f->e(date('D d M', strtotime($d['date']))); ?></div>
                        <div class="ai-bar ai-bar-lg" title="<?php echo $f->num($d['qty'], 1); ?>"><span style="width: <?php echo $f->bar($d['qty'], $max_qty); ?>%"></span></div>
                        <div class="ai-day-qty"><?php echo $f->num($d['qty'], 0); ?></div>
                        <div class="ai-day-value ai-muted"><?php echo $f->money($d['sales_value']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($fc['items']): ?>
                <div class="ai-subhead"><?php echo lang('ai_fc_items'); ?></div>
                <div class="ai-collapsible ai-collapsed">
                    <div class="table-responsive">
                        <table class="table ai-table ai-table-sm">
                            <thead>
                            <tr>
                                <th><?php echo lang('ai_item'); ?></th>
                                <?php foreach ($fc['days'] as $d): ?>
                                    <th class="text-end"><?php echo $f->e(date('D', strtotime($d['date']))); ?></th>
                                <?php endforeach; ?>
                                <th class="text-end"><?php echo lang('ai_col_total'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fc['items'] as $i => $it): ?>
                                <tr class="<?php echo $i >= $limit ? 'ai-more' : ''; ?>">
                                    <td><?php echo $f->e($it['name']); ?></td>
                                    <?php foreach ($it['days'] as $q): ?>
                                        <td class="text-end"><?php echo $f->num($q, 1); ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-end"><strong><?php echo $f->num($it['total'], 1); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($fc['items']) > $limit): ?>
                        <button type="button" class="ai-more-btn" data-less="<?php echo $f->e(lang('ai_show_less')); ?>"><?php echo $f->e(sprintf(lang('ai_show_all'), count($fc['items']))); ?></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="ai-subhead"><?php echo lang('ai_fc_purchase'); ?></div>
            <?php if (!$fc['purchase']): ?>
                <div class="ai-muted"><?php echo lang('ai_fc_no_purchase'); ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ai-table ai-table-sm">
                        <thead>
                        <tr>
                            <th><?php echo lang('ai_col_ingredient'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_need'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_safety'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_in_stock'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_buy'); ?></th>
                            <th class="text-end"><?php echo lang('ai_col_est_cost'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($fc['purchase'] as $p): ?>
                            <tr>
                                <td><strong><?php echo $f->e($p['name']); ?></strong></td>
                                <td class="text-end"><?php echo $f->qty($p['need_7d'], $p['unit']); ?></td>
                                <td class="text-end"><?php echo $f->qty($p['safety'], $p['unit']); ?></td>
                                <td class="text-end<?php echo $p['stock'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $f->qty($p['stock'], $p['unit']); ?></td>
                                <td class="text-end"><strong><?php echo $f->qty($p['buy_qty'], $p['buy_unit']); ?></strong></td>
                                <td class="text-end"><?php echo $f->money($p['est_cost']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="ai-total">
                            <td colspan="5"><?php echo lang('ai_col_total'); ?></td>
                            <td class="text-end"><?php echo $f->money($fc['purchase_total']); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="ai-muted ai-foot"><?php echo lang('ai_fc_foot'); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
