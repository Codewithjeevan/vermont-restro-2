<?php
$date_format = $this->session->userdata('date_format');
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));
$today = date('Y-m-d');
$slot_name = isset($slots[$slot]) ? $slots[$slot]->name : '';
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'register')); ?>
    <input type="hidden" id="mess_reg_date" value="<?php echo escape_output($date); ?>">
    <input type="hidden" id="mess_reg_slot" value="<?php echo (int) $slot; ?>">

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_meal_register'); ?> &middot; <?php echo escape_output($slot_name); ?></h3>
            <div class="mess-sub"><?php echo escape_output(date('l', strtotime($date)) . ' ' . date($date_format, strtotime($date))); ?></div>
        </div>
        <div class="mess-head-actions">
            <?php if (checkAccess(398, 'add')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/addExtra?date=<?php echo $date; ?>&slot=<?php echo (int) $slot; ?>&back=register"><i class="fa fa-cutlery"></i> <?php echo lang('mess_add_extra'); ?></a>
            <?php endif; ?>
            <?php if (checkAccess(402, 'view')): ?>
                <a class="mess-btn" target="_blank" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $date; ?>/<?php echo (int) $slot; ?>?print=1"><i class="fa fa-print"></i> <?php echo lang('mess_print_tick_sheet'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-daybar">
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $prev; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-left"></i></a>
        <form method="get" action="<?php echo base_url(); ?>Mess_daily/register" class="d-inline-flex gap-2" id="mess_reg_date_form">
            <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo escape_output($date); ?>" style="width:130px" onchange="this.form.submit()">
            <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
        </form>
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $next; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-right"></i></a>
        <?php if ($date != $today): ?>
            <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $today; ?>/<?php echo (int) $slot; ?>"><?php echo lang('mess_today'); ?></a>
        <?php endif; ?>
        <div class="mess-slot-tabs">
            <?php foreach ($slots as $s): ?>
                <a class="<?php echo $s->slot == $slot ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $date; ?>/<?php echo (int) $s->slot; ?>"><?php echo escape_output($s->name); ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mess-tiles" id="mess_reg_counts">
        <div class="mess-tile"><div class="num" data-count="expected"><?php echo (int) $counts->expected; ?></div><div class="lbl"><?php echo lang('mess_expected'); ?></div><div class="hint"><span data-count="members"><?php echo (int) $counts->members; ?></span> <?php echo lang('mess_tab_members'); ?> &middot; <?php echo lang('mess_nonveg'); ?> <span data-count="nonveg"><?php echo (int) $counts->nonveg; ?></span> &middot; <?php echo lang('mess_veg'); ?> <span data-count="veg"><?php echo (int) $counts->veg; ?></span></div></div>
        <div class="mess-tile good"><div class="num" data-count="served"><?php echo (int) $counts->served; ?></div><div class="lbl"><?php echo lang('mess_served'); ?></div></div>
        <div class="mess-tile warn"><div class="num" data-count="pending"><?php echo (int) $counts->pending; ?></div><div class="lbl"><?php echo lang('mess_pending'); ?></div></div>
        <div class="mess-tile bad"><div class="num" data-count="not_taken"><?php echo (int) $counts->not_taken; ?></div><div class="lbl"><?php echo lang('mess_not_taken'); ?></div></div>
        <div class="mess-tile"><div class="num" data-count="skipped"><?php echo (int) $counts->skipped; ?></div><div class="lbl"><?php echo lang('mess_skipped'); ?></div></div>
    </div>

    <div class="mess-card p-0">
        <div class="mess-daybar p-3 mb-0" style="border-bottom:1px solid var(--mess-line)">
            <input type="search" class="form-control" placeholder="<?php echo escape_output(lang('mess_search_member')); ?>" data-mess-filter="#mess_reg_list">
            <div class="mess-filter" id="mess_reg_mode">
                <a href="#" class="mess-btn on" data-mode=""><?php echo lang('all'); ?></a>
                <a href="#" class="mess-btn" data-mode="pickup"><?php echo lang('mess_pickup'); ?></a>
                <a href="#" class="mess-btn" data-mode="delivery"><?php echo lang('mess_delivery'); ?></a>
                <a href="#" class="mess-btn" data-mode="pending"><?php echo lang('mess_pending'); ?></a>
            </div>
        </div>
        <div id="mess_reg_list">
            <?php if (!$rows): ?>
                <div class="p-4 mess-muted"><?php echo lang('mess_no_members_expected'); ?></div>
            <?php endif; ?>
            <?php foreach ($rows as $r) {
                $this->load->view('mess/_registerRow', array(
                    'r' => $r,
                    'row_extras' => isset($extras[$r->member_id]) ? $extras[$r->member_id] : array(),
                    'can_mark' => $can_mark,
                    'is_future' => $is_future,
                ));
            } ?>
        </div>
        <?php if ($can_mark && $rows && !$is_future): ?>
        <div class="mess-reg-bulk">
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/bulkMark" class="mess-confirm-form" data-confirm="<?php echo escape_output(lang('mess_bulk_confirm')); ?>">
                <input type="hidden" name="date" value="<?php echo escape_output($date); ?>">
                <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
                <input type="hidden" name="action" value="served">
                <button type="submit" class="mess-btn success"><i class="fa fa-check"></i> <?php echo lang('mess_mark_all_served'); ?></button>
            </form>
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/bulkMark" class="mess-confirm-form" data-confirm="<?php echo escape_output(lang('mess_bulk_confirm')); ?>">
                <input type="hidden" name="date" value="<?php echo escape_output($date); ?>">
                <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
                <input type="hidden" name="action" value="not_taken">
                <button type="submit" class="mess-btn danger"><?php echo lang('mess_mark_all_not_taken'); ?></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>
