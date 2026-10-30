<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 | AI Insight module: non-secret settings. Plan: docs/ai_insight_plan.md
 |
 | The Anthropic API key is NOT here. It is read by ai_env('ANTHROPIC_API_KEY')
 | (helpers/ai_helper.php) from a real environment variable or from
 | application/.env, which is git-ignored and blocked from the web
 | (application/.htaccess + the root .htaccess rule for dot files).
 | Template: application/.env.example
 |
 | Per-company values (on/off, monthly $ limit, models, target food cost %)
 | live in tbl_ai_settings; the values below are only the defaults for a new row.
 */

// company whose Admin role may switch AI on/off for every company (AI Companies page)
$config['ai_main_company_id'] = 1;

// models offered on the AI Companies page
$config['ai_models'] = array(
    'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
    'claude-haiku-5-5' => 'Claude Haiku 5.5',
    'claude-opus-5-5' => 'Claude Opus 5.5',
);
$config['ai_default_report_model'] = 'claude-sonnet-5-5';
$config['ai_default_chat_model'] = 'claude-haiku-5-5';

// USD per million tokens, used for tbl_ai_usage.cost_usd (the row keeps the cost of
// its own day, so changing these later does not rewrite history).
// cache_write = 5-minute cache write (1.25 x input). Haiku 5.5 prices are for
// prompts up to 100K tokens; AI Insight prompts stay far below that.
$config['ai_prices'] = array(
    'claude-sonnet-5-5' => array('in' => 2.00, 'out' => 10.00, 'cache_read' => 0.20, 'cache_write' => 2.50),
    'claude-haiku-5-5' => array('in' => 0.10, 'out' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125),
    'claude-opus-5-5' => array('in' => 4.00, 'out' => 20.00, 'cache_read' => 0.20, 'cache_write' => 5.00),
);
// price used for any other model that answers (e.g. a server-side fallback): deliberately high
$config['ai_price_unknown'] = array('in' => 10.00, 'out' => 50.00, 'cache_read' => 1.00, 'cache_write' => 12.50);

// models that accept the server-side refusal fallback (beta server-side-fallback-2026-07-01, "default" form);
// Haiku 5.5 has none, so a refused chat answer just shows the polite error
$config['ai_fallback_models'] = array('claude-sonnet-5-5', 'claude-opus-5-5');

// daily report: built once per outlet per day, after this hour (company local time); the cron runs hourly
$config['ai_report_hour'] = 3;
$config['ai_report_max_attempts'] = 3;
$config['ai_report_max_tokens'] = 8000;
$config['ai_report_effort'] = 'medium';
// the page's Regenerate button: clicks per outlet per day (plan S9)
$config['ai_regen_per_day'] = 3;
// AI Settings page: price rounding steps offered
$config['ai_rounding_steps'] = array(0.01, 0.05, 0.10, 0.25, 0.50, 1, 5, 10);

// defaults of a new tbl_ai_settings row
$config['ai_defaults'] = array(
    'monthly_budget_usd' => 5.00,
    'target_food_cost_pct' => 30,
    'price_rounding' => 0.50,
    'overbuy_factor' => 1.20,
    'cover_days_limit' => 14,
    'safety_days' => 2,
    'chat_daily_limit' => 30,
);

// API call limits
$config['ai_timeout_report'] = 90;   // seconds
$config['ai_timeout_chat'] = 30;     // seconds
$config['ai_max_retries'] = 2;       // SDK retries 408/409/429/5xx and connection errors

// Data Health thresholds (docs/ai_insight_plan.md section 6.9)
$config['ai_health'] = array(
    'window_days' => 28,
    'recipe_cover_green' => 80,      // % of sold quantity with a recipe cost
    'recipe_cover_amber' => 50,
    'purchase_days' => 14,
    'waste_days' => 28,
    'history_days_min' => 28,        // prediction needs this window of sales...
    'history_days_good' => 56,       // ...and is good with this one
    'history_active_ratio' => 0.75,  // share of the window's days that must have sales
    'stock_count_days' => 35,
    'negative_stock_red' => 10,
);
