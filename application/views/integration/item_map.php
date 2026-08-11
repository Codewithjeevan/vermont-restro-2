<?php
/**
 * Setting -> Item Mapping
 *
 * Their SKUs on the left, our menu on the right. Rows appear here on their own:
 * every unmapped SKU seen on an inbound order is recorded, so the list fills
 * itself as orders arrive rather than needing to be typed in up front.
 */
?>
<section class="main-content-wrapper">
    <section class="content-header">
        <div class="row align-items-center">
            <div class="col-md-5">
                <h3 class="top-left-header"><?php echo lang('integration_item_map'); ?></h3>
            </div>
            <div class="col-md-7">
                <form method="get" action="<?php echo base_url(); ?>Integration/itemMap" class="d-flex justify-content-md-end gap-2 flex-wrap">
                    <select name="outlet_id" class="form-control" style="max-width:200px" onchange="this.form.submit()">
                        <?php foreach ($outlets as $outlet) { ?>
                            <option value="<?php echo (int) $outlet->id; ?>" <?php echo ((int) $outlet->id === (int) $outlet_id) ? 'selected' : ''; ?>>
                                <?php echo escape_output($outlet->outlet_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <select name="config_id" class="form-control" style="max-width:200px" onchange="this.form.submit()">
                        <?php
                        $provider_names = array();
                        foreach ($providers as $p) { $provider_names[(int) $p->id] = $p->name; }
                        foreach ($configs as $cfg) { ?>
                            <option value="<?php echo (int) $cfg->id; ?>" <?php echo ($config && (int) $config->id === (int) $cfg->id) ? 'selected' : ''; ?>>
                                <?php echo escape_output(isset($provider_names[(int) $cfg->provider_id]) ? $provider_names[(int) $cfg->provider_id] : $cfg->provider_id); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <select name="filter" class="form-control" style="max-width:170px" onchange="this.form.submit()">
                        <option value="all"      <?php echo $filter === 'all' ? 'selected' : ''; ?>><?php echo lang('integration_filter_all'); ?></option>
                        <option value="unmapped" <?php echo $filter === 'unmapped' ? 'selected' : ''; ?>><?php echo lang('integration_filter_unmapped'); ?></option>
                        <option value="mapped"   <?php echo $filter === 'mapped' ? 'selected' : ''; ?>><?php echo lang('integration_filter_mapped'); ?></option>
                    </select>
                </form>
            </div>
        </div>
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <?php if (!$config) { ?>
                <div class="alert alert-warning">
                    <div class="alert-body"><p><?php echo lang('integration_no_config_hint'); ?></p></div>
                </div>
            <?php } else { ?>

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="text-muted" style="font-size:13px;">
                        <?php echo lang('integration_item_map_hint'); ?>
                    </div>
                    <div>
                        <button type="button" id="integration_automap" class="btn bg-blue-btn btn-sm" data-config="<?php echo (int) $config->id; ?>">
                            <?php echo lang('integration_automap'); ?>
                        </button>
                    </div>
                </div>
                <div id="integration_automap_result" class="mb-2" style="font-size:13px;"></div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                                <th class="ir_w_10"><?php echo lang('type'); ?></th>
                                <th class="ir_w_20"><?php echo lang('integration_external_id'); ?></th>
                                <th class="ir_w_25"><?php echo lang('integration_external_name'); ?></th>
                                <th class="ir_w_34"><?php echo lang('integration_pos_item'); ?></th>
                                <th class="ir_w_10"><?php echo lang('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sn = 1; foreach ($rows as $row) {
                            $is_mod    = ($row->map_type === 'modifier');
                            $target_id = $is_mod ? (int) $row->modifier_id : (int) $row->food_menu_id;
                            $options   = $is_mod ? $modifiers : $menus;
                            ?>
                            <tr data-map-id="<?php echo (int) $row->id; ?>">
                                <td class="ir_txt_center"><?php echo $sn++; ?></td>
                                <td><span class="badge bg-secondary"><?php echo escape_output($row->map_type); ?></span></td>
                                <td><code><?php echo escape_output($row->external_item_id); ?></code></td>
                                <td><?php echo escape_output((string) $row->external_name); ?></td>
                                <td>
                                    <select class="form-control integration_map_select"
                                            data-map-id="<?php echo (int) $row->id; ?>"
                                            data-config="<?php echo (int) $config->id; ?>">
                                        <option value="0"><?php echo lang('integration_not_mapped'); ?></option>
                                        <?php foreach ($options as $option) { ?>
                                            <option value="<?php echo (int) $option->id; ?>" <?php echo ($target_id === (int) $option->id) ? 'selected' : ''; ?>>
                                                <?php echo escape_output($option->name); ?> (#<?php echo (int) $option->id; ?>)
                                            </option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td class="integration_map_status">
                                    <?php if ($target_id > 0) { ?>
                                        <span class="badge bg-success"><?php echo lang('integration_mapped'); ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger"><?php echo lang('integration_unmapped'); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (empty($rows)) { ?>
                            <tr><td colspan="6" class="ir_txt_center text-muted"><?php echo lang('integration_no_map_rows'); ?></td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<script>
    // Saving on change rather than behind a Save button: mapping is done while
    // a rejected order is sitting in the log, and a lost click means another
    // rejected order.
    $(document).on('change', '.integration_map_select', function () {
        var select = $(this);
        var row = select.closest('tr');
        var statusCell = row.find('.integration_map_status');

        $.ajax({
            url: '<?php echo base_url(); ?>Integration/saveItemMap',
            method: 'POST',
            dataType: 'json',
            data: {
                config_id: select.data('config'),
                map_id: select.data('map-id'),
                target_id: select.val()
            },
            success: function (res) {
                if (res.status !== 'ok') {
                    statusCell.html('<span class="badge bg-danger">' + (res.message || 'error') + '</span>');
                    return;
                }
                statusCell.html(Number(select.val()) > 0
                    ? '<span class="badge bg-success"><?php echo lang('integration_mapped'); ?></span>'
                    : '<span class="badge bg-danger"><?php echo lang('integration_unmapped'); ?></span>');
            },
            error: function () {
                statusCell.html('<span class="badge bg-danger">error</span>');
            }
        });
    });

    $(document).on('click', '#integration_automap', function () {
        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: '<?php echo base_url(); ?>Integration/autoMap',
            method: 'POST',
            dataType: 'json',
            data: { config_id: btn.data('config') },
            success: function (res) {
                if (res.status !== 'ok') {
                    $('#integration_automap_result').addClass('text-danger').text(res.message || 'error');
                    return;
                }
                $('#integration_automap_result')
                    .removeClass('text-danger').addClass('text-success')
                    .text(res.mapped + ' mapped, ' + res.unmapped + ' still unmapped.');
                if (res.mapped > 0) { location.reload(); }
            },
            error: function () {
                $('#integration_automap_result').addClass('text-danger').text('Request failed');
            },
            complete: function () { btn.prop('disabled', false); }
        });
    });
</script>
