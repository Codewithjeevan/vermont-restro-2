<?php
$s = $mess_settings;
$slot_rows = array(
    'breakfast' => lang('mess_breakfast'),
    'lunch' => lang('mess_lunch'),
    'dinner' => lang('mess_dinner'),
);
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'settings')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_settings'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_roles_hint'); ?></div>
        </div>
    </div>

    <?php echo form_open(base_url('Mess/settings'), array('class' => 'mess-form')); ?>
    <div class="mess-grid-2">
        <div class="mess-card">
            <h4><?php echo lang('mess_slots'); ?></h4>
            <table class="mess-table">
                <thead><tr><th><?php echo lang('mess_slot'); ?></th><th><?php echo lang('mess_slot_on'); ?></th><th><?php echo lang('mess_from_time'); ?></th><th><?php echo lang('mess_to_time'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($slot_rows as $key => $label): ?>
                    <tr>
                        <td class="name"><?php echo escape_output($label); ?></td>
                        <td><input type="checkbox" name="<?php echo $key; ?>_on" value="1" <?php echo $s->{$key . '_on'} ? 'checked' : ''; ?> style="width:18px;height:18px"></td>
                        <td><input type="time" name="<?php echo $key; ?>_from" class="form-control" value="<?php echo escape_output($s->{$key . '_from'}); ?>"></td>
                        <td><input type="time" name="<?php echo $key; ?>_to" class="form-control" value="<?php echo escape_output($s->{$key . '_to'}); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mess-card">
            <h4><?php echo lang('mess_subscriptions'); ?> &amp; <?php echo lang('mess_payments'); ?></h4>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_default_cycle_days'); ?></label>
                        <input type="number" min="1" max="366" name="cycle_days" class="form-control" value="<?php echo (int) $s->cycle_days; ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_expiring_days'); ?></label>
                        <input type="number" min="0" max="60" name="expiring_days" class="form-control" value="<?php echo (int) $s->expiring_days; ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_member_code_prefix'); ?></label>
                        <input type="text" name="member_code_prefix" maxlength="10" class="form-control" value="<?php echo escape_output($s->member_code_prefix); ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label><?php echo lang('mess_whatsapp_country_code'); ?></label>
                        <input type="text" name="whatsapp_country_code" maxlength="6" class="form-control" value="<?php echo escape_output($s->whatsapp_country_code); ?>">
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <label><?php echo lang('mess_skip_credit_policy'); ?></label>
                        <select name="skip_credit_policy" class="form-control">
                            <?php foreach (array('none', 'informed', 'always') as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo $s->skip_credit_policy == $p ? 'selected' : ''; ?>><?php echo lang('mess_skip_credit_' . $p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12">
                    <label class="mess-check"><input type="checkbox" name="require_open_register" value="1" <?php echo $s->require_open_register ? 'checked' : ''; ?>> <?php echo lang('mess_require_open_register'); ?></label>
                    <div class="mess-muted"><?php echo lang('mess_require_open_register_hint'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mess-card">
        <h4><?php echo lang('mess_whatsapp_templates'); ?></h4>
        <div class="mess-hint"><?php echo lang('mess_template_vars'); ?></div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo lang('mess_template_menu'); ?></label>
                    <textarea name="whatsapp_menu_template" rows="4" class="form-control"><?php echo escape_output($s->whatsapp_menu_template); ?></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo lang('mess_template_due'); ?></label>
                    <textarea name="whatsapp_due_template" rows="4" class="form-control"><?php echo escape_output($s->whatsapp_due_template); ?></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo lang('mess_template_expiry'); ?></label>
                    <textarea name="whatsapp_expiry_template" rows="4" class="form-control"><?php echo escape_output($s->whatsapp_expiry_template); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-2 mb-2">
            <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
        </div>
    </div>
    <?php echo form_close(); ?>
</section>
