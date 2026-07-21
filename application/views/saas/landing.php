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
$company = getMainCompany();

$social_links = isset($company->social_link_details) && $company->social_link_details?json_decode($company->social_link_details):'';
$customer_reviewers = isset($company->customer_reviewers) && $company->customer_reviewers?json_decode($company->customer_reviewers):'';
$counter_details = isset($company->counter_details) && $company->counter_details?json_decode($company->counter_details):'';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic -->
    <title><?php echo escape_output($site_name)?></title>

    <script src="<?php echo base_url()?>assets/landing/js/jquery-3.6.0.min.js"></script>

    <!-- Sweet alert -->
    <script src="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/POS/sweetalert2/dist/sweetalert.min.css">
    <link rel="icon" href="<?php echo base_url()?>assets/landing/img/favicon.ico">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/lib/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/lib/owl/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/lib/popup/popup.css">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/scss/style.css">
    <link rel="stylesheet" href="<?php echo base_url()?>assets/landing/css/inline_custom.css">
</head>
<body>
<input type="hidden" id="base_url_ajax" value="<?php echo base_url()?>">
<input type="hidden" name="hidden_alert" id="hidden_alert" class="hidden_alert"
       value="<?php echo lang('alert'); ?>!">
<input type="hidden" name="hidden_ok" id="hidden_ok" class="hidden_ok" value="<?php echo lang('ok'); ?>">
<input type="hidden" name="package_type" id="package_type" class="package_type" value="1">
<input type="hidden" name="update_plan" id="update_plan" class="update_plan" value="<?php echo escape_output($update_plan)?>">
<section id="banner" data-bs-spy="scroll" data-bs-target="#banner" data-bs-offset="0">
    <img class="main-bg-layer" src="<?php echo base_url()?>assets/landing/img/bg_layer.png" alt="">
    <div class="container">
        <header id="main-navbar">
            <nav>
                <a href="<?php echo base_url() ?>">
                    <!-- Dynamic -->
                    <?php
                        if(isset($system_logo) && $system_logo):
                    ?>
                    <img src="<?php echo escape_output($system_logo)?>" alt="">
                    <?php
                    else:
                    ?>

                    <?php
                        endif;
                    ?>
                </a>
                <a href="#" class="menu-trigger">
                    <span></span><span></span><span></span>
                </a>

                <ul class="menu-list">
                    <li class="menu-item"><a href="#banner">Home</a></li>
                    <li class="menu-item"><a href="#valueable-feature">Features</a></li>
                    <li class="menu-item"><a href="#review">Customer Review</a></li>
                    <li class="menu-item"><a href="#pricing">Pricing</a></li>
                    <li class="menu-item"><a href="#faq">Faq</a></li>
                    <li class="menu-item"><a href="#gallery">Gallery</a></li>
                    <li  class="menu-item"><a href="<?php echo base_url()?>reservation">Reservation</a></li>
                    <li class="menu-item"><a href="<?php echo base_url()?>authentication">Signin</a></li>
                    <li class="menu-item"><a href="#contact">Contact Us</a></li>
                </ul>
            </nav>
        </header>
        <!-- End Header -->

        <div class="row banner-content">
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="left-content">
                    <h2 class="title">Best restaurant </h2>
                    <h2 class="title">management</h2>
                    <h2 class="title">solution.</h2>
                    <p class="small-desc">Recipe Management, Stock Auto Deduct by Recipe on Sale, Powerful POS, Kitchen/Bar/Waiter Panel, CRM, SMS, Veg & Bar Item Filter</p>
                    <a href="<?php echo base_url()?>authentication" class="btn-read">Signin</a>
                </div>
            </div>
            <div class="col-md-12 col-lg-6 position-relative">
                <img class="laptop-bg-layer" src="<?php echo base_url()?>assets/landing/img/laptop-bg-layer.png" alt="">
                <div class="right-content position-relative">
                    <!-- <div class="tab-shape">
                        <img src="<?php echo base_url()?>assets/landing/img/laptop-muckup.png" alt="">
                    </div> -->
                    <img class="w-100" src="<?php echo base_url()?>assets/landing/img/large-screen-optimised.png" alt="">
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Banner Section -->
<div class="overlay"></div>
<aside id="sidebar">
    <ul class="menu-list">
        <li class="menu-item"><a href="#banner">Home</a></li>
        <li class="menu-item"><a href="#valueable-feature">Features</a></li>
        <li class="menu-item"><a href="#review">Customer Review</a></li>
        <li class="menu-item"><a href="#pricing">Pricing</a></li>
        <li class="menu-item"><a href="#faq">Faq</a></li>
        <li class="menu-item"><a href="#gallery">Gallery</a></li>
        <li  class="menu-item"><a href="<?php echo base_url()?>reservation">Reservation</a></li>
        <li class="menu-item"><a href="#contact">Contact Us</a></li>
    </ul>
