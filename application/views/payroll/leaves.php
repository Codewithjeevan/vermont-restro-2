<?php
$date_format = $this->session->userdata('date_format');
$can_add = checkAccess(425, 'add');
$can_delete = checkAccess(425, 'delete');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'leaves')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_leaves'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_leaves_hint'); ?></div>
        </div>
    </div>

    <?php if ($can_add): ?>
    <div class="pay-card pay-form">
        <h4><?php echo lang('payroll_add_leave'); ?></h4>
        <?php echo form_open(base_url('Payroll_setup/addLeave')); ?>
        <div class="row">
            <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                <label><?php echo lang('employee'); ?> <span class="required_star">*</span></label>
                <select name="employee_id" class="form-control select2">
                    <option value=""><?php echo lang('select'); ?></option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?php echo (int) $e->id; ?>"><?php echo escape_output($e->full_name); ?><?php echo $e->designation ? ' (' . escape_output($e->designation) . ')' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2 form-group">
                <label><?php echo lang('payroll_from_date'); ?> <span class="required_star">*</span></label>
                <input type="text" name="from_date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2 form-group">
                <label><?php echo lang('payroll_to_date'); ?> <span class="required_star">*</span></label>
                <input type="text" name="to_date" class="form-control customDatepicker" readonly value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-sm-12 col-md-6 col-lg-2 form-group">
                <label><?php echo lang('type'); ?></label>
                <select name="leave_type" class="form-control">
                    <option value="paid"><?php echo lang('payroll_leave_paid'); ?></option>
                    <option value="unpaid"><?php echo lang('payroll_leave_unpaid'); ?></option>
                </select>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-3 form-group">
                <label><?php echo lang('payroll_reason'); ?></label>
                <input type="text" name="reason" class="form-control" maxlength="255">
            </div>
            <div class="col-sm-12 col-lg-2 form-group">
                <button type="submit" class="pay-btn primary w-100 justify-content-center"><i class="fa fa-plus"></i> <?php echo lang('add'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
        <div class="pay-muted"><?php echo lang('payroll_leave_rule'); ?></div>
    </div>
    <?php endif; ?>

    <form method="get" action="<?php echo base_url(); ?>Payroll_setup/leaves" class="pay-filter">
        <div>
            <label><?php echo lang('year'); ?></label>
            <select name="year" class="form-control js-autosubmit">
                <?php for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 4; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label><?php echo lang('employee'); ?></label>
            <select name="employee_id" class="form-control js-autosubmit">
                <option value=""><?php echo lang('payroll_all_employees'); ?></option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?php echo (int) $e->id; ?>" <?php echo $employee_id == $e->id ? 'selected' : ''; ?>><?php echo escape_output($e->full_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_leaves'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th><?php echo lang('payroll_from_date'); ?></th>
                            <th><?php echo lang('payroll_to_date'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_calendar_days'); ?></th>
                            <th><?php echo lang('employee'); ?></th>
                            <th><?php echo lang('type'); ?></th>
                            <th><?php echo lang('payroll_reason'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leaves as $l): $days = (int) round((strtotime($l->to_date) - strtotime($l->from_date)) / 86400) + 1; ?>
                        <tr>
                            <td data-order="<?php echo escape_output($l->from_date); ?>"><?php echo escape_output(date($date_format, strtotime($l->from_date))); ?></td>
                            <td><?php echo escape_output(date($date_format, strtotime($l->to_date))); ?></td>
                            <td class="pay-num"><?php echo $days; ?></td>
                            <td class="name"><?php echo escape_output($l->full_name); ?></td>
                            <td><span class="pay-badge <?php echo $l->leave_type == 'paid' ? 'paid' : 'unpaid'; ?>"><?php echo lang('payroll_leave_' . $l->leave_type); ?></span></td>
                            <td><?php echo escape_output($l->reason); ?></td>
                            <td>
                                <?php if ($can_delete): ?>
                                    <a class="pay-btn sm danger pay-confirm" href="<?php echo base_url(); ?>Payroll_setup/deleteLeave/<?php echo (int) $l->id; ?>"><i class="fa fa-trash"></i></a>
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
