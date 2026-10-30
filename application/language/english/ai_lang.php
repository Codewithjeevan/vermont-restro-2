<?php
/*
 | AI Insight module (docs/ai_insight_plan.md).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 | The AI-written insight text itself is always English (client decision).
 */

// module, menu and role screen
$lang['ai_insight'] = 'AI Insight';
$lang['ai_settings'] = 'AI Settings';
$lang['ai_companies'] = 'AI Companies';
$lang['ai_ask'] = 'Ask AI';
$lang['ai_regenerate'] = 'Regenerate report';

// page
$lang['ai_insight_hint'] = 'Daily analysis of this outlet: what sells, what is under-priced, and where stock and money are lost.';
$lang['ai_outlet'] = 'Outlet';
$lang['ai_not_enabled'] = 'AI Insight is not switched on for your company. Ask your system administrator.';
$lang['ai_not_ready'] = 'AI Insight is not installed yet: run Update/ai_insight_migration.sql.';

// data health
$lang['ai_data_health'] = 'Data Health';
$lang['ai_data_health_hint'] = 'How complete this outlet\'s data is. The insights are only as good as this data.';
$lang['ai_check'] = 'Check';
$lang['ai_result'] = 'Result';
$lang['ai_what_to_do'] = 'What to do';
$lang['ai_used_for'] = 'Needed for';
$lang['ai_status_good'] = 'Good';
$lang['ai_status_watch'] = 'Needs attention';
$lang['ai_status_missing'] = 'Missing';
$lang['ai_status_na'] = 'Not applicable';
$lang['ai_checked_at'] = 'Checked at %s';
$lang['ai_open'] = 'Open';
$lang['ai_warehouse_note'] = 'No sales on this outlet in the last %s days, so it is treated as a stock-only outlet (purchases, transfers and stock).';
$lang['ai_h_recipe_cover'] = 'Sold items with a recipe cost';
$lang['ai_h_recipe_cover_val'] = '%s%% of the quantity sold in the last %s days';
$lang['ai_h_recipe_cover_fix'] = 'Add a recipe (ingredients and quantities) to the items listed below. Upload Recipe takes up to 1000 menus at a time.';
$lang['ai_h_no_sales'] = 'No sales in the last %s days';
$lang['ai_h_menus_recipe'] = 'Menus with a recipe';
$lang['ai_h_menus_recipe_val'] = '%s of %s menus on this outlet have a recipe cost';
$lang['ai_h_menus_recipe_fix'] = 'Menus without a recipe show a cost of 0, so their margin cannot be judged.';
$lang['ai_h_ing_price'] = 'Ingredient prices';
$lang['ai_h_ing_price_val'] = '%s of %s ingredients used in recipes have no price';
$lang['ai_h_ing_price_ok'] = 'All %s ingredients used in recipes have a price';
$lang['ai_h_ing_price_none'] = 'No recipes yet, so there are no ingredient prices to check';
$lang['ai_h_ing_price_fix'] = 'Enter a purchase for them, or set the price on the ingredient.';
$lang['ai_h_purchases'] = 'Purchases entered';
$lang['ai_h_purchases_val'] = '%s purchases in the last %s days (last on %s)';
$lang['ai_h_purchases_old'] = 'No purchase in the last %s days (last on %s)';
$lang['ai_h_purchases_none'] = 'No purchase entered yet';
$lang['ai_h_purchases_fix'] = 'Enter every purchase with its price on the day it arrives.';
$lang['ai_h_waste'] = 'Waste entered';
$lang['ai_h_waste_val'] = '%s waste entries in the last %s days (last on %s)';
$lang['ai_h_waste_none'] = 'No waste entered in the last %s days';
$lang['ai_h_waste_fix'] = 'Record waste every day. Weeks without any waste usually mean it is not being recorded.';
$lang['ai_h_negative'] = 'Negative stock';
$lang['ai_h_negative_val'] = '%s ingredients show stock below zero';
$lang['ai_h_negative_ok'] = 'No ingredient is below zero';
$lang['ai_h_negative_fix'] = 'Stock below zero means purchases or transfers were not entered. Enter them, or correct the stock with an inventory adjustment.';
$lang['ai_h_history'] = 'Sales history';
$lang['ai_h_history_val'] = '%s days with sales in the last %s days (first sale on %s)';
$lang['ai_h_history_fix'] = 'Predictions start after 4 weeks of sales; 8 weeks give good accuracy.';
$lang['ai_h_count'] = 'Last stock count';
$lang['ai_h_count_val'] = 'Last inventory adjustment on %s (%s days ago)';
$lang['ai_h_count_none'] = 'No stock count (inventory adjustment) saved yet';
$lang['ai_h_count_fix'] = 'Count the stock once a month and save it as an inventory adjustment.';
$lang['ai_used_menu'] = 'Menu performance';
$lang['ai_used_price'] = 'Price check';
$lang['ai_used_purchase'] = 'Purchase vs usage';
$lang['ai_used_pnl'] = 'Profit & loss';
$lang['ai_used_forecast'] = 'Sales prediction';
$lang['ai_used_waste'] = 'Waste analysis';
$lang['ai_used_shrink'] = 'Stock loss check';
$lang['ai_items_without_recipe'] = 'Best-selling items without a recipe cost';
$lang['ai_qty_sold'] = 'Qty sold';
$lang['ai_item'] = 'Item';
$lang['ai_stock'] = 'Stock';
$lang['ai_save'] = 'Save';

