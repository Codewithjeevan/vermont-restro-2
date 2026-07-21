<?php
$base_color = '';
?>

<?php
if ($this->session->flashdata('exception')) {

    echo '<section class="content-header"><div class="alert alert-success alert-dismissible"> 
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <p><i class="icon fa fa-check"></i>';
    echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
    echo '</p></div></section>';
}
?>
<!-- jQuery 3 -->
<script src="<?php echo base_url(); ?>assets/bower_components/jquery/dist/jquery.min.js"></script>
<!-- Select2 -->
<script src="<?php echo base_url(); ?>assets/bower_components/select2/dist/js/select2.full.min.js"></script>
<!-- Select2 -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/select2/dist/css/select2.min.css">

<!-- Bootstrap 3.3.7 -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/bootstrap/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/userHome.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/common.css">
<!-- Sweet alert -->
<script src="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.css">
<!-- Favicon -->
<link rel="shortcut icon" href="<?php echo base_url(); ?>images/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/AdminLTE.css">
<title> <?php echo lang('SignUp'); ?></title>
<section class="content-header">
    <h1 class="text-center">
        <?php echo lang('SignUp'); ?>
    </h1>

</section>
<input type="hidden" id="saas_m_ch" value="<?=file_exists(APPPATH.'controllers/Service.php')?'yes':''?>">
<input type="hidden" id="ok" value="<?php echo lang('ok'); ?>">
<input type="hidden" id="warning" value="<?php echo lang('alert'); ?>">
<input type="hidden" id="front_r_1" value="<?php echo lang('front_r_1'); ?>">
<input type="hidden" id="front_r_2" value="<?php echo lang('front_r_2'); ?>">
<input type="hidden" id="front_r_3" value="<?php echo lang('front_r_3'); ?>">
<input type="hidden" id="front_r_4" value="<?php echo lang('front_r_4'); ?>">
<input type="hidden" id="front_r_5" value="<?php echo lang('front_r_5'); ?>">
<input type="hidden" id="front_r_6" value="<?php echo lang('front_r_6'); ?>">
<input type="hidden" id="front_r_7" value="<?php echo lang('front_r_7'); ?>">
<input type="hidden" id="front_r_8" value="<?php echo lang('front_r_8'); ?>">
<input type="hidden" id="front_r_9" value="<?php echo lang('front_r_9'); ?>">
<input type="hidden" id="front_r_10" value="<?php echo lang('front_r_10'); ?>">
<input type="hidden" id="front_r_11" value="<?php echo lang('front_r_11'); ?>">
<input type="hidden" id="base_url" value="<?php echo base_url(); ?>">
<input type="hidden" id="please_select_payment_method" class="please_select_payment_method" value="<?php echo lang('please_select_payment_method')?>">
<input type="hidden" id="please_select_payment_type" class="please_select_payment_type" value="<?php echo lang('please_select_payment_type')?>">
<input type="hidden" value="<?php echo escape_output($is_trail_plan)?>" id="is_trail_plan">

