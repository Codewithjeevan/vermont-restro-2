<?php
/*
 | Mess module (monthly tiffin subscriptions).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['mess'] = 'Mess';
$lang['mess_dashboard'] = 'Mess Today';
$lang['mess_today'] = 'Today';
$lang['mess_plans'] = 'Mess Plans';
$lang['mess_plan'] = 'Plan';
$lang['mess_members'] = 'Mess Members';
$lang['mess_member'] = 'Member';
$lang['mess_subscriptions'] = 'Subscriptions';
$lang['mess_subscription'] = 'Subscription';
$lang['mess_payments'] = 'Mess Payments';
$lang['mess_payment'] = 'Mess Payment';
$lang['mess_daily_menu'] = 'Daily Menu';
$lang['mess_meal_register'] = 'Meal Register';
$lang['mess_extras'] = 'Extras';
$lang['mess_extra'] = 'Extra';
$lang['mess_daily_sheet'] = 'Daily Sheet';
$lang['mess_reports'] = 'Mess Reports';
$lang['mess_settings'] = 'Mess Settings';
$lang['mess_collection'] = 'Mess Collection';
$lang['mess_dues'] = 'Dues';
$lang['mess_expiring'] = 'Expiring / Renew';
$lang['reopen'] = 'Reopen';
$lang['mess_tab_members'] = 'Members';
$lang['mess_tab_renewals'] = 'Renewals';
$lang['mess_tab_payments'] = 'Payments';
$lang['mess_tab_plans'] = 'Plans';
$lang['mess_tab_reports'] = 'Reports';
$lang['mess_tab_settings'] = 'Settings';

// slots, diet, mode
$lang['mess_slot'] = 'Slot';
$lang['mess_slots'] = 'Meal slots';
$lang['mess_breakfast'] = 'Breakfast';
$lang['mess_lunch'] = 'Lunch';
$lang['mess_dinner'] = 'Dinner';
$lang['mess_diet'] = 'Diet';
$lang['mess_veg'] = 'Veg';
$lang['mess_nonveg'] = 'Non-veg';
$lang['mess_both'] = 'Both';
$lang['mess_mode'] = 'Mode';
$lang['mess_pickup'] = 'Pickup';
$lang['mess_delivery'] = 'Delivery';
$lang['mess_persons'] = 'Persons';
$lang['mess_meals'] = 'Meals';
$lang['mess_meals_persons'] = 'Meals x persons';
$lang['mess_meals_persons_hint'] = 'Persons per slot. "Lunch + 2 Dinner" = Lunch 1, Dinner 2.';

// status
$lang['mess_status_active'] = 'Active';
$lang['mess_status_expiring'] = 'Expiring';
$lang['mess_status_expired'] = 'Expired';
$lang['mess_status_upcoming'] = 'Upcoming';
$lang['mess_status_cancelled'] = 'Cancelled';
$lang['mess_status_paused'] = 'Paused today';
$lang['mess_status_inactive'] = 'Inactive';
$lang['mess_current'] = 'Current';
$lang['mess_renewed'] = 'Renewed';

// plans
$lang['mess_add_plan'] = 'Add Plan';
$lang['mess_edit_plan'] = 'Edit Plan';
$lang['mess_plan_name'] = 'Plan name';
$lang['mess_price_per_person'] = 'Price per person / cycle';
$lang['mess_per_meal_rate'] = 'Per meal (pro-rata)';
$lang['mess_cycle'] = 'Cycle';
$lang['mess_cycle_days'] = 'Cycle days';
$lang['mess_included_meals'] = 'Included meals';
$lang['mess_plan_hint'] = 'A plan is a price list entry, not a contract. Staff can type an agreed price on each subscription. Per-meal rate = price / (cycle days x meals a day) and is used for short cycles.';
$lang['mess_plan_need_slot'] = 'Tick at least one meal.';
$lang['mess_plan_in_use'] = 'This plan is used by subscriptions, it was switched off instead of deleted.';

// members
$lang['mess_add_member'] = 'Add Member';
$lang['mess_edit_member'] = 'Edit Member';
$lang['mess_member_code'] = 'Member code';
$lang['mess_phone2'] = 'Phone 2';
$lang['mess_building_room'] = 'Building / room';
$lang['mess_default_diet'] = 'Diet preference';
$lang['mess_default_mode'] = 'Pickup or delivery';
$lang['mess_member_hint'] = 'A member is one person or one room, created once. Every month is a new subscription of the same member.';
$lang['mess_phone_used_by'] = 'This phone is already used by';
$lang['mess_member_has_records'] = 'This member has subscriptions or payments. It was marked inactive instead of deleted.';
$lang['mess_last_cycle_end'] = 'Last cycle ends';
$lang['mess_last_payment'] = 'Last payment';
$lang['mess_no_subscription'] = 'No subscription';
$lang['mess_member_profile'] = 'Member profile';
$lang['mess_ledger'] = 'Ledger';
$lang['mess_charge'] = 'Charge';
$lang['mess_paid'] = 'Paid';
$lang['mess_balance_due'] = 'Balance due';
$lang['mess_advance_credit'] = 'Advance credit';
$lang['mess_print_statement'] = 'Print statement';
$lang['mess_statement'] = 'Member Statement';
$lang['mess_whatsapp_reminder'] = 'WhatsApp reminder';
$lang['mess_whatsapp'] = 'WhatsApp';
$lang['mess_no_entries'] = 'No entries yet';
$lang['mess_active_members'] = 'Active members';

// subscriptions
$lang['mess_new_subscription'] = 'New Subscription';
$lang['mess_edit_subscription'] = 'Edit Subscription';
$lang['mess_renew'] = 'Renew';
$lang['mess_renew_cycle'] = 'Renew cycle';
$lang['mess_renewing_from'] = 'Renewing the cycle that ends on';
$lang['mess_suggested_price'] = 'Suggested price';
$lang['mess_agreed_price'] = 'Agreed price';
$lang['mess_price_note'] = 'Price note';
$lang['mess_price_note_ph'] = 'e.g. old customer, driver rate';
$lang['mess_delivery_address'] = 'Delivery address';
$lang['mess_end_date_auto'] = 'End date fills from start date + cycle days';
$lang['mess_advance_now'] = 'Collect now';
$lang['mess_advance_now_hint'] = 'Optional. Goes to today\'s register like any mess payment.';
$lang['mess_save_subscription'] = 'Save subscription';
$lang['mess_overlap_warning'] = 'Warning: this member already has a cycle in these dates';
$lang['mess_meals_required'] = 'Enter persons for at least one meal slot.';
$lang['mess_dates_invalid'] = 'End date must be on or after the start date.';
$lang['mess_agreed_price_required'] = 'Agreed price is required.';
$lang['mess_cancel_subscription'] = 'Cancel subscription';
$lang['mess_cancel_confirm'] = 'Cancel this subscription? Its charge is removed from the ledger and no meals are expected any more.';
$lang['mess_sub_has_payments'] = 'This subscription has payments. It was cancelled instead of deleted; the payments stay on the member ledger as advance.';
$lang['mess_pause'] = 'Pause';
$lang['mess_pauses'] = 'Pauses (leave)';
$lang['mess_add_pause'] = 'Add pause';
$lang['mess_pause_hint'] = 'Member travelling: the end date moves forward by the same number of days and no meals are expected on those days.';
$lang['mess_pause_dates_invalid'] = 'The pause end date must be on or after its start date.';
$lang['mess_pause_outside_cycle'] = 'The pause must start inside the subscription dates.';
$lang['mess_pause_overlaps'] = 'This pause overlaps another pause.';
$lang['mess_pause_added'] = 'Pause saved, end date moved forward.';
$lang['mess_paused_days'] = 'Paused days';
$lang['mess_days_left'] = 'Days left';
$lang['mess_ends_today'] = 'ends today';
$lang['mess_ends_in'] = 'ends in';
$lang['mess_ended'] = 'ended';
$lang['mess_expiring_list'] = 'Expiring soon';
$lang['mess_expired_not_renewed'] = 'Expired, not renewed';
$lang['mess_expired_not_renewed_hint'] = 'Cycles that ended in the last 45 days with no newer cycle for the member.';
$lang['mess_within_days'] = 'within days';

// payments
$lang['mess_receive_payment'] = 'Receive Payment';
$lang['mess_payment_type'] = 'Entry type';
$lang['mess_type_payment'] = 'Payment (money in)';
$lang['mess_type_refund'] = 'Refund (money out)';
$lang['mess_type_adjustment'] = 'Adjustment (no money)';
$lang['mess_adjustment'] = 'Adjustment';
$lang['mess_adjust_credit'] = 'Credit: reduce the due (discount, waiver)';
$lang['mess_adjust_debit'] = 'Debit: add a charge';
$lang['mess_against_subscription'] = 'Against subscription';
$lang['mess_general_advance'] = 'General (no subscription)';
$lang['mess_amount_positive'] = 'Amount must be more than zero.';
$lang['mess_payment_method_required'] = 'Payment method is required for a payment or a refund.';
$lang['mess_payment_saved'] = 'Payment saved.';
$lang['mess_print_receipt'] = 'Print receipt';
$lang['mess_receipt'] = 'Mess Receipt';
$lang['mess_receipt_title'] = 'MESS PAYMENT RECEIPT';
$lang['mess_received_from'] = 'Received from';
$lang['mess_received_by'] = 'Received by';
$lang['mess_balance_after'] = 'Balance after this payment';
$lang['mess_thank_you'] = 'Thank you';
$lang['mess_payment_closed_register'] = 'This payment belongs to a register that is already closed. Only Admin can delete it.';
$lang['mess_register_needed'] = 'Mess payments need an open register (Mess Settings). Open the register first.';
$lang['mess_refund_adjust_admin'] = 'Refund and adjustment need Admin or the Mess Payments delete permission.';
$lang['mess_collection_today'] = 'Collected today';
$lang['mess_total_dues'] = 'Total dues';
$lang['mess_members_with_due'] = 'members with due';
$lang['mess_payments_count'] = 'payments';

// register card
$lang['mess_collection_plus'] = 'Mess Collection (+)';
$lang['mess_collection_register_hint'] = 'Mess payments taken on this register, included in cash in hand';

// daily menu
$lang['mess_menu_for'] = 'Menu for';
$lang['mess_dishes'] = 'Dishes';
$lang['mess_dish'] = 'Dish';
$lang['mess_add_dish'] = 'Add dish';
$lang['mess_portion_per_person'] = 'Portion / person';
$lang['mess_buffer'] = 'Buffer';
$lang['mess_expected_persons'] = 'Expected persons';
$lang['mess_planned_portions'] = 'Planned portions';
$lang['mess_copy_yesterday'] = 'Copy yesterday';
$lang['mess_copy_last_week'] = 'Copy same day last week';
$lang['mess_copy_from'] = 'Copy from date';
$lang['mess_copy_done'] = 'dishes copied';
$lang['mess_copy_confirm'] = 'Replace this day\'s menu with the copied one?';
$lang['mess_menu_text'] = 'Menu text (WhatsApp)';
$lang['mess_menu_missing'] = 'menu missing';
$lang['mess_menu_saved'] = 'Menu saved';
$lang['mess_no_recipe'] = 'no recipe';
$lang['mess_no_recipe_hint'] = 'This dish has no recipe on the Food Menu, stock will not move for it.';
$lang['mess_free_text_dish'] = 'or type a dish name';
$lang['mess_pick_food_menu'] = '-- Food Menu dish (with recipe) --';
$lang['mess_expected_hint'] = 'Expected persons come live from the subscriptions of the day minus skips.';
$lang['mess_copied_to_clipboard'] = 'Copied to clipboard';
$lang['mess_copy_text'] = 'Copy text';

// meal register
$lang['mess_expected'] = 'Expected';
$lang['mess_served'] = 'Served';
$lang['mess_pending'] = 'Pending';
$lang['mess_not_taken'] = 'Not taken';
$lang['mess_skipped'] = 'Skipped';
$lang['mess_partial'] = 'Partial';
$lang['mess_undo'] = 'Undo';
$lang['mess_mark_all_served'] = 'Mark all pending as Served';
$lang['mess_mark_all_not_taken'] = 'Mark all pending as Not taken';
$lang['mess_bulk_confirm'] = 'Mark every pending row?';
$lang['mess_search_member'] = 'Search name / phone / building';
$lang['mess_print_tick_sheet'] = 'Print tick sheet';
$lang['mess_no_members_expected'] = 'No member expects this meal. Check the date, the slot or the subscriptions.';
$lang['mess_skip_ahead'] = 'Skip in advance';
$lang['mess_skip_ahead_hint'] = '"Kal nahi aaunga": the meal drops out of the headcount so the kitchen cooks less.';
$lang['mess_skip_saved'] = 'Skip saved';
$lang['mess_skip_not_expected'] = 'This member does not expect that meal on that date.';
$lang['mess_skip_already_marked'] = 'That meal is already marked and cannot be skipped.';
$lang['mess_served_at'] = 'Served';
$lang['mess_flip_diet'] = 'Switch Veg / Non-veg for today';
$lang['mess_attendance_last_30'] = 'Meals in the last 30 days';
$lang['mess_future_only_skip'] = 'A meal of a future date can only be skipped.';
$lang['mess_invalid_action'] = 'Unknown action.';

// extras
$lang['mess_add_extra'] = 'Add Extra';
$lang['mess_settle'] = 'Settle';
$lang['mess_pay_now'] = 'Pay now';
$lang['mess_on_account'] = 'On account';
$lang['mess_on_account_hint'] = 'Added to the member balance, collected with the monthly money.';
$lang['mess_pay_now_hint'] = 'A mess payment is created now and reaches tonight\'s register.';
$lang['mess_custom_item'] = 'Custom item';
$lang['mess_item_name'] = 'Item name';
$lang['mess_unit_price'] = 'Unit price';
$lang['mess_extra_saved'] = 'Extra saved';
$lang['mess_mark_delivered'] = 'Mark delivered';
$lang['mess_extra_status_requested'] = 'Requested';
$lang['mess_extra_status_delivered'] = 'Delivered';
$lang['mess_extra_status_cancelled'] = 'Cancelled';
$lang['mess_cancel_extra'] = 'Cancel extra';
$lang['mess_extras_today'] = 'Extras today';
$lang['mess_extra_paid_cancel_note'] = 'This extra was paid on the spot. Cancelling it keeps the payment as member credit (refund it from Receive Payment if needed).';

// daily sheet
$lang['mess_sheet_title'] = 'DAILY MESS SHEET';
$lang['mess_headcount'] = 'Headcount';
$lang['mess_production_plan'] = 'Production plan';
$lang['mess_ingredient_need'] = 'Ingredient need (recipe x portions)';
$lang['mess_stock'] = 'Stock';
$lang['mess_short'] = 'short';
$lang['mess_ok'] = 'ok';
$lang['mess_member_checklist'] = 'Member checklist';
$lang['mess_sign'] = 'Sign';
$lang['mess_printed_by'] = 'printed by';
$lang['mess_stock_shortage'] = 'Stock shortage';
$lang['mess_delivery_route'] = 'Delivery list';
$lang['mess_skipped_advance'] = 'Skipped in advance';
$lang['mess_portions'] = 'Portions';

// today dashboard
$lang['mess_expected_today'] = 'expected';
$lang['mess_menu_today'] = 'Menu today';
$lang['mess_needs_attention'] = 'Needs attention';
$lang['mess_open_register'] = 'Open meal register';
$lang['mess_quick_actions'] = 'Quick actions';
$lang['mess_nothing_to_show'] = 'Nothing to show';

// reports
$lang['mess_attendance_matrix'] = 'Attendance matrix';
$lang['mess_attendance_matrix_hint'] = 'S served, P partial, N not taken, K skipped, - pending, blank not subscribed';
$lang['mess_daily_served_summary'] = 'Daily served summary';
$lang['mess_collection_report'] = 'Collection report';
$lang['mess_by_method'] = 'By payment method';
$lang['mess_member_x_day'] = 'Member x day';

// settings
$lang['mess_settings_saved'] = 'Mess settings saved';
$lang['mess_slot_on'] = 'On';
$lang['mess_from_time'] = 'From';
$lang['mess_to_time'] = 'To';
$lang['mess_default_cycle_days'] = 'Default cycle days';
$lang['mess_expiring_days'] = 'Expiring alert (days before end)';
$lang['mess_skip_credit_policy'] = 'Skip credit policy';
$lang['mess_skip_credit_none'] = 'No credit, skips only reduce cooking';
$lang['mess_skip_credit_informed'] = 'Credit when informed before the slot (manual adjustment)';
$lang['mess_skip_credit_always'] = 'Always credit (manual adjustment)';
$lang['mess_require_open_register'] = 'Require an open register for mess payments';
$lang['mess_require_open_register_hint'] = 'Recommended. Money taken without an open register never reaches a register close, so cash in hand will not match.';
$lang['mess_member_code_prefix'] = 'Member code prefix';
$lang['mess_whatsapp_country_code'] = 'WhatsApp country code';
$lang['mess_whatsapp_templates'] = 'WhatsApp text templates';
$lang['mess_template_menu'] = 'Daily menu';
$lang['mess_template_due'] = 'Due reminder';
$lang['mess_template_expiry'] = 'Expiry reminder';
$lang['mess_template_vars'] = 'Placeholders: {name} {amount} {currency} {end_date} {slot} {date} {menu}';
$lang['mess_roles_hint'] = 'Give each role only what it needs from Settings > Role: Counter = Meal Register, Extras, Payments, Today. Kitchen = Today, Daily Menu, Daily Sheet.';

// day close (stock)
$lang['mess_day_close'] = 'Day Close';
$lang['mess_cooked'] = 'Cooked';
$lang['mess_leftover'] = 'Leftover';
$lang['mess_leftover_goes_to'] = 'Leftover goes to';
$lang['mess_waste'] = 'Waste';
$lang['mess_carry'] = 'Carry to next meal';
$lang['mess_staff_meal'] = 'Staff meal';
$lang['mess_food_cost'] = 'Food cost';
$lang['mess_waste_cost'] = 'Waste cost';
$lang['mess_cost_per_meal'] = 'Cost per served meal';
$lang['mess_served_portions'] = 'Served (est.)';
$lang['mess_close_and_post'] = 'Close and post to stock';
$lang['mess_close_confirm'] = 'Close this meal? Cooked portions x recipe leave the stock, wasted leftover is booked as waste.';
$lang['mess_close_hint'] = 'Confirm what the kitchen actually cooked and what was left. Closing posts ingredient consumption (cooked minus wasted, x recipe) to the stock ledger and the wasted leftover as a waste entry, so the stock screens move by exactly what was cooked. "Carry" and "Staff meal" leftover count as used. Dishes without a recipe do not move stock.';
$lang['mess_already_closed'] = 'This meal is already closed. Reopen it first.';
$lang['mess_close_failed'] = 'The close could not be saved, nothing was posted. Try again.';
$lang['mess_close_future'] = 'A meal can be closed on its day or later.';
$lang['mess_closed_ok'] = 'Meal closed, stock posted.';
$lang['mess_reopened_ok'] = 'Meal reopened, the posted stock movement was reversed.';
$lang['mess_reopen_confirm'] = 'Reopen this meal? The stock consumption and waste it posted are reversed.';
$lang['mess_closed_by'] = 'by';
$lang['mess_pending_warning'] = 'Some members are still pending in the meal register. Mark them first so the served numbers are right.';
$lang['mess_open_waste'] = 'Open waste entry';
$lang['mess_status_closed'] = 'Closed';
$lang['mess_status_open'] = 'Open';
$lang['mess_day_close_report'] = 'Day close & food cost';
$lang['mess_meals_served'] = 'Meals served';
$lang['mess_not_closed'] = 'Not closed';