// AI Companies (main admin)
$lang['ai_companies_hint'] = 'Switch AI Insight on per company and set a monthly spending limit. All companies share one API key.';
$lang['ai_company'] = 'Company';
$lang['ai_outlets'] = 'Outlets';
$lang['ai_enabled'] = 'On';
$lang['ai_monthly_limit'] = 'Monthly limit (USD)';
$lang['ai_report_model'] = 'Daily report model';
$lang['ai_chat_model'] = 'Chat model';
$lang['ai_spent_this_month'] = 'Spent this month (USD)';
$lang['ai_calls_this_month'] = 'Calls this month';
$lang['ai_saved'] = 'AI settings saved.';
$lang['ai_invalid_request'] = 'The request could not be verified. Reload the page and try again.';
$lang['ai_invalid_value'] = 'Please check the values: the monthly limit must be between 0 and 1000 USD, and questions per day between 0 and 200.';
$lang['ai_key_status'] = 'API key';
$lang['ai_key_set'] = 'Set on the server';
$lang['ai_key_not_set'] = 'Not set: add ANTHROPIC_API_KEY to application/.env on the server';
$lang['ai_mock_on'] = 'Test mode (AI_MOCK=1): no real API calls are made.';
$lang['ai_test_connection'] = 'Test connection';
$lang['ai_test_ok'] = 'Connection OK: %s replied in %s seconds.';
$lang['ai_test_failed'] = 'Connection failed: %s';

// AI call results
$lang['ai_key_missing'] = 'The AI service is not configured on the server.';
$lang['ai_budget_reached'] = 'This month\'s AI limit for your company has been reached.';
$lang['ai_disabled_company'] = 'AI Insight is switched off for this company.';
$lang['ai_error_generic'] = 'The AI service could not be reached. The numbers are still shown; please try again later.';

