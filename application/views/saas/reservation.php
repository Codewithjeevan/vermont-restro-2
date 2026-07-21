<?php
$wl = getWhiteLabel();
$site_name = '';
$footer = '';
if($wl){
    if($wl->site_name){
        $site_name = $wl->site_name;
    }
    if($wl->footer){
        $footer = $wl->footer;
    }
    if($wl->system_logo){
        $system_logo = base_url()."images/".$wl->system_logo;
    }
}
$company_id =  isset($_GET['c_id']) && $_GET['c_id']?$_GET['c_id']:1;
$company = getCompanyInfoById($company_id);
$outlet = getFirstOutletByCompany($company_id);
$social_links = isset($company->social_link_details) && $company->social_link_details?json_decode($company->social_link_details):'';
$customer_reviewers = isset($company->customer_reviewers) && $company->customer_reviewers?json_decode($company->customer_reviewers):'';
$counter_details = isset($company->counter_details) && $company->counter_details?json_decode($company->counter_details):'';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reservation || <?php echo escape_output($site_name)?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><!-- Required meta tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo escape_output($site_name)?>">
    <meta property="og:description" content="<?php echo escape_output($site_name)?>">
    <meta property="og:url" content="<?php echo (base_url())?>">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/lib/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="<?php echo base_url(); ?>assets/bower_components/font-awesome/css/font-awesome.min.css">
    <script src="<?php echo base_url()?>assets/landing/js/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="<?php echo base_url()?>assets/landing/img/favicon.ico">
    <!-- Select2 -->
    <script src="<?php echo base_url(); ?>assets/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- Select2 -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/reservation/css/custom.css">
    <!--datepicker-->
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/datepicker/datepicker.css">
    <!-- bootstrap datepicker -->
    <script src="<?php echo base_url(); ?>assets/bower_components/datepicker/bootstrap-datepicker.js"></script>
    <!-- Sweet alert -->
    <script src="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.css">

</head>
<input type="hidden" id="base_url" value="<?php echo base_url()?>">
<input type="hidden" id="exception_msg" value="<?php echo escape_output($this->session->flashdata('exception'))?>">
    <?php
        unset($_SESSION['exception']);
    ?>
