<!-- Main content -->
<section class="main-content-wrapper">
    <?php
    if ($this->session->flashdata('exception_er')) {

        echo '<section class="alert-wrapper"><div class="alert alert-danger alert-dismissible"> 
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <p><i class="icon fa fa-times"></i>';
        echo escape_output($this->session->flashdata('exception_er'));;unset($_SESSION['exception_er']);
        echo '</p></section>';
    }
    ?>
    <?php
        $is_display_1 = '';
        $is_display_2 = '';
        $is_display_3 = '';
        $is_display_4 = '';
        $role = $this->session->userdata('role');
        if($role!="Admin"){
            $segment_2 = $this->uri->segment(2);
            $segment_3 = $this->uri->segment(3);

            $controller = "73";
            $function = "pos_1";
            if(!checkAccess($controller,$function)){
                $is_display_1 = "none";
            }
            $controller = "104";
            $function = "view";
            if(!checkAccess($controller,$function)){
                $is_display_2 = "none";
            }
            $controller = "98";
            $function = "view";
            if(!checkAccess($controller,$function)){
                $is_display_3 = "none";
            }
            $controller = "1";
            $function = "view";
            if(!checkAccess($controller,$function)){
                $is_display_4 = "none";
            }

        }
    ?>
    <div class="row">
        <div class="col-md-3 mb-2" style="display: <?php echo escape_output($is_display_4)?>">
            <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>Dashboard/dashboard">
                <?php echo lang('dashboard'); ?>
            </a>
        </div>
        <div class="col-md-3 mb-2" style="display: <?php echo escape_output($is_display_1)?>">
            <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>POSChecker/posAndWaiterMiddleman">
                <?php echo lang('pos_screen'); ?>
            </a>
        </div>
        <div class="col-md-3 mb-2" style="display: <?php echo escape_output($is_display_2)?>">
            <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>Waiter/panel">
                <?php echo lang('waiter_screen'); ?>
            </a>
        </div>
        <div class="col-md-3 mb-2" style="display: <?php echo escape_output($is_display_3)?>">
            <a class="btn bg-blue-btn w-100" href="<?php echo base_url() ?>Kitchen/kitchens">
                <?php echo lang('kitchen_panel'); ?>
            </a>
        </div>

    </div>
    <!-- general form elements -->
    <div class="box-wrapper">

        <div class="table-box">
            <div class="row">

                <div class="col-sm-12 my-3 col-md-4">
                    <img src="<?php echo base_url(); ?>images/chef.png" alt=""
                        class="w-100" />
                </div>
                <div class="col-sm-12 my-2 col-md-8">
                    <h1 class="user_info_header"><?php echo escape_output($this->session->userdata('full_name')); ?></h1>
                    <div class="user_detail_container">
                        <?php
                        $outlet_name = escape_output($this->session->userdata('outlet_name'));
                        if ($this->session->userdata('role') != 'Admin' && $outlet_name) {
                            ?>
                        <p class="user_information">
                            &nbsp;<i class="fa fa-cutlery me-2"></i> &nbsp;&nbsp;<?php echo escape_output($this->session->userdata('outlet_name')); ?> <br />
                        </p>
                        <?php } ?>
                        <p class="user_information">
                            <i data-feather="user" class="me-2"></i> <?php echo escape_output($this->session->userdata('designation')); ?><br />
                        </p>
                        <p class="user_information">
                            <i data-feather="phone" class="me-2"></i> <?php echo escape_output($this->session->userdata('phone')); ?> <br />
                        </p>
                        <?php if ($this->session->userdata('email_address') != '') { ?>
                        <p class="user_information">
                            <i data-feather="mail" class="me-2"></i>
                            <?php echo escape_output($this->session->userdata('email_address')); ?>
                        </p>
                        <?php } ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>