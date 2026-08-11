<?php
/**
 * Setting -> Channel Orders
 *
 * Every inbound order, why it failed if it did, the raw payload for disputes,
 * and a retry for outbound calls that dead-lettered.
 */
$status_class = array(
    'RECEIVED'  => 'bg-warning',
    'ACCEPTED'  => 'bg-primary',
    'PREPARING' => 'bg-primary',
    'READY'     => 'bg-info',
    'PICKED_UP' => 'bg-info',
    'COMPLETED' => 'bg-success',
    'REJECTED'  => 'bg-danger',
    'CANCELLED' => 'bg-dark',
);
?>
<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header"><?php echo lang('integration_order_log'); ?></h3>
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <form method="get" action="<?php echo base_url(); ?>Integration/orderLog" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="outlet_id" class="form-control">
                        <option value=""><?php echo lang('integration_all_outlets'); ?></option>
                        <?php foreach ($outlets as $outlet) { ?>
                            <option value="<?php echo (int) $outlet->id; ?>" <?php echo ((int) $filters['outlet_id'] === (int) $outlet->id) ? 'selected' : ''; ?>>
                                <?php echo escape_output($outlet->outlet_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="provider_code" class="form-control">
                        <option value=""><?php echo lang('integration_all_channels'); ?></option>
                        <?php foreach ($providers as $provider) { ?>
                            <option value="<?php echo escape_output($provider->code); ?>" <?php echo ($filters['provider_code'] === $provider->code) ? 'selected' : ''; ?>>
                                <?php echo escape_output($provider->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value=""><?php echo lang('integration_all_statuses'); ?></option>
                        <?php foreach ($statuses as $status) { ?>
                            <option value="<?php echo $status; ?>" <?php echo ($filters['status'] === $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?php echo escape_output((string) $filters['date_from']); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?php echo escape_output((string) $filters['date_to']); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <input type="text" name="q" class="form-control" placeholder="<?php echo lang('integration_search'); ?>" value="<?php echo escape_output((string) $filters['q']); ?>">
                    <button type="submit" class="btn bg-blue-btn"><i class="fa fa-search"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th class="ir_w_10"><?php echo lang('integration_channel'); ?></th>
                            <th class="ir_w_18"><?php echo lang('integration_external_order'); ?></th>
                            <th class="ir_w_13"><?php echo lang('sale_no'); ?></th>
                            <th class="ir_w_10"><?php echo lang('status'); ?></th>
                            <th class="ir_w_15"><?php echo lang('integration_received_utc'); ?></th>
                            <th class="ir_w_23"><?php echo lang('integration_note'); ?></th>
                            <th class="ir_w_10"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $sn = 1; foreach ($orders as $order) { ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $sn++; ?></td>
                            <td><?php echo escape_output($order->provider_code); ?></td>
                            <td>
                                <code><?php echo escape_output($order->external_order_id); ?></code>
                                <?php if ($order->external_order_no) { ?>
                                    <br><small class="text-muted">#<?php echo escape_output($order->external_order_no); ?></small>
                                <?php } ?>
                            </td>
                            <td><?php echo escape_output((string) $order->sale_no); ?></td>
                            <td>
                                <span class="badge <?php echo isset($status_class[$order->canonical_status]) ? $status_class[$order->canonical_status] : 'bg-secondary'; ?>">
                                    <?php echo escape_output($order->canonical_status); ?>
                                </span>
                            </td>
                            <td><?php echo escape_output($order->received_at_utc); ?></td>
                            <td style="font-size:12px;">
                                <?php
                                $note = $order->last_error ? $order->last_error : $order->reject_reason;
                                echo escape_output((string) $note);
                                ?>
                            </td>
                            <td class="ir_txt_center">
                                <button type="button" class="btn btn-outline-secondary btn-sm integration_detail_btn"
                                        data-id="<?php echo (int) $order->id; ?>">
                                    <?php echo lang('integration_details'); ?>
                                </button>
                                <?php if ($order->canonical_status === 'RECEIVED' && $order->kitchen_sale_id) { ?>
                                    <button type="button" class="btn bg-blue-btn btn-sm integration_accept_btn mt-1"
                                            data-id="<?php echo (int) $order->id; ?>"><?php echo lang('integration_accept'); ?></button>
                                    <button type="button" class="btn btn-danger btn-sm integration_reject_btn mt-1"
                                            data-id="<?php echo (int) $order->id; ?>"><?php echo lang('integration_reject'); ?></button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($orders)) { ?>
                        <tr><td colspan="8" class="ir_txt_center text-muted"><?php echo lang('integration_no_orders'); ?></td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- detail drawer -->
<div class="modal fade" id="integration_detail_modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo lang('integration_order_detail'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="integration_detail_summary" class="mb-3"></div>
                <h6><?php echo lang('integration_outbound_events'); ?></h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead><tr>
                            <th><?php echo lang('type'); ?></th>
                            <th><?php echo lang('status'); ?></th>
                            <th><?php echo lang('integration_attempts'); ?></th>
                            <th><?php echo lang('integration_last_error'); ?></th>
                            <th><?php echo lang('actions'); ?></th>
                        </tr></thead>
                        <tbody id="integration_detail_events"></tbody>
                    </table>
                </div>
                <h6><?php echo lang('integration_raw_payload'); ?></h6>
                <pre id="integration_detail_raw" style="max-height:340px;overflow:auto;background:#f6f8fa;padding:12px;font-size:12px;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
    var INTEGRATION_BASE = '<?php echo base_url(); ?>Integration/';

    $(document).on('click', '.integration_detail_btn', function () {
        var id = $(this).data('id');
        $('#integration_detail_summary').html('...');
        $('#integration_detail_events').html('');
        $('#integration_detail_raw').text('');

        $.ajax({
            url: INTEGRATION_BASE + 'orderDetail',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (res.status !== 'ok') { $('#integration_detail_summary').text(res.message || 'error'); return; }
                var o = res.order;
                var rows = [
                    ['<?php echo lang('integration_channel'); ?>', o.provider_code],
                    ['<?php echo lang('integration_external_order'); ?>', o.external_order_id + (o.external_order_no ? ' (#' + o.external_order_no + ')' : '')],
                    ['<?php echo lang('sale_no'); ?>', o.sale_no || '—'],
                    ['<?php echo lang('status'); ?>', o.canonical_status],
                    ['<?php echo lang('integration_received_utc'); ?>', o.received_at_utc || '—'],
                    ['<?php echo lang('integration_ingested_utc'); ?>', o.ingested_at_utc || '—'],
                    ['<?php echo lang('integration_accepted_utc'); ?>', o.accepted_at_utc || '—'],
                    ['<?php echo lang('integration_completed_utc'); ?>', o.completed_at_utc || '—'],
                    ['<?php echo lang('integration_note'); ?>', o.last_error || o.reject_reason || '—']
                ];
                var html = '<table class="table table-sm table-bordered mb-0">';
                rows.forEach(function (r) {
                    html += '<tr><th style="width:220px">' + r[0] + '</th><td>' + $('<div>').text(r[1]).html() + '</td></tr>';
                });
                html += '</table>';
                $('#integration_detail_summary').html(html);

                var ev = '';
                res.events.forEach(function (e) {
                    ev += '<tr><td>' + e.event_type + '</td><td>' + e.status + '</td><td>' + e.attempts + '</td>' +
                          '<td style="font-size:11px">' + $('<div>').text(e.last_error || '').html() + '</td><td>' +
                          ((e.status === 'dead' || e.status === 'failed')
                              ? '<button class="btn btn-sm btn-outline-primary integration_retry_btn" data-event="' + e.id + '"><?php echo lang('integration_retry'); ?></button>'
                              : '') +
                          '</td></tr>';
                });
                $('#integration_detail_events').html(ev || '<tr><td colspan="5" class="text-muted"><?php echo lang('integration_no_events'); ?></td></tr>');
                $('#integration_detail_raw').text(res.raw_payload || '');

                new bootstrap.Modal(document.getElementById('integration_detail_modal')).show();
            },
            error: function () { $('#integration_detail_summary').text('Request failed'); }
        });
    });

    $(document).on('click', '.integration_retry_btn', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: INTEGRATION_BASE + 'retryEvent',
            method: 'POST', dataType: 'json',
            data: { event_id: btn.data('event') },
            success: function (res) {
                btn.replaceWith('<span class="text-success">' + (res.message || 'queued') + '</span>');
            },
            error: function () { btn.prop('disabled', false); }
        });
    });

    $(document).on('click', '.integration_accept_btn, .integration_reject_btn', function () {
        var btn = $(this);
        var isReject = btn.hasClass('integration_reject_btn');
        var reason = '';
        if (isReject) {
            reason = window.prompt('<?php echo lang('integration_reject_reason_prompt'); ?>', 'ITEM_86');
            if (reason === null) { return; }
        }
        btn.prop('disabled', true);

        $.ajax({
            url: INTEGRATION_BASE + (isReject ? 'rejectOrder' : 'acceptOrder'),
            method: 'POST', dataType: 'json',
            data: { order_id: btn.data('id'), reason: reason },
            success: function (res) {
                if (res.status === 'ok') { location.reload(); }
                else { alert(res.message || 'error'); btn.prop('disabled', false); }
            },
            error: function () { btn.prop('disabled', false); }
        });
    });
</script>
