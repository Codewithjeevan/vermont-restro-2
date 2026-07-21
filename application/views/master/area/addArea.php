<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header">
            <?php echo lang('add_area'); ?>
        </h3>
    </section>

    
        <div class="box-wrapper">
            <!-- general form elements -->
            <div class="table-box">
                <!-- form start -->
                <?php echo form_open(base_url('area/addEditArea')); ?>
                <div>
                    <div class="row">
                        <?php
                        if(isLMni()):
                            ?>
                            <div class="col-sm-12 mb-2 col-md-4">

                                <div class="form-group">
                                    <label><?php echo lang('outlet'); ?> <span class="required_star">*</span></label>
                                    <select class="form-control select2" name="outlet">
                                        <?php
                                        foreach ($outlets as $outlet):
                                            ?>
                                            <option value="<?php echo escape_output($outlet->id)?>"><?php echo escape_output($outlet->outlet_name)?></option>
                                            <?php
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                                <?php if (form_error('outlet')) { ?>
                                    <div class="callout callout-danger my-2">
                                        <?php echo form_error('outlet'); ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php
                        endif;
                        ?>
                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('area_name'); ?> <span class="required_star">*</span></label>
                                <input tabindex="1" type="text" name="area_name" class="form-control"
                                    placeholder="<?php echo lang('area_name'); ?>"
                                    value="<?php echo set_value('area_name'); ?>">
                            </div>
                            <?php if (form_error('area_name')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('area_name'); ?>
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

                <div class="row mt-2">
                    <div class="col-sm-12 col-md-2 mb-2">
                        <button type="submit" name="submit" value="submit"
                        class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                    </div>
                    <div class="col-sm-12 col-md-2 mb-2">
                        <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>area/areas">
                            <?php echo lang('back'); ?></a>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
</section>