</aside>
<!-- End Sidebar -->

<section id="valueable-feature" data-bs-spy="scroll" data-bs-target="#main-navbar,#page-list" data-bs-offset="0">
    <div class="container m-bottom">
        <h2 class="main-title">Valueable feature</h2>
        <p class="small-desc">
            Optimize your restaurant operation, speed up and increase profitability using our restaurant solution.
        </p>
    </div>

    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="left-item ">
                    <div class="recipe-box">
                        <h4 class="title">How Recipe Works</h4>
                        <div class="b">
                            <!-- <h5 class="font-weight-bold my-3">Food Name</h5> -->
                            <ol class="l-line-height">
                                <li class="font-weight-bold">
                                    <span class="mx-4">Ingredient 1 </span>
                                    <span>200g</span>
                                </li>
                                <li class="font-weight-bold">
                                    <span class="mx-4">Ingredient 2 </span>
                                    <span>60g</span>
                                </li>
                                <li class="font-weight-bold">
                                    <span class="mx-4">Ingredient 3 </span>
                                    <span>100g</span>
                                </li>
                                <li class="font-weight-bold">
                                    <span class="mx-4">Ingredient 4 </span>
                                    <span>120g</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Recipe and Ingredient Stock</h3>
                    <p class="small-desc">
                        Our software has a very innovative Recipe Management System for your food menu, where you can assign which ingredients will be needed for your food menu as well as that's amount or quantity. And when you make a sale those ingredients will be auto deducted from stock.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item">
                        <img class="img-fluid img-shadow" src="<?php echo base_url()?>assets/landing/img/running-order2.png" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">Innovative Running Order Panel</h3>
                        <p class="small-desc">
                            You know the customers in a restaurant do not act same as in a retail shop. Like they come, take some stuffs and go, they usually sit order, eat, and ask for some more food after completing eating, even they can cancel an item after the order. So to match this practical scenario we built the Running Order feature in POS so when customers order and sit the order goes to Running Order list and then when they order for new item, you can add that to that order, if they cancel an item, you can do that, even if they change table, you can do that too. And finally you will print the receipt when they leave. In the mean time you can print a bill for them too, to show how much they need to pay.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item d-flex justify-content-center">
                    <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/point-of-sale.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Powerful Point-Of-Sale</h3>
                    <p class="small-desc">
                        We have a very powerful point of sale where you will find Running Order feature as mentioned before. And more features such as Searching and Filtering: Search by Item Name or Code, Filter Beverage Item or Vegetarian Item easily, filter by category etc. Moreover you will get notification when an item is ready cooking in kitchen. You can make quick invoice for faster serve, you can see how many items are ready in the kitchen and how many are remaining when the customer asks, you can print bill, KOT and BOT. You can add new items when customer asks while eating. Other than these you are getting all other common features like draft sales, print last invoice, see latest invoice list etc. You can also run the POS in full screen mode as well.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item">
                        <img class="img-fluid" src="<?php echo base_url()?>assets/landing/img/waste-tracking.png" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">Waste Tracking </h3>
                        <p class="small-desc">
                            It is common in a restaurant that sometime the ingredients get perished or wasted, even foods too. But those have value in money right? So our system helps you to track those waste and helps you to calculate the loss for those wastes as well.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item d-flex justify-content-center">
                    <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img//waiter-panel.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Kitchen, Bar, Waiter Panel </h3>
                    <p class="small-desc">
                        In case your chef is a smart guy to operate a tablet, we have a kitchen panel feature where the chef can see all items to prepare when an order is placed. He can also change status of an item that it is being cooked. So that cashier can tell the customer that his item is under cooking. Then the chef can mark item as done. And cashier will get notified in his POS screen. Even the waiter will also get a notification when an item will be done so that he can collect the item and serve. Not even the Kitchen Panel only, we have Bar Panel too, so that when you place an order all kitchen item will go to kitchen panel and all bar item will go to bar panel automatically.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item d-flex justify-content-center">
                        <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/tax.svg" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">GST, VAT, IVAT, HST Support</h3>
                        <p class="small-desc">
                            Our tax configuration is another innovation too. In this setup you can configure any of your tax types. You can setup all of your applicable taxes with their percentages. And then when you will add an item all tax fields with their values will be populated but if there is any change in percentage of tax in any item you can change those easily, and that will be applicable in invoice.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item">
                    <img class="w-100" src="<?php echo base_url()?>assets/landing/img//time-attendance.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Attendance Tracking</h3>
                    <p class="small-desc">
                        Not a big thing, it is common that you will need to track your employee's attendances. You can do that in our system.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item">
                        <img class="w-100" src="<?php echo base_url()?>assets/landing/img/CRM-facility.png" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">CRM Facility</h3>
                        <p class="small-desc">
                            In our system you can collect Date of Birth, Date of Anniversary etc of a customer and as there is SMS gateway is integrated within the system so you can send a wish to their Birthday or Anniversary, or you may be able to make special offer for their special day. If you can do this, for sure they will keep your restaurant in their mind whenever they want to have a dinner.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item">
                    <img class="w-100" src="<?php echo base_url()?>assets/landing/img/home-protection.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Multiple User and Access Control</h3>
                    <p class="small-desc">
                        You can have multiple users and control their access to the system as this is a common feature of any system. 
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item d-flex justify-content-center">
                        <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/optimize operation.svg" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">Optimize Operation</h3>
                        <p class="small-desc">
                            When you place an order, that will directly been popped up in the kitchen panel, when the item is ready your POS will show notification, your waiter will also get notified. You can even generate a kitchen performance report to know that how much time they are taking to make food. And also when a customer asks that what is the status of his food or how long his food will take to come to the table, you can simply check that from the POS screen. In these ways you can optimizes lot of interactions between your staffs that may reduce unnecessary waste of time. And overseeing kitchen performance report you will have more control to improve kitchen performance.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->

    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item d-flex justify-content-center align-items-center">
                    <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/item modifier.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Text and Item Modifier</h3>
                    <p class="small-desc">
                        When ordering a food people usually makes two types of notes, some additional small items like toppings e.g.: additional salad or sauce those may include additional price too. Our system supports this facility. Even those additional toppings may have ingredient consumptions too those needs to be deducted from stock when sale, no worry our system gives that facility.
                        And another is customer may provides additional instruction to prepare food like: less sugar, more pepper etc. In our system you can do that.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item d-flex justify-content-center">
                        <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/pay.png" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">Pre and Post Payment Support</h3>
                        <p class="small-desc">
                            Some restaurant takes payment after eating and some restaurants take payment when ordering, our system supports both cases means both types of restaurants.
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->

    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item">
                    <img class="w-100" src="<?php echo base_url()?>assets/landing/img/due-tracking.png" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Supplier and Customer Due Tracking</h3>
                    <p class="small-desc">
                       You may purchase from supplier with due and pay later. You can do that; even you can check that how much you have to pay for each supplier. You can also do that for customers too.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->
    <div class="flexinble bg-light-grey">
        <div class="container">
            <div class="row content-wrapper flex-md-row-reverse">
                <div class="col-md-12 col-lg-6 my-auto">
                    <div class="left-item">
                        <img class="img-fluid" src="<?php echo base_url()?>assets/landing/img/expense.png" alt="">
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="right-item txt-left">
                        <h3 class="title">Expense Tracking</h3>
                        <p class="small-desc">
                            Your restaurants may have many types of expenses than purchasing ingredients; you can track those by category. 
                        </p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->

    <div class="container">
        <div class="row my-5 content-wrapper">
            <div class="col-md-12 col-lg-6">
                <div class="left-item d-flex justify-content-center">
                    <img class="img-w-300" src="<?php echo base_url()?>assets/landing/img/analytics.svg" alt="">
                </div>
            </div>
            <div class="col-md-12 col-lg-6 my-auto">
                <div class="right-item txt-right">
                    <h3 class="title">Reports, Analytics & Dashboard</h3>
                    <p class="small-desc">
                        Our system provides lot of reports, analytics and Business Intelligence Dashboard that will help you to oversee the business and take decision quickly.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Features Section -->


