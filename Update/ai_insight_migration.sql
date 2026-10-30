-- AI Insight module: database changes. Select your database in phpMyAdmin, then run once.
-- Safe to run again: INSERT IGNORE, CREATE TABLE IF NOT EXISTS, and guarded index creation.
--
-- Daily per-outlet analysis (menu performance, price check, purchase vs usage, P&L,
-- 7-day prediction, top 5 improvements, data health) + an Ask AI chat.
-- Plan: docs/ai_insight_plan.md
--
-- The tbl_access ids are fixed on purpose: Ai_insight::ACCESS_* hard-codes them and
-- userHome.php uses them in data-access. Before running on a live database check that
-- ids 437-442 and tbl_main_modules id 14 are not taken by something else.
--
-- The Anthropic API key is NOT stored in the database: it goes in application/.env
-- (see application/.env.example).

INSERT IGNORE INTO tbl_main_modules (id, name, del_status) VALUES (14, 'ai_insight', 'Live');

INSERT IGNORE INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
  (437, 'ai_insight',  '',           'ai_insight',    0,   14,   'Live'),
  (438, '',            'view',       'view',          437, NULL, 'Live'),
  (439, '',            'chat',       'ai_ask',        437, NULL, 'Live'),
  (440, '',            'regenerate', 'ai_regenerate', 437, NULL, 'Live'),
  (441, 'ai_settings', '',           'ai_settings',   0,   14,   'Live'),
  (442, '',            'update',     'update',        441, NULL, 'Live');
UPDATE tbl_access SET del_status = 'Live' WHERE id BETWEEN 437 AND 442;

-- one row per company; AI stays off until the main admin switches it on (AI Companies page)
CREATE TABLE IF NOT EXISTS tbl_ai_settings (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'set only by the main admin (company 1, role Admin)',
  monthly_budget_usd DECIMAL(10,2) NOT NULL DEFAULT 5.00 COMMENT 'calls stop once tbl_ai_usage for the month reaches this',
  report_model VARCHAR(50) NOT NULL DEFAULT 'claude-sonnet-5-5',
  chat_model VARCHAR(50) NOT NULL DEFAULT 'claude-haiku-5-5',
  chat_enabled TINYINT(1) NOT NULL DEFAULT 1,
  chat_daily_limit INT NOT NULL DEFAULT 30 COMMENT 'questions per user per day',
  target_food_cost_pct DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  price_rounding DECIMAL(8,2) NOT NULL DEFAULT 0.50 COMMENT 'suggested prices are rounded up to this step',
  overbuy_factor DECIMAL(5,2) NOT NULL DEFAULT 1.20 COMMENT 'purchased > (used + wasted) x this = over-purchase',
  cover_days_limit INT NOT NULL DEFAULT 14 COMMENT 'more days of stock than this = over-purchase',
  safety_days INT NOT NULL DEFAULT 2 COMMENT 'extra days of usage kept when suggesting purchases',
  updated_by INT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ai_settings_company (company_id)
) ENGINE=InnoDB;

