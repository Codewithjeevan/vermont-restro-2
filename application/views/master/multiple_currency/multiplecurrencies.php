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
            <div class="col-md-12">
                <h2 class="top-left-header"><?php echo lang('MultipleCurrencies'); ?> </h2>
                <input type="hidden" class="datatable_name" data-title="<?php echo lang('MultipleCurrencies'); ?>" data-id_name="datatable">
            </div>
            <div>

            </div>
        </div>
    </section>


    <div class="box-wrapper">
        <div class="table-box">

            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                    <tr>
                        <th class="ir_w_1"> <?php echo lang('sn'); ?></th>
                        <th  class="ir_w_15 "><?php echo lang('currency'); ?></th>
                        <th  class="ir_w_20 "><?php echo lang('conversion_rate'); ?></th>
                        <th  class="ir_w_1 ir_txt_center not-export-col"><?php echo lang('actions'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($MultipleCurrencies && !empty($MultipleCurrencies)) {
                        $i = count($MultipleCurrencies);
                    }
                    foreach ($MultipleCurrencies as $MultipleCurrency) {
                        ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo escape_output($i--); ?></td>
                            <td><?php echo escape_output($MultipleCurrency->currency); ?></td>
                            <td><?php echo escape_output($MultipleCurrency->conversion_rate); ?></td>
                            <td class="ir_txt_center">
                                <div class="btn-group  actionDropDownBtn">
                                    <button type="button" class="btn bg-blue-color dropdown-toggle"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i data-feather="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton1" role="menu">
                                        <li data-access="update-55" class="menu_assign_class"><a
                                                    href="<?php echo base_url() ?>MultipleCurrency/addEditMultipleCurrency/<?php echo escape_output($this->custom->encrypt_decrypt($MultipleCurrency->id, 'encrypt')); ?>"><i
                                                        class="fa fa-pencil tiny-icon"></i><?php echo lang('edit'); ?></a>
                                        </li>
                                        <li data-access="delete-55" class="menu_assign_class"><a class="delete"
                                               href="<?php echo base_url() ?>MultipleCurrency/deleteMultipleCurrency/<?php echo escape_output($this->custom->encrypt_decrypt($MultipleCurrency->id, 'encrypt')); ?>"><i
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

        </div>
    </div>
</section>
<script src="<?php echo base_url(); ?>frequent_changing/js/inventory.js"></script>
<!-- DataTables -->
<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.flash.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable_custom/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>

<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report.js"></script>