</section>
<!-- End Features Section -->

<section id="review" data-bs-spy="scroll" data-bs-target="#main-navbar,#page-list" data-bs-offset="0">
    <div class="container position-relative">
        <div class="row">
            <div class="my-4 col-sm-12 col-lg-6">
                <div class="left-content">
                    <div class="review-info">
                        <h2 class="title">Customer Review</h2>
                        <p class="small-description">See how our customers from many region is happyly using our software and sends their valuable feedback.</p>
                    </div>

                    <div class="owl-carousel owl-theme review-slider">
                        <!-- Dynamic -->
                        <?php
                        if(isset($customer_reviewers) && $customer_reviewers):
                            foreach ($customer_reviewers as $key=>$spns) {
                                $current_row = json_decode($spns);
                                ?>
                                <div class="item">
                                    <img src="<?php echo base_url() ?>assets/landing/img/review-circle.png" alt=""
                                         class="circle1">
                                    <img src="<?php echo base_url() ?>assets/landing/img/review-circle.png" alt=""
                                         class="circle2">
                                    <h4 class="customer-name"><?php echo escape_output($current_row->name) ?></h4>
                                    <p class="customer-role"><?php echo escape_output($current_row->designation) ?></p>
                                    <p class="small-description">
                                        "<?php echo escape_output($current_row->description) ?>"
                                    </p>
                                    <ul>
                                        <li><i data-feather="star" class="full"></i></li>
                                        <li><i data-feather="star" class="full"></i></li>
                                        <li><i data-feather="star" class="full"></i></li>
                                        <li><i data-feather="star" class="full"></i></li>
                                        <li><i data-feather="star" class="half"></i></li>
                                    </ul>
                                </div>
                                <?php
                            }
                        endif;
                        ?>
                        <!-- End Single Review Slider -->
                    </div>

                    <img class="dots" src="<?php echo base_url()?>assets/landing/img/dots.png" alt="">
                </div>
            </div>
            <!-- End Left Content -->
            <div class="col-sm-12 col-lg-6 d-flex align-items-center">
                <div class="right-contnent w-100">
                    <img class="w-100 h-auto" src="<?php echo base_url()?>assets/landing/img/review-img.png" alt="">
                </div>
            </div>
            <!-- End Right Content -->
        </div>
    </div>
