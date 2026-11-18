<section class="main-content-wrapper">
    <?php $this->load->view('ai_insight/_top', array('ai_tab' => '')); ?>
    <div class="ai-head">
        <div>
            <h3><?php echo lang('ai_insight'); ?></h3>
        </div>
    </div>
    <div class="box-wrapper">
        <div class="table-box">
            <div class="ai-notice"><?php echo escape_output($notice); ?></div>
        </div>
    </div>
</section>
