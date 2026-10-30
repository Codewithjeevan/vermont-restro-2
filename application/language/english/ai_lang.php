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
$lang['ai_reports_coming'] = 'Menu performance, price check, purchase vs usage, profit & loss, sales prediction and the top 5 improvements will appear here once the daily report runs. Fix the red items below first: every section depends on them.';

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
$lang['ai_invalid_value'] = 'Please check the values: the monthly limit must be between 0 and 1000 USD.';
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