<!-- Main content -->
<section class="content">

    <?php
    $paymentSetting = paymentSetting();
    ?>
        <!-- left column -->
        <div class="col-md-8 col-md-offset-2">
                <!-- /.box-header -->
                <!-- form start -->
                <?php
                $attributes = array('id' => 'singup_company');
                $read_only = '';
                $display = '';
                if(isset($update_plan) && $update_plan){
                    $read_only = "readonly";
                    $display = "none";
                }
                echo form_open_multipart(base_url('#'),$attributes); ?>
            <input type="hidden" id="plan_id" value="<?php echo escape_output($plan_id)?>" name="plan_id">
            <input type="hidden" id="package_type" value="<?php echo escape_output($package_type)?>" name="package_type">
            <input type="hidden" id="total_amount_payment" value="<?php echo escape_output($total_payable_amount)?>" name="total_amount_payment">
            <input type="hidden" name="update_plan" id="update_plan" class="update_plan" value="<?php echo isset($update_plan) && $update_plan?$update_plan:''?>">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo lang('business_name'); ?> <span class="required_star">*</span></label>
                            <input tabindex="1" autocomplete="off" type="text" <?php echo escape_output($read_only) ?> id="business_name" name="business_name" class="form-control" placeholder="<?php echo lang('business_name'); ?>" value="">
                        </div>
                        <?php if (form_error('business_name')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('business_name'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">
                            <label><?php echo lang('phone'); ?>  <span class="required_star">*</span></label>
                            <input tabindex="2" autocomplete="off" type="text" <?php echo escape_output($read_only) ?>  id="phone" name="phone" class="form-control integerchk" placeholder="<?php echo lang('phone'); ?>" value="">
                        </div>
                        <?php if (form_error('phone')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('phone'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-md-4">

                        <div class="form-group">
                            <label><?php echo lang('address'); ?> <span class="required_star">*</span></label>
                            <textarea tabindex="3" id="address" name="address" <?php echo escape_output($read_only) ?>  class="form-control" placeholder="<?php echo lang('address'); ?>"></textarea>
                        </div>
                        <?php if (form_error('address')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('address'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-md-4" style="display: <?php echo escape_output($display)?>">
                        <div class="form-group">
                            <h4><?=lang('For_Admin_Access')?></h4>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-4" style="display: <?php echo escape_output($display)?>">
                        <div class="form-group">
                            <label><?php echo lang('admin_name'); ?>  <span class="required_star">*</span></label>
                            <input tabindex="4" autocomplete="off" type="text" id="admin_name" name="admin_name" class="form-control" placeholder="<?php echo lang('admin_name'); ?>" value="">
                        </div>
                        <?php if (form_error('admin_name')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('admin_name'); ?></p>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="col-md-4" style="display: <?php echo escape_output($display)?>">

                        <div class="form-group">
                            <label> <?php echo lang('email'); ?> <span class="required_star">*</span> (<?php echo lang('as_login'); ?>)</label>
                            <input tabindex="5" autocomplete="off" type="text" name="email" id="email" class="form-control" placeholder="<?php echo lang('email'); ?>" value="">
                        </div>
                        <?php if (form_error('email')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('email'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-4" style="display: <?php echo escape_output($display)?>">
                        <div class="form-group">
                            <label><?php echo lang('password'); ?> <span class="required_star">*</span></label>
                            <input tabindex="6" type="text" name="password" id="password" class="form-control"
                                   placeholder="<?php echo lang('password'); ?>"
                                   value="<?php echo set_value('password'); ?>">
                        </div>
                        <?php if (form_error('password')) { ?>
                            <div class="alert alert-error ir_p_5">
                                <span class="error_paragraph"><?php echo form_error('password'); ?></span>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="col-md-4" style="display: <?php echo escape_output($display)?>">

                        <div class="form-group">
                            <label><?php echo lang('confirm_password'); ?> <span
                                        class="required_star">*</span></label>
                            <input tabindex="7" type="text" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="<?php echo lang('confirm_password'); ?>"
                                   value="<?php echo set_value('confirm_password'); ?>">
                        </div>
                        <?php if (form_error('confirm_password')) { ?>
                            <div class="alert alert-error ir_p_5">
                                <span class="error_paragraph"><?php echo form_error('confirm_password'); ?></span>
                            </div>
                        <?php } ?>
                    </div>

                    <?php
                    if($is_trail_plan=="No"):
                    ?>
                        <div class="clearfix"></div>
                        <div class="col-md-4">
                            <div class="form-group">

                                <label> <?php echo lang('payment_type'); ?> <span class="required_star">*</span></label>
                                <select tabindex="8" class="form-control" name="payment_type" id="payment_type">
                                    <option value=""><?php echo lang('select'); ?></option>
                                    <option <?=set_select('payment_type','1')?> value="1">One Time</option>
                                    <option <?=set_select('payment_type','2')?> value="2">Recurring</option>
                                </select>
                            </div>
                            <?php if (form_error('payment_method')) { ?>
                                <div class="txt_35 alert alert-error">
                                    <p><?php echo form_error('payment_method'); ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> <?php echo lang('payment_method'); ?> <span class="required_star">*</span></label>
                            <select tabindex="8" class="form-control" name="payment_method" id="payment_method">
                                <option value=""><?php echo lang('select'); ?></option>
                                <?php if($paymentSetting->field_2==1):?>
                                    <option <?=set_select('payment_method','1')?> value="1">Paypal</option>
                                    <?php
                                endif;
                                ?>
                                <?php if($paymentSetting->field_3==1):?>
                                    <option id="stripe_hide" <?=set_select('payment_method','2')?> value="2">Stripe</option>
                                    <?php
                                endif;
                                ?>
                            </select>
                        </div>
                        <?php if (form_error('payment_method')) { ?>
                            <div class="txt_35 alert alert-error">
                                <p><?php echo form_error('payment_method'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                    endif;
                    ?>
                    <div class="clearfix"></div>
                    <div class="col-md-4 col-md-offset-4 text-center">
                        <div class="form-group">
                            <button type="button" name="submit" value="submit" class="btn btn-primary payment_now">
                                <?php echo lang('submit'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /.box-body -->

                <?php echo form_close(); ?>
            <div class="table-buy"><a class="btn btn-primary" href="<?php echo base_url()?>#pricing"><?php echo lang('back'); ?></a></div>
        </div>


    <!--Stripe payment form-->
    <form method="POST" action="<?php echo base_url()?>payment" id="stripe_form">
        <input type="hidden" value="yes" name="check_stripe" id="check_stripe">
        <input type="hidden" value="<?php echo isset($total_payable_amount) && $total_payable_amount?number_format($total_payable_amount,2):0?>" name="total_payable_str" id="total_payable_str">
        <input title="payment_company_id" name="payment_company_id" id="payment_company_id" class="payment_company_id" type="hidden" value="">
        <input title="item_description" name="item_description_str" id="item_description_str" type="hidden"
               value="<?php echo isset($pricingPlans->plan_name) && $pricingPlans->plan_name?$pricingPlans->plan_name:"Plan Name"?>">
    </form>

    <!--Paypal payment form-->
    <form method="POST" action="<?php echo base_url()?>payment" id="paypal_form">
        <input type="hidden" value="" name="tax_value" id="tax_value">
        <input type="hidden" value="" name="subtotal_value" id="subtotal_value">
        <input type="hidden" value="" name="discount_value" id="discount_value">
        <input type="hidden" value="<?php echo isset($total_payable_amount) && $total_payable_amount?number_format($total_payable_amount,2):0?>" name="item_price" id="total_payable">
        <input title="item_name" name="item_name" id="item_name" type="hidden" value="Monthly payment for <?php echo isset($pricingPlans->plan_name) && $pricingPlans->plan_name?$pricingPlans->plan_name:"Plan Name"?>">
        <input title="payment_company_id" name="payment_company_id" id="payment_company_id" class="payment_company_id" type="hidden" value="">
        <input title="item_number" name="item_number" type="hidden" value="0" id="item_number">
        <input title="item_description" name="item_description" type="hidden" value="Monthly payment for <?php echo isset($pricingPlans->plan_name) && $pricingPlans->plan_name?$pricingPlans->plan_name:"Plan Name"?>">
    </form>

    <!-- Buy button -->
    <form action="<?php echo escape_output($paymentSetting->url_paypal); ?>"  id="paypal_recurring_form" method="post">
        <!-- Identify your business so that you can collect the payments -->
        <input type="hidden" name="business" value="<?php echo escape_output($paymentSetting->paypal_business_email); ?>">
        <!-- Specify a subscriptions button. -->
        <input type="hidden" name="cmd" value="_xclick-subscriptions">
        <!-- Specify details about the subscription that buyers will purchase -->
        <input type="hidden" name="item_name" value="Monthly payment for <?php echo isset($pricingPlans->plan_name) && $pricingPlans->plan_name?$pricingPlans->plan_name:"Plan Name"?>">
        <input type="hidden" name="item_number" value="--">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="a3" id="paypalAmt" value="<?php echo isset($total_payable_amount) && $total_payable_amount?number_format($total_payable_amount,2):0?>" name="item_price" id="total_payable">
        <input type="hidden" name="p3" id="paypalValid" value="1">
        <input type="hidden" name="t3" value="M">
        <!-- Custom variable user ID -->
        <input title="payment_company_id" name="payment_company_id" id="payment_company_id" class="payment_company_id" type="hidden" value="">
        <!-- Specify urls -->
        <input type="hidden" name="cancel_return" value = "<?php echo base_url()?>paymentStatus?msg=payment_failed">
        <input type="hidden" id="update_success_url" name="return" value="">
        <input type="hidden" name="notify_url" value="<?php echo base_url(); ?>ipn_paypal">
    </form>

</section>
<script type="text/javascript" src="<?php echo base_url('frequent_changing/js/online_payment_front.js'); ?>"></script>
<script src="<?php echo base_url(); ?>assets/POS/js/media.js"></script>