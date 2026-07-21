<script type="text/javascript" src="<?php echo base_url('frequent_changing/js/self_order.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/self_order.css">

<!-- Main content -->
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
    if ($this->session->flashdata('exception_1')) {

        echo '<section class="alert-wrapper"><div class="alert alert-danger alert-dismissible"> 
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <div class="alert-body"><p><i class="m-right fa fa-check"></i>';
        echo escape_output($this->session->flashdata('exception_1'));unset($_SESSION['exception_1']);
        echo '</p></div></div></section>';
    }
    ?>

    <section class="content-header">
        <h3 class="top-left-header">
            <?php echo lang('sos_Self_Order_Setting'); ?>
        </h3>

    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <?php echo form_open(base_url() . 'setting/selfOrder/'.(isset($company) && $company->id?$company->id:''), $arrayName = array('id' => 'update_tax_setting','enctype'=>'multipart/form-data')) ?>
            <div>
                <div class="row">
                    <div class="mb-3 col-sm-12 col-md-4 col-lg-3">
                        <div class="form-group">
                            <label> <?php echo lang('sos_enable_self_order'); ?>  <div class="tooltip_custom">
                                    <i data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo lang('sos_tooltip'); ?>" data-feather="help-circle"></i>
                                </div></label>
                            <select tabindex="12" class="form-control select2" name="sos_enable_self_order"
                                    id="sos_enable_self_order">

                                <option <?php echo set_select('sos_enable_self_order',"No")?>
                                    <?= isset($company) && $company->sos_enable_self_order == "No" ? 'selected' : '' ?>
                                    value="No"><?php echo lang('no'); ?></option>
                                <option <?php echo set_select('sos_enable_self_order',"Yes")?>
                                    <?= isset($company) && $company->sos_enable_self_order == "Yes" ? 'selected' : '' ?>
                                    value="Yes"><?php echo lang('yes'); ?></option>
                            </select>
                        </div>
                        <?php if (form_error('sos_enable_self_order')) { ?>
                            <div class="callout callout-danger my-2">
                                <?php echo form_error('sos_enable_self_order'); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>

            <div class="row py-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <?php if(isset($company) && $company->sos_enable_self_order == "Yes"):?>
                <div class="col-sm-12 col-md-2 mb-2">

                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>setting/selfOrderQrcode">
                        <?php echo lang('generateQRCode'); ?>
                    </a>
                </div>
                <?php endif?>

            <?php echo form_close(); ?>
        </div>
    </div>

</section>
