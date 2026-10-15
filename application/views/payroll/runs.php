<?php
$currency = $this->session->userdata('currency');
$can_generate = checkAccess(412, 'add');
$can_delete = checkAccess(412, 'delete');
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));
$default_month = $previous_run ? $this_month : $last_month;
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'runs')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_runs'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_runs_hint'); ?></div>
        </div>
    </div>

    <div class="pay-tiles">
        <a class="pay-tile <?php echo $employees_setup ? 'good' : 'warn'; ?>" href="<?php echo base_url(); ?>Payroll_setup/salaries">
            <div class="num"><?php echo (int) $employees_setup; ?> / <?php echo (int) $employees_total; ?></div>
            <div class="lbl"><?php echo lang('payroll_employees_on_payroll'); ?></div>
            <div class="hint"><?php echo lang('payroll_employees_on_payroll_hint'); ?></div>
        </a>
        <div class="pay-tile">
            <div class="num"><?php echo escape_output($currency); ?> <?php echo getAmtPCustom($monthly_gross); ?></div>
            <div class="lbl"><?php echo lang('payroll_monthly_gross'); ?></div>
            <div class="hint"><?php echo lang('payroll_monthly_gross_hint'); ?></div>
        </div>
        <a class="pay-tile <?php echo $previous_run ? ($previous_run->status == 'approved' ? 'good' : 'warn') : 'bad'; ?>" href="<?php echo $previous_run ? base_url() . 'Payroll/run/' . $previous_run->id : '#generate'; ?>">
            <div class="num"><?php echo escape_output(date('M Y', strtotime($last_month . '-01'))); ?></div>
            <div class="lbl"><?php echo $previous_run ? lang('payroll_status_' . $previous_run->status) : lang('payroll_not_generated'); ?></div>
            <div class="hint"><?php echo $previous_run ? escape_output($currency) . ' ' . getAmtPCustom($previous_run->total_net - $previous_run->total_paid) . ' ' . lang('payroll_balance_to_pay') : lang('payroll_last_month'); ?></div>
        </a>
        <?php if (Payroll_model::screenOn('adjustments')): ?>
        <a class="pay-tile <?php echo $advances_open > 0 ? 'warn' : ''; ?>" href="<?php echo Payroll_model::screenOn('reports') ? base_url() . 'Payroll/advances' : base_url() . 'Payroll_setup/adjustments'; ?>">
            <div class="num"><?php echo escape_output($currency); ?> <?php echo getAmtPCustom($advances_open); ?></div>
            <div class="lbl"><?php echo lang('payroll_advances_open'); ?></div>
            <div class="hint"><?php echo lang('payroll_advances_open_hint'); ?></div>
        </a>
        <?php endif; ?>
    </div>

    <?php if (!$employees_setup): ?>
        <div class="pay-warning"><?php echo lang('payroll_no_setup_yet'); ?> <a href="<?php echo base_url(); ?>Payroll_setup/salaries"><?php echo lang('payroll_salary_setup'); ?></a></div>
    <?php endif; ?>

    <?php if ($can_generate): ?>
    <div class="pay-card" id="generate">
        <h4><?php echo lang('payroll_generate_title'); ?></h4>
        <?php echo form_open(base_url('Payroll/generate'), array('class' => 'pay-filter')); ?>
            <div>
                <label><?php echo lang('month'); ?></label>
                <select name="month" class="form-control">
                    <?php foreach ($month_options as $m => $label): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $default_month ? 'selected' : ''; ?>><?php echo escape_output($label); ?><?php echo $m == $this_month ? ' (' . lang('payroll_running') . ')' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="pay-btn primary"><i class="fa fa-cogs"></i> <?php echo lang('payroll_generate'); ?></button>
            </div>
        <?php echo form_close(); ?>
        <div class="pay-muted"><?php echo lang('payroll_generate_hint'); ?></div>
    </div>
    <?php endif; ?>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_runs'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th><?php echo lang('month'); ?></th>
                            <th><?php echo lang('status'); ?></th>
                            <th class="text-end"><?php echo lang('employees'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_earnings'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_deductions'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_net_salary'); ?></th>
                            <th class="text-end"><?php echo lang('paid'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_balance'); ?></th>
                            <th><?php echo lang('payroll_generated_by'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($runs as $r): $balance = $r->total_net - $r->total_paid; ?>
                        <tr>
                            <td data-order="<?php echo $r->pay_month; ?>"><a class="pay-link" href="<?php echo base_url(); ?>Payroll/run/<?php echo (int) $r->id; ?>"><?php echo escape_output($this->Payroll_model->monthLabel($r->pay_month)); ?></a></td>
                            <td>
                                <span class="pay-badge <?php echo $r->status; ?>"><?php echo lang('payroll_status_' . $r->status); ?></span>
                                <?php if ($r->status == 'approved'): ?>
                                    <span class="pay-badge <?php echo $balance <= 0.0005 ? 'paid' : ($r->total_paid > 0 ? 'partial' : 'unpaid'); ?>"><?php echo $balance <= 0.0005 ? lang('paid') : ($r->total_paid > 0 ? lang('payroll_partial') : lang('payroll_unpaid')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="pay-num"><?php echo (int) $r->employees; ?></td>
                            <td class="pay-num"><?php echo getAmtPCustom($r->total_earnings); ?></td>
                            <td class="pay-num"><?php echo getAmtPCustom($r->total_deductions); ?></td>
                            <td class="pay-num"><b><?php echo getAmtPCustom($r->total_net); ?></b></td>
                            <td class="pay-num"><?php echo getAmtPCustom($r->total_paid); ?></td>
                            <td class="pay-num <?php echo $balance > 0.0005 ? 'pay-neg' : ''; ?>"><?php echo getAmtPCustom($balance); ?></td>
                            <td><?php echo escape_output($r->generated_by_name); ?><span class="pay-muted d-block"><?php echo escape_output(date($this->session->userdata('date_format'), strtotime($r->generated_at))); ?></span></td>
                            <td>
                                <div class="pay-actions">
                                    <a class="pay-btn sm" href="<?php echo base_url(); ?>Payroll/run/<?php echo (int) $r->id; ?>"><i class="fa fa-eye"></i> <?php echo lang('payroll_open'); ?></a>
                                    <?php if ($can_delete && $r->status == 'draft'): ?>
                                        <a class="pay-btn sm danger pay-confirm" href="<?php echo base_url(); ?>Payroll/deleteRun/<?php echo (int) $r->id; ?>"><i class="fa fa-trash"></i></a>
                                    <?php endif; ?>
                                </div>
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