// report page
$lang['ai_period_28'] = 'Last 28 days';
$lang['ai_period_7'] = 'Last 7 days';
$lang['ai_report_of'] = 'Report of %s';
$lang['ai_figures_to'] = 'figures up to %s';
$lang['ai_made_at'] = 'made at %s';
$lang['ai_text_ai'] = 'Written by AI';
$lang['ai_text_ai_partly'] = 'Some parts use the standard text because a figure could not be checked.';
$lang['ai_text_standard'] = 'Standard text';
$lang['ai_text_pending'] = 'The AI text is still being written.';
$lang['ai_text_retry'] = 'The AI service did not answer; it tries again within the hour.';
$lang['ai_text_unavailable'] = 'The AI service could not be used for this report.';
$lang['ai_regen_left'] = '%s of %s left today';
$lang['ai_regen_working'] = 'Making the report…';
$lang['ai_regen_busy'] = 'This report is already being made. Wait a minute and reload the page.';
$lang['ai_regen_limit'] = 'A report can be made again %s times a day per outlet. Please try again tomorrow.';
$lang['ai_regen_done'] = 'The report was made again.';
$lang['ai_regen_done_standard'] = 'The report was made again with the standard text (the AI text was not available).';
$lang['ai_no_report'] = 'This outlet has no report yet. Reports are made automatically every day after %s:00.';
$lang['ai_no_report_regen'] = 'Press Regenerate report to make the first one now.';
$lang['ai_old_report'] = 'This is the report of %s. Today\'s report is made after %s:00.';
$lang['ai_note_ai'] = 'AI note';
$lang['ai_note_standard'] = 'Summary';
$lang['ai_gate_blocked'] = 'This section needs more data:';
$lang['ai_gate_limited'] = 'These figures are incomplete:';
$lang['ai_gate_see_health'] = 'See Data Health below.';
$lang['ai_show_all'] = 'Show all (%s)';
$lang['ai_show_less'] = 'Show less';
$lang['ai_vs_prev'] = 'vs the period before';
$lang['ai_against'] = 'against';
$lang['ai_new'] = 'New';
$lang['ai_days'] = '%s days';
$lang['ai_days_unit'] = 'days';
$lang['ai_k_yesterday'] = 'Yesterday';
$lang['ai_k_orders'] = '%s orders';
$lang['ai_k_vs_last_week'] = 'vs same day last week';
$lang['ai_k_target'] = 'Target %s%%';

// top 5
$lang['ai_top5'] = 'Top 5 improvements';
$lang['ai_top5_hint'] = 'Ranked by what each one is worth per month';
$lang['ai_top5_none'] = 'Nothing urgent was found in the last 28 days.';
$lang['ai_per_month'] = 'about %s a month';
$lang['ai_conf_high'] = 'High confidence';
$lang['ai_conf_medium'] = 'Medium confidence';
$lang['ai_conf_low'] = 'Low confidence';
$lang['ai_it_underpriced'] = 'Under-priced';
$lang['ai_it_loss_item'] = 'Sold at a loss';
$lang['ai_it_overbuy'] = 'Over-purchase';
$lang['ai_it_waste'] = 'High waste';
$lang['ai_it_discounts'] = 'Discounts';
$lang['ai_it_sales_drop'] = 'Sales drop';
$lang['ai_it_item_drop'] = 'Item sells less';
$lang['ai_it_expense_jump'] = 'Expense increase';
$lang['ai_it_dog_item'] = 'Weak item';
$lang['ai_it_unsold_item'] = 'Not selling';

// menu performance
$lang['ai_c_star'] = 'Popular, earns well';
$lang['ai_c_plowhorse'] = 'Popular, earns little';
$lang['ai_c_puzzle'] = 'Earns well, sells little';
$lang['ai_c_dog'] = 'Sells little, earns little';
$lang['ai_c_none'] = 'No recipe cost';
$lang['ai_menu_avg_margin'] = 'Average margin per plate: %s. Each item is grouped by its margin against this average and by how often it sells.';
$lang['ai_col_sold'] = 'Sold';
$lang['ai_free'] = '+%s free';
$lang['ai_col_share'] = 'Share of sales';
$lang['ai_col_avg_price'] = 'Avg price';
$lang['ai_col_plate_cost'] = 'Plate cost';
$lang['ai_col_margin'] = 'Margin per plate';
$lang['ai_col_total_margin'] = 'Total margin';
$lang['ai_col_vs_prev'] = 'vs before';
$lang['ai_col_group'] = 'Group';
$lang['ai_unsold'] = 'Not sold in the period (%s)';

