<?php
$can_edit = checkAccess(418, 'update');
$currency = $this->session->userdata('currency');
$date_format = $this->session->userdata('date_format');
$set = 0;
foreach ($employees as $e) {
    if ($e->salary_id) {
        $set++;
    }
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'salaries')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_salary_setup'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_salary_setup_hint'); ?></div>
        </div>
        <div class="pay-head-actions">
            <span class="pay-badge <?php echo $set == count($employees) ? 'paid' : 'partial'; ?>"><?php echo sprintf(lang('payroll_n_of_m_set'), $set, count($employees)); ?></span>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_salary_setup'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('employee'); ?></th>
                            <th><?php echo lang('payroll_salary_type'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_basic_rate'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_allowances'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_gross_month'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_fixed_deduction'); ?></th>
                            <th><?php echo lang('payroll_weekly_off'); ?></th>
                            <th><?php echo lang('payroll_joining_date'); ?></th>
                            <th><?php echo lang('payroll_payment_mode'); ?></th>
                            <th><?php echo lang('status'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = count($employees); foreach ($employees as $e):
                            $allow = (float) $e->housing_allowance + (float) $e->transport_allowance + (float) $e->food_allowance + (float) $e->other_allowance;
                        ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $n--; ?></td>
                            <td><span class="name"><?php echo escape_output($e->full_name); ?></span>
                                <span class="pay-muted d-block"><?php echo escape_output($e->designation); ?><?php echo $e->will_login == 'No' ? ' &middot; ' . lang('payroll_no_login') : ''; ?></span></td>
                            <?php if ($e->salary_id): ?>
                                <td><span class="pay-badge <?php echo $e->salary_type; ?>"><?php echo lang('payroll_type_' . $e->salary_type); ?></span></td>
                                <td class="pay-num"><?php echo getAmtPCustom($e->basic_salary); ?><span class="pay-muted"> /<?php echo lang('payroll_per_' . $e->salary_type); ?></span></td>
                                <td class="pay-num"><?php echo getAmtPCustom($allow); ?></td>
                                <td class="pay-num"><b><?php echo $e->salary_type == 'monthly' ? getAmtPCustom($this->Payroll_model->monthlyGross($e)) : '&ndash;'; ?></b></td>
                                <td class="pay-num"><?php echo getAmtPCustom($e->fixed_deduction); ?></td>
                                <td><?php echo escape_output($this->Payroll_model->weekdayNames($e->weekly_off)) ?: '<span class="pay-muted">&ndash;</span>'; ?></td>
                                <td><?php echo $e->joining_date ? escape_output(date($date_format, strtotime($e->joining_date))) : '<span class="pay-muted">&ndash;</span>'; ?>
                                    <?php if ($e->leaving_date): ?><span class="pay-muted d-block"><?php echo lang('payroll_left'); ?> <?php echo escape_output(date($date_format, strtotime($e->leaving_date))); ?></span><?php endif; ?></td>
                                <td><?php echo lang('payroll_mode_' . $e->payment_mode); ?></td>
                                <td><span class="pay-badge <?php echo $e->salary_status; ?>"><?php echo lang('payroll_status_' . $e->salary_status); ?></span></td>
                            <?php else: ?>
                                <td><span class="pay-badge notset"><?php echo lang('payroll_not_set'); ?></span></td>
                                <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($can_edit): ?>
                                    <a class="pay-btn sm <?php echo $e->salary_id ? '' : 'primary'; ?>" href="<?php echo base_url(); ?>Payroll_setup/editSalary/<?php echo (int) $e->id; ?>">
                                        <i class="fa fa-<?php echo $e->salary_id ? 'pencil' : 'plus'; ?>"></i> <?php echo $e->salary_id ? lang('edit') : lang('payroll_set_up'); ?></a>
                                <?php endif; ?>
                                <?php if (Payroll_model::screenOn('reports') && checkAccess(433, 'view') && $e->salary_id): ?>
                                    <a class="pay-btn sm" href="<?php echo base_url(); ?>Payroll/salaryHistory?employee_id=<?php echo (int) $e->id; ?>"><i class="fa fa-history"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="pay-muted"><?php echo lang('payroll_employees_from_users'); ?></div>
</section>
<?php $this->load->view('payroll/_datatable'); ?>
