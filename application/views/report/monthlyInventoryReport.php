<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/report.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/sales_summary_report.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/monthly_inventory_report.css?v=<?php echo @filemtime(FCPATH . 'frequent_changing/css/monthly_inventory_report.css'); ?>">

<?php
$date_format = $this->session->userdata('date_format');
$outlet_name = getOutletNameById($outlet_id);
$period_text = date($date_format, strtotime($start_date)) . ' ' . lang('ssr_to') . ' ' . date($date_format, strtotime($end_date));
$report_heading = lang('monthly_inventory_report') . ' - ' . $period_text . ' - ' . $outlet_name;

//a zero shows as "-", the way the sheet does. At least 2 decimals, more only when they matter
$number = function ($value, $decimals = 3) {
    if (abs($value) < 0.00005) {
        return '-';
    }
    $text = number_format($value, $decimals, '.', '');
    return preg_replace('/(\.\d{2}\d*?)0+$/', '$1', $text);
};
$money = function ($value) {
    return abs($value) < 0.00005 ? '-' : getAmtP($value);
};
$negative = function ($value) {
    return $value < -0.00005 ? 'mir_negative' : '';
};
$flag_labels = array(
    'no_unit_price' => lang('mir_no_unit_price'),
    'count_higher' => lang('mir_count_higher'),
    'negative_stock' => lang('mir_negative_stock'),
    'no_conversion' => lang('mir_no_conversion'),
    'listed_twice' => lang('mir_listed_twice'),
);
$flag_remarks = array(
    'no_unit_price' => lang('mir_no_unit_price_remark'),
    'count_higher' => lang('mir_count_higher_remark'),
    'negative_stock' => lang('mir_negative_stock_remark'),
    'no_conversion' => lang('mir_no_conversion_remark'),
    'listed_twice' => lang('mir_listed_twice_remark'),
);
?>

