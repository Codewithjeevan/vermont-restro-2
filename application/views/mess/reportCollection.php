<?php
$date_format = $this->session->userdata('date_format');
$col_totals = array();
$grand = 0;
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'reports')); ?>
    <div class="mess-head">
        <div><h3><?php echo lang('mess_collection_report'); ?></h3></div>
        <form method="get" action="<?php echo base_url(); ?>Mess_report/collection" class="mess-filter">
            <input type="text" name="from" class="form-control customDatepicker" readonly value="<?php echo escape_output($from); ?>" style="width:130px">
            <input type="text" name="to" class="form-control customDatepicker" readonly value="<?php echo escape_output($to); ?>" style="width:130px">
            <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            <button type="button" class="mess-btn" onclick="window.print()"><i class="fa fa-print"></i></button>
        </form>
    </div>
    <?php $this->load->view('mess/_reportNav'); ?>
    <div class="mess-card">
        <h4><?php echo lang('mess_by_method'); ?></h4>
        <?php if (!$grid): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="mess-table">
            <thead><tr>
                <th><?php echo lang('date'); ?></th>
                <?php foreach ($methods as $name): ?><th class="mess-num"><?php echo escape_output($name); ?></th><?php endforeach; ?>
                <th class="mess-num"><?php echo lang('total'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($grid as $day => $vals):
                $row_total = 0; ?>
                <tr>
                    <td><?php echo escape_output(date($date_format, strtotime($day))); ?></td>
                    <?php foreach ($methods as $mid => $name):
                        $v = isset($vals[$mid]) ? $vals[$mid] : 0;
                        $row_total += $v;
                        $col_totals[$mid] = (isset($col_totals[$mid]) ? $col_totals[$mid] : 0) + $v; ?>
                        <td class="mess-num"><?php echo $v ? getAmtPCustom($v) : '-'; ?></td>
                    <?php endforeach; $grand += $row_total; ?>
                    <td class="mess-num"><b><?php echo getAmtPCustom($row_total); ?></b></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td><?php echo lang('total'); ?></td>
                <?php foreach ($methods as $mid => $name): ?><td class="mess-num"><?php echo getAmtPCustom($col_totals[$mid]); ?></td><?php endforeach; ?>
                <td class="mess-num"><?php echo getAmtPCustom($grand); ?></td>
            </tr></tfoot>
        </table>
        </div>
        <?php endif; ?>
        <?php if ($adjust && ($adjust->credit || $adjust->debit)): ?>
            <div class="mess-hint"><?php echo lang('mess_adjustment'); ?>: <?php echo lang('mess_adjust_credit'); ?> <?php echo getAmtPCustom($adjust->credit); ?> &middot; <?php echo lang('mess_adjust_debit'); ?> <?php echo getAmtPCustom($adjust->debit); ?></div>
        <?php endif; ?>
    </div>
    <?php if ($by_user): ?>
    <div class="mess-card">
        <h4><?php echo lang('user'); ?></h4>
        <table class="mess-table">
            <tbody>
            <?php foreach ($by_user as $u): ?>
                <tr><td class="name"><?php echo escape_output($u->full_name); ?></td><td class="mess-num"><?php echo (int) $u->cnt; ?> <?php echo lang('mess_payments_count'); ?></td><td class="mess-num"><b><?php echo getAmtPCustom($u->total); ?></b></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
