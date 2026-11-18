<?php
/*
 | Top 5 improvements: the engine's ranking (money per month), with the report's
 | explanation and action for each.
 */
$conf_label = array('high' => lang('ai_conf_high'), 'medium' => lang('ai_conf_medium'), 'low' => lang('ai_conf_low'));
?>
<div class="box-wrapper">
    <div class="table-box">
        <div class="ai-section-head">
            <div>
                <h4><?php echo lang('ai_top5'); ?></h4>
                <div class="ai-muted"><?php echo lang('ai_top5_hint'); ?> &middot; <?php echo $period_28; ?></div>
            </div>
        </div>
        <?php if (!$m['top5']): ?>
            <div class="ai-muted"><?php echo lang('ai_top5_none'); ?></div>
        <?php else: ?>
            <ol class="ai-top5">
                <?php foreach ($m['top5'] as $n => $t): ?>
                    <?php
                    $txt = isset($report['issue_text'][$t['id']]) ? $report['issue_text'][$t['id']] : array('explanation' => '', 'action' => $t['action']);
                    $type_key = 'ai_it_' . $t['type'];
                    $type_label = lang($type_key) ?: $t['type'];
                    ?>
                    <li class="ai-issue">
                        <div class="ai-issue-num"><?php echo (int) $n + 1; ?></div>
                        <div class="ai-issue-body">
                            <div class="ai-issue-head">
                                <strong><?php echo $f->e($t['subject']); ?></strong>
                                <span class="ai-chip"><?php echo $f->e($type_label); ?></span>
                                <?php if (isset($conf_label[$t['confidence']])): ?>
                                    <span class="ai-conf is-<?php echo $f->e($t['confidence']); ?>"><?php echo $f->e($conf_label[$t['confidence']]); ?></span>
                                <?php endif; ?>
                                <?php if ($t['impact_30d'] > 0): ?>
                                    <span class="ai-issue-impact"><?php echo sprintf(lang('ai_per_month'), $f->money($t['impact_30d'], 0)); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($txt['explanation'] !== ''): ?>
                                <div class="ai-issue-text"><?php echo $f->text($txt['explanation']); ?></div>
                            <?php endif; ?>
                            <div class="ai-issue-action"><span class="ai-arrow">&rarr;</span><div><?php echo $f->text($txt['action']); ?></div></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>