<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header text-left"><?php echo lang('monthly_inventory_report'); ?></h3>
    </section>

    <div>
        <h4 class="txt-color-grey"><?php echo lang('outlet'); ?>: <?php echo escape_output($outlet_name); ?></h4>
        <h4 class="txt-color-grey"><?php echo lang('date'); ?>: <?php echo escape_output($period_text); ?></h4>
    </div>

    <div class="box-wrapper">
        <div class="row">
            <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                <?php echo form_open(base_url() . 'Report/monthlyInventoryReport') ?>
                <div class="form-group">
                    <input tabindex="1" type="text" name="startDate" readonly class="form-control customDatepicker"
                           placeholder="<?php echo lang('start_date'); ?>" value="<?php echo escape_output($start_date); ?>">
                </div>
            </div>
            <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                <div class="form-group">
                    <input tabindex="2" type="text" name="endDate" readonly class="form-control customDatepicker"
                           placeholder="<?php echo lang('end_date'); ?>" value="<?php echo escape_output($end_date); ?>">
                </div>
            </div>
            <?php if (isLMni()): ?>
                <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                    <div class="form-group">
                        <select tabindex="3" class="form-control select2 ir_w_100" id="outlet_id" name="outlet_id">
                            <?php foreach (getAllOutlestByAssign() as $value): ?>
                                <option <?php echo $value->id == $outlet_id ? 'selected' : ''; ?> value="<?php echo escape_output($value->id); ?>"><?php echo escape_output($value->outlet_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-sm-12 mb-3 col-md-2 pull-right">
                <div class="form-group">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <div class="table-box">
            <p class="mir_note"><?php echo lang('mir_physical_count_note'); ?></p>
            <div class="table-responsive">
                <table class="table ssr_table mir_table" data-title="<?php echo escape_output($report_heading); ?>" data-search="yes" data-paging="no">
                    <thead>
                    <tr class="mir_group_row">
                        <th colspan="6"><?php echo lang('mir_group_item'); ?></th>
                        <th colspan="4"><?php echo lang('quantity'); ?></th>
                        <th colspan="2"><?php echo lang('mir_total_stock_value'); ?></th>
                        <th colspan="2"><?php echo lang('mir_physical_count'); ?></th>
                        <th colspan="2"><?php echo lang('mir_group_usage'); ?></th>
                        <th colspan="2"><?php echo lang('mir_group_review'); ?></th>
                    </tr>
                    <tr>
                        <th><?php echo lang('mir_no'); ?></th>
                        <th><?php echo lang('mir_item_description'); ?></th>
                        <th><?php echo lang('unit'); ?></th>
                        <th><?php echo lang('mir_pack_size'); ?></th>
                        <th><?php echo lang('consumption_unit'); ?></th>
                        <th class="ssr_amount"><?php echo lang('conversion_rate'); ?></th>
                        <th class="ssr_amount"><?php echo lang('opening_stock'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_receiving'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_transfer'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_total_stock'); ?></th>
                        <th class="ssr_amount"><?php echo lang('unit_price'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_total_stock_value'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_physical_count_qty'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_physical_count_value'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_usage_qty'); ?></th>
                        <th class="ssr_amount"><?php echo lang('mir_usage_value'); ?></th>
                        <th><?php echo lang('mir_check'); ?></th>
                        <th><?php echo lang('mir_remarks'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($report['rows'] as $key => $row):
                        $pack_size = '';
                        if ($row->conversion_rate > 0) {
                            $pack_size = '1 ' . $row->purchase_unit;
                            if ($row->purchase_unit != $row->consumption_unit || $row->conversion_rate != 1) {
                                $pack_size .= ' = ' . (float)$row->conversion_rate . ' ' . $row->consumption_unit;
                            }
                        }
                        $checks = array();
                        $remarks = array();
                        foreach ($row->flags as $flag) {
                            $checks[] = $flag_labels[$flag];
                            $remarks[] = $flag_remarks[$flag];
                        }
                        //stock sent out shows in brackets, like the sheet
                        $transfer_text = $row->transfer < -0.00005 ? '(' . $number(abs($row->transfer)) . ')' : $number($row->transfer);
                        ?>
                        <tr class="<?php echo $row->flags ? 'mir_flagged' : ''; ?>">
                            <td><?php echo $key + 1; ?></td>
                            <td class="mir_item"><?php echo escape_output($row->name . ' (' . $row->code . ')'); ?></td>
                            <td><?php echo escape_output($row->purchase_unit); ?></td>
                            <td class="mir_pack"><?php echo escape_output($pack_size); ?></td>
                            <td><?php echo escape_output($row->consumption_unit); ?></td>
                            <td class="ssr_amount"><?php echo $row->conversion_rate > 0 ? (float)$row->conversion_rate : ''; ?></td>
                            <td class="ssr_amount <?php echo $negative($row->opening_stock); ?>"><?php echo $number($row->opening_stock); ?></td>
                            <td class="ssr_amount"><?php echo $number($row->receiving); ?></td>
                            <td class="ssr_amount"><?php echo $transfer_text; ?></td>
                            <td class="ssr_amount <?php echo $negative($row->total_stock); ?>"><?php echo $number($row->total_stock); ?></td>
                            <td class="ssr_amount"><?php echo $number($row->unit_price, 4); ?></td>
                            <td class="ssr_amount <?php echo $negative($row->total_stock_value); ?>"><?php echo $money($row->total_stock_value); ?></td>
                            <td class="ssr_amount <?php echo $negative($row->physical_count); ?>"><?php echo $number($row->physical_count); ?></td>
                            <td class="ssr_amount <?php echo $negative($row->physical_count_value); ?>"><?php echo $money($row->physical_count_value); ?></td>
                            <td class="ssr_amount <?php echo $negative($row->usage); ?>"><?php echo $number($row->usage); ?></td>
                            <td class="ssr_amount <?php echo $negative($row->usage_value); ?>"><?php echo $money($row->usage_value); ?></td>
                            <td class="mir_check"><?php echo escape_output(implode(', ', $checks)); ?></td>
                            <td class="mir_remarks"><?php echo escape_output(implode(' ', $remarks)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="ssr_total_row">
                        <td></td>
                        <td><?php echo lang('mir_total'); ?></td>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        <td class="ssr_amount"><?php echo $money($report['totals']['total_stock_value']); ?></td>
                        <td></td>
                        <td class="ssr_amount"><?php echo $money($report['totals']['physical_count_value']); ?></td>
                        <td></td>
                        <td class="ssr_amount"><?php echo $money($report['totals']['usage_value']); ?></td>
                        <td><?php echo (int)$report['totals']['flagged']; ?> <?php echo lang('mir_to_check'); ?></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
<!-- DataTables -->
<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js">
</script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>

<script src="<?php echo base_url(); ?>frequent_changing/js/sales_summary_report.js"></script>