// price check
$lang['ai_price_hint'] = 'Target food cost %s%%, suggested prices rounded up to %s';
$lang['ai_b_loss'] = 'Sold at a loss';
$lang['ai_b_critical'] = 'Above 50%';
$lang['ai_b_high'] = 'Well above target';
$lang['ai_b_watch'] = 'Slightly above target';
$lang['ai_b_good'] = 'On target';
$lang['ai_col_price'] = 'Price';
$lang['ai_col_net_price'] = 'Price without VAT';
$lang['ai_col_food_cost'] = 'Food cost';
$lang['ai_col_status'] = 'Status';
$lang['ai_col_sold_28'] = 'Sold (28 days)';
$lang['ai_col_suggested'] = 'Suggested price';
$lang['ai_col_gain'] = 'Extra (28 days)';
$lang['ai_price_foot'] = 'Food cost = plate cost / price without VAT. The dark line on each bar is the target. Extra = what the suggested price would have added on the same quantity.';

// purchase vs usage
$lang['ai_t_bought'] = 'Bought';
$lang['ai_t_used'] = 'Used';
$lang['ai_t_wasted'] = 'Wasted';
$lang['ai_t_shrink'] = 'Lost in stock counts';
$lang['ai_t_excess'] = 'Extra stock bought';
$lang['ai_t_high_waste'] = '%s with high waste';
$lang['ai_t_overbought'] = '%s over-bought';
$lang['ai_col_ingredient'] = 'Ingredient';
$lang['ai_col_bought'] = 'Bought';
$lang['ai_col_transfers'] = 'Transfers in / out';
$lang['ai_col_used'] = 'Used';
$lang['ai_col_wasted'] = 'Wasted';
$lang['ai_col_stock_now'] = 'Stock now';
$lang['ai_col_days_cover'] = 'Stock lasts';
$lang['ai_col_flags'] = 'Flags';
$lang['ai_f_overbuy'] = 'Over-bought';
$lang['ai_f_waste'] = 'High waste';
$lang['ai_f_negative'] = 'Below zero';
$lang['ai_purchase_foot'] = 'Quantities are in the consumption unit. Over-bought = more than %s days of stock (the dark line on the bar) or bought well above what was used and wasted.';

// profit & loss
$lang['ai_col_this'] = 'This period';
$lang['ai_col_prev'] = 'Period before';
$lang['ai_col_change'] = 'Change';
$lang['ai_l_orders'] = 'Orders';
$lang['ai_l_gross'] = 'Sales with VAT';
$lang['ai_l_vat'] = 'VAT';
$lang['ai_l_tips'] = 'Tips (not income)';
$lang['ai_l_net_sales'] = 'Net sales';
$lang['ai_l_mess'] = 'Mess income';
$lang['ai_l_refunds'] = 'Refunds';
$lang['ai_l_food'] = 'Food cost';
$lang['ai_l_waste'] = 'Waste';
$lang['ai_l_salaries'] = 'Salaries paid';
$lang['ai_l_expenses'] = 'Expenses';
$lang['ai_l_net_profit'] = 'Net profit';
$lang['ai_l_included'] = 'Already taken out of net sales';
$lang['ai_l_discounts'] = 'Discounts';
$lang['ai_l_complimentary'] = 'Complimentary items';
$lang['ai_l_staff'] = 'Staff meals';
$lang['ai_l_avg_ticket'] = 'Average bill';
$lang['ai_pnl_foot'] = 'Food cost is the recipe cost of what was sold, mess meals included. Salaries are the payroll payments of this outlet; expenses paid through payroll are not counted twice.';

