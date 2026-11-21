<?php
/*
 | Email Settings (permission 443 update): the SMTP account bills are sent from.
 | The password is never printed back; an empty password field keeps the saved one.
 | Values are checked again in Email_invoice::readForm.
 */
$s = $settings;
$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$has_password = (string) $s->password !== '';
$is_on = (int) $s->enabled === 1;
$provider_js = array();
foreach ($providers as $key => $p) {
    $provider_js[$key] = array('host' => $p['host'], 'port' => $p['port'], 'encryption' => $p['encryption'], 'help' => lang('ei_help_' . $key));
}
?>
<section class="main-content-wrapper ei-page">
    <?php $this->load->view('email_invoice/_top', array('ei_tab' => 'settings')); ?>

    <div class="ei-head">
        <h3><?php echo lang('ei_email_settings'); ?>
            <span class="ei-status <?php echo $is_on ? 'on' : 'off'; ?>"><?php echo $is_on ? lang('ei_on') : lang('ei_off'); ?></span>
        </h3>
        <div class="ei-sub"><?php echo lang('ei_settings_hint'); ?></div>
    </div>

    <?php if ($s->last_test_at): ?>
        <div class="ei-card">
            <?php if ((int) $s->last_test_ok === 1): ?>
                <span class="ei-status sent"><?php echo lang('ei_last_test_ok'); ?></span>
            <?php else: ?>
                <span class="ei-status failed"><?php echo lang('ei_last_test_failed'); ?></span>
                <span class="ei-error-text"><?php echo $e($s->last_test_error); ?></span>
            <?php endif; ?>
            <span class="ei-muted">&nbsp;<?php echo $e($s->last_test_at); ?></span>
            <?php if ($pending > 0): ?>
                <span class="ei-muted">&nbsp;|&nbsp;<?php echo $e(sprintf(lang('ei_pending_count'), $pending)); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php echo form_open(base_url('Email_invoice/saveSettings'), array('id' => 'ei_form', 'autocomplete' => 'off')); ?>
        <?php echo ai_csrf_field(); ?>

        <div class="ei-card">
            <label class="ei-switch">
                <input type="checkbox" name="enabled" value="1" <?php echo $is_on ? 'checked' : ''; ?>>
                <?php echo lang('ei_enable'); ?>
            </label>
            <div class="ei-muted"><?php echo lang('ei_enable_help'); ?></div>
        </div>

        <div class="ei-card">
            <h4>1. <?php echo lang('ei_email_account'); ?></h4>
            <div class="ei-grid">
                <div class="ei-field">
                    <label for="ei_provider"><?php echo lang('ei_provider'); ?></label>
                    <select id="ei_provider" name="provider" class="form-control">
                        <?php foreach ($providers as $key => $p): ?>
                            <option value="<?php echo $e($key); ?>" <?php echo $s->provider === $key ? 'selected' : ''; ?>><?php echo $e($p['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ei-field">
                    <label for="ei_username"><?php echo lang('ei_username'); ?></label>
                    <input type="text" id="ei_username" name="username" class="form-control" maxlength="150" value="<?php echo $e($s->username); ?>" placeholder="billing@yourrestaurant.com" autocomplete="off">
                    <div class="ei-muted"><?php echo lang('ei_username_help'); ?></div>
                </div>
                <div class="ei-field">
                    <label for="ei_password"><?php echo lang('ei_password'); ?></label>
                    <input type="password" id="ei_password" name="password" class="form-control" maxlength="200" value="" autocomplete="new-password"
                           placeholder="<?php echo $has_password ? $e(lang('ei_password_saved')) : ''; ?>">
                    <div class="ei-muted"><?php echo $has_password ? lang('ei_password_keep') : lang('ei_password_help'); ?></div>
                </div>
            </div>
            <div class="ei-help" id="ei_provider_help"></div>

            <details id="ei_advanced" style="margin-top:14px;" <?php echo $s->provider === 'custom' ? 'open' : ''; ?>>
                <summary><?php echo lang('ei_advanced'); ?></summary>
                <div class="ei-grid">
                    <div class="ei-field">
                        <label for="ei_host"><?php echo lang('ei_host'); ?></label>
                        <input type="text" id="ei_host" name="host" class="form-control" maxlength="150" value="<?php echo $e($s->host); ?>" placeholder="smtp.example.com">
                    </div>
                    <div class="ei-field">
                        <label for="ei_port"><?php echo lang('ei_port'); ?></label>
                        <input type="number" id="ei_port" name="port" class="form-control" min="1" max="65535" value="<?php echo (int) $s->port; ?>">
                    </div>
                    <div class="ei-field">
                        <label for="ei_encryption"><?php echo lang('ei_encryption'); ?></label>
                        <select id="ei_encryption" name="encryption" class="form-control">
                            <option value="tls" <?php echo $s->encryption === 'tls' ? 'selected' : ''; ?>>TLS / STARTTLS (587)</option>
                            <option value="ssl" <?php echo $s->encryption === 'ssl' ? 'selected' : ''; ?>>SSL (465)</option>
                            <option value="none" <?php echo $s->encryption === 'none' ? 'selected' : ''; ?>><?php echo lang('ei_none'); ?> (25)</option>
                        </select>
                    </div>
                    <div class="ei-field">
                        <label for="ei_from_email"><?php echo lang('ei_from_email'); ?></label>
                        <input type="email" id="ei_from_email" name="from_email" class="form-control" maxlength="150" value="<?php echo $e($s->from_email); ?>">
                        <div class="ei-muted"><?php echo lang('ei_from_email_help'); ?></div>
                    </div>
                    <div class="ei-field">
                        <label for="ei_from_name"><?php echo lang('ei_from_name'); ?></label>
                        <input type="text" id="ei_from_name" name="from_name" class="form-control" maxlength="100" value="<?php echo $e($s->from_name); ?>">
                        <div class="ei-muted"><?php echo lang('ei_from_name_help'); ?></div>
                    </div>
                    <div class="ei-field">
                        <label for="ei_reply_to"><?php echo lang('ei_reply_to'); ?></label>
                        <input type="email" id="ei_reply_to" name="reply_to" class="form-control" maxlength="150" value="<?php echo $e($s->reply_to); ?>">
                        <div class="ei-muted"><?php echo lang('ei_reply_to_help'); ?></div>
                    </div>
                    <div class="ei-field">
                        <label for="ei_bcc_email"><?php echo lang('ei_bcc_email'); ?></label>
                        <input type="email" id="ei_bcc_email" name="bcc_email" class="form-control" maxlength="150" value="<?php echo $e($s->bcc_email); ?>">
                        <div class="ei-muted"><?php echo lang('ei_bcc_help'); ?></div>
                    </div>
                </div>
            </details>
        </div>

        <div class="ei-card">
            <h4>2. <?php echo lang('ei_pos_section'); ?></h4>
            <label class="ei-switch">
                <input type="checkbox" name="pos_auto_tick" value="1" <?php echo (int) $s->pos_auto_tick === 1 ? 'checked' : ''; ?>>
                <?php echo lang('ei_pos_auto_tick'); ?>
            </label>
            <div class="ei-muted"><?php echo lang('ei_pos_auto_tick_help'); ?></div>
        </div>

        <div class="ei-card">
            <h4>3. <?php echo lang('ei_test_section'); ?></h4>
            <div class="ei-actions">
                <input type="email" id="ei_test_to" class="form-control" style="max-width:320px;" maxlength="150" placeholder="<?php echo $e(lang('ei_test_to')); ?>">
                <button type="button" class="btn bg-blue-btn" id="ei_test_btn"><?php echo lang('ei_send_test'); ?></button>
            </div>
            <div class="ei-muted"><?php echo lang('ei_test_help'); ?></div>
            <div class="ei-result" id="ei_test_result"></div>
        </div>

        <div class="ei-actions">
            <button type="submit" class="btn bg-blue-btn"><?php echo lang('ei_save'); ?></button>
        </div>
    <?php echo form_close(); ?>
</section>

<script>
(function () {
    var providers = <?php echo json_encode($provider_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var base = <?php echo json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var txtSending = <?php echo json_encode(lang('ei_sending'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var txtNetwork = <?php echo json_encode(lang('ei_network_error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var provider = document.getElementById('ei_provider');
    var help = document.getElementById('ei_provider_help');

    function showHelp() {
        var p = providers[provider.value];
        help.textContent = p ? p.help : '';
        help.style.display = p && p.help ? 'block' : 'none';
    }
    provider.addEventListener('change', function () {
        var p = providers[provider.value];
        if (!p) { return; }
        if (p.host) {
            document.getElementById('ei_host').value = p.host;
            document.getElementById('ei_port').value = p.port;
            document.getElementById('ei_encryption').value = p.encryption;
        } else {
            document.getElementById('ei_advanced').open = true;
        }
        showHelp();
    });
    // the port follows the security choice when it still has a standard value
    document.getElementById('ei_encryption').addEventListener('change', function () {
        var port = document.getElementById('ei_port');
        var standard = { tls: 587, ssl: 465, none: 25 };
        if (['587', '465', '25', ''].indexOf(String(port.value)) !== -1) {
            port.value = standard[this.value];
        }
    });
    showHelp();

    var btn = document.getElementById('ei_test_btn');
    var out = document.getElementById('ei_test_result');
    btn.addEventListener('click', function () {
        var form = document.getElementById('ei_form');
        var data = new FormData(form);
        data.append('test_to', document.getElementById('ei_test_to').value);
        btn.disabled = true;
        out.className = 'ei-result';
        out.textContent = txtSending;
        fetch(base + 'Email_invoice/testEmail', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                out.className = 'ei-result ' + (res.ok ? 'ok' : 'err');
                out.textContent = res.ok ? res.msg : res.error;
                if (!res.ok && res.hint) {
                    var h = document.createElement('div');
                    h.className = 'ei-help';
                    h.textContent = res.hint;
                    out.appendChild(h);
                }
            })
            .catch(function () {
                out.className = 'ei-result err';
                out.textContent = txtNetwork;
            })
            .then(function () { btn.disabled = false; });
    });
})();
</script>
