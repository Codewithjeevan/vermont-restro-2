<?php
/*
 | Ask AI panel (plan 7.2), opened by the "Ask AI" button. The open conversation's
 | questions and answers are rendered here through Ai_format; new answers arrive from
 | Ai_insight/chatSend as HTML the server already escaped (frequent_changing/js/ai_insight.js).
 */
$suggestions = array(lang('ai_chat_s1'), lang('ai_chat_s2'), lang('ai_chat_s3'), lang('ai_chat_s4'));
$left = sprintf(lang('ai_chat_left'), max(0, $chat['per_day'] - $chat['today']), $chat['per_day'])
    . ' · ' . sprintf(lang('ai_chat_left_chat'), max(0, $chat['per_chat'] - $chat['questions']), $chat['per_chat']);
?>
<div class="ai-chat" id="ai_chat" hidden role="dialog" aria-label="<?php echo $f->e(lang('ai_ask')); ?>"
     data-send="<?php echo base_url(); ?>Ai_insight/chatSend" data-new="<?php echo base_url(); ?>Ai_insight/chatNew"
     data-token="<?php echo $f->e(ai_csrf_token()); ?>" data-session="<?php echo (int) $chat['session_id']; ?>"
     data-thinking="<?php echo $f->e(lang('ai_chat_thinking')); ?>" data-error="<?php echo $f->e(lang('ai_chat_reload')); ?>">
    <div class="ai-chat-head">
        <div class="ai-chat-title">
            <strong><?php echo lang('ai_ask'); ?></strong>
            <div class="ai-muted"><?php echo lang('ai_chat_hint'); ?></div>
        </div>
        <button type="button" class="ai-chat-new" data-chat-new><?php echo lang('ai_chat_new'); ?></button>
        <button type="button" class="ai-chat-x" data-chat-close aria-label="<?php echo $f->e(lang('ai_chat_close')); ?>">&times;</button>
    </div>
    <div class="ai-chat-log" aria-live="polite">
        <?php foreach ($chat['history'] as $h): ?>
            <?php if ($h->role == 'user'): ?>
                <div class="ai-msg is-user"><?php echo nl2br($f->e($h->display_text)); ?></div>
            <?php else: ?>
                <div class="ai-msg is-ai"><?php echo $f->text($h->display_text); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="ai-chat-suggest"<?php echo $chat['history'] ? ' hidden' : ''; ?>>
        <?php foreach ($suggestions as $s): ?>
            <button type="button"><?php echo $f->e($s); ?></button>
        <?php endforeach; ?>
    </div>
    <form class="ai-chat-form">
        <textarea name="question" rows="2" maxlength="<?php echo (int) $chat['max_chars']; ?>" placeholder="<?php echo $f->e(lang('ai_chat_placeholder')); ?>"></textarea>
        <button type="submit" class="btn bg-blue-btn"><?php echo lang('ai_chat_send'); ?></button>
    </form>
    <div class="ai-chat-left ai-muted"><?php echo $f->e($left); ?></div>
</div>
