<?php
$today = date('Y-m-d');
$posted = $this->input->post('submit') ? true : false;
$src = $sub ? $sub : $renew_from;
$v = function ($field, $default = '') use ($posted, $sub, $renew_from) {
    if ($posted) {
        return $this->input->post($field);
    }
    if ($sub && isset($sub->$field)) {
        return $sub->$field;
    }
    if ($renew_from && isset($renew_from->$field)) {
        return $renew_from->$field;
    }
    return $default;
};
$meal = function ($slot, $field) use ($posted, $src) {
    if ($posted) {
        return (int) $this->input->post($field);
    }
    if ($src) {
        return isset($src->meals[$slot]) ? (int) $src->meals[$slot] : 0;
    }
    return $slot == 3 ? 1 : 0;
};
if ($posted) {
    $start = $this->input->post('start_date');
    $end = $this->input->post('end_date');
    $cycle_days = (int) $this->input->post('cycle_days');
} elseif ($sub) {
    $start = $sub->start_date;
    $end = $sub->end_date;
    $cycle_days = (int) $sub->cycle_days;
} elseif ($renew_from) {
    $start = date('Y-m-d', strtotime($renew_from->end_date . ' +1 day'));
    $cycle_days = (int) $renew_from->cycle_days;
    $end = date('Y-m-d', strtotime($start . ' +' . ($cycle_days - 1) . ' days'));
} else {
    $start = $today;
    $cycle_days = (int) $mess_settings->cycle_days;
    $end = date('Y-m-d', strtotime($start . ' +' . ($cycle_days - 1) . ' days'));
}
$agreed = $v('agreed_price');
if (!$posted && $agreed !== '' && $agreed !== null) {
    $agreed = number_format((float) $agreed, (int) getCompanyInfo()->precision, '.', '');
}
$plan_id = $v('plan_id');
$diet = $v('diet', 'nonveg');
$mode = $v('delivery_mode', 'pickup');
$slot_fields = array(1 => 'persons_breakfast', 2 => 'persons_lunch', 3 => 'persons_dinner');
$slot_names = array(1 => lang('mess_breakfast'), 2 => lang('mess_lunch'), 3 => lang('mess_dinner'));
$title = $sub ? lang('mess_edit_subscription') : ($renew_from ? lang('mess_renew_cycle') : lang('mess_new_subscription'));
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'subscriptions')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo $title; ?></h3>
            <?php if ($renew_from): ?>
                <div class="mess-sub"><?php echo lang('mess_renewing_from'); ?> <?php echo escape_output(date($this->session->userdata('date_format'), strtotime($renew_from->end_date))); ?> &middot; <?php echo escape_output($renew_from->member_name); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box mess-form">
            <?php echo form_open(base_url('Mess/addEditSubscription/' . $encrypted_id), array(
                'id' => 'mess_subscription_form',
                'data-new' => $sub ? '0' : '1',
                'data-renew' => $renew_from ? '1' : '0',
                'data-today' => $today,
                'data-paused' => $sub ? (int) $sub->paused_days : 0,
            )); ?>
            <?php if ($renew_from): ?>
                <input type="hidden" name="renewed_from_id" value="<?php echo (int) $renew_from->id; ?>">
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="mess-errors"><ul><?php foreach ($errors as $e): ?><li><?php echo escape_output($e); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (!empty($overlaps)): ?>
                <div class="mess-warning"><i class="fa fa-exclamation-triangle"></i> <?php echo lang('mess_overlap_warning'); ?>:
                    <?php foreach ($overlaps as $o): ?> <?php echo escape_output($o->start_date . ' - ' . $o->end_date); ?>;<?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mess-section-title"><?php echo lang('mess_member'); ?> &amp; <?php echo lang('mess_plan'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_member'); ?> <span class="required_star">*</span>
                            <?php if (checkAccess(378, 'add') && !$sub): ?>
                                &middot; <a href="<?php echo base_url(); ?>Mess/addEditMember"><?php echo lang('mess_add_member'); ?></a>
                            <?php endif; ?>
                        </label>
                        <?php $member_sel = $posted ? (int) $this->input->post('member_id') : (int) $preselect_member; ?>
                        <select name="member_id" id="member_id" class="form-control select2" <?php echo $sub ? 'disabled' : ''; ?>>
                            <option value=""><?php echo lang('select'); ?></option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo (int) $m->id; ?>" <?php echo $member_sel == $m->id ? 'selected' : ''; ?>>
                                    <?php echo escape_output($m->name . ' (' . $m->member_code . ')' . ($m->phone ? ' ' . $m->phone : '') . ($m->status == 'inactive' ? ' - ' . lang('mess_status_inactive') : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($sub): ?><input type="hidden" name="member_id" value="<?php echo (int) $sub->member_id; ?>"><?php endif; ?>
                        <div class="mess-muted mt-1" id="mess_member_info" style="display:none" data-due="<?php echo escape_output(lang('mess_balance_due')); ?>" data-credit="<?php echo escape_output(lang('mess_advance_credit')); ?>"></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_plan'); ?></label>
                        <select name="plan_id" id="plan_id" class="form-control select2">
                            <option value="">-- <?php echo lang('select'); ?> --</option>
                            <?php foreach ($plans as $p):
                                if (!$p->is_active && $p->id != $plan_id) { continue; } ?>
                                <option value="<?php echo (int) $p->id; ?>" <?php echo $plan_id == $p->id ? 'selected' : ''; ?>
                                    data-price="<?php echo (float) $p->price_per_person; ?>" data-cycle="<?php echo (int) $p->cycle_days; ?>"
                                    data-b="<?php echo (int) $p->has_breakfast; ?>" data-l="<?php echo (int) $p->has_lunch; ?>" data-d="<?php echo (int) $p->has_dinner; ?>"
                                    data-diet="<?php echo escape_output($p->diet); ?>">
                                    <?php echo escape_output($p->name . ' - ' . getAmtPCustom($p->price_per_person) . ' / ' . $p->cycle_days . ' ' . lang('days')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mess-section-title"><?php echo lang('mess_meals_persons'); ?></div>
            <div class="mess-muted mb-2"><?php echo lang('mess_meals_persons_hint'); ?></div>
            <div class="mess-slot-inputs mb-3">
                <?php foreach ($slot_fields as $slot => $field):
                    $val = $meal($slot, $field);
                    if (!isset($slots[$slot]) && $val < 1) { ?>
                        <input type="hidden" name="<?php echo $field; ?>" class="mess-persons" value="0">
                    <?php continue; } ?>
                    <div class="mess-slot-input">
                        <label><?php echo escape_output($slot_names[$slot]); ?></label>
                        <div class="mess-stepper">
                            <button type="button" data-step="-1">&minus;</button>
                            <input type="number" min="0" max="50" name="<?php echo $field; ?>" class="form-control mess-persons" value="<?php echo (int) $val; ?>">
                            <button type="button" data-step="1">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_diet'); ?></label>
                        <select name="diet" class="form-control select2">
                            <?php foreach (array('nonveg', 'veg', 'both') as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo $diet == $d ? 'selected' : ''; ?>><?php echo lang('mess_' . $d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_mode'); ?></label>
                        <select name="delivery_mode" class="form-control select2">
                            <option value="pickup" <?php echo $mode == 'pickup' ? 'selected' : ''; ?>><?php echo lang('mess_pickup'); ?></option>
                            <option value="delivery" <?php echo $mode == 'delivery' ? 'selected' : ''; ?>><?php echo lang('mess_delivery'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4" id="mess_address_row">
                    <div class="form-group">
                        <label><?php echo lang('mess_delivery_address'); ?></label>
                        <input type="text" name="delivery_address" class="form-control" maxlength="255" value="<?php echo escape_output($v('delivery_address')); ?>">
                    </div>
                </div>
            </div>

            <div class="mess-section-title"><?php echo lang('date'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('start_date'); ?> <span class="required_star">*</span></label>
                        <input type="text" name="start_date" class="form-control customDatepicker" readonly value="<?php echo escape_output($start); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_cycle_days'); ?></label>
                        <input type="number" min="1" max="366" id="cycle_days" name="cycle_days" class="form-control" value="<?php echo (int) $cycle_days; ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('end_date'); ?> <span class="required_star">*</span></label>
                        <input type="text" name="end_date" class="form-control customDatepicker" readonly value="<?php echo escape_output($end); ?>">
                        <div class="mess-muted mt-1"><?php echo lang('mess_end_date_auto'); ?><?php echo $sub && $sub->paused_days ? ' (+' . (int) $sub->paused_days . ' ' . lang('mess_paused_days') . ')' : ''; ?></div>
                    </div>
                </div>
            </div>

            <div class="mess-section-title"><?php echo lang('price'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-4">
                    <div class="mess-price-box mb-3">
                        <?php echo lang('mess_suggested_price'); ?>:<br>
                        <span class="mess-muted" id="mess_suggested_calc"></span><b id="mess_suggested_text">0</b>
                        <input type="hidden" id="mess_suggested_value" value="0">
                        <div><a href="#" id="mess_use_suggested"><?php echo lang('select'); ?> &rarr;</a></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_agreed_price'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>) <span class="required_star">*</span></label>
                        <input type="text" name="agreed_price" class="form-control integerchk" value="<?php echo escape_output($agreed); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_price_note'); ?></label>
                        <input type="text" name="price_note" class="form-control" maxlength="150" value="<?php echo escape_output($v('price_note')); ?>" placeholder="<?php echo escape_output(lang('mess_price_note_ph')); ?>">
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label><?php echo lang('note'); ?></label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?php echo escape_output($v('note')); ?>">
                    </div>
                </div>
            </div>

            <?php if (!$sub && checkAccess(388, 'add')): ?>
                <div class="mess-section-title"><?php echo lang('mess_advance_now'); ?></div>
                <div class="mess-muted mb-2"><?php echo lang('mess_advance_now_hint'); ?>
                    <?php if ($mess_settings->require_open_register && !$register_open): ?>
                        <span class="mess-due"><?php echo lang('mess_register_needed'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-4">
                        <div class="form-group">
                            <label><?php echo lang('amount'); ?></label>
                            <input type="text" name="advance_amount" class="form-control integerchk" value="<?php echo escape_output($posted ? $this->input->post('advance_amount') : ''); ?>" placeholder="0">
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <div class="form-group">
                            <label><?php echo lang('payment_method'); ?></label>
                            <select name="advance_payment_id" class="form-control select2">
                                <?php $pm_sel = $posted ? (int) $this->input->post('advance_payment_id') : 0; foreach ($payment_methods as $pm): ?>
                                    <option value="<?php echo (int) $pm->id; ?>" <?php echo $pm_sel == $pm->id ? 'selected' : ''; ?>><?php echo escape_output($pm->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mt-2">
                <div class="col-sm-12 col-md-3 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('mess_save_subscription'); ?></button>
                </div>
                <?php if (!$sub && checkAccess(388, 'add')): ?>
                <div class="col-sm-12 col-md-3 mb-2">
                    <button type="submit" name="submit" value="print" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?> + <?php echo lang('mess_print_receipt'); ?></button>
                </div>
                <?php endif; ?>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?><?php echo $src ? 'Mess/member/' . (int) $src->member_id : 'Mess/subscriptions'; ?>"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