</section>

<section id="two-layer" >
    <img class="bg-layer" src="<?php echo base_url()?>assets/landing/img/object.png" alt="">
    <div class="digit-box">
        <div class="container">
            <div class="content position-relative">
                <div class="row">
                    <!-- End Single Item -->
                    <!-- Dynamic -->
                    <div class="item my-4 col-sm-12 col-md-6 col-lg-3">
                        <div class="icon">
                            <img src="<?php echo base_url()?>assets/landing/img/icons/restaurants.svg" alt="">
                        </div>
                        <h2 class="total-digit"><?php echo isset($counter_details->restaurants) && $counter_details->restaurants?escape_output(number_format($counter_details->restaurants,0)):'0'; ?></h2>
                        <p class="title">Restaurants</p>
                    </div>
                    <!-- End Single Item -->
                    <div class="item my-4 col-sm-12 col-md-6 col-lg-3">
                        <div class="icon">
                            <img src="<?php echo base_url()?>assets/landing/img/icons/user.svg" alt="">
                        </div>
                        <h2 class="total-digit"><?php echo isset($counter_details->users) && $counter_details->users?escape_output(number_format($counter_details->users,0)):'0'; ?></h2>
                        <p class="title">Users</p>
                    </div>
                    <!-- End Single Item -->
                    <div class="item my-4 col-sm-12 col-md-6 col-lg-3">
                        <div class="icon">
                            <img src="<?php echo base_url()?>assets/landing/img/icons/reference.svg" alt="">
                        </div>
                        <h2 class="total-digit"><?php echo isset($counter_details->reference) && $counter_details->reference?escape_output(number_format($counter_details->reference,0)):'0'; ?></h2>
                        <p class="title">Reference</p>
                    </div>
                    <div class="item my-4 col-sm-12 col-md-6 col-lg-3">
                        <div class="icon">
                            <img src="<?php echo base_url()?>assets/landing/img/icons/transactions.svg" alt="">
                        </div>
                        <h2 class="total-digit"><?php echo isset($counter_details->daily_transactions) && $counter_details->daily_transactions?escape_output(number_format($counter_details->daily_transactions,2)):'0'; ?>+</h2>
                        <p class="title">Daily Transactions</p>
                    </div>
                    <!-- End Single Item -->
                </div>
            </div>
        </div>
    </div>
    <!-- End Digit Count -->

    <div id="pricing" class="pricing-plan" data-bs-spy="scroll" data-bs-target="#main-navbar" data-bs-offset="0">
        <div class="container">
            <h2 class="title">Best Pricing Plans</h2>
            <div class="pricing-plan-wrapper">
                <div class="row">
                    <div class="col-sm-12 col-md-4">

                    </div>
                    <div class="col-sm-12 col-md-4 div_btn_middle">
                        <a href="#" class="payment_type div_active" type="1">Pay Monthly</a>
                        <a href="#" class="payment_type" type="2">Pay Yearly</a>
                    </div>
                    <div class="col-sm-12 col-md-4">

                    </div>
                </div>

                <div class="row">
                    <!-- Dynamic -->
                    <?php
                        $getPricingPlan  = getPricingPlan();
                        if(isset($getPricingPlan) && $getPricingPlan):
                            foreach ($getPricingPlan as $value):


                    ?>
                    <div class="b my-4 col-sm-12 col-md-4 <?php echo isset($value->is_recommended) && $value->is_recommended=="Yes"?"highlight-plan":''?>">
                        <div class="plan">
                            <header>
                                <h3 class="title"><?php echo escape_output($value->plan_name)?></h3>
                            </header>
                            <ul>
                                <li class="show_div_1">$<?php echo !$value->monthly_cost?'0':escape_output($value->monthly_cost)?><sup>/
                                    <?php
                                        echo lang('month');
                                    ?></li>
                                <li style="display: none" class="show_div_2"><?php echo !$value->price_for_month2?'0':'<del>$'.(($value->monthly_cost*12) - ($value->price_for_month2*12)).'</del> $'.escape_output($value->price_for_month2)?><sup>/
                                    <?php
                                    echo lang('month');
                                    ?>
                                    <br>
                                        <p class="yearly_pay">Pay $<?php echo escape_output($value->price_for_month2*12)?> / Year</p>
                                </li>

                                <li><?php echo escape_output($value->number_of_maximum_users)?> <span><?php echo lang('users')?></li>
                                <li><?php echo escape_output($value->number_of_maximum_outlets)?> <span><?php echo lang('outlets')?></li>
                                <li><?php echo escape_output($value->number_of_maximum_invoices)?> <span><?php echo lang('invoices')?></li>
                                <li>  <?php
                                    if($value->trail_days==111):
                                        echo  "30 Days";
                                        else:
                                    echo escape_output($value->trail_days)." Days Trial";
                                        endif;

                                    ?></li>
                            </ul>
                            <?php
                                $update_plan = isset($update_plan) && $update_plan?"/?update_plan=".$update_plan:'';
                            ?>
                            <a class="plan-btn set_url" data-id="<?php echo escape_output($value->id)?>" href="<?php base_url()?>plan/<?php echo escape_output($value->id)."/1".$update_plan?>">get Started</a>
                        </div>
                    </div>
                        <?php
                            endforeach;
                        endif;
                        ?>

                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Tow Layer -->

