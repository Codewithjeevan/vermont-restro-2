<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/report.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/sales_summary_report.css">

<?php
/*
 * one view for Sales Summary, Sales Issues, Daily Sales Reconciliation and Invoice Detail reports
 * $report_method decides which report is shown, all of them use the same data
 */
$report_method = isset($report_method) && $report_method ? $report_method : 'salesSummaryReport';
$report_title = isset($report_title) && $report_title ? $report_title : lang('sales_summary_report');
$date_format = $this->session->userdata('date_format');
$currency = $this->session->userdata('currency');
$report_date = '';
if (isset($start_date) && $start_date && isset($end_date) && $end_date) {
    $report_date = date($date_format, strtotime($start_date)) . " " . lang('ssr_to') . " " . date($date_format, strtotime($end_date));
} elseif (isset($start_date) && $start_date) {
    $report_date = date($date_format, strtotime($start_date));
} elseif (isset($end_date) && $end_date) {
    $report_date = date($date_format, strtotime($end_date));
}
$report_heading = $report_title . ($report_date ? " - " . $report_date : '');
$outlet_name = isset($outlet_id) && $outlet_id ? getOutletNameById($outlet_id) : '';
?>

<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header text-left"><?php echo escape_output($report_title); ?></h3>
    </section>

    <div>
        <?php
        if(isLMni() && isset($outlet_id)):
            ?>
            <h4 class="txt-color-grey"> <?php echo lang('outlet'); ?>: <?php echo escape_output($outlet_name)?></h4>
            <?php
        endif;
        ?>
        <h4 class="txt-color-grey"><?php echo $report_date ? lang('date') . ": " . escape_output($report_date) : ''; ?></h4>
    </div>

    <div class="box-wrapper">
        <div class="row">
            <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                <?php echo form_open(base_url() . 'Report/' . $report_method) ?>
                <div class="form-group">
                    <input tabindex="1" type="text" id="" name="startDate" readonly class="form-control customDatepicker"
                           placeholder="<?php echo lang('start_date'); ?>" value="<?php echo set_value('startDate'); ?>">
                </div>
            </div>
            <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                <div class="form-group">
                    <input tabindex="2" type="text" id="endMonth" name="endDate" readonly
                           class="form-control customDatepicker" placeholder="<?php echo lang('end_date'); ?>"
                           value="<?php echo set_value('endDate'); ?>">
                </div>
            </div>
            <?php
            if(isLMni()):
                ?>
                <div class="col-sm-12 mb-3 col-md-4 col-lg-2">
                    <div class="form-group">
                        <select tabindex="3" class="form-control select2 ir_w_100" id="outlet_id" name="outlet_id">
                            <?php
                            $outlets = getAllOutlestByAssign();
                            foreach ($outlets as $value):
                                ?>
                                <option <?= set_select('outlet_id',$value->id)?>  value="<?php echo escape_output($value->id) ?>"><?php echo escape_output($value->outlet_name) ?></option>
                                <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
                <?php
            endif;
            ?>
            <div class="col-sm-12 mb-3 col-md-2 pull-right">
                <div class="form-group">
                    <button type="submit" name="submit" value="submit"
                            class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <?php
        if (isset($salesSummary) && $salesSummary):
            $total_sales = $salesSummary['total']['g_total'];
            $percent = function ($amount) use ($total_sales) {
                return number_format($total_sales != 0 ? ($amount / $total_sales) * 100 : 0, 2) . '%';
            };
            //amounts exactly as saved, no round off
            $CI = &get_instance();
            $amt = function ($amount) use ($CI) {
                return $CI->Report_model->salesSummaryAmount($amount);
            };
            ?>
        <div class="table-box">
            <div class="table-responsive">
                <h3 class="ir_txt_center txt-color-grey"><?php echo escape_output($report_heading); ?></h3>

        <?php if ($report_method == 'salesSummaryReport'): ?>
                <!-- Summary -->
                <table id="datatable_summary" class="table ssr_table" data-title="<?php echo escape_output($report_heading); ?>" data-search="no">
                    <thead>
                    <tr>
                        <th class="ssr_label_col"><?php echo lang('payment_method'); ?></th>
                        <th class="ssr_amount"><?php echo lang('amount'); ?> (<?php echo escape_output($currency); ?>)</th>
                        <th class="ssr_amount"><?php echo lang('ssr_percent_of_sales'); ?></th>
                        <th class="ssr_amount"><?php echo lang('invoices'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($salesSummary['methods'] as $method): ?>
                        <tr>
                            <td><?php echo escape_output($method['name']); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($method['amount'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($percent($method['amount'])); ?></td>
                            <td class="ssr_amount"><?php echo (int)$method['invoices']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><?php echo lang('ssr_unrecorded_payment'); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($salesSummary['unrecorded'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($percent($salesSummary['unrecorded'])); ?></td>
                        <td class="ssr_amount"><?php echo (int)$salesSummary['total']['unrecorded_invoices']; ?></td>
                    </tr>
                    <tr class="ssr_total_row">
                        <td><?php echo lang('ssr_total_sales'); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($total_sales)); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($percent($total_sales)); ?></td>
                        <td class="ssr_amount"><?php echo (int)$salesSummary['total']['invoices']; ?></td>
                    </tr>
                    <tr class="ssr_blank_row"><td>&nbsp;</td><td></td><td></td><td></td></tr>

                    <tr class="ssr_section_row">
                        <th><?php echo lang('ssr_grouped_view'); ?></th>
                        <th class="ssr_amount"><?php echo lang('amount'); ?> (<?php echo escape_output($currency); ?>)</th>
                        <th class="ssr_amount"><?php echo lang('ssr_percent_of_sales'); ?></th>
                        <th></th>
                    </tr>
                    <?php
                    $group_total = 0;
                    foreach ($salesSummary['groups'] as $group):
                        $group_total += $group['amount'];
                        ?>
                        <tr>
                            <td><?php echo escape_output($group['label']); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($group['amount'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($percent($group['amount'])); ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ssr_total_row">
                        <td><?php echo lang('total'); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($group_total)); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($percent($group_total)); ?></td>
                        <td></td>
                    </tr>
                    <tr class="ssr_blank_row"><td>&nbsp;</td><td></td><td></td><td></td></tr>

                    <tr class="ssr_section_row">
                        <th><?php echo lang('ssr_total_checks'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_value_a'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_value_b'); ?></th>
                        <th><?php echo lang('ssr_result'); ?></th>
                    </tr>
                    <?php foreach ($salesSummary['checks'] as $check):
                        if ($check['ok']) {
                            $result_text = lang('ssr_ok');
                        } else {
                            $result_text = lang('ssr_difference') . " " . ($check['is_count'] ? (int)$check['difference'] : $amt($check['difference']));
                            if (!empty($check['mismatch_invoices'])) {
                                $result_text .= " (" . $check['mismatch_invoices'] . " " . lang('invoices') . ")";
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo escape_output($check['label']); ?></td>
                            <td class="ssr_amount"><?php echo $check['is_count'] ? (int)$check['a'] : escape_output($amt($check['a'])); ?></td>
                            <td class="ssr_amount"><?php echo $check['is_count'] ? (int)$check['b'] : escape_output($amt($check['b'])); ?></td>
                            <td class="<?php echo $check['ok'] ? 'ssr_ok' : 'ssr_diff'; ?>"><?php echo escape_output($result_text); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ssr_note_row">
                        <td><?php echo lang('ssr_checks_note'); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>

        <?php elseif ($report_method == 'salesIssuesReport'): ?>
                <!-- Issues -->
                <table id="datatable_issues" class="table ssr_table" data-title="<?php echo escape_output($report_heading); ?>" data-search="yes">
                    <thead>
                    <tr>
                        <th class="ir_w2_txt_center">#</th>
                        <th><?php echo lang('date'); ?></th>
                        <th><?php echo lang('sale_no'); ?></th>
                        <th class="ssr_amount"><?php echo lang('g_total'); ?> (<?php echo escape_output($currency); ?>)</th>
                        <th class="ssr_amount"><?php echo lang('paid'); ?> (<?php echo escape_output($currency); ?>)</th>
                        <th class="ssr_amount"><?php echo lang('ssr_gap'); ?> (<?php echo escape_output($currency); ?>)</th>
                        <th><?php echo lang('payment_method'); ?></th>
                        <th><?php echo lang('ssr_issue'); ?></th>
                        <th><?php echo lang('ssr_action'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total_gap = 0;
                    foreach ($salesSummary['issues'] as $key => $issue):
                        $total_gap += $issue->gap;
                        ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo (int)($key + 1); ?></td>
                            <td><?php echo escape_output(date($date_format, strtotime($issue->sale_date))); ?></td>
                            <td><?php echo escape_output($issue->sale_no); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($issue->g_total)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($issue->paid)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($issue->gap)); ?></td>
                            <td><?php echo escape_output($issue->payment_method); ?></td>
                            <td><?php echo escape_output($issue->issue); ?></td>
                            <td><?php echo escape_output($issue->action); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$salesSummary['issues']): ?>
                        <tr>
                            <td></td>
                            <td><?php echo lang('ssr_no_issues'); ?></td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr class="ssr_total_row">
                        <td></td>
                        <td></td>
                        <td><?php echo lang('ssr_total_gap'); ?></td>
                        <td></td>
                        <td></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($total_gap)); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>

        <?php elseif ($report_method == 'dailySalesReconciliationReport'): ?>
                <!-- Daily Reconciliation -->
                <table id="datatable_daily" class="table ssr_table" data-title="<?php echo escape_output($report_heading); ?>" data-search="yes">
                    <thead>
                    <tr>
                        <th><?php echo lang('date'); ?></th>
                        <?php foreach ($salesSummary['methods'] as $method): ?>
                            <th class="ssr_amount"><?php echo escape_output($method['name']); ?></th>
                        <?php endforeach; ?>
                        <th class="ssr_amount"><?php echo lang('ssr_total_collected'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_unrecorded_payment_col'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_detailed_g_total'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_daily_report_total'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_difference_col'); ?></th>
                        <th><?php echo lang('status'); ?></th>
                        <th class="ssr_amount"><?php echo lang('invoices'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_problem_invoices'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $day_total = array('methods' => array(), 'collected' => 0, 'unrecorded' => 0, 'g_total' => 0, 'daily_report' => 0, 'difference' => 0, 'invoices' => 0, 'problem_invoices' => 0);
                    $all_match = true;
                    foreach ($salesSummary['days'] as $date => $day):
                        $day_match = $day['match'];
                        if (!$day_match) {
                            $all_match = false;
                        }
                        foreach (array('collected', 'unrecorded', 'g_total', 'daily_report', 'difference', 'invoices', 'problem_invoices') as $field) {
                            $day_total[$field] += $day[$field];
                        }
                        ?>
                        <tr>
                            <td><?php echo escape_output(date($date_format, strtotime($date))); ?></td>
                            <?php foreach ($salesSummary['methods'] as $payment_id => $method):
                                $method_amount = isset($day['methods'][$payment_id]) ? $day['methods'][$payment_id] : 0;
                                $day_total['methods'][$payment_id] = (isset($day_total['methods'][$payment_id]) ? $day_total['methods'][$payment_id] : 0) + $method_amount;
                                ?>
                                <td class="ssr_amount"><?php echo escape_output($amt($method_amount)); ?></td>
                            <?php endforeach; ?>
                            <td class="ssr_amount"><?php echo escape_output($amt($day['collected'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($day['unrecorded'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($day['g_total'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($day['daily_report'])); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($day['difference'])); ?></td>
                            <td class="<?php echo $day_match ? 'ssr_ok' : 'ssr_diff'; ?>"><?php echo $day_match ? lang('ssr_match') : lang('ssr_mismatch'); ?></td>
                            <td class="ssr_amount"><?php echo (int)$day['invoices']; ?></td>
                            <td class="ssr_amount <?php echo $day['problem_invoices'] ? 'ssr_diff' : ''; ?>"><?php echo (int)$day['problem_invoices']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="ssr_total_row">
                        <td><?php echo lang('total'); ?></td>
                        <?php foreach ($salesSummary['methods'] as $payment_id => $method): ?>
                            <td class="ssr_amount"><?php echo escape_output($amt(isset($day_total['methods'][$payment_id]) ? $day_total['methods'][$payment_id] : 0)); ?></td>
                        <?php endforeach; ?>
                        <td class="ssr_amount"><?php echo escape_output($amt($day_total['collected'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($day_total['unrecorded'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($day_total['g_total'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($day_total['daily_report'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($day_total['difference'])); ?></td>
                        <td class="<?php echo $all_match ? 'ssr_ok' : 'ssr_diff'; ?>"><?php echo $all_match ? lang('ssr_all_match') : lang('ssr_check_needed'); ?></td>
                        <td class="ssr_amount"><?php echo (int)$day_total['invoices']; ?></td>
                        <td class="ssr_amount"><?php echo (int)$day_total['problem_invoices']; ?></td>
                    </tr>
                    </tfoot>
                </table>

        <?php elseif ($report_method == 'invoiceDetailReport'): ?>
                <!-- Invoice Detail -->
                <table id="datatable_invoices" class="table ssr_table" data-title="<?php echo escape_output($report_heading); ?>" data-search="yes" data-paging="yes">
                    <thead>
                    <tr>
                        <th class="ir_w2_txt_center"><?php echo lang('sn'); ?></th>
                        <th><?php echo lang('date'); ?></th>
                        <th><?php echo lang('sale_no'); ?></th>
                        <th class="ssr_amount"><?php echo lang('total_items'); ?></th>
                        <th class="ssr_amount"><?php echo lang('subtotal'); ?></th>
                        <th class="ssr_amount"><?php echo lang('delivery'); ?> <?php echo lang('delivery_charge'); ?></th>
                        <th class="ssr_amount"><?php echo lang('service_charge'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_tips'); ?></th>
                        <th class="ssr_amount"><?php echo lang('discount'); ?></th>
                        <th class="ssr_amount"><?php echo lang('vat'); ?></th>
                        <th class="ssr_amount"><?php echo lang('g_total'); ?></th>
                        <th><?php echo lang('ssr_payment_as_report'); ?></th>
                        <?php foreach ($salesSummary['methods'] as $method): ?>
                            <th class="ssr_amount"><?php echo escape_output($method['name']); ?></th>
                        <?php endforeach; ?>
                        <th class="ssr_amount"><?php echo lang('ssr_total_paid'); ?></th>
                        <th class="ssr_amount"><?php echo lang('ssr_paid_diff'); ?></th>
                        <th class="ssr_amount"><?php echo lang($salesSummary['tax_on_top'] ? 'ssr_calc_check' : 'ssr_calc_check_inclusive'); ?></th>
                        <th><?php echo lang('ssr_issue'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $invoice_total = array('items' => 0, 'sub_total' => 0, 'delivery' => 0, 'service' => 0, 'tips' => 0, 'discount' => 0, 'vat' => 0, 'g_total' => 0, 'methods' => array(), 'paid' => 0, 'paid_diff' => 0, 'calc_check' => 0);
                    foreach ($salesSummary['invoices'] as $key => $invoice):
                        foreach (array('items', 'sub_total', 'delivery', 'service', 'tips', 'discount', 'vat', 'g_total', 'paid', 'paid_diff', 'calc_check') as $field) {
                            $invoice_total[$field] += $invoice->$field;
                        }
                        ?>
                        <tr class="<?php echo $invoice->issue != '' ? 'ssr_issue_row' : ''; ?>">
                            <td class="ir_txt_center"><?php echo (int)($key + 1); ?></td>
                            <td><?php echo escape_output(date($date_format, strtotime($invoice->sale_date))); ?></td>
                            <td><?php echo escape_output($invoice->sale_no); ?></td>
                            <td class="ssr_amount"><?php echo (int)$invoice->items; ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->sub_total)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->delivery)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->service)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->tips)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->discount)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->vat)); ?></td>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->g_total)); ?></td>
                            <td><?php echo escape_output($invoice->payment_method); ?></td>
                            <?php foreach ($salesSummary['methods'] as $payment_id => $method):
                                $method_amount = isset($invoice->paid_by_method[$payment_id]) ? $invoice->paid_by_method[$payment_id] : 0;
                                $invoice_total['methods'][$payment_id] = (isset($invoice_total['methods'][$payment_id]) ? $invoice_total['methods'][$payment_id] : 0) + $method_amount;
                                ?>
                                <td class="ssr_amount"><?php echo isset($invoice->paid_by_method[$payment_id]) ? escape_output($amt($method_amount)) : ''; ?></td>
                            <?php endforeach; ?>
                            <td class="ssr_amount"><?php echo escape_output($amt($invoice->paid)); ?></td>
                            <td class="ssr_amount <?php echo $invoice->has_gap ? 'ssr_diff' : ''; ?>"><?php echo escape_output($amt($invoice->paid_diff)); ?></td>
                            <td class="ssr_amount <?php echo $invoice->has_calc_diff ? 'ssr_diff' : ''; ?>"><?php echo escape_output($amt($invoice->calc_check)); ?></td>
                            <td><?php echo escape_output($invoice->issue); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="ssr_total_row">
                        <td></td>
                        <td></td>
                        <td><?php echo lang('total'); ?></td>
                        <td class="ssr_amount"><?php echo (int)$invoice_total['items']; ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['sub_total'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['delivery'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['service'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['tips'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['discount'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['vat'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['g_total'])); ?></td>
                        <td></td>
                        <?php foreach ($salesSummary['methods'] as $payment_id => $method): ?>
                            <td class="ssr_amount"><?php echo escape_output($amt(isset($invoice_total['methods'][$payment_id]) ? $invoice_total['methods'][$payment_id] : 0)); ?></td>
                        <?php endforeach; ?>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['paid'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['paid_diff'])); ?></td>
                        <td class="ssr_amount"><?php echo escape_output($amt($invoice_total['calc_check'])); ?></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
        <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>
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
