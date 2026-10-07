<?php
$rtabs = array(
    'mess/reportDailySummary' => array('Mess_report/dailySummary', lang('mess_daily_served_summary')),
    'mess/reportAttendanceMatrix' => array('Mess_report/attendanceMatrix', lang('mess_attendance_matrix')),
    'mess/reportCollection' => array('Mess_report/collection', lang('mess_collection_report')),
    'mess/reportDayCloses' => array('Mess_report/dayCloses', lang('mess_day_close_report')),
);
?>
<div class="mess-filter mb-3">
    <?php foreach ($rtabs as $view => $t): ?>
        <a class="mess-btn <?php echo $report_tab == $view ? 'on' : ''; ?>" href="<?php echo base_url() . $t[0]; ?>"><?php echo escape_output($t[1]); ?></a>
    <?php endforeach; ?>
    <a class="mess-btn" href="<?php echo base_url(); ?>Mess/dues"><?php echo lang('mess_dues'); ?></a>
    <a class="mess-btn" href="<?php echo base_url(); ?>Mess/expiring"><?php echo lang('mess_expiring'); ?></a>
    <a class="mess-btn" href="<?php echo base_url(); ?>Mess_daily/extras"><?php echo lang('mess_extras'); ?></a>
</div>
