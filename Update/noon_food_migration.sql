-- =====================================================================
-- noon Food migration  (integration platform driver #2)
-- See docs/integration_platform_noon_food.md
--
-- Run AFTER, in this order:
--   1. Update/sale_no_format_migration.sql          (hard prerequisite)
--   2. Update/integration_platform_migration.sql    (tables + seed)
--   3. Update/integration_platform_ui_migration.sql (tbl_access 362-370)
--   4. this file
--
-- Additive and safe to re-run. MySQL 8.0 has no ADD COLUMN IF NOT EXISTS,
-- so every ALTER is guarded through information_schema.
--
-- What it does:
--   A. tbl_integration_providers.is_visible - UI-only visibility flag.
--      The client must see exactly one provider card: noon Food.
--      Hidden is NOT disabled: the webhook resolves by is_active only,
--      so hiding is cosmetic and stopping traffic is is_active='No'.
--   B. Hide + deactivate every provider except noon; activate noon.
--   C. tbl_integration_company_credentials - company-level credentials.
--      noon's credential is a service account (one RS256 key + one 30-day
--      session per company); the store id stays per outlet. Outlet rows
--      may still override any key - see Base_channel_driver::credentials().
-- =====================================================================

SET @db := DATABASE();

-- ---------------------------------------------------------------------
-- A. Visibility flag on the provider catalogue
-- ---------------------------------------------------------------------
SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_integration_providers'
      AND COLUMN_NAME='is_visible')=0,
    'ALTER TABLE tbl_integration_providers ADD COLUMN is_visible ENUM(''Yes'',''No'') NOT NULL DEFAULT ''Yes''',
    'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- B. Only noon Food is visible and active.
--    The other rows keep their code and drivers - one UPDATE brings any
--    of them back. is_active='No' is what actually stops their traffic.
-- ---------------------------------------------------------------------
UPDATE tbl_integration_providers SET is_visible = 'No', is_active = 'No'
 WHERE code IN ('talabat','deliveroo','careem','deliverect');

UPDATE tbl_integration_providers
   SET is_visible = 'Yes', is_active = 'Yes',
       name = 'noon Food', driver_class = 'Noon_food_driver',
       capabilities = 'order_in,status_out', sort_order = 1
 WHERE code = 'noon';

-- ---------------------------------------------------------------------
-- C. Company-level credentials (one row per company x provider).
--    Mirrors the credential columns of tbl_integration_configs; the outlet
--    row's blob overrides key-by-key. oauth_token doubles as the cached
--    session cookie for cookie-auth providers - one login shared by every
--    outlet of the company instead of N outlets thrashing N sessions.
--    Talabat has no row here and keeps its per-outlet path byte-identical.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_integration_company_credentials (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  company_id  INT NOT NULL,
  provider_id INT NOT NULL,

  credentials    TEXT DEFAULT NULL,          -- encrypted JSON: private_key, key_id, project_code, channel_identifier, base_url, sandbox_base_url, login_url, webhook_header, user_agent ...
  webhook_secret VARCHAR(190) DEFAULT NULL,  -- encrypted; the static header value noon sends on every delivery

  -- token/session cache (filled by Base_channel_driver::session_cookie / oauth)
  oauth_token          TEXT DEFAULT NULL,
  oauth_expires_at_utc DATETIME DEFAULT NULL,

  created_at_utc DATETIME DEFAULT NULL,
  updated_at_utc DATETIME DEFAULT NULL,

  UNIQUE KEY uq_company_provider (company_id, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Verification: expect exactly one visible row (noon Food, active) and
-- the company credentials table to exist.
-- ---------------------------------------------------------------------
SELECT code, name, driver_class, capabilities, is_active, is_visible
  FROM tbl_integration_providers ORDER BY sort_order;
