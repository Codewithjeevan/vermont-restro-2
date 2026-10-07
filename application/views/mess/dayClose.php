<?php
$date_format = $this->session->userdata('date_format');
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));
$today = date('Y-m-d');
$slot_name = isset($slots[$slot]) ? $slots[$slot]->name : '';
$fmtq = function ($v) {
    $v = (float) $v;
    return abs($v - round($v)) < 0.005 ? (string) round($v) : number_format($v, 2, '.', '');
};
$actions = array('waste' => lang('mess_waste'), 'carry' => lang('mess_carry'), 'staff' => lang('mess_staff_meal'));
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'close')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_day_close'); ?> &middot; <?php echo escape_output($slot_name); ?></h3>
            <div class="mess-sub"><?php echo escape_output(date('l', strtotime($date)) . ' ' . date($date_format, strtotime($date))); ?></div>
        </div>
        <div class="mess-head-actions">
            <?php if (checkAccess(402, 'view')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/sheet/<?php echo $date; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-file-text-o"></i> <?php echo lang('mess_daily_sheet'); ?></a>
            <?php endif; ?>
            <?php if (checkAccess(395, 'view')): ?>
                <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $date; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-check-square-o"></i> <?php echo lang('mess_meal_register'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mess-daybar">
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo $prev; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-left"></i></a>
        <form method="get" action="<?php echo base_url(); ?>Mess_daily/dayClose" class="d-inline-flex">
            <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo escape_output($date); ?>" style="width:130px" onchange="this.form.submit()">
            <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
        </form>
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo $next; ?>/<?php echo (int) $slot; ?>"><i class="fa fa-chevron-right"></i></a>
        <?php if ($date != $today): ?><a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo $today; ?>/<?php echo (int) $slot; ?>"><?php echo lang('mess_today'); ?></a><?php endif; ?>
        <div class="mess-slot-tabs">
            <?php foreach ($slots as $s): ?>
                <a class="<?php echo $s->slot == $slot ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo $date; ?>/<?php echo (int) $s->slot; ?>"><?php echo escape_output($s->name); ?><?php echo !empty($slot_closed[$s->slot]) ? ' &#10003;' : ''; ?></a>
            <?php endforeach; ?>
        </div>
    </div>

<?php if ($closed): /* ---------------- closed: summary + reopen ---------------- */
    $cost_per_meal = $close->served > 0 ? $close->food_cost / $close->served : 0; ?>
    <div class="mess-tiles">
        <div class="mess-tile good"><div class="num"><?php echo lang('mess_status_closed'); ?></div><div class="lbl"><?php echo escape_output(date('h:i A', strtotime($close->closed_at))); ?></div><div class="hint"><?php echo lang('mess_closed_by'); ?> <?php echo escape_output($close->closed_by_name); ?></div></div>
        <div class="mess-tile"><div class="num"><?php echo (int) $close->expected; ?></div><div class="lbl"><?php echo lang('mess_expected'); ?></div><div class="hint"><?php echo lang('mess_served'); ?> <?php echo (int) $close->served; ?> &middot; <?php echo lang('mess_not_taken'); ?> <?php echo (int) $close->not_taken; ?> &middot; <?php echo lang('mess_skipped'); ?> <?php echo (int) $close->skipped; ?></div></div>
        <div class="mess-tile"><div class="num"><?php echo getAmtPCustom($close->food_cost); ?></div><div class="lbl"><?php echo lang('mess_food_cost'); ?></div><div class="hint"><?php echo lang('mess_cost_per_meal'); ?> <?php echo getAmtPCustom($cost_per_meal); ?></div></div>
        <div class="mess-tile <?php echo $close->waste_cost > 0 ? 'bad' : ''; ?>"><div class="num"><?php echo getAmtPCustom($close->waste_cost); ?></div><div class="lbl"><?php echo lang('mess_waste_cost'); ?></div>
            <div class="hint"><?php if ($close->waste_id && checkAccess(137, 'view')): ?><a href="<?php echo base_url(); ?>Waste/wasteDetails/<?php echo (int) $close->waste_id; ?>"><?php echo lang('mess_open_waste'); ?></a><?php else: ?>&nbsp;<?php endif; ?></div></div>
    </div>
    <div class="mess-card">
        <div class="table-responsive">
            <table class="mess-table">
                <thead><tr>
                    <th><?php echo lang('mess_dish'); ?></th><th class="mess-num"><?php echo lang('mess_planned_portions'); ?></th><th class="mess-num"><?php echo lang('mess_served_portions'); ?></th>
                    <th class="mess-num"><?php echo lang('mess_cooked'); ?></th><th class="mess-num"><?php echo lang('mess_leftover'); ?></th><th><?php echo lang('mess_leftover_goes_to'); ?></th>
                    <th class="mess-num"><?php echo lang('mess_food_cost'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td class="name"><?php echo escape_output($it->dish_name); ?><?php echo $it->food_menu_id && !$it->has_recipe ? ' <span class="mess-muted">(' . lang('mess_no_recipe') . ')</span>' : ''; ?></td>
                        <td class="mess-num"><?php echo $fmtq($it->planned); ?></td>
                        <td class="mess-num"><?php echo $fmtq($it->served); ?></td>
                        <td class="mess-num"><b><?php echo $fmtq($it->cooked); ?></b></td>
                        <td class="mess-num"><?php echo $fmtq($it->leftover); ?></td>
                        <td><?php echo $it->leftover > 0 ? escape_output($actions[$it->leftover_action]) : '-'; ?></td>
                        <td class="mess-num"><?php echo $it->has_recipe ? getAmtPCustom($it->food_cost) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($close->note): ?><div class="mess-hint"><?php echo escape_output($close->note); ?></div><?php endif; ?>
        <?php if ($can_reopen): ?>
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/reopenDay" class="mess-confirm-form mt-3" data-confirm="<?php echo escape_output(lang('mess_reopen_confirm')); ?>">
                <input type="hidden" name="date" value="<?php echo escape_output($date); ?>">
                <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
                <button type="submit" class="mess-btn danger"><i class="fa fa-undo"></i> <?php echo lang('reopen'); ?></button>
            </form>
        <?php endif; ?>
    </div>

<?php else: /* ---------------- open: close form ---------------- */
    $c = $plan->counts; ?>
    <div class="mess-tiles">
        <div class="mess-tile"><div class="num"><?php echo (int) $c->expected; ?></div><div class="lbl"><?php echo lang('mess_expected'); ?></div></div>
        <div class="mess-tile good"><div class="num"><?php echo (int) $c->served; ?></div><div class="lbl"><?php echo lang('mess_served'); ?></div></div>
        <div class="mess-tile bad"><div class="num"><?php echo (int) $c->not_taken; ?></div><div class="lbl"><?php echo lang('mess_not_taken'); ?></div></div>
        <div class="mess-tile <?php echo $c->pending ? 'warn' : ''; ?>"><div class="num"><?php echo (int) $c->pending; ?></div><div class="lbl"><?php echo lang('mess_pending'); ?></div></div>
    </div>
    <?php if ($c->pending): ?>
        <div class="mess-warning"><i class="fa fa-exclamation-triangle"></i> <?php echo lang('mess_pending_warning'); ?>
            <?php if (checkAccess(395, 'view')): ?><a href="<?php echo base_url(); ?>Mess_daily/register/<?php echo $date; ?>/<?php echo (int) $slot; ?>"><?php echo lang('mess_meal_register'); ?> &rarr;</a><?php endif; ?></div>
    <?php endif; ?>

    <div class="mess-card">
        <div class="mess-hint"><?php echo lang('mess_close_hint'); ?></div>
        <?php if (!$plan->dishes): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <form method="post" action="<?php echo base_url(); ?>Mess_daily/closeDay" id="mess_close_form" class="mess-confirm-form" data-confirm="<?php echo escape_output(lang('mess_close_confirm')); ?>">
            <input type="hidden" name="date" value="<?php echo escape_output($date); ?>">
            <input type="hidden" name="slot" value="<?php echo (int) $slot; ?>">
            <div class="table-responsive">
            <table class="mess-table">
                <thead><tr>
                    <th><?php echo lang('mess_dish'); ?></th><th class="mess-num"><?php echo lang('mess_planned_portions'); ?></th><th class="mess-num"><?php echo lang('mess_served_portions'); ?></th>
                    <th style="width:110px"><?php echo lang('mess_cooked'); ?></th><th style="width:110px"><?php echo lang('mess_leftover'); ?></th><th style="width:170px"><?php echo lang('mess_leftover_goes_to'); ?></th>
                    <th class="mess-num"><?php echo lang('mess_food_cost'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($plan->dishes as $d):
                    $k = $this->Mess_model->dishFormKey($d);
                    $cooked = round($d->portions, 2);
                    $left = max(0, round($d->portions - $d->served, 2)); ?>
                    <tr class="mess-close-row" data-cost="<?php echo $d->recipe_lines ? round((float) $d->unit_cost, 4) : 0; ?>" data-served="<?php echo round($d->served, 2); ?>">
                        <td class="name"><?php echo escape_output($d->dish_name); ?>
                            <?php if (!$d->food_menu_id || !$d->recipe_lines): ?><div class="mess-muted"><?php echo lang('mess_no_recipe'); ?> &middot; <?php echo lang('mess_stock'); ?> -</div><?php endif; ?></td>
                        <td class="mess-num"><?php echo $fmtq($d->portions); ?></td>
                        <td class="mess-num"><?php echo $fmtq($d->served); ?></td>
                        <td><input type="number" step="0.5" min="0" name="close[<?php echo $k; ?>][cooked]" class="form-control mess-cooked" value="<?php echo $fmtq($cooked); ?>"></td>
                        <td><input type="number" step="0.5" min="0" name="close[<?php echo $k; ?>][leftover]" class="form-control mess-leftover" value="<?php echo $fmtq($left); ?>"></td>
                        <td><select name="close[<?php echo $k; ?>][action]" class="form-control">
                            <?php foreach ($actions as $ak => $al): ?><option value="<?php echo $ak; ?>"><?php echo escape_output($al); ?></option><?php endforeach; ?>
                        </select></td>
                        <td class="mess-num mess-rowcost"><?php echo $d->recipe_lines ? getAmtPCustom($cooked * $d->unit_cost) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="6"><?php echo lang('mess_food_cost'); ?></td><td class="mess-num" id="mess_close_total">0</td></tr></tfoot>
            </table>
            </div>
            <div class="form-group mt-2">
                <label><?php echo lang('note'); ?></label>
                <input type="text" name="note" class="form-control" maxlength="255">
            </div>
            <?php if ($can_close && $date <= $today): ?>
                <button type="submit" class="mess-btn primary"><i class="fa fa-lock"></i> <?php echo lang('mess_close_and_post'); ?></button>
            <?php elseif ($date > $today): ?>
                <div class="mess-muted"><?php echo lang('mess_close_future'); ?></div>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
</section>
