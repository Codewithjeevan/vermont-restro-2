<?php
$date_format = $this->session->userdata('date_format');
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));
$last_week = date('Y-m-d', strtotime($date . ' -7 days'));
$today = date('Y-m-d');
// food menu <option>s grouped by category, reused by every row and the template
$options = '<option value="">' . escape_output(lang('mess_pick_food_menu')) . '</option>';
$group = null;
foreach ($food_menus as $fm) {
    $cat = $fm->category_name ? $fm->category_name : '-';
    if ($cat !== $group) {
        if ($group !== null) {
            $options .= '</optgroup>';
        }
        $options .= '<optgroup label="' . escape_output($cat) . '">';
        $group = $cat;
    }
    $options .= '<option value="' . (int) $fm->id . '" data-recipe="' . ($fm->recipe_lines > 0 ? 1 : 0) . '">' . escape_output($fm->name) . '</option>';
}
if ($group !== null) {
    $options .= '</optgroup>';
}
$dish_row = function ($slot, $diet, $i, $d) use ($options, $can_edit) {
    $fid = $d ? (int) $d->food_menu_id : 0;
    $opts = $fid ? str_replace('value="' . $fid . '"', 'value="' . $fid . '" selected', $options) : $options;
    $name = $d ? $d->dish_name : '';
    $portion = $d ? (float) $d->portion_per_person : 1;
    $buffer = $d ? (float) $d->buffer_portions : 0;
    $p = 'dish[' . $slot . '][' . $diet . '][' . $i . ']';
    ob_start(); ?>
    <div class="mess-dish-row">
        <div>
            <select name="<?php echo $p; ?>[food_menu_id]" class="form-control mess-dish-food" <?php echo $can_edit ? '' : 'disabled'; ?>><?php echo $opts; ?></select>
            <input type="text" name="<?php echo $p; ?>[dish_name]" class="form-control mt-1 mess-dish-name" maxlength="150" value="<?php echo escape_output($fid ? '' : $name); ?>" placeholder="<?php echo escape_output(lang('mess_free_text_dish')); ?>" <?php echo $can_edit ? '' : 'disabled'; ?>>
            <div class="mess-muted mess-norecipe" style="display:none" title="<?php echo escape_output(lang('mess_no_recipe_hint')); ?>"><i class="fa fa-exclamation-triangle" style="color:#b7791f"></i> <?php echo lang('mess_no_recipe'); ?></div>
        </div>
        <input type="number" step="0.25" min="0" name="<?php echo $p; ?>[portion]" class="form-control mess-portion" value="<?php echo $portion; ?>" <?php echo $can_edit ? '' : 'disabled'; ?>>
        <input type="number" step="1" min="0" name="<?php echo $p; ?>[buffer]" class="form-control mess-buffer" value="<?php echo $buffer; ?>" <?php echo $can_edit ? '' : 'disabled'; ?>>
        <div class="planned mess-num" style="text-align:center;font-weight:700">0</div>
        <?php if ($can_edit): ?><button type="button" class="x" title="<?php echo lang('delete'); ?>">&times;</button><?php else: ?><span></span><?php endif; ?>
    </div>
    <?php return ob_get_clean();
};
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'menu')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_daily_menu'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_menu_for'); ?> <?php echo escape_output(date('l', strtotime($date)) . ' ' . date($date_format, strtotime($date))); ?></div>
        </div>
        <?php if ($can_edit): ?>
        <div class="mess-head-actions">
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/copyMenu" class="mess-confirm-form" data-confirm="<?php echo escape_output(lang('mess_copy_confirm')); ?>">
                <input type="hidden" name="to_date" value="<?php echo $date; ?>"><input type="hidden" name="from_date" value="<?php echo $prev; ?>">
                <button type="submit" class="mess-btn"><i class="fa fa-copy"></i> <?php echo lang('mess_copy_yesterday'); ?></button>
            </form>
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/copyMenu" class="mess-confirm-form" data-confirm="<?php echo escape_output(lang('mess_copy_confirm')); ?>">
                <input type="hidden" name="to_date" value="<?php echo $date; ?>"><input type="hidden" name="from_date" value="<?php echo $last_week; ?>">
                <button type="submit" class="mess-btn"><i class="fa fa-copy"></i> <?php echo lang('mess_copy_last_week'); ?></button>
            </form>
            <form method="post" action="<?php echo base_url(); ?>Mess_daily/copyMenu" class="mess-confirm-form mess-filter" data-confirm="<?php echo escape_output(lang('mess_copy_confirm')); ?>">
                <input type="hidden" name="to_date" value="<?php echo $date; ?>">
                <input type="text" name="from_date" class="form-control customDatepicker" readonly placeholder="yyyy-mm-dd" style="width:130px">
                <button type="submit" class="mess-btn"><?php echo lang('mess_copy_from'); ?></button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="mess-daybar">
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/menu/<?php echo $prev; ?>"><i class="fa fa-chevron-left"></i></a>
        <form method="get" action="<?php echo base_url(); ?>Mess_daily/menu" class="d-inline-flex">
            <input type="text" name="date" class="form-control customDatepicker" readonly value="<?php echo escape_output($date); ?>" style="width:130px" onchange="this.form.submit()">
        </form>
        <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/menu/<?php echo $next; ?>"><i class="fa fa-chevron-right"></i></a>
        <?php if ($date != $today): ?><a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/menu/<?php echo $today; ?>"><?php echo lang('mess_today'); ?></a><?php endif; ?>
        <span class="mess-muted"><?php echo lang('mess_expected_hint'); ?></span>
    </div>

    <form method="post" action="<?php echo base_url(); ?>Mess_daily/saveMenu" id="mess_menu_form">
        <input type="hidden" name="date" value="<?php echo escape_output($date); ?>">
        <?php foreach ($slots as $slot):
            $h = $headcount[$slot->slot]; ?>
            <div class="mess-card">
                <h4><?php echo escape_output($slot->name); ?>
                    <span class="mess-muted"><?php echo (int) $h->expected; ?> <?php echo lang('mess_expected_today'); ?> &middot; <?php echo escape_output($slot->from . ' - ' . $slot->to); ?></span>
                </h4>
                <div class="mess-grid-2">
                <?php foreach (array('nonveg', 'veg') as $diet):
                    $expected = $diet == 'veg' ? $h->veg : $h->nonveg;
                    $dishes = isset($menu[$slot->slot][$diet]) ? $menu[$slot->slot][$diet] : array();
                    $extra_attrs = '';
                    foreach ($extra_qty as $k => $q) {
                        list($ks, $kf) = explode('_', $k);
                        if ((int) $ks == (int) $slot->slot) {
                            $extra_attrs .= ' data-extra-' . (int) $kf . '="' . (float) $q . '"';
                        }
                    } ?>
                    <div class="mess-menu-cell" data-slot="<?php echo (int) $slot->slot; ?>" data-diet="<?php echo $diet; ?>" data-expected="<?php echo (int) $expected; ?>" data-next="<?php echo count($dishes) + 1; ?>"<?php echo $extra_attrs; ?>>
                        <div class="mess-menu-cell-head">
                            <span class="ttl"><span class="mess-badge <?php echo $diet; ?>"><?php echo lang('mess_' . $diet); ?></span> <?php echo lang('mess_expected_persons'); ?>: <?php echo (int) $expected; ?></span>
                            <?php if (!$dishes && $expected > 0): ?><span class="mess-due"><?php echo lang('mess_menu_missing'); ?></span><?php endif; ?>
                        </div>
                        <div class="mess-dish-row mess-dish-head">
                            <span><?php echo lang('mess_dish'); ?></span><span><?php echo lang('mess_portion_per_person'); ?></span><span><?php echo lang('mess_buffer'); ?></span><span class="planned" style="text-align:center"><?php echo lang('mess_planned_portions'); ?></span><span></span>
                        </div>
                        <div class="mess-dish-rows">
                            <?php foreach ($dishes as $i => $d) { echo $dish_row($slot->slot, $diet, $i, $d); } ?>
                        </div>
                        <?php if ($can_edit): ?>
                            <a href="#" class="mess-btn sm mess-add-dish"><i class="fa fa-plus"></i> <?php echo lang('mess_add_dish'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php if (!empty($menu_texts[$slot->slot])): ?>
                    <div class="mt-2">
                        <label class="mess-muted"><?php echo lang('mess_menu_text'); ?></label>
                        <div class="d-flex gap-2 align-items-start">
                            <textarea class="form-control" rows="3" id="mess_menu_text_<?php echo (int) $slot->slot; ?>" readonly><?php echo escape_output($menu_texts[$slot->slot]); ?></textarea>
                            <button type="button" class="mess-btn" data-mess-copy="#mess_menu_text_<?php echo (int) $slot->slot; ?>"><i class="fa fa-clipboard"></i> <?php echo lang('mess_copy_text'); ?></button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($can_edit): ?>
            <div class="row">
                <div class="col-sm-12 col-md-2 mb-3">
                    <button type="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
            </div>
        <?php endif; ?>
    </form>
    <script type="text/template" id="mess_dish_template"><?php echo $dish_row('__SLOT__', '__DIET__', '__I__', null); ?></script>
</section>