<body>
<section>
    <section class="irp_banner img" data-src="<?php echo base_url()?>frequent_changing/reservation/img/banner_reservation.jpg" style="background: url(<?php echo base_url()?>frequent_changing/reservation/img/banner_reservation.jpg);">
        <div class="container irp_padding_27 color_container">
            <img src="<?php echo escape_output($system_logo)?>" alt="">
            <p>&nbsp;</p>
            <h4 class="text_decoration"><?php echo escape_output($company->business_name)?></h4>
            <p class="address"><i class="fa fa-map-marker"></i> <a class="text_decoration outlet_address" href="#"><?php echo escape_output($outlet->address)?></a></p>
            <ul class="contact_info">
                <li><a class="text_decoration outlet_phone" href="tel:<?php echo escape_output($outlet->phone)?>"><i class="fa fa-phone fa-flip-horizontal phone"></i> <?php echo escape_output($outlet->phone)?> </a></li>
                <li><a class="text_decoration outlet_email" href="mailto:<?php echo escape_output($outlet->email)?>"><i class="fa fa-envelope email"></i> <?php echo escape_output($outlet->email)?></a></li>
            </ul>
        </div>
    </section>


    <div class="container">
        <div class="reservation_title">
            <h4 class="section_heading"><span>Reservation</span></h4>
        </div>
        <div class="row">
            <div class="col-md-8">
                <form action="<?php echo base_url()?>authentication/add_reservation" method="post" autocomplete="off" class="defaultForm add_reservation">
                    <?php
                        $is_saas = 'none';
                    if(isServiceAccessOnly('sGmsJaFJE')):
                            $is_saas = '';
                        endif;

                        $is_mul_outlet = 'none';
                        if(str_rot13($company->language_manifesto)=="eriutoeri"){
                            $is_mul_outlet = '';
                        }
                    ?>
                    <!-- csrf token -->
                    <div class="reservation_form">
                        <div class="row">
                            <?php if(!$is_saas):?>
                            <div class="form-group col-md-6 pr-5">
                                <label for="">Company <span class="error">*</span></label>
                                <select name="company_id" id="company_id" class="form-control select2">
                                    <option value="">Select</option>
                                    <?php foreach ($companies as $value):?>
                                        <option value="<?php echo escape_output($value->id)?>"><?php echo escape_output($value->business_name)?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>
                                <div class="clearfix"></div>
                            <?php else:?>
                                <input type="hidden" id="company_id" name="company_id" value="<?php echo escape_output($company_id) ?>">
                            <?php endif?>

                            <?php if(!$is_mul_outlet):?>
                            <div class="form-group col-md-6 pl-5">
                                <label for="">Outlet <span class="error">*</span></label>
                                <select name="outlet_id" id="outlet_id" class="form-control select2">
                                    <option value="">Select</option>
                                </select>
                                <div class="clearfix"></div>
                            </div>
                            <?php else:?>
                                <input type="hidden" id="outlet_id" name="outlet_id" value="1">
                            <?php endif?>
                            <div class="form-group col-md-6 pr-5">
                                <label for="">Name <span class="error">*</span></label>
                                <input type="text" name="name" placeholder="Name" class="form-control required_checker">
                            </div>
                            <div class="form-group col-md-6 pl-5">
                                <label for="">Phone <span class="error">*</span></label>
                                <input type="text" name="phone" placeholder="Phone" class="form-control required_checker">
                            </div>


                            <div class="form-group col-md-6 pr-5">
                                <label for="">Email</label>
                                <input type="text" name="email" placeholder="Email" class="form-control">
                            </div>

                            <div class="form-group col-md-6 pl-5">
                                <label for="">Number of Guest <span class="error">*</span></label>
                                <input type="number" placeholder="Number of Guest" name="number_of_guest" class="form-control required_checker">
                            </div>

                            <div class="form-group col-md-6 pr-5">
                                <label for="">Reservation Date <span class="error">*</span></label>
                                <input type="text" name="reservation_date" id="reservation_date" placeholder="Reservation Date" class="form-control start_date_today"  readonly>
                            </div>
                            <div class="form-group col-md-6 pl-5">
                                <label for="">Reservation Type <span class="error">*</span></label>
                                <select name="reservation_type"  id="reservation_type" class="form-control select2">
                                    <option value="">Select</option>
                                    <option value="Regular Booking">Regular Booking</option>
                                    <option value="Dinner Booking">Dinner Booking</option>
                                    <option value="Birthday Dinner">Birthday Dinner</option>
                                    <option value="Birthday Party">Birthday Party</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>

                            <div class="form-group col-md-12">
                                <label for="">Description</label>
                                <textarea name="special_request" id="comments" placeholder="Write your special request here..." class="form-control" cols="5" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <button type="submit" class="btn btn-primary custom_btn">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="available_days">
                    <div class="appointment_schedule_area mt-10">
                        <p class="time_info"><i class="fa fa-info-circle"></i> Reservation availability times</p>
                        <ul class="available_time">
                            <?php
                            $reservation_times = json_decode($company->reservation_times);
                            ?>
                            <?php
                            foreach ($reservation_times as $value):
                                $txt_check = "fa fa-check";
                                if($value->status){
                                    $txt_check = "fa fa-times reservation_times ";
                                }
                                ?>
                                <li>
                                    <i class="<?php echo escape_output($txt_check)?> available_check" data-title="<?php echo escape_output(isset($value->counter_name) && $value->counter_name?$value->counter_name:'')?>"></i>
                                    <span><?php echo escape_output(isset($value->counter_name) && $value->counter_name?$value->counter_name:'')?>
                                        <b>(<?php echo isset($value->start_time) && $value->start_time?$value->start_time:'-'?> - <?php echo isset($value->end_time) && $value->end_time?$value->end_time:'-'?>)</b></span>
                                </li>
                            <?php endforeach;?>
                        </ul>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

<script src="<?php echo base_url()?>assets/landing/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="<?php echo base_url()?>frequent_changing/reservation/js/main_script.js"></script>
</body>
</html>