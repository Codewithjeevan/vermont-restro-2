<?php
/*
 | One member row of the meal register. Rendered by mealRegister.php and by
 | Mess_daily::mark (AJAX) so a tap only redraws its own row.
 | $r (attendance row + member), $row_extras, $can_mark, $is_future
 */
$done = $r->status != 'pending';
$status_text = array(
    'pending' => lang('mess_pending'),
    'served' => lang('mess_served'),
    'partial' => lang('mess_partial'),
    'not_taken' => lang('mess_not_taken'),
    'skipped' => lang('mess_skipped'),
);
$search = $r->member_name . ' ' . $r->member_code . ' ' . $r->phone . ' ' . $r->building_room;
?>
<div class="mess-reg-row<?php echo $done ? ' done' : ''; ?>" id="mess_att_<?php echo (int) $r->id; ?>"
     data-search="<?php echo escape_output($search); ?>" data-mode="<?php echo escape_output($r->delivery_mode); ?>" data-status="<?php echo escape_output($r->status); ?>">
    <div class="qty" title="<?php echo lang('mess_persons'); ?>"><?php echo (int) $r->persons_expected; ?></div>
    <div class="who">
        <div class="nm"><a href="<?php echo base_url(); ?>Mess/member/<?php echo (int) $r->member_id; ?>" class="text-reset text-decoration-none"><?php echo escape_output($r->member_name); ?></a></div>
        <div class="meta">
            <?php if ($r->sub_diet == 'both' && $can_mark): ?>
                <a href="#" class="mess-badge <?php echo escape_output($r->diet); ?> mess-flip" data-id="<?php echo (int) $r->id; ?>" title="<?php echo escape_output(lang('mess_flip_diet')); ?>"><?php echo lang('mess_' . $r->diet); ?> &#8646;</a>
            <?php else: ?>
                <span class="mess-badge <?php echo escape_output($r->diet); ?>"><?php echo lang('mess_' . $r->diet); ?></span>
            <?php endif; ?>
            <?php echo lang('mess_' . $r->delivery_mode); ?>
            <?php if ($r->phone): ?> &middot; <?php echo escape_output($r->phone); ?><?php endif; ?>
            <?php if ($r->building_room): ?> &middot; <?php echo escape_output($r->building_room); ?><?php endif; ?>
            <?php if (!empty($row_extras)):
                $ex = array();
                foreach ($row_extras as $e) { $ex[] = (float) $e->qty . ' ' . $e->item_name; } ?>
                <br><span class="mess-badge requested">+ <?php echo lang('mess_extra'); ?>: <?php echo escape_output(implode(', ', $ex)); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="state">
        <span class="mess-badge <?php echo escape_output($r->status); ?>"><?php echo $status_text[$r->status]; ?><?php echo $r->status == 'partial' ? ' ' . (int) $r->persons_served . '/' . (int) $r->persons_expected : ''; ?></span>
        <?php if ($r->marked_at && $r->status != 'pending'): ?>
            <div class="mess-muted"><?php echo escape_output(date('h:i A', strtotime($r->marked_at))); ?><?php echo $r->marked_by_name ? ' · ' . escape_output($r->marked_by_name) : ''; ?></div>
        <?php endif; ?>
    </div>
    <?php if ($can_mark): ?>
    <div class="acts">
        <?php if ($r->status == 'pending'): ?>
            <?php if (!$is_future): ?>
                <button type="button" class="mess-btn success mess-mark" data-id="<?php echo (int) $r->id; ?>" data-action="served"><i class="fa fa-check"></i> <?php echo lang('mess_served'); ?></button>
                <?php if ($r->persons_expected > 1): ?>
                    <select class="form-control mess-partial" data-id="<?php echo (int) $r->id; ?>" style="width:auto;min-height:38px">
                        <option value=""><?php echo lang('mess_partial'); ?></option>
                        <?php for ($p = 1; $p < $r->persons_expected; $p++): ?>
                            <option value="<?php echo $p; ?>"><?php echo $p; ?> / <?php echo (int) $r->persons_expected; ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
                <button type="button" class="mess-btn danger mess-mark" data-id="<?php echo (int) $r->id; ?>" data-action="not_taken"><?php echo lang('mess_not_taken'); ?></button>
            <?php endif; ?>
            <button type="button" class="mess-btn mess-mark" data-id="<?php echo (int) $r->id; ?>" data-action="skip"><?php echo lang('mess_skipped'); ?></button>
        <?php else: ?>
            <button type="button" class="mess-btn mess-mark" data-id="<?php echo (int) $r->id; ?>" data-action="undo"><i class="fa fa-undo"></i> <?php echo lang('mess_undo'); ?></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