// sales prediction
$lang['ai_forecast_title'] = 'Sales prediction: next 7 days';
$lang['ai_fc_line'] = 'About %s items worth %s are expected. Busiest day: %s.';
$lang['ai_fc_accuracy'] = 'Over the last 7 days the prediction was off by %s.';
$lang['ai_fc_items'] = 'Expected quantity per item';
$lang['ai_col_total'] = 'Total';
$lang['ai_fc_purchase'] = 'Suggested purchase for the next 7 days';
$lang['ai_fc_no_purchase'] = 'The stock in hand covers the next 7 days.';
$lang['ai_col_need'] = 'Needed';
$lang['ai_col_safety'] = 'Safety stock';
$lang['ai_col_in_stock'] = 'In stock';
$lang['ai_col_buy'] = 'Buy';
$lang['ai_col_est_cost'] = 'Estimated cost';
$lang['ai_fc_foot'] = 'Needed = expected sales x recipe quantities plus the usual waste. Safety stock = extra days of use (AI Settings). Rounded up to whole purchase units.';

// AI Settings
$lang['ai_settings_hint'] = 'Used from the next report: tomorrow after %s:00, or press Regenerate report on the AI Insight page.';
$lang['ai_s_target'] = 'Target food cost';
$lang['ai_s_target_help'] = 'Items above this get a suggested price. Most restaurants aim for 25-35%.';
$lang['ai_s_rounding'] = 'Round suggested prices up to';
$lang['ai_s_rounding_help'] = 'For example, 0.50 turns 21.14 into 21.50.';
$lang['ai_s_overbuy'] = 'Over-purchase when bought is more than';
$lang['ai_s_overbuy_help'] = 'above what was used and wasted in the period.';
$lang['ai_s_cover'] = 'Over-purchase when stock lasts more than';
$lang['ai_s_cover_help'] = 'at the current daily use.';
$lang['ai_s_safety'] = 'Safety stock in suggested purchases';
$lang['ai_s_safety_help'] = 'Extra days of use to keep in hand.';
$lang['ai_settings_saved'] = 'AI settings saved. They apply from the next report.';
$lang['ai_settings_invalid'] = 'Please check the values: target 15-60%, over-purchase 5-200%, stock 3-60 days, safety stock 0-7 days.';

// Ask AI
$lang['ai_chat_daily'] = 'Questions / user / day';
$lang['ai_chat_hint'] = 'Answers come only from this outlet\'s data.';
$lang['ai_chat_new'] = 'New chat';
$lang['ai_chat_close'] = 'Close';
$lang['ai_chat_send'] = 'Send';
$lang['ai_chat_placeholder'] = 'Ask about sales, prices, stock, purchases or costs';
$lang['ai_chat_thinking'] = 'Looking at your data…';
$lang['ai_chat_left'] = '%s of %s questions left today';
$lang['ai_chat_left_chat'] = '%s of %s left in this chat';
$lang['ai_chat_s1'] = 'Why is my food cost high?';
$lang['ai_chat_s2'] = 'What should I buy for the next 3 days?';
$lang['ai_chat_s3'] = 'Which items should I remove from the menu?';
$lang['ai_chat_s4'] = 'How were sales last week compared with the week before?';
$lang['ai_chat_off'] = 'Ask AI is switched off for your company.';
$lang['ai_chat_empty'] = 'Please type a question.';
$lang['ai_chat_too_long'] = 'Please keep the question under %s characters.';
$lang['ai_chat_session_full'] = 'This chat has reached %s questions. Press New chat to continue.';
$lang['ai_chat_busy'] = 'Your previous question is still being answered.';
$lang['ai_chat_day_limit'] = 'You have used all %s questions for today. Please try again tomorrow.';
$lang['ai_chat_refused'] = 'Sorry, I can\'t answer that. Ask about this outlet\'s sales, menu, stock, purchases or costs.';
$lang['ai_chat_failed'] = 'The answer could not be completed. Please try again.';
$lang['ai_chat_reload'] = 'Please reload the page and try again.';