-- the daily report of one outlet: computed numbers + the AI text
CREATE TABLE IF NOT EXISTS tbl_ai_snapshots (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  outlet_id INT NOT NULL,
  report_date DATE NOT NULL COMMENT 'the day the report was made for (company local date)',
  window_days INT NOT NULL DEFAULT 28,
  metrics_json MEDIUMTEXT NULL COMMENT 'every number shown on the page, computed in PHP/SQL',
  narrative_json MEDIUMTEXT NULL COMMENT 'AI text, validated against metrics_json',
  narrative_status VARCHAR(10) NOT NULL DEFAULT 'pending' COMMENT 'pending / ok / template / failed',
  attempts INT NOT NULL DEFAULT 0,
  error VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ai_snapshot_outlet_day (outlet_id, report_date),
  KEY ix_ai_snapshot_company (company_id, report_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_ai_chat_sessions (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  outlet_id INT NOT NULL,
  user_id INT NOT NULL,
  questions INT NOT NULL DEFAULT 0,
  status VARCHAR(10) NOT NULL DEFAULT 'open' COMMENT 'open / closed',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_ai_chat_user (user_id, created_at)
) ENGINE=InnoDB;

-- append-only: content_json is the exact message content sent to / received from the API
CREATE TABLE IF NOT EXISTS tbl_ai_chat_messages (
  id BIGINT NOT NULL AUTO_INCREMENT,
  session_id INT NOT NULL,
  role VARCHAR(10) NOT NULL COMMENT 'user / assistant',
  content_json MEDIUMTEXT NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_ai_chat_session (session_id)
) ENGINE=InnoDB;

-- one row per API call (or per call refused before sending), for limits and the usage page
CREATE TABLE IF NOT EXISTS tbl_ai_usage (
  id BIGINT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  outlet_id INT NULL,
  user_id INT NULL,
  feature VARCHAR(20) NOT NULL COMMENT 'report / chat / test',
  model VARCHAR(50) NOT NULL,
  input_tokens INT NOT NULL DEFAULT 0,
  cache_read_tokens INT NOT NULL DEFAULT 0,
  cache_write_tokens INT NOT NULL DEFAULT 0,
  output_tokens INT NOT NULL DEFAULT 0,
  cost_usd DECIMAL(12,6) NOT NULL DEFAULT 0,
  status VARCHAR(10) NOT NULL DEFAULT 'ok' COMMENT 'ok / refusal / error / blocked',
  error VARCHAR(255) NULL,
  request_id VARCHAR(64) NULL,
  duration_ms INT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_ai_usage_company (company_id, created_at),
  KEY ix_ai_usage_user (user_id, feature, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_ai_cron_log (
  id INT NOT NULL AUTO_INCREMENT,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  companies INT NOT NULL DEFAULT 0,
  outlets_done INT NOT NULL DEFAULT 0,
  errors INT NOT NULL DEFAULT 0,
  message TEXT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Indexes for the analysis queries (they also speed up the existing reports).
-- MySQL has no ADD INDEX IF NOT EXISTS, so each one is created only when missing.
SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_sales' AND index_name = 'ix_ai_outlet_sale_date') = 0,
  'ALTER TABLE tbl_sales ADD INDEX ix_ai_outlet_sale_date (outlet_id, sale_date, order_status)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_sales_details' AND index_name = 'ix_ai_sales_id') = 0,
  'ALTER TABLE tbl_sales_details ADD INDEX ix_ai_sales_id (sales_id)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_sale_consumptions_of_menus' AND index_name = 'ix_ai_sales_id') = 0,
  'ALTER TABLE tbl_sale_consumptions_of_menus ADD INDEX ix_ai_sales_id (sales_id)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_sale_consumptions_of_menus' AND index_name = 'ix_ai_outlet_ingredient') = 0,
  'ALTER TABLE tbl_sale_consumptions_of_menus ADD INDEX ix_ai_outlet_ingredient (outlet_id, ingredient_id)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_purchase_ingredients' AND index_name = 'ix_ai_outlet_ingredient') = 0,
  'ALTER TABLE tbl_purchase_ingredients ADD INDEX ix_ai_outlet_ingredient (outlet_id, ingredient_id)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

SET @ai_sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tbl_waste_ingredients' AND index_name = 'ix_ai_outlet_ingredient') = 0,
  'ALTER TABLE tbl_waste_ingredients ADD INDEX ix_ai_outlet_ingredient (outlet_id, ingredient_id)', 'SELECT 1');
PREPARE ai_stmt FROM @ai_sql; EXECUTE ai_stmt; DEALLOCATE PREPARE ai_stmt;

-- Ingredient cost fix (plan finding F1): the Profit & Loss report costs food with
-- average_consumption_per_unit, but a purchase only refreshed consumption_unit_cost.
-- Purchase::syncIngredientPrice now updates both; this brings existing rows in line once.
UPDATE tbl_ingredients SET average_consumption_per_unit = consumption_unit_cost
WHERE consumption_unit_cost > 0 AND (average_consumption_per_unit IS NULL OR average_consumption_per_unit <> consumption_unit_cost);
