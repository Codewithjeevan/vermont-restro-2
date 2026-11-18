<?php
/*
 | Data limits of one section ($section: menu, price, purchase, pnl, forecast) as the
 | report saw them: "blocked" lists what is missing, "limited" says the figures are
 | incomplete. The live state is in the Data Health section at the bottom.
 */
$gate = isset($m['health']['gates'][$section]) ? $m['health']['gates'][$section] : 'ready';
$problems = array();
if ($gate != 'ready') {
    foreach ($m['health']['checks'] as $c) {
        if (in_array($section, $c['used_for']) && in_array($c['status'], array('missing', 'watch'))) {
            $problems[] = $c;
        }
    }
}
?>
<?php if ($gate != 'ready'): ?>
    <div class="ai-gate-box is-<?php echo $f->e($gate); ?>">
        <strong><?php echo lang($gate == 'blocked' ? 'ai_gate_blocked' : 'ai_gate_limited'); ?></strong>
        <ul>
            <?php foreach ($problems as $c): ?>
                <li>
                    <?php echo $f->e($c['result']); ?>.
                    <?php echo $f->e($c['fix']); ?>
                    <?php if ($c['link']): ?>
                        <a class="ai-link" href="<?php echo base_url() . $f->e($c['link']); ?>"><?php echo lang('ai_open'); ?> &rarr;</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (!$problems): ?>
                <li><?php echo lang('ai_gate_see_health'); ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
