<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header">
            <?php echo lang('edit_denomination'); ?>
        </h3>
    </section>

    
        <div class="box-wrapper">
            
            <div class="table-box">
                
                <?php echo form_open(base_url('denomination/addEditDenomination/' . $encrypted_id)); ?>
                <div>
                    <div class="row">
                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('amount'); ?> <span class="required_star">*</span></label>
                                <input tabindex="1" type="text" name="amount" class="form-control"
                                    placeholder="<?php echo lang('amount'); ?>"
                                    value="<?php echo escape_output($denomination->amount) ?>">
                            </div>
                            <?php if (form_error('amount')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('amount'); ?>
                            </div>
                            <?php } ?>

                        </div>

                        <div class="col-sm-12 mb-2 col-md-4">

                            <div class="form-group">
                                <label><?php echo lang('description'); ?></label>
                                <input tabindex="2" type="text" name="description" class="form-control"
                                       placeholder="<?php echo lang('description'); ?>"
                                       value="<?php echo escape_output($denomination->description) ?>">
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
                        <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>denomination/denominations">
                            <?php echo lang('back'); ?></a>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
        
</section>