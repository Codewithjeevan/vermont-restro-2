<script src="<?php echo base_url(); ?>frequent_changing/js/select_2.js"></script>
 <section class="main-content-wrapper">
    <?php
    if ($this->session->flashdata('exception')) {

        echo '<section class="alert-wrapper">
        <div class="alert alert-success alert-dismissible fade show">
        <div class="alert-body">';
            echo '<p><i class="m-right fa fa-check"></i>'.escape_output($this->session->flashdata('exception')).'</p>';
        echo '
        </div>
        </div></section>
        ';
    }
    ?>
        <section class="content-header">
            <div class="col-sm-12">
                <h2 class="top-left-header"><?php echo lang('food_menus'); ?> </h2>
                <input type="hidden" class="datatable_name" data-filter="yes" data-title="<?php echo lang('food_menus'); ?>" data-id_name="datatable">
            </div>
        </section>
         <div class="box-wrapper">
            <div class="row my-3">
                        <div class="col-sm-12 col-md-3">

                        </div>
                        <div class="col-sm-12 col-md-3">
                            <a  data-access="upload_food_menu-234" class="btn bg-blue-btn menu_assign_class"  href="<?php echo base_url() ?>foodMenu/uploadFoodMenu">
                                <i class="m-right" data-feather="upload"></i> <?php echo lang('upload_food_menu'); ?>
                            </a>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <a  data-access="upload_food_menu_ingredients-234" class="btn bg-blue-btn menu_assign_class"  href="<?php echo base_url() ?>foodMenu/uploadFoodMenuIngredients">
                               <i class="m-right" data-feather="upload-cloud"></i> <?php echo lang('upload_food_menu_ingredients'); ?>
                            </a>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <a data-access="item_barcode-234" class="btn bg-blue-btn menu_assign_class" href="<?php echo base_url() ?>foodMenu/foodMenuBarcode">
                                <i class="m-right" data-feather="codesandbox"></i> <?php echo lang('food_menu_barcode'); ?>
                            </a>
                        </div>
            </div>

             <div class="table-box">
                 <!-- /.box-header -->
                 <div class="table-responsive">
                     <table id="datatable" class="table table-striped">
                         <thead>
                             <tr>
                                 <th class="ir_w_1"> <?php echo lang('sn'); ?></th>
                                 <th class="ir_w_10"><?php echo lang('product_type'); ?></th>
                                 <th class="ir_w_8"><?php echo lang('code'); ?></th>
                                 <th class="ir_w_25"><?php echo lang('name'); ?></th>
                                 <th class="ir_w_25"><?php echo lang('Variation'); ?>(<?php echo lang('code'); ?>)-<?php echo lang('sale_price'); ?>:<?php echo lang('dine'); ?>-<?php echo lang('take_away'); ?></th>
                                 <th class="ir_w_13"><?php echo lang('category'); ?></th>
                                 <th class="ir_w_13"><?php echo lang('sale_price'); ?>(Di-Ta)</th>
                                 <th class="ir_w_10"><?php echo lang('total_ingredients'); ?></th>
                                 <th class="ir_w_13"><?php echo lang('total'); ?> <?php echo lang('cost'); ?></th>
                                 <th class="ir_w_1 ir_txt_center not-export-col"><?php echo lang('actions'); ?></th>
                             </tr>
                         </thead>
                         <tbody>
                             <?php
                            if ($foodMenus && !empty($foodMenus)) {
                                $i = count($foodMenus);
                            }
                            foreach ($foodMenus as $value) {
                                ?>
                             <tr>
                                 <td class="ir_txt_center"><?php echo escape_output($i--); ?></td>
                                 <td><?php
                                     if($value->product_type==1){
                                         echo lang('Regular');
                                     }else if($value->product_type==2){
                                         echo lang('Combo');
                                     }else if($value->product_type==3){
                                         echo lang('Product');
                                     }
                                     ?>
                                 </td>
                                 <td><?php echo escape_output($value->code) ?></td>
                                 <td><?php echo escape_output($value->name) ?> <?php echo escape_output(getAlternativeNameById($value->id))?></td>
                                 <td class="arabic_text_left">
                                    <?php
                                        foreach ($value->variations as $key=>$val):?>
                                            <?php echo escape_output($val->name) ?>: <?php echo escape_output(getAmtPCustom($val->sale_price)) ?>-<?php echo escape_output(getAmtPCustom($val->sale_price_take_away)) ?>
                                            <?php
                                            if($key < (sizeof($value->variations) -1)){
                                                echo "<br>";
                                            }
                                            ?>

                                        <?php endforeach;?>
                                 </td>
                                 <td><?php echo escape_output(foodMenucategoryName($value->category_id)); ?></td>
                                 <td> <?php echo escape_output(getAmtPCustom($value->sale_price)) ?>-<?php echo escape_output(getAmtPCustom($value->sale_price_take_away)) ?></td>
                                 <td class="ir_txt_center"><?php echo escape_output(totalIngredients($value->id)); ?></td>
                                 <td><?php echo escape_output(getAmtPCustom($value->total_cost)) ?></td>
                                 <td class="ir_txt_center">
                                     <div class="btn-group  actionDropDownBtn">
                                         <button type="button" class="btn bg-blue-color dropdown-toggle"
                                             id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                             <i data-feather="more-vertical"></i>
                                         </button>
                                         <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton1" role="menu">
                                             <li data-access="view_details-234" class="menu_assign_class"><a
                                                     href="<?php echo base_url() ?>foodMenu/foodMenuDetails/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
                                                         class="fa fa-eye tiny-icon"></i><?php echo lang('view_details'); ?></a>
                                             </li>
                                             <li data-access="update-234" class="menu_assign_class"><a
                                                     href="<?php echo base_url() ?>foodMenu/addEditFoodMenu/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
                                                         class="fa fa-pencil tiny-icon"></i><?php echo lang('edit'); ?></a>
                                             </li>
                                             <li data-access="assign_modifier-234" class="menu_assign_class"><a
                                                     href="<?php echo base_url() ?>foodMenu/assignFoodMenuModifier/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
                                                         class="fa fa-plus tiny-icon"></i><?php echo lang('assign_modifier'); ?></a>
                                             </li>
                                             <li data-access="delete-234" class="menu_assign_class"><a class="delete"
                                                     href="<?php echo base_url() ?>foodMenu/deleteFoodMenu/<?php echo escape_output($this->custom->encrypt_decrypt($value->id, 'encrypt')); ?>"><i
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
 
         <div class="modal fade" id="filterModal" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"><?php echo lang('food_menus'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i data-feather="x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <?php echo form_open(base_url() . 'foodMenu/foodMenus') ?>
                            <select name="category_id" class="form-control w-100 select2">
                                <option value=""><?php echo lang('category'); ?></option>
                                <?php foreach ($foodMenuCategories as $ctry) { ?>
                                <option value="<?php echo escape_output($ctry->id) ?>" <?php echo set_select('category_id', $ctry->id); ?>>
                                    <?php echo escape_output($ctry->category_name) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="col-sm-12 col-md-6">
                            <button type="submit" name="submit" value="submit"
                                class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
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
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report.js"></script>