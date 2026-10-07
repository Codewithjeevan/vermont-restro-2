<?php
$date_format = $this->session->userdata('date_format');
$t = array('expected' => 0, 'served' => 0, 'not_taken' => 0, 'skipped' => 0, 'pending' => 0, 'extras' => 0);
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'reports')); ?>
    <div class="mess-head">
        <div><h3><?php echo lang('mess_daily_served_summary'); ?></h3></div>
        <form method="get" action="<?php echo base_url(); ?>Mess_report/dailySummary" class="mess-filter">
            <input type="text" name="from" class="form-control customDatepicker" readonly value="<?php echo escape_output($from); ?>" style="width:130px">
            <input type="text" name="to" class="form-control customDatepicker" readonly value="<?php echo escape_output($to); ?>" style="width:130px">
            <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            <button type="button" class="mess-btn" onclick="window.print()"><i class="fa fa-print"></i></button>
        </form>
    </div>
    <?php $this->load->view('mess/_reportNav'); ?>
    <div class="mess-card">
        <?php if (!$rows): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="mess-table">
            <thead><tr>
                <th><?php echo lang('date'); ?></th><th><?php echo lang('mess_slot'); ?></th>
                <th class="mess-num"><?php echo lang('mess_expected'); ?></th><th class="mess-num"><?php echo lang('mess_served'); ?></th>
                <th class="mess-num"><?php echo lang('mess_not_taken'); ?></th><th class="mess-num"><?php echo lang('mess_skipped'); ?></th>
                <th class="mess-num"><?php echo lang('mess_pending'); ?></th><th class="mess-num"><?php echo lang('mess_veg'); ?></th>
                <th class="mess-num"><?php echo lang('mess_delivery'); ?></th><th class="mess-num"><?php echo lang('mess_extras'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $e = isset($extras[$r->att_date . '_' . $r->slot]) ? $extras[$r->att_date . '_' . $r->slot] : null;
                $t['expected'] += $r->expected; $t['served'] += $r->served; $t['not_taken'] += $r->not_taken; $t['skipped'] += $r->skipped; $t['pending'] += $r->pending;
                $t['extras'] += $e ? $e->qty : 0; ?>
                <tr>
                    <td><?php echo escape_output(date('D', strtotime($r->att_date)) . ' ' . date($date_format, strtotime($r->att_date))); ?></td>
                    <td><?php echo isset($slots[$r->slot]) ? escape_output($slots[$r->slot]->name) : (int) $r->slot; ?></td>
                    <td class="mess-num"><b><?php echo (int) $r->expected; ?></b></td>
                    <td class="mess-num mess-credit"><?php echo (int) $r->served; ?></td>
                    <td class="mess-num <?php echo $r->not_taken ? 'mess-due' : ''; ?>"><?php echo (int) $r->not_taken; ?></td>
                    <td class="mess-num"><?php echo (int) $r->skipped; ?></td>
                    <td class="mess-num"><?php echo (int) $r->pending; ?></td>
                    <td class="mess-num"><?php echo (int) $r->veg; ?></td>
                    <td class="mess-num"><?php echo (int) $r->delivery; ?></td>
                    <td class="mess-num"><?php echo $e ? (float) $e->qty : 0; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="2"><?php echo lang('total'); ?></td>
                <td class="mess-num"><?php echo (int) $t['expected']; ?></td><td class="mess-num"><?php echo (int) $t['served']; ?></td>
                <td class="mess-num"><?php echo (int) $t['not_taken']; ?></td><td class="mess-num"><?php echo (int) $t['skipped']; ?></td>
                <td class="mess-num"><?php echo (int) $t['pending']; ?></td><td></td><td></td><td class="mess-num"><?php echo (float) $t['extras']; ?></td>
            </tr></tfoot>
        </table>
        </div>
        <?php endif; ?>
    </div>
</section>
