<link rel="stylesheet" href="<?php echo base_url(); ?>frequent_changing/css/opening_stock.css?v=<?php echo @filemtime(FCPATH . 'frequent_changing/css/opening_stock.css'); ?>">
<input type="hidden" id="opening_stock_invalid" value="<?php echo lang('opening_stock_invalid'); ?>">
<input type="hidden" id="opening_stock_no_changes" value="<?php echo lang('opening_stock_no_changes'); ?>">

<section class="main-content-wrapper">
    <?php
    if ($this->session->flashdata('exception')) {
        echo '<section class="alert-wrapper"><div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="alert-body"><p><i class="m-right fa fa-check"></i>';
        echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
        echo '</p></div></div></section>';
    }
    if ($this->session->flashdata('exception_er')) {
        echo '<section class="alert-wrapper"><div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="alert-body"><p>';
        echo escape_output($this->session->flashdata('exception_er'));unset($_SESSION['exception_er']);
        echo '</p></div></div></section>';
    }
    ?>

    <section class="content-header">
        <h3 class="top-left-header"><?php echo lang('opening_stock'); ?></h3>
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <?php if (!$opening_enabled): ?>
                <div class="alert alert-danger"><?php echo lang('opening_stock_migration_needed'); ?></div>
            <?php else: ?>
                <?php echo form_open(base_url() . 'Inventory/openingStock', array('id' => 'opening_stock_form')); ?>
                <input type="hidden" name="opening_rows" id="opening_rows" value="">
                <p class="opening_help"><?php echo lang('opening_stock_help'); ?></p>
                <div class="row">
                    <div class="col-sm-12 mb-3 col-md-4 col-lg-3">
                        <div class="form-group">
                            <label><?php echo lang('outlet'); ?></label>
                            <input type="text" class="form-control" readonly value="<?php echo escape_output(getOutletNameById($this->session->userdata('outlet_id'))); ?>">
                        </div>
                    </div>
                    <div class="col-sm-12 mb-3 col-md-4 col-lg-3">
                        <div class="form-group">
                            <label><?php echo lang('opening_date'); ?> <span class="required_star">*</span></label>
                            <input type="text" name="opening_date" id="opening_date" readonly class="form-control customDatepicker" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                    </div>
                    <div class="col-sm-12 mb-3 col-md-4 col-lg-3">
                        <div class="form-group">
                            <label><?php echo lang('category'); ?></label>
                            <select class="form-control select2 ir_w_100" id="opening_category">
                                <option value=""><?php echo lang('all'); ?></option>
                                <?php foreach ($categories as $category_name): ?>
                                    <option value="<?php echo escape_output($category_name); ?>"><?php echo escape_output($category_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12 mb-3 col-md-4 col-lg-3">
                        <div class="form-group">
                            <label><?php echo lang('ingredient'); ?></label>
                            <input type="text" class="form-control" id="opening_search" autocomplete="off" placeholder="<?php echo lang('ingredient'); ?>">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table opening_stock_table">
                        <thead>
                        <tr>
                            <th><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('ingredient'); ?>(<?php echo lang('code'); ?>)</th>
                            <th><?php echo lang('category'); ?></th>
                            <th><?php echo lang('purchase_unit'); ?></th>
                            <th class="opening_number"><?php echo lang('conversion_rate'); ?></th>
                            <th class="opening_number"><?php echo lang('current_stock'); ?></th>
                            <th class="opening_input_col"><?php echo lang('opening_stock'); ?></th>
                            <th><?php echo lang('opening_date'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $key => $row): ?>
                            <tr class="opening_row"
                                data-category="<?php echo escape_output($row->category_name); ?>"
                                data-search="<?php echo escape_output(strtolower($row->name . ' ' . $row->code)); ?>">
                                <td><?php echo $key + 1; ?></td>
                                <td><?php echo escape_output($row->name . '(' . $row->code . ')'); ?></td>
                                <td><?php echo escape_output($row->category_name); ?></td>
                                <td><?php echo escape_output($row->purchase_unit); ?></td>
                                <td class="opening_number"><?php echo $row->conversion_rate > 0 ? (float)$row->conversion_rate : ''; ?></td>
                                <td class="opening_number <?php echo $row->current_stock < 0 ? 'opening_negative' : ''; ?>"><?php echo (float)$row->current_stock; ?></td>
                                <td class="opening_input_col">
                                    <?php if ($row->conversion_rate > 0): ?>
                                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control opening_qty"
                                               data-id="<?php echo escape_output($row->id); ?>"
                                               data-initial="<?php echo escape_output($row->opening_qty); ?>"
                                               value="<?php echo escape_output($row->opening_qty); ?>">
                                    <?php else: ?>
                                        <span class="opening_no_rate"><?php echo lang('opening_stock_no_conversion'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row->opening_date ? escape_output(date($this->session->userdata('date_format'), strtotime($row->opening_date))) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="opening_error" id="opening_error"></p>
                <div class="box-footer p-0 mt-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-2 mb-2">
                            <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                        </div>
                        <div class="col-sm-12 col-md-2 mb-2">
                            <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?>Inventory/index"><?php echo lang('back'); ?></a>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<script src="<?php echo base_url(); ?>frequent_changing/js/opening_stock.js?v=<?php echo @filemtime(FCPATH . 'frequent_changing/js/opening_stock.js'); ?>"></script>
