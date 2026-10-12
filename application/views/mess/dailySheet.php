<?php
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));
$today = date('Y-m-d');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'sheet')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_daily_sheet'); ?></h3>
        </div>
        <div class="mess-head-actions">
            <a class="mess-btn primary" target="_blank" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $date; ?>/<?php echo (int) $slot; ?>?print=1"><i class="fa fa-print"></i> <?php echo lang('print'); ?></a>
            <?php if (checkAccess(395, 'view')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $date; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-check-square-o"></i> <?php echo lang('mess_meal_register'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-daybar">
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $prev; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-left"></i></a>
        <form method="get" action="<?php echo base_url(); ?>Mess_daily/sheet" class="d-inline-flex">
            <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo escape_output($date); ?>" style="width:130px" onchange="this.form.submit()">
            <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
        </form>
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $next; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-right"></i></a>
        <?php if ($date != $today): ?><a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $today; ?>/<?php echo (int) $slot; ?>"><?php echo lang('mess_today'); ?></a><?php endif; ?>
        <div class="mess-slot-tabs">
            <?php foreach ($slots as $s): ?>
                <a class="<?php echo $s->slot == $slot ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $date; ?>/<?php echo (int) $s->slot; ?>"><?php echo escape_output($s->name); ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mess-card">
        <?php $this->load->view('mess/_sheetBody'); ?>
    </div>
</section>
