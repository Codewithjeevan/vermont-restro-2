<?php
/*
 | AI Settings of the company (permission 441): target food cost and the thresholds
 | the engine uses. Ranges are checked again in Ai_insight::saveSettings (plan S14).
 */
$s = $settings;
$overbuy_pct = (int) round(((float) $s->overbuy_factor - 1) * 100);
$num = function ($v) {
    return htmlspecialchars(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8');
};
?>
<section class="main-content-wrapper ai-page">
    <?php $this->load->view('ai_insight/_top', array('ai_tab' => 'settings')); ?>

    <div class="ai-head">
        <div>
            <h3><?php echo lang('ai_settings'); ?></h3>
            <div class="ai-sub"><?php echo htmlspecialchars(sprintf(lang('ai_settings_hint'), (int) ai_config('ai_report_hour')), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <?php echo form_open(base_url('Ai_insight/saveSettings'), array('class' => 'ai-settings')); ?>
                <?php echo ai_csrf_field(); ?>
                <div class="ai-field">
                    <label for="ai_target"><?php echo lang('ai_s_target'); ?></label>
                    <div class="ai-input-unit">
                        <input type="number" id="ai_target" name="target_food_cost_pct" class="form-control" min="15" max="60" step="0.5" required value="<?php echo $num($s->target_food_cost_pct); ?>">
                        <span>%</span>
                    </div>
                    <div class="ai-muted"><?php echo lang('ai_s_target_help'); ?></div>
                </div>
                <div class="ai-field">
                    <label for="ai_rounding"><?php echo lang('ai_s_rounding'); ?></label>
                    <select id="ai_rounding" name="price_rounding" class="form-control">
                        <?php foreach ($steps as $step): ?>
                            <option value="<?php echo $num($step); ?>" <?php echo abs((float) $s->price_rounding - $step) < 0.0001 ? 'selected' : ''; ?>><?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format($step, 2); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ai-muted"><?php echo lang('ai_s_rounding_help'); ?></div>
                </div>
                <div class="ai-field">
                    <label for="ai_overbuy"><?php echo lang('ai_s_overbuy'); ?></label>
                    <div class="ai-input-unit">
                        <input type="number" id="ai_overbuy" name="overbuy_pct" class="form-control" min="5" max="200" step="1" required value="<?php echo $overbuy_pct; ?>">
                        <span>%</span>
                    </div>
                    <div class="ai-muted"><?php echo lang('ai_s_overbuy_help'); ?></div>
                </div>
                <div class="ai-field">
                    <label for="ai_cover"><?php echo lang('ai_s_cover'); ?></label>
                    <div class="ai-input-unit">
                        <input type="number" id="ai_cover" name="cover_days_limit" class="form-control" min="3" max="60" step="1" required value="<?php echo (int) $s->cover_days_limit; ?>">
                        <span><?php echo lang('ai_days_unit'); ?></span>
                    </div>
                    <div class="ai-muted"><?php echo lang('ai_s_cover_help'); ?></div>
                </div>
                <div class="ai-field">
                    <label for="ai_safety"><?php echo lang('ai_s_safety'); ?></label>
                    <div class="ai-input-unit">
                        <input type="number" id="ai_safety" name="safety_days" class="form-control" min="0" max="7" step="1" required value="<?php echo (int) $s->safety_days; ?>">
                        <span><?php echo lang('ai_days_unit'); ?></span>
                    </div>
                    <div class="ai-muted"><?php echo lang('ai_s_safety_help'); ?></div>
                </div>
                <div class="ai-field">
                    <button type="submit" class="btn bg-blue-btn"><?php echo lang('ai_save'); ?></button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</section>