<section id="faq" data-bs-spy="scroll" data-bs-target="#main-navbar,#page-list" data-bs-offset="0">
    <div class="container">
        <img class="faq-left" src="<?php echo base_url()?>assets/landing/img/faq-left.png" alt="">
        <img class="faq-right" src="<?php echo base_url()?>assets/landing/img/faq-right.png" alt="">
        <h2 class="title">FAQ</h2>
        <div class="content">
            <div class="row">
                <div class="mb-3 col-md-12 col-lg-6">
                    <div class="accordion" id="accordionLeft">
                        <div class="accordion accordion-flush" id="accordionFlushExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                        What is <?php echo escape_output($site_name)?>?
                                    </button>
                                </h2>
                                <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <?php echo escape_output($site_name)?> is a complete solution for a restaurant where you will get Recipe Management, Stock Auto Deduct by Recipe on Sale, Powerful POS, Kitchen/Bar/Waiter Panel, CRM, SMS, Veg & Bar Item Filter.
                                    </div>
                                </div>
                            </div>
                            <!-- End SIngle Accordion -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                        Why use <?php echo escape_output($site_name)?> software?
                                    </button>
                                </h2>
                                <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Please check our valuable feature list, you will get a clear idea about our software. Moreover you can signup and play with this software for some trial days to get more clear understanding.
                                    </div>
                                </div>
                            </div>
                            <!-- End SIngle Accordion -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                        Do I need to have a credit card to pay?
                                    </button>
                                </h2>
                                <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        Yes you do have for uninterrupted operation. But we also take cash payment too.
                                    </div>
                                </div>
                            </div>
                            <!-- End SIngle Accordion -->
                        </div>
                    </div>
                </div>
                <!-- End Single Item -->
                <div class="col-md-12 col-lg-6">
                    <div class="accordion" id="accordionRight">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOneOne" aria-expanded="false" aria-controls="flush-collapseOneOne">
                                    How about support?
                                </button>
                            </h2>
                            <div id="flush-collapseOneOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    You will get ASAP support for any issue you face. Please submit your problem using our contact form whenever you face any issue.
                                </div>
                            </div>
                        </div>
                        <!-- End SIngle Accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwoTwo" aria-expanded="false" aria-controls="flush-collapseTwoTwo">
                                    How much I have to pay to use this software?
                                </button>
                            </h2>
                            <div id="flush-collapseTwoTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    Please check our pricing plan that shows monthly payment amount as well as limitation of users, outlets, invoices etc. Please choose any plan that best suits you.
                                </div>
                            </div>
                        </div>
                        <!-- End SIngle Accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThreeThree" aria-expanded="false" aria-controls="flush-collapseThreeThree">
                                    How about data protection?
                                </button>
                            </h2>
                            <div id="flush-collapseThreeThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    Our system is 99% secured from any type of security vulnerability. It is strongly protected from any kind of web attack like: XSS, SQL Injection, CSRF, Session Fixation, Session Hijacking, Insecure File Upload, Insecure Data Transfer. So you may rely on our system with full trust.
                                </div>
                            </div>
                        </div>
                        <!-- End SIngle Accordion -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End FAQ Item -->

