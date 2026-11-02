<?php
/*
 | The report text of one section ($key: menu, price, purchase, pnl, forecast, data).
 | AI-written when the report's source is "ai", else the built-in standard text.
 */
$note = isset($report['text']['sections'][$key]) ? $report['text']['sections'][$key] : '';
$by_ai = isset($report['text']['source']) && $report['text']['source'] == 'ai';
?>
<?php if ($note !== ''): ?>
    <div class="ai-note<?php echo $by_ai ? ' is-ai' : ''; ?>">
        <div class="ai-note-label"><?php echo lang($by_ai ? 'ai_note_ai' : 'ai_note_standard'); ?></div>
        <div class="ai-note-text"><?php echo $f->text($note); ?></div>
    </div>
<?php endif; ?>
