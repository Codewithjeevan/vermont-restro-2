<?php
$v = function ($field, $default = '') use ($plan) {
    if (isset($_POST[$field])) {
        return set_value($field);
    }
    return $plan ? $plan->$field : $default;
};
$checked = function ($field, $default) use ($plan) {
    if (isset($_POST['submit'])) {
        return $this->input->post($field) ? 'checked' : '';
    }
    $val = $plan ? $plan->$field : $default;
    return $val ? 'checked' : '';
};
$diet = $v('diet', 'nonveg');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'plans')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo $plan ? lang('mess_edit_plan') : lang('mess_add_plan'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_plan_hint'); ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box mess-form">
            <?php echo form_open(base_url('Mess/addEditPlan/' . $encrypted_id)); ?>
            <?php if (validation_errors() || !empty($slot_error)): ?>
                <div class="mess-errors"><?php echo validation_errors(); ?><?php echo !empty($slot_error) ? '<p>' . escape_output($slot_error) . '</p>' : ''; ?></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_plan_name'); ?> <span class="required_star">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="100" value="<?php echo escape_output($v('name')); ?>" placeholder="Dinner Non-veg">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_diet'); ?></label>
                        <select name="diet" class="form-control select2">
                            <?php foreach (array('nonveg', 'veg', 'both') as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo $diet == $d ? 'selected' : ''; ?>><?php echo lang('mess_' . $d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_included_meals'); ?> <span class="required_star">*</span></label>
                        <div class="pt-1">
                            <label class="mess-check"><input type="checkbox" name="has_breakfast" value="1" <?php echo $checked('has_breakfast', 0); ?>> <?php echo lang('mess_breakfast'); ?></label>
                            <label class="mess-check"><input type="checkbox" name="has_lunch" value="1" <?php echo $checked('has_lunch', 0); ?>> <?php echo lang('mess_lunch'); ?></label>
                            <label class="mess-check"><input type="checkbox" name="has_dinner" value="1" <?php echo $checked('has_dinner', 1); ?>> <?php echo lang('mess_dinner'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_price_per_person'); ?> (<?php echo escape_output($this->session->userdata('currency')); ?>) <span class="required_star">*</span></label>
                        <input type="text" name="price_per_person" class="form-control integerchk" value="<?php echo escape_output($v('price_per_person') !== '' ? (float) $v('price_per_person') : ''); ?>" placeholder="350">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_cycle_days'); ?> <span class="required_star">*</span></label>
                        <input type="number" min="1" max="366" name="cycle_days" class="form-control" value="<?php echo escape_output($v('cycle_days', $mess_settings->cycle_days)); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('status'); ?></label>
                        <div class="pt-1">
                            <label class="mess-check"><input type="checkbox" name="is_active" value="1" <?php echo $checked('is_active', 1); ?>> <?php echo lang('mess_status_active'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-lg-8">
                    <div class="form-group">
                        <label><?php echo lang('description'); ?></label>
                        <input type="text" name="description" class="form-control" maxlength="255" value="<?php echo escape_output($v('description')); ?>">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?>Mess/plans"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
