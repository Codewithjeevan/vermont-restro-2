<?php
$posted = $this->input->post('submit') ? true : false;
$settle = $posted ? $this->input->post('settle_type') : 'on_account';
$slot_sel = $posted ? (int) $this->input->post('slot') : (int) $preselect_slot;
$date_val = $posted ? $this->input->post('extra_date') : $preselect_date;
$pm_sel = $posted ? (int) $this->input->post('payment_id') : (isset($payment_methods[0]) ? (int) $payment_methods[0]->id : 0);
$options = '<option value="">' . escape_output(lang('mess_custom_item')) . '</option>';
$group = null;
foreach ($food_menus as $fm) {
    $cat = $fm->category_name ? $fm->category_name : '-';
    if ($cat !== $group) {
        if ($group !== null) { $options .= '</optgroup>'; }
        $options .= '<optgroup label="' . escape_output($cat) . '">';
        $group = $cat;
    }
    $options .= '<option value="' . (int) $fm->id . '" data-price="' . (float) $fm->sale_price . '">' . escape_output($fm->name) . ' (' . getAmtPCustom($fm->sale_price) . ')</option>';
}
if ($group !== null) { $options .= '</optgroup>'; }
$item_row = function ($i, $it) use ($options) {
    $fid = $it && !empty($it['food_menu_id']) ? (int) $it['food_menu_id'] : 0;
    $opts = $fid ? str_replace('value="' . $fid . '"', 'value="' . $fid . '" selected', $options) : $options;
    ob_start(); ?>
    <div class="row g-2 align-items-end mess-item mb-2">
        <div class="col-sm-12 col-md-5">
            <select name="items[<?php echo $i; ?>][food_menu_id]" class="form-control"><?php echo $opts; ?></select>
            <input type="text" name="items[<?php echo $i; ?>][item_name]" class="form-control mt-1 mess-item-name" maxlength="150" placeholder="<?php echo escape_output(lang('mess_item_name')); ?>" value="<?php echo $it && !$fid ? escape_output($it['item_name']) : ''; ?>">
        </div>
        <div class="col-4 col-md-2">
            <label class="mess-muted"><?php echo lang('qty'); ?></label>
            <input type="number" step="0.5" min="0" name="items[<?php echo $i; ?>][qty]" class="form-control mess-item-qty" value="<?php echo $it ? escape_output($it['qty']) : 1; ?>">
        </div>
        <div class="col-4 col-md-2">
            <label class="mess-muted"><?php echo lang('mess_unit_price'); ?></label>
            <input type="text" name="items[<?php echo $i; ?>][unit_price]" class="form-control integerchk mess-item-price" value="<?php echo $it ? escape_output($it['unit_price']) : ''; ?>">
        </div>
        <div class="col-3 col-md-2 mess-num"><b class="mess-item-total">0</b></div>
        <div class="col-1 col-md-1"><button type="button" class="mess-btn sm danger x">&times;</button></div>
    </div>
    <?php return ob_get_clean();
};
$posted_items = $posted && is_array($this->input->post('items')) ? array_values($this->input->post('items')) : array(null);
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'extras')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_add_extra'); ?></h3>
            <div class="mess-sub">"Aaj mujhe ye chahiye"</div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box mess-form">
            <?php echo form_open(base_url('Mess_daily/addExtra'), array('id' => 'mess_extra_form', 'data-next' => count($posted_items) + 1)); ?>
            <input type="hidden" name="back" value="<?php echo escape_output($back); ?>">
            <?php if (!empty($errors)): ?>
                <div class="mess-errors"><ul><?php foreach ($errors as $e): ?><li><?php echo escape_output($e); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_member'); ?> <span class="required_star">*</span></label>
                        <select name="member_id" class="form-control select2">
                            <option value=""><?php echo lang('select'); ?></option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo (int) $m->id; ?>" <?php echo (int) $preselect_member == $m->id ? 'selected' : ''; ?>><?php echo escape_output($m->name . ' (' . $m->member_code . ')' . ($m->phone ? ' ' . $m->phone : '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label><?php echo lang('date'); ?></label>
                        <input type="text" name="extra_date" class="form-control customDatepicker" readonly value="<?php echo escape_output($date_val); ?>">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label><?php echo lang('mess_slot'); ?></label>
                        <select name="slot" class="form-control">
                            <?php foreach ($slots as $s): ?>
                                <option value="<?php echo (int) $s->slot; ?>" <?php echo $slot_sel == $s->slot ? 'selected' : ''; ?>><?php echo escape_output($s->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mess-section-title"><?php echo lang('item'); ?></div>
            <div id="mess_items">
                <?php foreach ($posted_items as $i => $it) { echo $item_row($i, $it); } ?>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="#" class="mess-btn sm" id="mess_add_item"><i class="fa fa-plus"></i> <?php echo lang('item'); ?></a>
                <div><?php echo lang('total'); ?>: <b><?php echo escape_output($this->session->userdata('currency')); ?> <span id="mess_extra_total">0</span></b></div>
            </div>

            <div class="mess-section-title"><?php echo lang('mess_settle'); ?></div>
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <label class="mess-check d-flex"><input type="radio" name="settle_type" value="on_account" <?php echo $settle != 'pay_now' ? 'checked' : ''; ?>> <span><b><?php echo lang('mess_on_account'); ?></b><br><span class="mess-muted"><?php echo lang('mess_on_account_hint'); ?></span></span></label>
                </div>
                <?php if ($can_pay_now): ?>
                <div class="col-sm-12 col-md-6">
                    <label class="mess-check d-flex"><input type="radio" name="settle_type" value="pay_now" <?php echo $settle == 'pay_now' ? 'checked' : ''; ?>> <span><b><?php echo lang('mess_pay_now'); ?></b><br><span class="mess-muted"><?php echo lang('mess_pay_now_hint'); ?></span>
                        <?php if ($mess_settings->require_open_register && !$register_open): ?><br><span class="mess-due"><?php echo lang('mess_register_needed'); ?></span><?php endif; ?></span></label>
                </div>
                <?php endif; ?>
            </div>
            <div class="row mt-2" id="mess_paynow_row" style="display:none">
                <div class="col-sm-12 col-md-4">
                    <div class="form-group">
                        <label><?php echo lang('payment_method'); ?></label>
                        <select name="payment_id" class="form-control">
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?php echo (int) $pm->id; ?>" <?php echo $pm_sel == $pm->id ? 'selected' : ''; ?>><?php echo escape_output($pm->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group mt-2">
                <label><?php echo lang('note'); ?></label>
                <input type="text" name="note" class="form-control" maxlength="150" value="<?php echo escape_output($posted ? $this->input->post('note') : ''); ?>">
            </div>
            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?><?php echo $back == 'register' ? 'Mess_daily/register' : ($preselect_member ? 'Mess/member/' . (int) $preselect_member : 'Mess_daily/extras'); ?>"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
            <script type="text/template" id="mess_item_template"><?php echo $item_row('__I__', null); ?></script>
        </div>
    </div>
</section>
