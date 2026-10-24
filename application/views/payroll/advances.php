<?php
$date_format = $this->session->userdata('date_format');
$t = array('given' => 0, 'recovered' => 0, 'balance' => 0);
foreach ($balances as $b) {
    $t['given'] += $b->given;
    $t['recovered'] += $b->recovered;
    $t['balance'] += $b->balance;
}
?>
<section class="main-content-wrapper">
    <?php $this->load->view('payroll/_top', array('pay_tab' => 'advances')); ?>

    <div class="pay-head">
        <div>
            <h3><?php echo lang('payroll_advance_balances'); ?></h3>
            <div class="pay-sub"><?php echo lang('payroll_advance_balances_hint'); ?></div>
        </div>
        <div class="pay-head-actions">
            <?php if (checkAccess(421, 'add')): ?>
                <a class="pay-btn primary" href="<?php echo base_url(); ?>Payroll_setup/adjustments"><i class="fa fa-plus"></i> <?php echo lang('payroll_give_advance'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('payroll_advance_balances'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th><?php echo lang('employee'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_advances'); ?></th>
                            <th><?php echo lang('payroll_last_advance'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_given'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_recovered'); ?></th>
                            <th class="text-end"><?php echo lang('payroll_balance'); ?></th>
                            <th class="not-export-col"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($balances as $b): ?>
                        <tr>
                            <td><span class="name"><?php echo escape_output($b->full_name); ?></span><span class="pay-muted d-block"><?php echo escape_output($b->designation); ?></span></td>
                            <td class="pay-num"><?php echo (int) $b->advances; ?></td>
                            <td data-order="<?php echo escape_output($b->last_date); ?>"><?php echo escape_output(date($date_format, strtotime($b->last_date))); ?></td>
                            <td class="pay-num"><?php echo getAmtPCustom($b->given); ?></td>
                            <td class="pay-num"><?php echo getAmtPCustom($b->recovered); ?></td>
                            <td class="pay-num <?php echo $b->balance > 0.0005 ? 'pay-neg' : 'pay-pos'; ?>"><?php echo getAmtPCustom($b->balance); ?></td>
                            <td><a class="pay-btn sm" href="<?php echo base_url(); ?>Payroll/salaryHistory?employee_id=<?php echo (int) $b->employee_id; ?>"><i class="fa fa-history"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php echo lang('total'); ?></th><th></th><th></th>
                            <th class="text-end"><?php echo getAmtPCustom($t['given']); ?></th>
                            <th class="text-end"><?php echo getAmtPCustom($t['recovered']); ?></th>
                            <th class="text-end"><?php echo getAmtPCustom($t['balance']); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="pay-muted"><?php echo lang('payroll_advance_balances_note'); ?></div>
</section>
<?php $this->load->view('payroll/_datatable'); ?>
