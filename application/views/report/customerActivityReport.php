<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/foodMenuSales.css">

<section class="main-content-wrapper">

    <section class="content-header">
        <h3 class="text-left top-left-header"><?php echo lang('customer_activity_report'); ?></h3>

        <input type="hidden" class="datatable_name" data-title="<?php echo lang('customer_activity_report'); ?>" data-id_name="datatable">

    </section>

    <div class="my-3">
        <?php
        if(isLMni() && isset($outlet_id)):
            ?>
            <h4> <?php echo lang('outlet'); ?>: <?php echo escape_output(getOutletNameById($outlet_id))?></h4>
            <?php
        endif;
        ?>
        <h4>
            <?php
            if (isset($customer_id) && $customer_id):
                $customer = getCustomerData($customer_id);
                echo lang('customer').": ".escape_output($customer->name)." (".escape_output($customer->phone).")";
            elseif (isset($start_date)):
                echo lang('customer').": ".lang('all');
            endif;
            ?>
        </h4>
        <h4>
            <?= isset($start_date) && $start_date && isset($end_date) && $end_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($start_date)) . " - " . date($this->session->userdata('date_format'), strtotime($end_date)) : '' ?><?= isset($start_date) && $start_date && !$end_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($start_date)) : '' ?><?= isset($end_date) && $end_date && !$start_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($end_date)) : '' ?>
        </h4>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="row">
                <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                    <?php echo form_open(base_url() . 'Report/customerActivityReport', $arrayName = array('id' => 'customerActivityReport')) ?>
                    <div class="form-group">
                        <input tabindex="1" type="text" id="" name="startDate" readonly class="form-control customDatepicker"
                               placeholder="<?php echo lang('start_date'); ?>" value="<?php echo set_value('startDate'); ?>">
                    </div>
                </div>
                <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                    <div class="form-group">
                        <input tabindex="2" type="text" id="endMonth" name="endDate" readonly
                               class="form-control customDatepicker" placeholder="<?php echo lang('end_date'); ?>"
                               value="<?php echo set_value('endDate'); ?>">
                    </div>
                </div>
                <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                    <div class="form-group">
                        <select tabindex="3" class="form-control select2 ir_w_100" id="customer_id" name="customer_id">
                            <option value=""><?php echo lang('all'); ?> <?php echo lang('customers'); ?></option>
                            <?php
                            $check_walk_in_customer = 1;
                            foreach ($customers as $value) {
                                if($value->id==1){
                                    $check_walk_in_customer++;
                                }
                                ?>
                                <option value="<?php echo escape_output($value->id) ?>" <?php echo set_select('customer_id', $value->id); ?>>
                                    <?php echo escape_output($value->name) ?></option>
                            <?php }
                            if($check_walk_in_customer==1){?>
                                <option value="1" <?php echo set_select('customer_id', 1); ?>>Walk-in Customer</option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php
                if(isLMni()):
                    ?>
                    <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                        <div class="form-group">
                            <select tabindex="4" class="form-control select2 ir_w_100" id="outlet_id" name="outlet_id">
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
                <div class="col-sm-12 col-md-4 col-lg-2">
                    <div class="form-group">
                        <button type="submit" name="submit" value="submit"
                                class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-box">
            <!-- /.box-header -->
            <div class="table-responsive">

                <table id="datatable" class="table">
                    <thead>
                    <tr>
                        <th class="ir_w2_txt_center"><?php echo lang('sn'); ?></th>
                        <th><?php echo lang('customer_name'); ?></th>
                        <th><?php echo lang('phone'); ?></th>
                        <th><?php echo lang('total_visits'); ?></th>
                        <th><?php echo lang('total_qty_purchased'); ?></th>
                        <th><?php echo lang('unique_items'); ?></th>
                        <th><?php echo lang('most_purchased_item'); ?></th>
                        <th><?php echo lang('total_purchase_amount'); ?></th>
                        <th><?php echo lang('avg_order_value'); ?></th>
                        <th><?php echo lang('due'); ?></th>
                        <th><?php echo lang('first_visit'); ?></th>
                        <th><?php echo lang('last_visit'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $date_format = $this->session->userdata('date_format');
                    $grand_total_visits = 0;
                    $grand_total_qty = 0;
                    $grand_total_purchase = 0;
                    $grand_total_due = 0;
                    if (isset($customerActivityReport)):
                        foreach ($customerActivityReport as $key => $value) {
                            $key++;
                            $grand_total_visits += $value->total_visits;
                            $grand_total_qty += $value->total_qty;
                            $grand_total_purchase += $value->total_purchase;
                            $grand_total_due += $value->total_due;
                            $favourite = $value->favourite_item ? $value->favourite_item." (".getAmtP($value->favourite_item_qty).")" : '';
                            ?>
                            <tr>
                                <td class="ir_txt_center"><?php echo escape_output($key); ?></td>
                                <td><?php echo escape_output($value->customer_name) ?></td>
                                <td><?php echo escape_output($value->phone) ?></td>
                                <td><?php echo escape_output($value->total_visits) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_qty)) ?></td>
                                <td><?php echo escape_output($value->unique_items) ?></td>
                                <td><?php echo escape_output($favourite) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_purchase)) ?></td>
                                <td><?php echo escape_output(getAmtP($value->avg_order_value)) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_due)) ?></td>
                                <td><?php echo $value->first_visit ? escape_output(date($date_format, strtotime($value->first_visit))) : '' ?></td>
                                <td><?php echo $value->last_visit ? escape_output(date($date_format, strtotime($value->last_visit))) : '' ?></td>
                            </tr>
                            <?php
                        }
                    endif;
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th class="ir_txt_center"></th>
                        <th class="ir_txt_center"></th>
                        <th class="pull-right"><?php echo lang('total'); ?></th>
                        <th><?php echo escape_output($grand_total_visits) ?></th>
                        <th><?php echo escape_output(getAmtP($grand_total_qty)) ?></th>
                        <th class="ir_txt_center"></th>
                        <th class="ir_txt_center"></th>
                        <th><?php echo escape_output(getAmtP($grand_total_purchase)) ?></th>
                        <th class="ir_txt_center"></th>
                        <th><?php echo escape_output(getAmtP($grand_total_due)) ?></th>
                        <th class="ir_txt_center"></th>
                        <th class="ir_txt_center"></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.box-body -->
        </div>

        <?php
        /*item breakdown only makes sense for one customer at a time*/
        if (isset($customerItems)):
            ?>
            <div class="table-box">
                <h4 class="my-3"><?php echo lang('purchased_items'); ?></h4>
                <div class="table-responsive">
                    <table id="datatable2" class="table">
                        <thead>
                        <tr>
                            <th class="ir_w2_txt_center"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('item_name'); ?></th>
                            <th><?php echo lang('category'); ?></th>
                            <th><?php echo lang('times_ordered'); ?></th>
                            <th><?php echo lang('quantity'); ?></th>
                            <th><?php echo lang('total_amount'); ?></th>
                            <th><?php echo lang('last_ordered'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $item_total_qty = 0;
                        $item_total_amount = 0;
                        foreach ($customerItems as $key => $value) {
                            $key++;
                            $item_total_qty += $value->total_qty;
                            $item_total_amount += $value->total_amount;
                            ?>
                            <tr>
                                <td class="ir_txt_center"><?php echo escape_output($key); ?></td>
                                <td><?php echo escape_output($value->menu_name) ?></td>
                                <td><?php echo escape_output($value->category_name) ?></td>
                                <td><?php echo escape_output($value->order_count) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_qty)) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_amount)) ?></td>
                                <td><?php echo $value->last_ordered ? escape_output(date($date_format, strtotime($value->last_ordered))) : '' ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th class="ir_txt_center"></th>
                            <th class="ir_txt_center"></th>
                            <th class="ir_txt_center"></th>
                            <th class="pull-right"><?php echo lang('total'); ?></th>
                            <th><?php echo escape_output(getAmtP($item_total_qty)) ?></th>
                            <th><?php echo escape_output(getAmtP($item_total_amount)) ?></th>
                            <th class="ir_txt_center"></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php
        endif;
        ?>
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

<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report1.js"></script>
