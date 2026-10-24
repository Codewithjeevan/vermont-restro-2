<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(429, 'add');
$can_delete = checkAccess(429, 'delete');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'holidays')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_holidays'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_holidays_hint'); ?></div>
        </div>
    </div>

    <?php if ($can_add): ?>
    <div class="pay-card pay-form">
        <h4><?php echo lang('payroll_add_holiday'); ?></h4>
        <?php echo form_open(base_url('Payroll_setup/addHoliday')); ?>
        <div class="row">
            <div class="col-sm-6 col-md-3 col-lg-2 form-group">
                <label><?php echo lang('payroll_from_date'); ?> <span class="required_star">*</span></label>
                <input type="text" name="holiday_date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2 form-group">
                <label><?php echo lang('payroll_to_date'); ?></label>
                <input type="text" name="to_date" class="form-control customDatepicker" readonly placeholder="<?php echo escape_output(lang('payroll_same_day')); ?>">
            </div>
            <div class="col-sm-12 col-md-6 col-lg-5 form-group">
                <label><?php echo lang('name'); ?> <span class="required_star">*</span></label>
                <input type="text" name="name" class="form-control" maxlength="100" placeholder="<?php echo escape_output(lang('payroll_holiday_example')); ?>">
            </div>
            <div class="col-sm-12 col-lg-3 form-group">
                <label class="d-block">&nbsp;</label>
                <button type="submit" class="pay-btn primary w-100 justify-content-center"><i class="fa fa-plus"></i> <?php echo lang('add'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
    <?php endif; ?>

    <form method="get" action="<?php echo base_url(); ?>Payroll_setup/holidays" class="pay-filter">
        <div>
            <label><?php echo lang('year'); ?></label>
            <select name="year" class="form-control js-autosubmit">
                <?php for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 4; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </form>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_holidays') . ' ' . $year; ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th><?php echo lang('date'); ?></th>
                            <th><?php echo lang('payroll_day'); ?></th>
                            <th><?php echo lang('name'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($holidays as $h): ?>
                        <tr>
                            <td data-order="<?php echo escape_output($h->holiday_date); ?>"><?php echo escape_output(date($date_format, strtotime($h->holiday_date))); ?></td>
                            <td><?php echo escape_output(date('l', strtotime($h->holiday_date))); ?></td>
                            <td class="name"><?php echo escape_output($h->name); ?></td>
                            <td>
                                <?php if ($can_delete): ?>
                                    <a class="pay-btn sm danger pay-confirm" href="<?php echo base_url(); ?>Payroll_setup/deleteHoliday/<?php echo (int) $h->id; ?>"><i class="fa fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php $this->load->view('payroll/_datatable'); ?>
