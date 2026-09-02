-- =====================================================================
-- Integration Platform migration  (phase 1 + 2)
-- Talabat first, any channel next.  See docs/integration_platform_talabat_uae.md
--
-- Additive and safe to re-run.  Target: MySQL 8.0 (this install is 8.0.30),
-- which does NOT support "ALTER TABLE ... ADD COLUMN IF NOT EXISTS", so every
-- ALTER below is guarded through information_schema + a prepared statement.
--
-- Run with:  mysql -uroot restodb < Update/integration_platform_migration.sql
-- =====================================================================

SET @db := DATABASE();

-- ---------------------------------------------------------------------
-- 1. Catalogue of supported apps.
--    Adding a provider later = one INSERT here + one driver file.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_providers (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(40)  NOT NULL,
  name         VARCHAR(80)  NOT NULL,
  driver_class VARCHAR(80)  NOT NULL,
  capabilities VARCHAR(255) NOT NULL DEFAULT '',
  logo         VARCHAR(120) DEFAULT NULL,
  is_active    ENUM('Yes','No') NOT NULL DEFAULT 'Yes',
  sort_order   INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_provider_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. Per company + outlet + provider settings.  THIS holds the on/off switch.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_configs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  company_id  INT NOT NULL,
  outlet_id   INT NOT NULL,
  provider_id INT NOT NULL,
  is_enabled  ENUM('Yes','No') NOT NULL DEFAULT 'No',

  -- their id for this branch; the webhook carries this and nothing else,
  -- so it is the only way to resolve company_id / outlet_id for an inbound order
  external_store_id VARCHAR(80) DEFAULT NULL,

  credentials    TEXT DEFAULT NULL,          -- encrypted JSON: client_id, client_secret, base_url ...
  webhook_secret VARCHAR(190) DEFAULT NULL,  -- encrypted; HMAC key for inbound verification

  ingest_on   ENUM('placed','accepted') NOT NULL DEFAULT 'accepted',
  auto_accept ENUM('Yes','No')          NOT NULL DEFAULT 'No',
  default_prep_minutes INT NOT NULL DEFAULT 20,

  price_mode         ENUM('delivery','normal') NOT NULL DEFAULT 'delivery',
  price_source       ENUM('provider','pos')    NOT NULL DEFAULT 'provider',
  price_includes_tax ENUM('Yes','No')          NOT NULL DEFAULT 'Yes',

  payment_method_id   INT DEFAULT NULL,   -- settlement method, e.g. "Talabat Credit"
  delivery_partner_id INT DEFAULT NULL,   -- link to existing tbl_delivery_partners row
  customer_id         INT DEFAULT NULL,   -- synthetic channel customer (auto-created on first order)
  ingest_user_id      INT DEFAULT NULL,   -- POS user the injected sale is booked under
  counter_id          INT NOT NULL DEFAULT 0,

  auto_settle    ENUM('Yes','No') NOT NULL DEFAULT 'No',
  push_status_on VARCHAR(190) NOT NULL DEFAULT 'ACCEPTED,READY,COMPLETED,CANCELLED',
  is_sandbox     ENUM('Yes','No') NOT NULL DEFAULT 'Yes',

  -- OAuth token cache (filled by Base_channel_driver::oauth_token)
  oauth_token           TEXT DEFAULT NULL,
  oauth_expires_at_utc  DATETIME DEFAULT NULL,

  created_at_utc DATETIME DEFAULT NULL,
  updated_at_utc DATETIME DEFAULT NULL,

  UNIQUE KEY uq_cfg (company_id, outlet_id, provider_id),
  KEY ix_store (provider_id, external_store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. Item mapping: our menu <-> their menu.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_item_map (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  config_id INT NOT NULL,
  map_type  ENUM('item','modifier') NOT NULL DEFAULT 'item',
  external_item_id VARCHAR(80) NOT NULL,
  external_name    VARCHAR(190) DEFAULT NULL,   -- last name seen from the provider, for the mapping UI
  food_menu_id INT DEFAULT NULL,
  modifier_id  INT DEFAULT NULL,
  UNIQUE KEY uq_map (config_id, map_type, external_item_id),
  KEY ix_menu (food_menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. One row per inbound order.  uq_ext is the idempotency guarantee:
--    an aggregator retry can never punch the same order twice.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  config_id     INT NOT NULL,
  provider_code VARCHAR(40) NOT NULL,
  external_order_id VARCHAR(80) NOT NULL,
  external_order_no VARCHAR(40) DEFAULT NULL,
  company_id INT NOT NULL,
  outlet_id  INT NOT NULL,
  kitchen_sale_id INT DEFAULT NULL,
  sale_id         INT DEFAULT NULL,
  sale_no         VARCHAR(60) DEFAULT NULL,
  canonical_status VARCHAR(20) NOT NULL DEFAULT 'RECEIVED',
  reject_reason    VARCHAR(190) DEFAULT NULL,
  last_error       VARCHAR(255) DEFAULT NULL,
  raw_payload  LONGTEXT,
  received_at_utc  DATETIME NOT NULL,
  ingested_at_utc  DATETIME DEFAULT NULL,
  accepted_at_utc  DATETIME DEFAULT NULL,
  completed_at_utc DATETIME DEFAULT NULL,
  UNIQUE KEY uq_ext (provider_code, external_order_id),
  KEY ix_outlet_status (outlet_id, canonical_status),
  KEY ix_kitchen_sale (kitchen_sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. Outbound work queue (phase 3 consumes it; phase 2 already fills it).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  integration_order_id INT NOT NULL,
  direction  ENUM('out','in') NOT NULL DEFAULT 'out',
  event_type VARCHAR(40) NOT NULL,      -- status.push | order.accept | order.reject
  payload    TEXT,
  status     ENUM('pending','sent','failed','dead') NOT NULL DEFAULT 'pending',
  attempts   INT NOT NULL DEFAULT 0,
  last_error VARCHAR(255) DEFAULT NULL,
  next_attempt_at_utc DATETIME DEFAULT NULL,
  created_at_utc DATETIME NOT NULL,
  KEY ix_due (status, next_attempt_at_utc),
  KEY ix_order (integration_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. Raw call log, inbound and outbound, for disputes and support.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  config_id     INT DEFAULT NULL,
  provider_code VARCHAR(40) DEFAULT NULL,
  direction ENUM('in','out') NOT NULL,
  endpoint  VARCHAR(190) DEFAULT NULL,
  http_status INT DEFAULT NULL,
  request_body  LONGTEXT,
  response_body LONGTEXT,
  duration_ms INT DEFAULT NULL,
  created_at_utc DATETIME NOT NULL,
  KEY ix_prov_time (provider_code, created_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7. Tag the sale itself so every existing report can filter by channel.
--    Guarded ALTERs (MySQL 8 has no ADD COLUMN IF NOT EXISTS).
-- ---------------------------------------------------------------------
SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_kitchen_sales' AND COLUMN_NAME='channel_code')=0,
    'ALTER TABLE tbl_kitchen_sales ADD COLUMN channel_code VARCHAR(40) NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_kitchen_sales' AND COLUMN_NAME='external_order_id')=0,
    'ALTER TABLE tbl_kitchen_sales ADD COLUMN external_order_id VARCHAR(80) NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_kitchen_sales' AND COLUMN_NAME='integration_order_id')=0,
    'ALTER TABLE tbl_kitchen_sales ADD COLUMN integration_order_id INT NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_sales' AND COLUMN_NAME='channel_code')=0,
    'ALTER TABLE tbl_sales ADD COLUMN channel_code VARCHAR(40) NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_sales' AND COLUMN_NAME='external_order_id')=0,
    'ALTER TABLE tbl_sales ADD COLUMN external_order_id VARCHAR(80) NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_sales' AND COLUMN_NAME='integration_order_id')=0,
    'ALTER TABLE tbl_sales ADD COLUMN integration_order_id INT NULL DEFAULT NULL', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- index so the Order Log and channel reports do not table-scan
SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_kitchen_sales' AND INDEX_NAME='ix_channel')=0,
    'ALTER TABLE tbl_kitchen_sales ADD INDEX ix_channel (channel_code, external_order_id)', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_sales' AND INDEX_NAME='ix_channel')=0,
    'ALTER TABLE tbl_sales ADD INDEX ix_channel (channel_code, external_order_id)', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- 8. Seed the catalogue.  Only Talabat has a driver today; the rest are
--    catalogue rows so the settings grid can render them as "coming soon".
-- ---------------------------------------------------------------------
INSERT INTO tbl_integration_providers (code, name, driver_class, capabilities, is_active, sort_order) VALUES
 ('talabat',    'Talabat',            'Talabat_driver',    'order_in,status_out,store_status',                     'Yes', 1),
 ('deliveroo',  'Deliveroo',          'Deliveroo_driver',  'order_in,status_out,menu_push,store_status',           'No',  2),
 ('noon',       'noon Food',          'Noon_food_driver',  'order_in,status_out',                                  'No',  3),
 ('careem',     'Careem Food',        'Careem_driver',     'order_in,status_out',                                  'No',  4),
 ('deliverect', 'Deliverect (multi)', 'Deliverect_driver', 'order_in,status_out,menu_push,store_status',           'No',  9)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  driver_class = VALUES(driver_class),
  capabilities = VALUES(capabilities);
