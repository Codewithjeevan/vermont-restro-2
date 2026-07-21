<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header">
            <?php echo lang('add_table'); ?>
        </h3>
    </section>

    
        <div class="box-wrapper">
            <!-- general form elements -->
            <?php echo form_open(base_url('table/addEditTable')); ?>
            <div class="table-box">
                <!-- form start -->

                <div>
                    <div class="row">
                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('area'); ?> <span class="required_star">*</span></label>
                                <select class="form-control select2" name="area">
                                 <option value=""><?php echo lang('select'); ?></option>
                                 <?php foreach ($areas as $value):?>
                                      <option <?php echo set_select('area',$value->id)?> value="<?php echo escape_output($value->id)?>"><?php echo escape_output($value->area_name)?></option>
                                 <?php endforeach;?>
                             </select>
                            </div>
                            <?php if (form_error('area')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('area'); ?>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('table_name'); ?> <span class="required_star">*</span></label>
                                <input tabindex="1" type="text" name="name" class="form-control"
                                    placeholder="<?php echo lang('table_name'); ?>"
                                    value="<?php echo set_value('name'); ?>">
                            </div>
                            <?php if (form_error('name')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('name'); ?>
                            </div>
                            <?php } ?>
                        </div>
                         
                        <div class="col-sm-12 mb-2 col-md-4">
                            <div class="form-group">
                                <label><?php echo lang('seat_capacity'); ?> <span class="required_star">*</span> <div class="tooltip_custom">
                                            <i data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo lang('table_type_tooltip'); ?>" data-feather="help-circle"></i>
                                        </div></label>
                                <select class="form-control select2" name="sit_capacity">
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==1?'selected':''?> value="1">1</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==2?'selected':''?> value="2">2</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==3?'selected':''?> value="3">3</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==4?'selected':''?> value="4">4</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==6?'selected':''?> value="6">6</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==8?'selected':''?> value="8">8</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==10?'selected':''?> value="10">10</option>
                                    <option <?php echo isset($table_information->sit_capacity) && $table_information->sit_capacity==12?'selected':''?> value="12">12</option>
                                </select>
                            </div>
                            <?php if (form_error('sit_capacity')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('sit_capacity'); ?>
                            </div>
                            <?php } ?>

                        </div>
                         
                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('description'); ?></label>
                                <input tabindex="2" type="text" name="description" class="form-control"
                                       placeholder="<?php echo lang('description'); ?>"
                                       value="<?php echo set_value('description'); ?>">
                            </div>
                            <?php if (form_error('description')) { ?>
                                <div class="callout callout-danger my-2">
                                    <?php echo form_error('description'); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    </div>

            </div>

                <div class="row mt-2">
                    <div class="col-sm-12 col-md-2 mb-2">
                        <button type="submit" name="submit" value="submit"
                        class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                    </div>
                    <div class="col-sm-12 col-md-2 mb-2">
                        <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>table/tables">
                            <?php echo lang('back'); ?></a>
                    </div>
                </div>
                <?php echo form_close(); ?>
         </div>


        
</section>