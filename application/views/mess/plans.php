<?php
$can_edit = checkAccess(373, 'update');
$can_delete = checkAccess(373, 'delete');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'plans')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_plans'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_plan_hint'); ?></div>
        </div>
        <div class="mess-head-actions">
            <a class="mess-btn primary menu_assign_class" data-access="add-373" href="<?php echo base_url(); ?>Mess/addEditPlan"><i class="fa fa-plus"></i> <?php echo lang('mess_add_plan'); ?></a>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('mess_plans'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('mess_plan'); ?></th>
                            <th><?php echo lang('mess_meals'); ?></th>
                            <th><?php echo lang('mess_diet'); ?></th>
                            <th class="text-end"><?php echo lang('mess_price_per_person'); ?></th>
                            <th class="text-end"><?php echo lang('mess_per_meal_rate'); ?></th>
                            <th><?php echo lang('mess_cycle'); ?></th>
                            <th><?php echo lang('status'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = count($plans); foreach ($plans as $p):
                            $meals = array();
                            if ($p->has_breakfast) { $meals[] = lang('mess_breakfast'); }
                            if ($p->has_lunch) { $meals[] = lang('mess_lunch'); }
                            if ($p->has_dinner) { $meals[] = lang('mess_dinner'); }
                            $rate = $this->Mess_model->perMealRate($p);
                        ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $i--; ?></td>
                            <td><span class="name"><?php echo escape_output($p->name); ?></span>
                                <?php if ($p->description): ?><div class="mess-muted"><?php echo escape_output($p->description); ?></div><?php endif; ?>
                            </td>
                            <td><?php echo escape_output(implode(', ', $meals)); ?></td>
                            <td><span class="mess-badge <?php echo escape_output($p->diet); ?>"><?php echo lang('mess_' . $p->diet); ?></span></td>
                            <td class="mess-num"><?php echo getAmtPCustom($p->price_per_person); ?></td>
                            <td class="mess-num"><?php echo getAmtPCustom($rate); ?></td>
                            <td><?php echo (int) $p->cycle_days; ?> <?php echo lang('days'); ?></td>
                            <td><span class="mess-badge <?php echo $p->is_active ? 'active' : 'inactive'; ?>"><?php echo $p->is_active ? lang('mess_status_active') : lang('mess_status_inactive'); ?></span></td>
                            <td>
                                <?php if ($can_edit): ?>
                                <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addEditPlan/<?php echo escape_output($this->custom->encrypt_decrypt($p->id, 'encrypt')); ?>"><i class="fa fa-pencil"></i> <?php echo lang('edit'); ?></a>
                                <?php endif; ?>
                                <?php if ($can_delete): ?>
                                <a class="mess-btn sm danger mess-confirm" href="<?php echo base_url(); ?>Mess/deletePlan/<?php echo escape_output($this->custom->encrypt_decrypt($p->id, 'encrypt')); ?>"><i class="fa fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php $this->load->view('mess/_datatable'); ?>