<div id="gallery" data-bs-spy="scroll" data-bs-target="#main-navbar,#page-list" data-bs-offset="0">
    <h2 class="title">Gallery</h2>
    <div class="owl-carousel owl-theme gallery-slider">
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_1.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_1.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_2.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_2.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_3.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_3.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_4.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_4.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_5.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_5.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_6.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_6.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_7.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_7.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_8.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_8.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_9.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_9.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_10.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_10.png">
                <i data-feather="image"></i>
            </a>
        </div>
        <div class="item popup-gallery">
            <img src="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_11.png" alt="">
            <a class="image-popup-no-margins" href="<?php echo base_url()?>assets/landing/img/SaasSC/Screenshot_11.png">
                <i data-feather="image"></i>
            </a>
        </div>
    </div>
</div>
<!-- End Gallery Item -->

<div id="contact" data-bs-spy="scroll" data-bs-target="#main-navbar,#page-list" data-bs-offset="0">
    <div class="container">
        <h2 class="title">Contact Us</h2>
        <form class="row" id="contact_us_form" action="#">
            <div class="mb-4 col-md-12 col-lg-6">
                <input type="text" id="name" name="name" class="form-control required_check" placeholder="Name">
            </div>
            <div class="mb-4 col-md-12 col-lg-6">
                <input type="email" name="email" id="email" class="form-control required_check" placeholder="Email">
            </div>
            <div class="mb-4 col-md-12 col-lg-6">
                <input type="text" id="phone" name="phone" class="form-control required_check" placeholder="Phone Number">
            </div>
            <div class="mb-4 col-md-12 col-lg-6">
                <input type="text" id="subject" name="subject" class="form-control required_check" placeholder="Subject">
            </div>
            <div class="mb-4 col-md-12">
                <textarea name="message" id="message" class="form-control required_check" placeholder="Write Message"></textarea>
            </div>
            <div class="col-md-12 text-center">
                <button class="submit-btn send_mail">Send Message</button>
            </div>
        </form>
    </div>
