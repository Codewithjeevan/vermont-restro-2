<?php
$can_add = checkAccess(378, 'add');
$can_edit = checkAccess(378, 'update');
$can_subscribe = checkAccess(383, 'add');
$can_pay = checkAccess(388, 'add');
$today = date('Y-m-d');
$date_format = $this->session->userdata('date_format');
?>
<section class="main-content-wrapper">
    <?php $this->load->view('mess/_top', array('mess_tab' => 'members')); ?>

    <div class="mess-head">
        <div>
            <h3><?php echo lang('mess_members'); ?></h3>
            <div class="mess-sub"><?php echo lang('mess_member_hint'); ?></div>
        </div>
        <div class="mess-head-actions">
            <div class="mess-filter">
                <a class="mess-btn <?php echo $status == 'active' ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess/members?status=active"><?php echo lang('mess_status_active'); ?></a>
                <a class="mess-btn <?php echo $status == 'inactive' ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess/members?status=inactive"><?php echo lang('mess_status_inactive'); ?></a>
                <a class="mess-btn <?php echo $status == '' ? 'on' : ''; ?>" href="<?php echo base_url(); ?>Mess/members?status="><?php echo lang('all'); ?></a>
            </div>
            <?php if ($can_add): ?>
                <a class="mess-btn primary" href="<?php echo base_url(); ?>Mess/addEditMember"><i class="fa fa-plus"></i> <?php echo lang('mess_add_member'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="box-wrapper">
        <div class="table-box">
            <input type="hidden" class="datatable_name" data-title="<?php echo lang('mess_members'); ?>" data-id_name="datatable">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w_1"><?php echo lang('sn'); ?></th>
                            <th><?php echo lang('mess_member_code'); ?></th>
                            <th><?php echo lang('name'); ?></th>
                            <th><?php echo lang('phone'); ?></th>
                            <th><?php echo lang('mess_building_room'); ?></th>
                            <th><?php echo lang('mess_diet'); ?></th>
                            <th><?php echo lang('mess_mode'); ?></th>
                            <th><?php echo lang('mess_last_cycle_end'); ?></th>
                            <th class="text-end"><?php echo lang('balance'); ?></th>
                            <th><?php echo lang('mess_last_payment'); ?></th>
                            <th class="not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = count($members); foreach ($members as $m): ?>
                        <tr>
                            <td class="ir_txt_center"><?php echo $i--; ?></td>
                            <td><?php echo escape_output($m->member_code); ?></td>
                            <td>
                                <a class="name" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $m->id; ?>"><?php echo escape_output($m->name); ?></a>
                                <?php if ($m->status == 'inactive'): ?> <span class="mess-badge inactive"><?php echo lang('mess_status_inactive'); ?></span><?php endif; ?>
                            </td>
                            <td><?php echo escape_output($m->phone); ?></td>
                            <td><?php echo escape_output($m->building_room); ?></td>
                            <td><span class="mess-badge <?php echo escape_output($m->diet_pref); ?>"><?php echo lang('mess_' . $m->diet_pref); ?></span></td>
                            <td><?php echo lang('mess_' . $m->delivery_mode); ?></td>
                            <td>
                                <?php if ($m->last_end_date): ?>
                                    <?php echo escape_output(date($date_format, strtotime($m->last_end_date))); ?>
                                    <?php if ($m->last_end_date < $today): ?> <span class="mess-badge expired"><?php echo lang('mess_status_expired'); ?></span><?php endif; ?>
                                <?php else: ?>
                                    <span class="mess-muted"><?php echo lang('mess_no_subscription'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="mess-num <?php echo $m->balance > 0.0005 ? 'mess-due' : ($m->balance < -0.0005 ? 'mess-credit' : ''); ?>"><?php echo getAmtPCustom($m->balance); ?></td>
                            <td><?php echo $m->last_payment_date ? escape_output(date($date_format, strtotime($m->last_payment_date))) : '-'; ?></td>
                            <td style="white-space:nowrap">
                                <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $m->id; ?>"><i class="fa fa-eye"></i></a>
                                <?php if ($can_edit): ?>
                                    <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addEditMember/<?php echo escape_output($this->custom->encrypt_decrypt($m->id, 'encrypt')); ?>"><i class="fa fa-pencil"></i></a>
                                <?php endif; ?>
                                <?php if ($can_subscribe): ?>
                                    <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addEditSubscription?member_id=<?php echo (int) $m->id; ?>"><i class="fa fa-calendar-plus-o"></i> <?php echo lang('mess_subscription'); ?></a>
                                <?php endif; ?>
                                <?php if ($can_pay): ?>
                                    <a class="mess-btn sm" href="<?php echo base_url(); ?>Mess/addPayment?member_id=<?php echo (int) $m->id; ?>"><i class="fa fa-money"></i> <?php echo lang('payment'); ?></a>
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
