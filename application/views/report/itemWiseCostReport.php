<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/foodMenuSales.css">

<section class="main-content-wrapper">

    <section class="content-header">
        <h3 class="text-left top-left-header"><?php echo lang('item_wise_cost_report'); ?></h3>

        <input type="hidden" class="datatable_name" data-title="<?php echo lang('item_wise_cost_report'); ?>" data-id_name="datatable">

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
            <?= isset($start_date) && $start_date && isset($end_date) && $end_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($start_date)) . " - " . date($this->session->userdata('date_format'), strtotime($end_date)) : '' ?><?= isset($start_date) && $start_date && !$end_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($start_date)) : '' ?><?= isset($end_date) && $end_date && !$start_date ? lang('date').": " . date($this->session->userdata('date_format'), strtotime($end_date)) : '' ?>
        </h4>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="row">
                <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                    <?php echo form_open(base_url() . 'Report/itemWiseCostReport', $arrayName = array('id' => 'itemWiseCostReport')) ?>
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
                        <select tabindex="3" class="form-control select2 ir_w_100" id="category_id" name="category_id">
                            <option value=""><?php echo lang('all'); ?> <?php echo lang('category'); ?></option>
                            <?php foreach ($categories as $ctry) { ?>
                                <option value="<?php echo escape_output($ctry->id) ?>" <?php echo set_select('category_id', $ctry->id); ?>>
                                    <?php echo escape_output($ctry->category_name) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                    <div class="form-group">
                        <select tabindex="4" class="form-control select2 ir_w_100" id="product_type" name="product_type">
                            <option value=""><?php echo lang('select_product_type'); ?></option>
                            <option <?php echo set_select('product_type',1)?> value="1"><?php echo lang('Regular'); ?></option>
                            <option <?php echo set_select('product_type',2)?> value="2"><?php echo lang('Combo'); ?></option>
                            <option <?php echo set_select('product_type',3)?> value="3"><?php echo lang('Product'); ?></option>
                        </select>
                    </div>
                </div>
                <?php
                if(isLMni()):
                    ?>
                    <div class="mb-3 col-md-4 col-lg-2 col-sm-12">
                        <div class="form-group">
                            <select tabindex="5" class="form-control select2 ir_w_100" id="outlet_id" name="outlet_id">
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
                        <th><?php echo lang('code'); ?></th>
                        <th><?php echo lang('item_name'); ?></th>
                        <th><?php echo lang('category'); ?></th>
                        <th><?php echo lang('qty_of_sale'); ?></th>
                        <th><?php echo lang('cost'); ?></th>
                        <th><?php echo lang('cost_total_amount'); ?></th>
                        <th><?php echo lang('sale_price'); ?></th>
                        <th><?php echo lang('sale_total_amount'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $grand_total_qty = 0;
                    $grand_total_cost = 0;
                    $grand_total_sale = 0;
                    if (isset($itemWiseCostReport)):
                        foreach ($itemWiseCostReport as $key => $value) {
                            $key++;
                            $unit_cost = isset($value->unit_cost) && $value->unit_cost ? $value->unit_cost : 0;
                            $unit_sale_price = isset($value->unit_sale_price) && $value->unit_sale_price ? $value->unit_sale_price : 0;
                            $cost_total_amount = $unit_cost * $value->total_qty;
                            $sale_total_amount = $unit_sale_price * $value->total_qty;
                            $grand_total_qty += $value->total_qty;
                            $grand_total_cost += $cost_total_amount;
                            $grand_total_sale += $sale_total_amount;
                            ?>
                            <tr>
                                <td class="ir_txt_center"><?php echo escape_output($key); ?></td>
                                <td><?php echo escape_output($value->code) ?></td>
                                <td><?php echo escape_output($value->menu_name) ?></td>
                                <td><?php echo escape_output($value->category_name) ?></td>
                                <td><?php echo escape_output(getAmtP($value->total_qty)) ?></td>
                                <td><?php echo escape_output(getAmtP($unit_cost)) ?></td>
                                <td><?php echo escape_output(getAmtP($cost_total_amount)) ?></td>
                                <td><?php echo escape_output(getAmtP($unit_sale_price)) ?></td>
                                <td><?php echo escape_output(getAmtP($sale_total_amount)) ?></td>
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
                        <th class="ir_txt_center"></th>
                        <th class="pull-right"><?php echo lang('total'); ?></th>
                        <th><?php echo escape_output(getAmtP($grand_total_qty)) ?></th>
                        <th class="ir_txt_center"></th>
                        <th><?php echo escape_output(getAmtP($grand_total_cost)) ?></th>
                        <th class="ir_txt_center"></th>
                        <th><?php echo escape_output(getAmtP($grand_total_sale)) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.box-body -->
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

<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report1.js"></script>
