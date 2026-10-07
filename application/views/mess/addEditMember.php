<?php
$v = function ($field, $default = '') use ($member) {
    if (isset($_POST[$field])) {
        return set_value($field);
    }
    return $member ? $member->$field : $default;
};
$diet = $v('diet_pref', 'nonveg');
$mode = $v('delivery_mode', 'pickup');
$status = $v('status', 'active');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'members')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo $member ? lang('mess_edit_member') : lang('mess_add_member'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_member_hint'); ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box mess-form">
            <?php echo form_open(base_url('Mess/addEditMember/' . $encrypted_id), array('id' => 'mess_member_form')); ?>
            <input type="hidden" id="mess_member_id" value="<?php echo $member ? (int) $member->id : 0; ?>">
            <?php if (validation_errors()): ?>
                <div class="mess-errors"><?php echo validation_errors(); ?></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('mess_member_code'); ?></label>
                        <input type="text" class="form-control" readonly value="<?php echo escape_output($next_code); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-5">
                    <div class="form-group">
                        <label><?php echo lang('name'); ?> <span class="required_star">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="150" autofocus value="<?php echo escape_output($v('name')); ?>" placeholder="Arif / Khaled">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('status'); ?></label>
                        <select name="status" class="form-control select2">
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>><?php echo lang('mess_status_active'); ?></option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>><?php echo lang('mess_status_inactive'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('phone'); ?></label>
                        <input type="text" name="phone" class="form-control" maxlength="30" value="<?php echo escape_output($v('phone')); ?>" placeholder="055-1234567">
                    </div>
                    <div class="mess-warning" id="mess_phone_warning" style="display:none"><?php echo lang('mess_phone_used_by'); ?>: <span></span></div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('mess_phone2'); ?></label>
                        <input type="text" name="phone2" class="form-control" maxlength="30" value="<?php echo escape_output($v('phone2')); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('mess_default_diet'); ?></label>
                        <select name="diet_pref" class="form-control select2">
                            <?php foreach (array('nonveg', 'veg', 'both') as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo $diet == $d ? 'selected' : ''; ?>><?php echo lang('mess_' . $d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="form-group">
                        <label><?php echo lang('mess_default_mode'); ?></label>
                        <select name="delivery_mode" class="form-control select2">
                            <option value="pickup" <?php echo $mode == 'pickup' ? 'selected' : ''; ?>><?php echo lang('mess_pickup'); ?></option>
                            <option value="delivery" <?php echo $mode == 'delivery' ? 'selected' : ''; ?>><?php echo lang('mess_delivery'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="form-group">
                        <label><?php echo lang('mess_building_room'); ?></label>
                        <input type="text" name="building_room" class="form-control" maxlength="150" value="<?php echo escape_output($v('building_room')); ?>" placeholder="Al Noor bldg, room 204">
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-8">
                    <div class="form-group">
                        <label><?php echo lang('address'); ?></label>
                        <input type="text" name="address" class="form-control" maxlength="255" value="<?php echo escape_output($v('address')); ?>">
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label><?php echo lang('note'); ?></label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?php echo escape_output($v('note')); ?>" placeholder="no beef, gate 2">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12 col-md-2 mb-2">
                    <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                </div>
                <?php if (!$member && checkAccess(383, 'add')): ?>
                <div class="col-sm-12 col-md-3 mb-2">
                    <button type="submit" name="submit" value="subscribe" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?> + <?php echo lang('mess_new_subscription'); ?></button>
                </div>
                <?php endif; ?>
                <div class="col-sm-12 col-md-2 mb-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?><?php echo $member ? 'Mess/member/' . (int) $member->id : 'Mess/members'; ?>"><?php echo lang('back'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
