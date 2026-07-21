<section class="main-content-wrapper">

    <section class="content-header">
        <h3 class="top-left-header">
            <?php echo isset($MultipleCurrencies) ?lang('edit') :lang('add') ?> <?php echo lang('MultipleCurrency'); ?>
        </h3>
    </section>
    <div class="box-wrapper">
        <!-- general form elements -->
        <div class="table-box">
            <!-- form start -->
            <?php echo form_open(base_url('MultipleCurrency/addEditMultipleCurrency/' . (isset($MultipleCurrencies) ? $this->custom->encrypt_decrypt($MultipleCurrencies->id, 'encrypt') : ''))); ?>
            <div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo lang('currency'); ?> <span class="required_star">*</span></label>
                            <input tabindex="1" autocomplete="off" type="text" name="currency" class="form-control" placeholder="<?php echo lang('currency'); ?>" value="<?php echo isset($MultipleCurrencies) && $MultipleCurrencies ? $MultipleCurrencies->currency : set_value('currency') ?>">
                        </div>
                        <?php if (form_error('currency')) { ?>
                            <div class="alert alert-error txt-uh-21">
                                <p><?php echo form_error('currency'); ?></p>
                            </div>
                        <?php } ?>

                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo lang('conversion_rate'); ?> <span class="required_star">*</span></label>
                            <input tabindex="1" autocomplete="off" type="text" name="conversion_rate" class="form-control" placeholder="<?php echo lang('conversion_rate'); ?>" value="<?php echo isset($MultipleCurrencies) && $MultipleCurrencies ? $MultipleCurrencies->conversion_rate : set_value('conversion_rate') ?>">
                        </div>
                        <?php if (form_error('conversion_rate')) { ?>
                            <div class="alert alert-error txt-uh-21">
                                <p><?php echo form_error('conversion_rate'); ?></p>
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
                    <a  class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>MultipleCurrency/MultipleCurrencies">
                        <?php echo lang('back'); ?>
                    </a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>


</section>