<?php
/**
 * Setting -> Integrations
 *
 * One card per provider, for one outlet. The grid is rendered by looping
 * tbl_integration_providers, so a new app appears here automatically once its
 * catalogue row exists - no edit to this file.
 */
$currency = $this->session->userdata('currency');
?>
<section class="main-content-wrapper">
    <?php if ($this->session->flashdata('exception')) { ?>
        <section class="alert-wrapper">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-body"><p><i class="m-right fa fa-check"></i>
                    <?php echo escape_output($this->session->flashdata('exception')); unset($_SESSION['exception']); ?>
                </p></div>
            </div>
        </section>
    <?php } ?>
    <?php if ($this->session->flashdata('exception_1')) { ?>
        <section class="alert-wrapper">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-body"><p><i class="m-right fa fa-times"></i>
                    <?php echo escape_output($this->session->flashdata('exception_1')); unset($_SESSION['exception_1']); ?>
                </p></div>
            </div>
        </section>
    <?php } ?>

    <section class="content-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3 class="top-left-header"><?php echo lang('integration_settings'); ?></h3>
            </div>
            <div class="col-md-6">
                <form method="get" action="<?php echo base_url(); ?>Integration/settings" class="d-flex justify-content-md-end gap-2">
                    <label class="align-self-center mb-0 me-2"><?php echo lang('outlet'); ?></label>
                    <select name="outlet_id" class="form-control" style="max-width:260px" onchange="this.form.submit()">
                        <?php foreach ($outlets as $outlet) { ?>
                            <option value="<?php echo escape_output($outlet->id); ?>" <?php echo ((int) $outlet->id === (int) $outlet_id) ? 'selected' : ''; ?>>
                                <?php echo escape_output($outlet->outlet_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>
            </div>
        </div>
    </section>

    <?php if ($dead_events > 0) { ?>
        <section class="alert-wrapper">
            <div class="alert alert-danger">
                <div class="alert-body"><p>
                    <i class="m-right fa fa-exclamation-triangle"></i>
                    <?php echo escape_output(sprintf(lang('integration_dead_events'), $dead_events)); ?>
                    <a href="<?php echo base_url(); ?>Integration/orderLog"><?php echo lang('integration_order_log'); ?></a>
                </p></div>
            </div>
        </section>
    <?php } ?>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="row">
                <?php foreach ($providers as $provider) {
                    $config    = isset($configs[(int) $provider->id]) ? $configs[(int) $provider->id] : null;
                    $installed = !empty($driver_installed[$provider->code]);
                    $stat      = isset($stats[$provider->code]) ? $stats[$provider->code] : null;
                    $unmapped  = isset($unmapped_counts[$provider->code]) ? (int) $unmapped_counts[$provider->code] : 0;
                    $enabled   = ($config && $config->is_enabled === 'Yes');
                    $modal_id  = 'cfg_modal_'.(int) $provider->id;
                    ?>
                    <div class="col-sm-12 col-md-6 col-lg-4 mb-3">
                        <div class="card h-100" style="border:1px solid #e3e6ef;border-radius:8px;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?php echo escape_output($provider->name); ?></h5>
                                    <span class="badge <?php echo $enabled ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $enabled ? lang('integration_enabled') : lang('integration_disabled'); ?>
                                    </span>
                                </div>

                                <?php if (!$installed) { ?>
                                    <p class="text-muted mb-2"><i class="fa fa-plug"></i> <?php echo lang('integration_driver_missing'); ?></p>
                                <?php } ?>

                                <p class="mb-1 text-muted" style="font-size:12px;">
                                    <?php echo lang('integration_store_id'); ?>:
                                    <strong><?php echo $config && $config->external_store_id ? escape_output($config->external_store_id) : '—'; ?></strong>
                                </p>
                                <p class="mb-1 text-muted" style="font-size:12px;">
                                    <?php echo lang('integration_orders_today'); ?>:
                                    <strong><?php echo $stat ? (int) $stat['orders_today'] : 0; ?></strong>
                                    <?php if ($stat && $stat['rejected_today'] > 0) { ?>
                                        <span class="text-danger">(<?php echo (int) $stat['rejected_today']; ?> <?php echo lang('integration_rejected'); ?>)</span>
                                    <?php } ?>
                                </p>
                                <p class="mb-2 text-muted" style="font-size:12px;">
                                    <?php echo lang('integration_last_order'); ?>:
                                    <strong><?php echo $stat && $stat['last_at'] ? escape_output($stat['last_at']).' UTC' : '—'; ?></strong>
                                </p>

                                <?php if ($unmapped > 0) { ?>
                                    <p class="mb-2">
                                        <a class="text-danger" href="<?php echo base_url(); ?>Integration/itemMap?config_id=<?php echo (int) $config->id; ?>&filter=unmapped">
                                            <i class="fa fa-exclamation-circle"></i>
                                            <?php echo escape_output(sprintf(lang('integration_unmapped_count'), $unmapped)); ?>
                                        </a>
                                    </p>
                                <?php } ?>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn bg-blue-btn btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo $modal_id; ?>">
                                        <?php echo $config ? lang('integration_settings_btn') : lang('integration_connect'); ?>
                                    </button>
                                    <?php if ($config && $installed) { ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm integration_test_btn" data-config="<?php echo (int) $config->id; ?>">
                                            <?php echo lang('integration_test_connection'); ?>
                                        </button>
                                    <?php } ?>
                                </div>
                                <div class="integration_test_result mt-2" data-for="<?php echo $config ? (int) $config->id : 0; ?>" style="font-size:12px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- per-provider drawer -->
                    <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <?php echo form_open(base_url().'Integration/saveConfig'); ?>
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo escape_output($provider->name); ?> — <?php echo lang('integration_settings'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="outlet_id" value="<?php echo (int) $outlet_id; ?>">
                                    <input type="hidden" name="provider_id" value="<?php echo (int) $provider->id; ?>">

                                    <div class="alert alert-info" style="font-size:12px;">
                                        <?php echo lang('integration_webhook_url'); ?>:<br>
                                        <code><?php echo escape_output($webhook_base.$provider->code.'/webhook'); ?></code>
                                    </div>

                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_enabled'); ?></label>
                                            <select name="is_enabled" class="form-control">
                                                <option value="No"  <?php echo ($config && $config->is_enabled === 'No')  ? 'selected' : ''; ?>><?php echo lang('no'); ?></option>
                                                <option value="Yes" <?php echo ($config && $config->is_enabled === 'Yes') ? 'selected' : ''; ?>><?php echo lang('yes'); ?></option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_sandbox'); ?></label>
                                            <select name="is_sandbox" class="form-control">
                                                <option value="Yes" <?php echo (!$config || $config->is_sandbox === 'Yes') ? 'selected' : ''; ?>><?php echo lang('yes'); ?></option>
                                                <option value="No"  <?php echo ($config && $config->is_sandbox === 'No')  ? 'selected' : ''; ?>><?php echo lang('no'); ?></option>
                                            </select>
                                        </div>

                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_store_id'); ?></label>
                                            <input type="text" name="external_store_id" class="form-control"
                                                   value="<?php echo $config ? escape_output($config->external_store_id) : ''; ?>">
                                            <small class="text-muted"><?php echo lang('integration_store_id_hint'); ?></small>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_webhook_secret'); ?></label>
                                            <input type="password" name="webhook_secret" class="form-control" autocomplete="new-password"
                                                   placeholder="<?php echo $config && $config->webhook_secret ? lang('integration_secret_set') : ''; ?>">
                                            <small class="text-muted"><?php echo lang('integration_secret_hint'); ?></small>
                                        </div>

                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_ingest_on'); ?></label>
                                            <select name="ingest_on" class="form-control">
                                                <option value="accepted" <?php echo (!$config || $config->ingest_on === 'accepted') ? 'selected' : ''; ?>><?php echo lang('integration_ingest_accepted'); ?></option>
                                                <option value="placed"   <?php echo ($config && $config->ingest_on === 'placed') ? 'selected' : ''; ?>><?php echo lang('integration_ingest_placed'); ?></option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_auto_accept'); ?></label>
                                            <select name="auto_accept" class="form-control">
                                                <option value="No"  <?php echo (!$config || $config->auto_accept === 'No')  ? 'selected' : ''; ?>><?php echo lang('no'); ?></option>
                                                <option value="Yes" <?php echo ($config && $config->auto_accept === 'Yes') ? 'selected' : ''; ?>><?php echo lang('yes'); ?></option>
                                            </select>
                                            <small class="text-muted"><?php echo lang('integration_auto_accept_hint'); ?></small>
                                        </div>

                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('integration_prep_minutes'); ?></label>
                                            <input type="number" min="1" name="default_prep_minutes" class="form-control"
                                                   value="<?php echo $config ? (int) $config->default_prep_minutes : 20; ?>">
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('integration_price_source'); ?></label>
                                            <select name="price_source" class="form-control">
                                                <option value="provider" <?php echo (!$config || $config->price_source === 'provider') ? 'selected' : ''; ?>><?php echo lang('integration_price_provider'); ?></option>
                                                <option value="pos"      <?php echo ($config && $config->price_source === 'pos') ? 'selected' : ''; ?>><?php echo lang('integration_price_pos'); ?></option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('integration_price_mode'); ?></label>
                                            <select name="price_mode" class="form-control">
                                                <option value="delivery" <?php echo (!$config || $config->price_mode === 'delivery') ? 'selected' : ''; ?>><?php echo lang('integration_price_delivery'); ?></option>
                                                <option value="normal"   <?php echo ($config && $config->price_mode === 'normal') ? 'selected' : ''; ?>><?php echo lang('integration_price_normal'); ?></option>
                                            </select>
                                        </div>

                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('integration_price_includes_tax'); ?></label>
                                            <select name="price_includes_tax" class="form-control">
                                                <option value="Yes" <?php echo (!$config || $config->price_includes_tax === 'Yes') ? 'selected' : ''; ?>><?php echo lang('yes'); ?></option>
                                                <option value="No"  <?php echo ($config && $config->price_includes_tax === 'No')  ? 'selected' : ''; ?>><?php echo lang('no'); ?></option>
                                            </select>
                                            <small class="text-muted"><?php echo lang('integration_tax_hint'); ?></small>
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('payment_method'); ?></label>
                                            <select name="payment_method_id" class="form-control">
                                                <option value=""><?php echo lang('integration_none'); ?></option>
                                                <?php foreach ($payment_methods as $pm) { ?>
                                                    <option value="<?php echo (int) $pm->id; ?>" <?php echo ($config && (int) $config->payment_method_id === (int) $pm->id) ? 'selected' : ''; ?>>
                                                        <?php echo escape_output($pm->name); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label><?php echo lang('deliveryPartner'); ?></label>
                                            <select name="delivery_partner_id" class="form-control">
                                                <option value=""><?php echo lang('integration_none'); ?></option>
                                                <?php foreach ($delivery_partners as $dp) { ?>
                                                    <option value="<?php echo (int) $dp->id; ?>" <?php echo ($config && (int) $config->delivery_partner_id === (int) $dp->id) ? 'selected' : ''; ?>>
                                                        <?php echo escape_output($dp->name); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_ingest_user'); ?></label>
                                            <select name="ingest_user_id" class="form-control">
                                                <option value=""><?php echo lang('integration_auto'); ?></option>
                                                <?php foreach ($users as $user) { ?>
                                                    <option value="<?php echo (int) $user->id; ?>" <?php echo ($config && (int) $config->ingest_user_id === (int) $user->id) ? 'selected' : ''; ?>>
                                                        <?php echo escape_output($user->full_name); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('counter'); ?></label>
                                            <select name="counter_id" class="form-control">
                                                <option value="0"><?php echo lang('integration_none'); ?></option>
                                                <?php foreach ($counters as $counter) { ?>
                                                    <option value="<?php echo (int) $counter->id; ?>" <?php echo ($config && (int) $config->counter_id === (int) $counter->id) ? 'selected' : ''; ?>>
                                                        <?php echo escape_output($counter->name); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="mb-3 col-md-12">
                                            <label><?php echo lang('integration_push_status_on'); ?></label>
                                            <?php
                                            $selected_statuses = $config ? array_map('trim', explode(',', (string) $config->push_status_on)) : array('ACCEPTED', 'READY', 'COMPLETED', 'CANCELLED');
                                            $all_statuses = array('ACCEPTED', 'REJECTED', 'PREPARING', 'READY', 'PICKED_UP', 'COMPLETED', 'CANCELLED');
                                            ?>
                                            <div class="d-flex flex-wrap gap-3">
                                                <?php foreach ($all_statuses as $status) { ?>
                                                    <label class="d-flex align-items-center gap-1 mb-0" style="font-weight:normal;">
                                                        <input type="checkbox" name="push_status_on[]" value="<?php echo $status; ?>"
                                                            <?php echo in_array($status, $selected_statuses, true) ? 'checked' : ''; ?>>
                                                        <?php echo $status; ?>
                                                    </label>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6><?php echo lang('integration_credentials'); ?></h6>
                                    <?php $creds = array();
                                    if ($config) {
                                        require_once APPPATH.'libraries/Integration/Integration_crypto.php';
                                        $creds = Integration_crypto::decrypt_json($config->credentials);
                                    } ?>
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_base_url'); ?></label>
                                            <input type="text" name="base_url" class="form-control"
                                                   value="<?php echo isset($creds['base_url']) ? escape_output($creds['base_url']) : ''; ?>">
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_sandbox_base_url'); ?></label>
                                            <input type="text" name="sandbox_base_url" class="form-control"
                                                   value="<?php echo isset($creds['sandbox_base_url']) ? escape_output($creds['sandbox_base_url']) : ''; ?>">
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_token_url'); ?></label>
                                            <input type="text" name="token_url" class="form-control"
                                                   value="<?php echo isset($creds['token_url']) ? escape_output($creds['token_url']) : ''; ?>">
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_client_id'); ?></label>
                                            <input type="text" name="client_id" class="form-control"
                                                   value="<?php echo isset($creds['client_id']) ? escape_output($creds['client_id']) : ''; ?>">
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_client_secret'); ?></label>
                                            <input type="password" name="client_secret" class="form-control" autocomplete="new-password"
                                                   placeholder="<?php echo !empty($creds['client_secret']) ? lang('integration_secret_set') : ''; ?>">
                                            <small class="text-muted"><?php echo lang('integration_secret_hint'); ?></small>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label><?php echo lang('integration_scope'); ?></label>
                                            <input type="text" name="scope" class="form-control"
                                                   value="<?php echo isset($creds['scope']) ? escape_output($creds['scope']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('cancel'); ?></button>
                                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn"><?php echo lang('submit'); ?></button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).on('click', '.integration_test_btn', function () {
        var btn = $(this);
        var configId = btn.data('config');
        var box = $('.integration_test_result[data-for="' + configId + '"]');
        btn.prop('disabled', true);
        box.removeClass('text-success text-danger').text('...');

        $.ajax({
            url: '<?php echo base_url(); ?>Integration/testConnection',
            method: 'POST',
            dataType: 'json',
            data: { config_id: configId },
            success: function (res) {
                box.addClass(res.status === 'ok' ? 'text-success' : 'text-danger').text(res.message);
            },
            error: function (xhr) {
                var msg = 'Request failed';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                box.addClass('text-danger').text(msg);
            },
            complete: function () { btn.prop('disabled', false); }
        });
    });
</script>
