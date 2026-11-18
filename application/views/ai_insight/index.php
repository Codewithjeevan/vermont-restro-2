<?php
/*
 | AI Insight page of the session outlet (plan Phase 4).
 | $report = null (no report yet) or: m (Ai_metrics_model::build), text (AI or
 | template text), issue_text, status, date, made_at, is_today.
 | $m = $report['m'] (set by the controller so the section partials get it too).
 | $f = Ai_format: every value is echoed through it (plan S6).
 | Section order: headline, top 5, menu, price, purchase, P&L, prediction, Data Health.
 */
$stock_only = $m ? !empty($m['health']['stock_only']) : !empty($health['stock_only']);
$suffix = $days == 7 ? '7' : '';
$menu = $m ? $m['menu' . $suffix] : null;
$purchase = $m ? $m['purchase' . $suffix] : null;
$pnl = $m ? $m['pnl' . $suffix] : null;
$win = $m ? $m['windows'][$days == 7 ? 'cur7' : 'cur'] : null;
$win_prev = $m ? $m['windows'][$days == 7 ? 'prev7' : 'prev'] : null;
$period = $m ? sprintf('%s &ndash; %s', $f->date($win['from']), $f->date($win['to'])) : '';
$period_prev = $m ? sprintf('%s &ndash; %s', $f->date($win_prev['from']), $f->date($win_prev['to'])) : '';
$period_28 = $m ? sprintf('%s &ndash; %s', $f->date($m['windows']['cur']['from']), $f->date($m['windows']['cur']['to'])) : '';
$js_ver = @filemtime(FCPATH . 'frequent_changing/js/ai_insight.js');
?>
<section class="main-content-wrapper ai-page">
    <?php $this->load->view('ai_insight/_top', array('ai_tab' => 'insight')); ?>

    <div class="ai-head">
        <div>
            <h3><?php echo lang('ai_insight'); ?> <span class="ai-outlet"><?php echo $f->e($health['outlet_name']); ?></span></h3>
            <div class="ai-sub"><?php echo lang('ai_insight_hint'); ?></div>
        </div>
        <div class="ai-head-actions">
            <?php if ($m && !$stock_only): ?>
                <div class="ai-toggle" role="group">
                    <a href="<?php echo base_url(); ?>Ai_insight/index" class="<?php echo $days == 28 ? 'active' : ''; ?>"><?php echo lang('ai_period_28'); ?></a>
                    <a href="<?php echo base_url(); ?>Ai_insight/index/7" class="<?php echo $days == 7 ? 'active' : ''; ?>"><?php echo lang('ai_period_7'); ?></a>
                </div>
            <?php endif; ?>
            <?php if ($chat): ?>
                <button type="button" class="btn ai-btn-light" data-chat-open><?php echo lang('ai_ask'); ?></button>
            <?php endif; ?>
            <?php if ($can_regenerate): ?>
                <?php echo form_open(base_url('Ai_insight/regenerate'), array('class' => 'd-inline ai-regen-form')); ?>
                    <?php echo ai_csrf_field(); ?>
                    <input type="hidden" name="days" value="<?php echo (int) $days; ?>">
                    <button type="submit" class="btn bg-blue-btn" <?php echo $regen_left ? '' : 'disabled'; ?>
                            data-busy="<?php echo $f->e(lang('ai_regen_working')); ?>"><?php echo lang('ai_regenerate'); ?></button>
                <?php echo form_close(); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($m): ?>
        <div class="ai-reportline">
            <span><?php echo sprintf(lang('ai_report_of'), $f->date($report['date'])); ?></span>
            <span><?php echo sprintf(lang('ai_figures_to'), $f->date($m['windows']['cur']['to'])); ?></span>
            <span><?php echo sprintf(lang('ai_made_at'), $f->e(date('d M Y H:i', strtotime($report['made_at'])))); ?></span>
            <span class="ai-src is-<?php echo $f->e($report['status']['kind']); ?>"><?php echo $f->e($report['status']['label']); ?></span>
            <?php if ($report['status']['reason'] !== ''): ?>
                <span class="ai-muted"><?php echo $f->e($report['status']['reason']); ?></span>
            <?php endif; ?>
            <?php if ($can_regenerate): ?>
                <span class="ai-muted"><?php echo lang('ai_regenerate'); ?>: <?php echo $f->e(sprintf(lang('ai_regen_left'), $regen_left, $regen_max)); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!$report['is_today']): ?>
            <div class="ai-notice"><?php echo $f->e(sprintf(lang('ai_old_report'), date('d M Y', strtotime($report['date'])), $report_hour)); ?></div>
        <?php endif; ?>
        <?php if ($stock_only): ?>
            <div class="ai-notice"><?php echo $f->e(sprintf(lang('ai_warehouse_note'), $m['windows']['cur']['days'])); ?></div>
        <?php endif; ?>

        <?php // ---------- headline ---------- ?>
        <div class="box-wrapper">
            <div class="table-box ai-hero">
                <div class="ai-headline"><?php echo $f->text($report['text']['headline']); ?></div>
                <div class="ai-summary"><?php echo $f->text($report['text']['summary']); ?></div>
                <div class="ai-kpis">
                    <?php if ($stock_only): ?>
                        <?php $t = $purchase['totals']; ?>
                        <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_bought'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['purchased_value']); ?></div></div>
                        <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_used'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['usage_value']); ?></div></div>
                        <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_wasted'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['waste_value']); ?></div></div>
                        <div class="ai-kpi"><div class="ai-kpi-label"><?php echo lang('ai_t_excess'); ?></div><div class="ai-kpi-value"><?php echo $f->money($t['excess_value']); ?></div></div>
                    <?php else: ?>
                        <?php $y = $m['yesterday']; $c = $pnl['change']; $p = $pnl['cur']; ?>
                        <div class="ai-kpi">
                            <div class="ai-kpi-label"><?php echo lang('ai_k_yesterday'); ?> &middot; <?php echo $f->date($y['date']); ?></div>
                            <div class="ai-kpi-value"><?php echo $f->money($y['gross_sales']); ?></div>
                            <div class="ai-kpi-sub">
                                <?php echo $f->e(sprintf(lang('ai_k_orders'), (int) $y['orders'])); ?>
                                <?php if ($y['change_pct'] !== null): ?>
                                    &middot; <?php echo $f->change(array('value' => $y['gross_sales'] - $y['last_week_gross_sales'], 'pct' => $y['change_pct'])); ?> <?php echo lang('ai_k_vs_last_week'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ai-kpi">
                            <div class="ai-kpi-label"><?php echo lang('ai_l_net_sales'); ?> &middot; <?php echo lang($days == 7 ? 'ai_period_7' : 'ai_period_28'); ?></div>
                            <div class="ai-kpi-value"><?php echo $f->money($p['net_sales']); ?></div>
                            <div class="ai-kpi-sub"><?php echo $f->change($c['net_sales']); ?> <?php echo lang('ai_vs_prev'); ?></div>
                        </div>
                        <div class="ai-kpi">
                            <div class="ai-kpi-label"><?php echo lang('ai_l_food'); ?></div>
                            <div class="ai-kpi-value"><?php echo $f->pct($p['food_cost_pct']); ?></div>
                            <div class="ai-kpi-sub"><?php echo $f->e(sprintf(lang('ai_k_target'), rtrim(rtrim(number_format($m['meta']['target_food_cost_pct'], 2), '0'), '.'))); ?></div>
                        </div>
                        <div class="ai-kpi">
                            <div class="ai-kpi-label"><?php echo lang('ai_l_net_profit'); ?></div>
                            <div class="ai-kpi-value<?php echo $p['net_profit'] < 0 ? ' ai-neg' : ''; ?>"><?php echo $f->money($p['net_profit']); ?></div>
                            <div class="ai-kpi-sub"><?php echo $f->change($c['net_profit']); ?> <?php echo lang('ai_vs_prev'); ?></div>
                        </div>
                        <div class="ai-kpi">
                            <div class="ai-kpi-label"><?php echo lang('ai_l_orders'); ?></div>
                            <div class="ai-kpi-value"><?php echo $f->num($p['orders'], 0); ?></div>
                            <div class="ai-kpi-sub"><?php echo lang('ai_l_avg_ticket'); ?> <?php echo $f->money($p['avg_ticket']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php
        $this->load->view('ai_insight/_top5', array('period_28' => $period_28));
        if (!$stock_only) {
            $this->load->view('ai_insight/_menu', array('menu' => $menu, 'period' => $period));
            $this->load->view('ai_insight/_price', array('period_28' => $period_28));
        }
        $this->load->view('ai_insight/_purchase', array('purchase' => $purchase, 'period' => $period));
        if (!$stock_only) {
            $this->load->view('ai_insight/_pnl', array('pnl' => $pnl, 'period' => $period, 'period_prev' => $period_prev));
            $this->load->view('ai_insight/_forecast');
        }
        ?>
    <?php else: ?>
        <div class="ai-notice">
            <?php echo $f->e(sprintf(lang('ai_no_report'), $report_hour)); ?>
            <?php if ($can_regenerate && $regen_left): ?>
                <?php echo lang('ai_no_report_regen'); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php $this->load->view('ai_insight/_health'); ?>
    <?php if ($chat): ?>
        <?php $this->load->view('ai_insight/_chat'); ?>
    <?php endif; ?>
</section>
<script src="<?php echo base_url(); ?>frequent_changing/js/ai_insight.js?v=<?php echo (int) $js_ver; ?>"></script>
