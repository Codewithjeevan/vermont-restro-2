
<section class="main-content-wrapper">

    <?php
    if ($this->session->flashdata('exception')) {

        echo '<section class="alert-wrapper">
        <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="alert-body">
        <p><i class="m-right fa fa-check"></i>';
        echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
        echo '</p></div></div></section>';
    }
    ?>


    <section class="content-header">
        <div class="row">
            <div class="col-sm-12 col-md-8">
                <h2 class="top-left-header"><?php echo lang('running_order'); ?>
                    <small class="ir_color_gray">(<?php echo count($running_orders); ?>)</small>
                </h2>
                <input type="hidden" class="datatable_name" data-title="<?php echo lang('running_order'); ?>" data-id_name="datatable">
            </div>
            <div class="col-sm-12 col-md-4 text-end">
                <a href="<?php echo base_url() ?>Sale/runningOrders" class="btn_list m-right btn bg-blue-btn"><i class="fa fa-sync-alt"></i> <?php echo lang('refresh'); ?></a>
            </div>
        </div>
    </section>



    <div class="box-wrapper">
        <!-- general form elements -->
        <div class="table-box">
            <!-- /.box-header -->
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="w-5 text-center"><?php echo lang('sn'); ?></th>
                            <th class="w-10"><?php echo lang('sale_no'); ?></th>
                            <th class="w-10"><?php echo lang('order_type'); ?></th>
                            <th class="w-10"><?php echo lang('table'); ?></th>
                            <th class="w-10"><?php echo lang('customer'); ?></th>
                            <th class="w-10"><?php echo lang('waiter'); ?></th>
                            <th class="w-20"><?php echo lang('item_list'); ?></th>
                            <th class="w-10"><?php echo lang('total_payable'); ?></th>
                            <?php if($show_outlet): ?>
                                <th class="w-10"><?php echo lang('outlet'); ?></th>
                            <?php endif; ?>
                            <th class="w-10"><?php echo lang('added_by'); ?></th>
                            <th class="w-10"><?php echo lang('order_time'); ?></th>
                            <th class="w-10"><?php echo lang('last_updated'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //shared DataTable init sorts column 0 desc, so count down: newest update ends up on top
                        $i = count($running_orders);
                        foreach ($running_orders as $value) {
                            ?>
                        <tr>
                            <td class="text-center"><?php echo escape_output($i--); ?></td>
                            <td><strong><?php echo escape_output($value->sale_no); ?></strong></td>
                            <td><?php echo escape_output($value->order_type_text); ?></td>
                            <td><?php echo escape_output($value->table_text); ?></td>
                            <td><?php echo escape_output($value->customer_name); ?></td>
                            <td><?php echo escape_output($value->waiter_name); ?></td>
                            <td>
                                <?php if(!empty($value->items)): ?>
                                    <ul class="running_order_item_list">
                                        <?php foreach ($value->items as $item): ?>
                                            <li>
                                                <span class="ro_qty"><?php echo escape_output($item->qty); ?> &times;</span>
                                                <?php echo escape_output($item->name); ?>
                                                <?php if($item->modifiers != ''): ?>
                                                    <small class="ir_color_gray">(<?php echo escape_output($item->modifiers); ?>)</small>
                                                <?php endif; ?>
                                                <?php if($item->note != ''): ?>
                                                    <br><small class="ir_color_gray"><i class="fa fa-sticky-note"></i> <?php echo escape_output($item->note); ?></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo getAmtCustom($value->total_payable); ?></td>
                            <?php if($show_outlet): ?>
                                <td><?php echo escape_output($value->outlet_name); ?></td>
                            <?php endif; ?>
                            <td><?php echo escape_output($value->user_name); ?></td>
                            <td><?php echo escape_output($value->order_time); ?></td>
                            <td><?php echo escape_output($value->updated_at); ?></td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
</section>
<style>
    .running_order_item_list { list-style: none; margin: 0; padding: 0; }
    .running_order_item_list li { padding: 2px 0; border-bottom: 1px dashed #e5e5e5; white-space: nowrap; }
    .running_order_item_list li:last-child { border-bottom: 0; }
    .running_order_item_list .ro_qty { display: inline-block; min-width: 32px; font-weight: 600; }
</style>
<!-- DataTables -->
<script src="<?php echo base_url(); ?>frequent_changing/js/inventory.js"></script>
<!-- DataTables -->
<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.flash.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>

<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report.js"></script>
