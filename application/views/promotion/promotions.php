 <section class="main-content-wrapper">
        <?php
        if ($this->session->flashdata('exception')) {

            echo '<section class="alert-wrapper"><div class="alert alert-success alert-dismissible fade show"> 
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-body"><p><i class="m-right fa fa-check"></i>';
            echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
            echo '</p></div></div></section>';
        }
        ?>

     <?php
            if ($this->session->flashdata('exception_err')) {

                echo '<section class="alert-wrapper"><div class="alert alert-danger alert-dismissible fade show"> 
                <button type="button" class="btn-close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <div class="alert-body"><p><i class="m-right fa fa-times"></i>';
                echo escape_output($this->session->flashdata('exception_err'));unset($_SESSION['exception_err']);
                echo '</p></div></div></section>';
            }
            ?>


            <section class="content-header">
                <div class="row">
                    <div class="col-sm-12 col-md-8">
                        <h2 class="top-left-header"><?php echo lang('promotions'); ?> </h2>
                        <input type="hidden" class="datatable_name" data-title="<?php echo lang('promotions'); ?>" data-id_name="datatable">
                    </div>
                    <div class="col-sm-12 col-md-4">

                    </div>
                </div>
            </section>

            <div class="box-wrapper">
                <div class="table-box">
                    <!-- /.box-header -->
                    <div class="table-responsive">
                        <table id="datatable" class="table">
                            <thead>
                                <tr>
                                    <th class="ir_w_1"> <?php echo lang('sn'); ?></th>
                                    <th class="ir_w_11"><?php echo lang('title'); ?></th>
                                    <th class="ir_w_11"><?php echo lang('type'); ?></th>
                                    <th class="ir_w_8"><?php echo lang('start_date'); ?></th>
                                    <th class="ir_w_8"><?php echo lang('end_date'); ?></th>
                                    <th><?php echo lang('food_menu'); ?></th>
                                    <th class="ir_w_9"><?php echo lang('discount'); ?></th>
                                    <th class="ir_w_9"><?php echo lang('status'); ?></th>
                                    <th class="ir_w_12"><?php echo lang('added_by'); ?></th>
                                    <th class="ir_w_6 not-export-col"><?php echo lang('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($promotions && !empty($promotions)) {
                                    $i = count($promotions);
                                }
                                foreach ($promotions as $wsts) {
                                    ?>
                                <tr>
                                    <td class="ir_txt_center"><?php echo escape_output($i--); ?></td>
                                    <td><?php echo escape_output($wsts->title) ?></td>
                                    <td><?php echo escape_output($wsts->type==1?'Discount':'Free Item') ?></td>
                                    <td><?php echo escape_output(date($this->session->userdata('date_format'), strtotime($wsts->start_date))); ?></td>
                                    <td><?php echo escape_output(date($this->session->userdata('date_format'), strtotime($wsts->end_date))); ?></td>
                                    <td>
                                                <?php if($wsts->type==1):
                                                    echo getFoodMenuNameById($wsts->food_menu_id)."(".getFoodMenuCodeById($wsts->food_menu_id).")";
                                                else:
                                                    echo "<b>Buy: </b>".getFoodMenuNameById($wsts->food_menu_id)."(".getFoodMenuCodeById($wsts->food_menu_id).") - ".$wsts->qty."(qty)";
                                                    echo "<br><b>Get: </b>".getFoodMenuNameById($wsts->get_food_menu_id)."(".getFoodMenuCodeById($wsts->get_food_menu_id).") - ".$wsts->get_qty."(qty)";
                                                    endif;?>
                                    </td>
                                    <?php if($wsts->type==1):?>
                                    <td><?php echo escape_output(getDiscountSymbol($wsts->discount)).(isset($wsts->discount) && $wsts->discount?$wsts->discount:0) ?></td>
                                        <?php else:?>
                                        <td>-</td>
                                        <?php endif;?>
                                    <td><?php echo escape_output($wsts->status==1?lang('Active'):lang('Inactive')) ?></td>
                                    <td><?php echo escape_output(userName($wsts->user_id)); ?></td>
                                    <td class="ir_txt_center">
                                        <div class="btn-group actionDropDownBtn">
                                            <button type="button" class="btn bg-blue-color dropdown-toggle"
                                                id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i data-feather="more-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton1" role="menu">
                                                <li data-access="update-118" class="menu_assign_class"><a
                                                            href="<?php echo base_url() ?>Promotion/addEditPromotion/<?php echo escape_output($this->custom->encrypt_decrypt($wsts->id, 'encrypt')); ?>"><i
                                                                class="fa fa-pencil tiny-icon"></i><?php echo lang('edit'); ?></a>
                                                </li>
                                                <li data-access="delete-118" class="menu_assign_class"><a class="delete"
                                                        href="<?php echo base_url() ?>Promotion/deletePromotion/<?php echo escape_output($this->custom->encrypt_decrypt($wsts->id, 'encrypt')); ?>"><i
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

  <!-- DataTables -->
<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.colVis.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report.js"></script>