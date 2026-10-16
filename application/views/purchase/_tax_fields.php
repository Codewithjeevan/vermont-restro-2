<?php
//overall purchase tax (addPurchase + editPurchase): subtotal, tax type, tax %, tax amount.
//Values: what was typed when validation failed, else the saved purchase, else the company tax settings.
if (empty($tax_enabled)) {
    return;
}
$saved_tax = isset($purchase_details->tax_type);
$tax_type_value = set_value('tax_type', $saved_tax ? $purchase_details->tax_type : $default_tax['tax_type']);
$tax_percent_value = set_value('tax_percent', $saved_tax ? $purchase_details->tax_percent : $default_tax['tax_percent']);
?>
<div class="col-md-8"></div>
<div class="col-md-3">
    <div class="form-group">
        <label><?php echo lang('subtotal'); ?></label>
        <input class="form-control" readonly type="text" id="subtotal"
               value="<?php echo $saved_tax ? escape_output($purchase_details->subtotal) : ''; ?>">
    </div>
</div>
<div class="col-md-1"></div>
<div class="clearfix"></div>
<div class="col-md-8"></div>
<div class="col-md-3">
    <div class="form-group">
        <label><?php echo lang('tax_type'); ?> <span class="required_star">*</span></label>
        <select class="form-control select2 ir_w_100" name="tax_type" id="tax_type">
            <option value="1" <?php echo $tax_type_value == 1 ? 'selected' : ''; ?>><?php echo lang('exclusive_tax'); ?></option>
            <option value="2" <?php echo $tax_type_value == 2 ? 'selected' : ''; ?>><?php echo lang('inclusive_tax'); ?></option>
        </select>
    </div>
    <?php if (form_error('tax_type')) { ?>
    <div class="callout callout-danger my-2">
        <?php echo form_error('tax_type'); ?>
    </div>
    <?php } ?>
</div>
<div class="col-md-1"></div>
<div class="clearfix"></div>
<div class="col-md-8"></div>
<div class="col-md-3">
    <div class="form-group">
        <label><?php echo lang('vat'); ?> (%) <span class="required_star">*</span></label>
        <input class="form-control integerchk" type="text" name="tax_percent" id="tax_percent"
               onfocus="this.select();" value="<?php echo escape_output($tax_percent_value); ?>">
    </div>
    <?php if (form_error('tax_percent')) { ?>
    <div class="callout callout-danger my-2">
        <?php echo form_error('tax_percent'); ?>
    </div>
    <?php } ?>
</div>
<div class="col-md-1"></div>
<div class="clearfix"></div>
<div class="col-md-8"></div>
<div class="col-md-3">
    <div class="form-group">
        <label><?php echo lang('tax_amount'); ?></label>
        <input class="form-control" readonly type="text" id="tax_amount"
               value="<?php echo $saved_tax ? escape_output($purchase_details->tax_amount) : ''; ?>">
    </div>
</div>
<div class="col-md-1"></div>
<div class="clearfix"></div>
