
<!DOCTYPE html>
<?php
$currency = "$";
//default base color
$base_color = "#6ab04c";
$wl = getWhiteLabel();
$system_logo = '';
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
//get company information
$getCompanyInfo = getCompanyInfo();
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo lang('customer_panel'); ?></title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- jQuery 3 -->
        <script src="<?php echo base_url(); ?>assets/bower_components/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap 3.3.7 -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/bootstrap/dist/css/bootstrap.min.css">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/font-awesome/css/font-awesome.min.css">
        <!-- Ionicons -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/Ionicons/css/ionicons.min.css">
        <!-- Theme style -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/AdminLTE.min.css">
        <!-- iCheck -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/plugins/iCheck/square/blue.css">
        <!-- Sweet alert -->
        <script src="<?php echo base_url(); ?>assets/bower_components/sweetalert2/dist/sweetalert.min.js"></script>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/sweetalert2/dist/sweetalert.min.css">
        <!-- custom login page css -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/login.css">
        <!-- Favicon -->

        <link rel="icon" href="<?php echo base_url(); ?>images/favicon.ico" type="image/x-icon">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="<?php echo base_url(); ?>assets/plugins/local/html5shiv.min.js"></script>
        <script src="<?php echo base_url(); ?>assets/plugins/local/respond.min.js"></script>
        <![endif]-->

        <!-- Google Font -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/plugins/local/google_font.css">
    </head>
    <body>
        <input type="hidden" value="<?=base_url()?>" id="base_url">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h2 class="text-center"><?php echo lang('customer_panel'); ?> <a href="javascript:void(0)" id="fullscreen" class="icon pull-right"><i
                                class="fa fa-arrows-alt"></i></a></h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th><?php echo lang('item'); ?></th>
                        <th class="text_right"><?php echo lang('price'); ?></th>
                        <th class="text_center"><?php echo lang('quantity'); ?></th>
                        <th class="text_center"><?php echo lang('discount'); ?></th>
                        <th class="text_right"><?php echo lang('sub_total'); ?></th>
                    </tr>
                    </thead>
                    <tbody id="items">

                    </tbody>
                </table>

                <table class="text_001">
                    <thead>

                    </thead>
                    <tbody>
                        <tr>
                            <th><?php echo lang('total_item'); ?></th>
                            <th class="text_002"><span id="total_item">0</span>(<span id="total_item_1">0</span>)</th>
                        </tr>
                        <tr>
                            <th><?php echo lang('sub_total'); ?></th>
                            <th class="text_002"><span id="total_sub_total">0.00</span></th>
                        </tr>
                        <tr>
                            <th><?php echo lang('discount'); ?>(<?php echo lang('in_global'); ?>)</th>
                            <th class="text_002"><span id="total_discount">0.00</span></th>
                        </tr>
                        <tr>
                            <th><?php echo lang('vat'); ?></th>
                            <th class="text_002"><span id="total_tax">0.00</span></th>
                        </tr>
                        <tr>
                            <th><?php echo lang('charge'); ?></th>
                            <th class="text_002"><span id="total_charge">0.00</th>
                        </tr>
                        <tr>
                            <th><?php echo lang('tips'); ?></th>
                            <th class="text_002"><span id="total_tips">0.00</th>
                        </tr>
                    </tbody>
                </table>
                <h1 class="total__payment"><b><?php echo lang('total_payable'); ?>: <span id="total_payable">0.00</span></b></h1>
            </div>
        </div>

        <!-- Bootstrap 3.3.7 -->
        <script src="<?php echo base_url(); ?>assets/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <!-- SlimScroll -->
        <script src="<?php echo base_url(); ?>assets/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
        <!-- FastClick -->
        <script src="<?php echo base_url(); ?>assets/bower_components/fastclick/lib/fastclick.js"></script>
        <!-- AdminLTE App -->
        <script src="<?php echo base_url(); ?>assets/dist/js/adminlte.min.js"></script>
        <!-- AdminLTE for demo purposes -->
        <script src="<?php echo base_url(); ?>assets/dist/js/demo.js"></script>
        <!-- custom login page js -->
        <script src="<?php echo base_url(); ?>frequent_changing/js/customer_panel.js"></script>
    </body> 
</html>