</div>
<!-- End Contact Us  -->

<footer id="footer-info">
    <div class="container">
        <!-- Dynamic -->
        <?php
        if(isset($system_logo) && $system_logo):
            ?>
            <img class="logo" src="<?php echo escape_output($system_logo)?>" alt="">
            <?php
        else:
            ?>

            <?php
        endif;
        ?>
        <p class="small-desc">
            Recipe Management, Stock Auto Deduct by Recipe on Sale, Powerful POS, Kitchen/Bar/Waiter Panel, CRM, SMS, Veg & Bar Item Filter
        </p>
        <div class="divider"></div>

        <ul id="page-list" class="page-list">
            <li><a href="#banner">Home</a></li>
            <li><a href="#valueable-feature">Features</a></li>
            <li><a href="#review">Customer Review</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#gallery">Gallery</a></li>
            <li><a href="<?php echo base_url()?>reservation">Reservation</a></li>
            <li><a href="<?php echo base_url()?>authentication">Signin</a></li>
            <li><a href="#contact">Contact Us</a></li>
        </ul>

        <div class="copyright-txt">
            <p>Copyright @<?php echo date("Y",strtotime('today'))?>. All rights reserved</p>
            <ul>
                <!-- Dynamic -->
                <?php
                    if(isset($social_links) && $social_links->facebook):
                ?>
                        <li><a href="<?php echo escape_output($social_links->facebook)?>" target="_blank"><i data-feather="facebook"></i></a></li>
                <?php
                    else:
                ?>
                        <li><a href="#"><i data-feather="facebook"></i></a></li>
                <?php
                endif;
                ?>

                <?php
                if(isset($social_links) && $social_links->twitter):
                    ?>
                    <li><a href="<?php echo escape_output($social_links->twitter)?>" target="_blank"><i data-feather="twitter"></i></a></li>
                    <?php
                else:
                    ?>
                    <li><a href="#"><i data-feather="twitter"></i></a></li>
                    <?php
                endif;
                ?>

                <?php
                if(isset($social_links) && $social_links->instagram):
                    ?>
                    <li><a href="<?php echo escape_output($social_links->instagram)?>" target="_blank"><i data-feather="instagram"></i></a></li>
                    <?php
                else:
                    ?>
                    <li><a href="#"><i data-feather="instagram"></i></a></li>
                    <?php
                endif;
                ?>

                <?php
                if(isset($social_links) && $social_links->youtube):
                    ?>
                    <li><a href="<?php echo escape_output($social_links->youtube)?>" target="_blank"><i data-feather="youtube"></i></a></li>
                    <?php
                else:
                    ?>
                    <li><a href="#"><i data-feather="youtube"></i></a></li>
                    <?php
                endif;
                ?>
            </ul>
        </div>
    </div>
</footer>

<a href="#banner" class="totop">
    <i data-feather="arrow-up"></i>
</a>

<script src="<?php echo base_url()?>assets/landing/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="<?php echo base_url()?>assets/landing/lib/owl/owl.carousel.min.js"></script>
<script src="<?php echo base_url()?>assets/landing/lib/popup/popup.min.js"></script>
<script src="<?php echo base_url()?>assets/landing/lib/fatherIcon/feather.min.js"></script>
<script src="<?php echo base_url()?>assets/landing/js/main.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/contact_us.js"></script>
</body>
</html>