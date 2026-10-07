<?php
$date_format = $this->session->userdata('date_format');
$t = array('expected' => 0, 'served' => 0, 'cooked' => 0, 'leftover' => 0, 'food' => 0, 'waste' => 0);
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'reports')); ?>
    <div class="mess-head">
        <div><h3><?php echo lang('mess_day_close_report'); ?></h3></div>
        <form method="get" action="<?php echo base_url(); ?>Mess_report/dayCloses" class="mess-filter">
            <input type="text" name="from" class="form-control customDatepicker" readonly value="<?php echo escape_output($from); ?>" style="width:130px">
            <input type="text" name="to" class="form-control customDatepicker" readonly value="<?php echo escape_output($to); ?>" style="width:130px">
            <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            <button type="button" class="mess-btn" onclick="window.print()"><i class="fa fa-print"></i></button>
        </form>
    </div>
    <?php $this->load->view('mess/_reportNav'); ?>
    <div class="mess-card">
        <?php if (!$closes): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="mess-table">
            <thead><tr>
                <th><?php echo lang('date'); ?></th><th><?php echo lang('mess_slot'); ?></th>
                <th class="mess-num"><?php echo lang('mess_expected'); ?></th><th class="mess-num"><?php echo lang('mess_meals_served'); ?></th>
                <th class="mess-num"><?php echo lang('mess_cooked'); ?></th><th class="mess-num"><?php echo lang('mess_leftover'); ?></th>
                <th class="mess-num"><?php echo lang('mess_food_cost'); ?></th><th class="mess-num"><?php echo lang('mess_cost_per_meal'); ?></th>
                <th class="mess-num"><?php echo lang('mess_waste_cost'); ?></th><th><?php echo lang('mess_closed_by'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($closes as $c):
                $t['expected'] += $c->expected; $t['served'] += $c->served; $t['cooked'] += $c->cooked; $t['leftover'] += $c->leftover; $t['food'] += $c->food_cost; $t['waste'] += $c->waste_cost; ?>
                <tr>
                    <td><a class="name" href="<?php echo base_url(); ?>Mess_daily/dayClose/<?php echo escape_output($c->close_date); ?>/<?php echo (int) $c->slot; ?>"><?php echo escape_output(date('D', strtotime($c->close_date)) . ' ' . date($date_format, strtotime($c->close_date))); ?></a></td>
                    <td><?php echo isset($slots[$c->slot]) ? escape_output($slots[$c->slot]->name) : (int) $c->slot; ?></td>
                    <td class="mess-num"><?php echo (int) $c->expected; ?></td>
                    <td class="mess-num"><?php echo (int) $c->served; ?></td>
                    <td class="mess-num"><?php echo (float) $c->cooked; ?></td>
                    <td class="mess-num <?php echo $c->leftover > 0 ? 'mess-due' : ''; ?>"><?php echo (float) $c->leftover; ?></td>
                    <td class="mess-num"><?php echo getAmtPCustom($c->food_cost); ?></td>
                    <td class="mess-num"><?php echo $c->served > 0 ? getAmtPCustom($c->food_cost / $c->served) : '-'; ?></td>
                    <td class="mess-num"><?php echo getAmtPCustom($c->waste_cost); ?></td>
                    <td><?php echo escape_output($c->closed_by_name); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="2"><?php echo lang('total'); ?></td>
                <td class="mess-num"><?php echo (int) $t['expected']; ?></td><td class="mess-num"><?php echo (int) $t['served']; ?></td>
                <td class="mess-num"><?php echo (float) $t['cooked']; ?></td><td class="mess-num"><?php echo (float) $t['leftover']; ?></td>
                <td class="mess-num"><?php echo getAmtPCustom($t['food']); ?></td>
                <td class="mess-num"><?php echo $t['served'] > 0 ? getAmtPCustom($t['food'] / $t['served']) : '-'; ?></td>
                <td class="mess-num"><?php echo getAmtPCustom($t['waste']); ?></td><td></td>
            </tr></tfoot>
        </table>
        </div>
        <?php endif; ?>
    </div>
</section>
