<section class="main-content-wrapper">
    <?php $this->load->view('ai_insight/_top', array('ai_tab' => 'companies')); ?>

    <div class="ai-head">
        <div>
            <h3><?php echo lang('ai_companies'); ?></h3>
            <div class="ai-sub"><?php echo lang('ai_companies_hint'); ?></div>
        </div>
        <div class="ai-head-actions">
            <?php echo form_open(base_url('Ai_insight/testConnection'), array('class' => 'd-inline')); ?>
                <?php echo ai_csrf_field(); ?>
                <button type="submit" class="btn bg-blue-btn"><?php echo lang('ai_test_connection'); ?></button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <?php if ($test_result): ?>
        <div class="alert <?php echo $test_result['ok'] ? 'alert-success' : 'alert-danger'; ?>">
            <div class="alert-body"><p class="m-0"><?php echo escape_output($test_result['text']); ?></p></div>
        </div>
    <?php endif; ?>

    <div class="ai-keyline">
        <strong><?php echo lang('ai_key_status'); ?>:</strong>
        <?php if ($key_configured): ?>
            <span class="ai-status is-good"><?php echo lang('ai_key_set'); ?></span>
        <?php else: ?>
            <span class="ai-status is-missing"><?php echo lang('ai_key_not_set'); ?></span>
        <?php endif; ?>
        <?php if ($mock_mode): ?>
            <span class="ai-status is-watch"><?php echo lang('ai_mock_on'); ?></span>
        <?php endif; ?>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="table-responsive">
                <table class="table ai-table">
                    <thead>
                    <tr>
                        <th><?php echo lang('ai_company'); ?></th>
                        <th class="text-center"><?php echo lang('ai_outlets'); ?></th>
                        <th class="text-center"><?php echo lang('ai_enabled'); ?></th>
                        <th><?php echo lang('ai_monthly_limit'); ?></th>
                        <th><?php echo lang('ai_report_model'); ?></th>
                        <th><?php echo lang('ai_chat_model'); ?></th>
                        <th class="text-end"><?php echo lang('ai_spent_this_month'); ?></th>
                        <th class="text-end"><?php echo lang('ai_calls_this_month'); ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($companies as $c): ?>
                        <?php $form_id = 'ai_company_' . (int) $c->id; ?>
                        <tr>
                            <td>
                                <strong><?php echo escape_output($c->business_name); ?></strong>
                                <div class="ai-muted">#<?php echo (int) $c->id; ?></div>
                                <?php echo form_open(base_url('Ai_insight/saveCompany'), array('id' => $form_id)); ?>
                                    <?php echo ai_csrf_field(); ?>
                                    <input type="hidden" name="company_id" value="<?php echo (int) $c->id; ?>">
                                <?php echo form_close(); ?>
                            </td>
                            <td class="text-center"><?php echo (int) $c->outlets; ?></td>
                            <td class="text-center">
                                <input type="checkbox" name="enabled" value="1" form="<?php echo $form_id; ?>" <?php echo (int) $c->enabled ? 'checked' : ''; ?>>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="1000" name="monthly_budget_usd" form="<?php echo $form_id; ?>"
                                       class="form-control ai-input-sm" value="<?php echo escape_output(number_format((float) $c->monthly_budget_usd, 2, '.', '')); ?>">
                            </td>
                            <td>
                                <select name="report_model" form="<?php echo $form_id; ?>" class="form-control ai-input-md">
                                    <?php foreach ($models as $id => $label): ?>
                                        <option value="<?php echo escape_output($id); ?>" <?php echo $c->report_model == $id ? 'selected' : ''; ?>><?php echo escape_output($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="chat_model" form="<?php echo $form_id; ?>" class="form-control ai-input-md">
                                    <?php foreach ($models as $id => $label): ?>
                                        <option value="<?php echo escape_output($id); ?>" <?php echo $c->chat_model == $id ? 'selected' : ''; ?>><?php echo escape_output($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-end"><?php echo escape_output(number_format((float) $c->spent, 4)); ?></td>
                            <td class="text-end"><?php echo (int) $c->calls; ?></td>
                            <td class="text-end">
                                <button type="submit" form="<?php echo $form_id; ?>" class="btn bg-blue-btn btn-sm"><?php echo lang('ai_save'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
