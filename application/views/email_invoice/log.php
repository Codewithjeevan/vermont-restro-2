<?php
/*
 | Email Log (permission 443 view): the last 200 bill emails of the company.
 | Retry (443 send) puts a failed email back in the queue.
 */
$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$pending = 0;
foreach ($rows as $r) {
    if ($r->status === 'pending' || $r->status === 'sending') {
        $pending++;
    }
}
?>
<section class="main-content-wrapper ei-page">
    <?php $this->load->view('email_invoice/_top', array('ei_tab' => 'log')); ?>

    <div class="ei-head">
        <h3><?php echo lang('ei_email_log'); ?></h3>
        <div class="ei-sub"><?php echo lang('ei_log_hint'); ?></div>
    </div>

    <?php if ((int) $settings->enabled !== 1): ?>
        <div class="alert alert-warning"><?php echo lang('ei_not_enabled'); ?></div>
    <?php endif; ?>

    <?php if ($pending > 0): ?>
        <div class="ei-card ei-actions">
            <span><?php echo $e(sprintf(lang('ei_pending_count'), $pending)); ?></span>
            <button type="button" class="btn bg-blue-btn" id="ei_process_now"><?php echo lang('ei_send_now'); ?></button>
            <span class="ei-result" id="ei_process_result"></span>
        </div>
    <?php endif; ?>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><?php echo lang('ei_date'); ?></th>
                        <th><?php echo lang('ei_invoice_no'); ?></th>
                        <th><?php echo lang('ei_outlet'); ?></th>
                        <th><?php echo lang('ei_to'); ?></th>
                        <th><?php echo lang('ei_status'); ?></th>
                        <th><?php echo lang('ei_attempts'); ?></th>
                        <th><?php echo lang('ei_details'); ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="8" class="text-center"><?php echo lang('ei_log_empty'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo $e($r->created_at); ?></td>
                            <td><?php echo $e($r->sale_no); ?><?php echo $r->source === 'resend' ? ' <span class="ei-muted">(' . $e(lang('ei_resend')) . ')</span>' : ''; ?></td>
                            <td><?php echo $e(isset($outlets[(int) $r->outlet_id]) ? $outlets[(int) $r->outlet_id] : ''); ?></td>
                            <td><?php echo $e($r->to_email); ?></td>
                            <td><span class="ei-status <?php echo $e($r->status); ?>"><?php echo $e(lang('ei_status_' . $r->status)); ?></span></td>
                            <td><?php echo (int) $r->attempts; ?></td>
                            <td>
                                <?php if ($r->status === 'sent'): ?>
                                    <span class="ei-muted"><?php echo $e($r->sent_at); ?></span>
                                <?php elseif ($r->last_error): ?>
                                    <div class="ei-error-text"><?php echo $e($r->last_error); ?></div>
                                    <?php if ($r->status === 'pending' && $r->next_attempt_at): ?>
                                        <div class="ei-muted"><?php echo $e(sprintf(lang('ei_next_try'), $r->next_attempt_at)); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r->status === 'failed' && $can_send): ?>
                                    <?php echo form_open(base_url('Email_invoice/retry'), array('style' => 'margin:0')); ?>
                                        <?php echo ai_csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                                        <button type="submit" class="btn btn-sm bg-blue-btn"><?php echo lang('ei_retry'); ?></button>
                                    <?php echo form_close(); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var btn = document.getElementById('ei_process_now');
    if (!btn) { return; }
    var base = <?php echo json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var out = document.getElementById('ei_process_result');
    btn.addEventListener('click', function () {
        btn.disabled = true;
        out.textContent = <?php echo json_encode(lang('ei_sending'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var data = new FormData();
        data.append('now', '1');
        fetch(base + 'Email_invoice/process', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function () { window.location.reload(); })
            .catch(function () { btn.disabled = false; out.textContent = ''; });
    });
})();
</script>
