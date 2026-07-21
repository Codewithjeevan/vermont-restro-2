
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
            <div class="col-sm-12">
                <h2 class="top-left-header"><?php echo lang('counters'); ?> </h2>
                <input type="hidden" class="datatable_name" data-title="<?php echo lang('counters'); ?>" data-id_name="datatable">
            </div>
            <div>

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
                            <th class="w-5"> <?php echo lang('sn'); ?></th>
                            <?php if(isLMni()):?>         
                                <th class="w-15"><?php echo lang('outlet'); ?></th>
                             <?php endif;?>
                            <th class="w-20"><?php echo lang('counter_name'); ?></th>
                            <th class="w-15"><?php echo lang('invoice_printer'); ?></th>
                            <th class="w-15"><?php echo lang('bill_printer'); ?></th>
                            <th class="w-20"><?php echo lang('description'); ?></th>
                            <th class="w-10"><?php echo lang('added_by'); ?></th> 
                            <th class="w-5 text-center"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($counters && !empty($counters)) {
                            $i = count($counters);
                        }
                        foreach ($counters as $value) {
                            ?>
                        <tr>
                            
                            <td class="text-center"><?php echo escape_output($i--); ?></td>
                            <?php if(isLMni()):?>     
                                <td><?php echo escape_output($value->outlet_name) ?></td>      
                             <?php endif;?>
                             <td><?php echo escape_output($value->counter_name) ?></td>
                            <td><?php echo escape_output($value->invoice_printer) ?></td>
                            <td><?php echo escape_output($value->bill_printer) ?></td>
                            <td><?php echo escape_output($value->description) ?></td>
                            <td><?php echo escape_output($value->added_by) ?></td> 
                            <td class="text-center">
                            <div class="btn-group  actionDropDownBtn">
                                        <button type="button" class="btn bg-blue-color dropdown-toggle"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i data-feather="more-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton1" role="menu">
                                            <li data-access="update-275" class="menu_assign_class"><a
                                                    href="<?php echo base_url() ?>Counter/addEditCounter/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
                                                        class="fa fa-pencil tiny-icon"></i><?php echo lang('edit'); ?></a>
                                            </li>
                                            <li data-access="delete-275" class="menu_assign_class"><a class="delete"
                                                    href="<?php echo base_url() ?>Counter/deleteCounter/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
                                                        class="fa fa-trash tiny-icon"></i><?php echo lang('delete'); ?></a>
                                            </li>
                                        </ul>
                                    </div>

                            </td>
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