<section class="main-content-wrapper">
    <?php
    if ($this->session->flashdata('exception')) {

        echo '<section class="alert-wrapper">
            <div class="alert alert-success alert-dismissible fade show"> 
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-body">
            <p class="m-0"><i class="icon fa fa-check"></i>';
        echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
        echo '</p></div></div></section>';
    }
    ?>

    <section class="content-header">
        <div class="row">
            <div class="col-sm-12 col-md-8">
                <h3 class="top-left-header"><?php echo lang('roles'); ?> </h3>
                <input type="hidden" class="datatable_name" data-title="<?php echo lang('roles'); ?>" data-id_name="datatable">
            </div>
            <div class="col-sm-12 col-md-4">

            </div>
        </div>
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <!-- /.box-header -->
            <div class="table-responsive">
                <table id="datatable" class="table table-responsive">
                    <thead>
                    <tr>
                        <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                        <th class="width_23_p" ><?php echo lang('role_name'); ?></th>
                        <th class="ir_w5_txt_center not-export-col"><?php echo lang('actions'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($roles && !empty($roles)) {
                        $i = count($roles);
                    }
                    foreach ($roles as $usrs) {
                        ?>
                        <tr>
                            <td class="c_center"><?php echo escape_output($i--); ?></td>
                            <td><?php echo escape_output($usrs->role_name); ?></td>
                            <td class="ir_txt_center">
                                <div class="btn-group  actionDropDownBtn">
                                    <button type="button" class="btn bg-blue-color dropdown-toggle"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i data-feather="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton1" role="menu">
                                        <li> <a href="<?php echo base_url() ?>Role/addEditRole/<?php echo escape_output($this->custom->encrypt_decrypt($usrs->id, 'encrypt')); ?>/copy" ><i class="fa fa-copy"></i><?php echo lang('copy'); ?></a></li>
                                        <li> <a href="<?php echo base_url() ?>Role/addEditRole/<?php echo escape_output($this->custom->encrypt_decrypt($usrs->id, 'encrypt')); ?>/" ><i class="fa fa-edit"></i><?php echo lang('edit'); ?></a></li>
                                        <li><a  class="delete"  href="<?php echo base_url() ?>Role/deleteRole/<?php echo escape_output($this->custom->encrypt_decrypt($usrs->id, 'encrypt')); ?>" ><i class="fa fa-trash tiny-icon"></i><?php echo lang('delete'); ?></a> </li>
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
<script src="<?php echo base_url(); ?>frequent_changing/js/inventory.js"></script>

<!-- DataTables -->
<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.colVis.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>



<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report.js"></script>

