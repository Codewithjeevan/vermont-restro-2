<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'reports')); ?>
    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_attendance_matrix'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_attendance_matrix_hint'); ?></div>
        </div>
        <form method="get" action="<?php echo base_url(); ?>Mess_report/attendanceMatrix" class="mess-filter">
            <input type="month" name="month" class="form-control" value="<?php echo escape_output($month); ?>" style="width:160px">
            <select name="slot" class="form-control" style="width:auto">
                <?php foreach ($slots as $s): ?>
                    <option value="<?php echo (int) $s->slot; ?>" <?php echo $s->slot == $slot ? 'selected' : ''; ?>><?php echo escape_output($s->name); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="mess-btn"><?php echo lang('submit'); ?></button>
            <button type="button" class="mess-btn" onclick="window.print()"><i class="fa fa-print"></i></button>
        </form>
    </div>
    <?php $this->load->view('mess/_reportNav'); ?>
    <div class="mess-card">
        <?php if (!$members): ?>
            <div class="mess-muted"><?php echo lang('mess_nothing_to_show'); ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="mess-matrix">
                <thead>
                    <tr>
                        <th style="text-align:left"><?php echo lang('mess_member'); ?></th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?><th><?php echo $d; ?></th><?php endfor; ?>
                        <th><?php echo lang('mess_served'); ?></th><th><?php echo lang('mess_not_taken'); ?></th><th><?php echo lang('mess_skipped'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td class="nm"><?php echo escape_output($m->name); ?></td>
                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $l = isset($m->days[$d]) ? $m->days[$d] : ''; ?>
                            <td class="<?php echo $l == '-' ? 'W' : $l; ?>"><?php echo $l; ?></td>
                        <?php endfor; ?>
                        <td><b><?php echo (int) $m->served; ?></b></td><td><?php echo (int) $m->not_taken; ?></td><td><?php echo (int) $m->skipped; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
