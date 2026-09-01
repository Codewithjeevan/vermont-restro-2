-- =====================================================================
-- Integration Platform migration, part 2 — admin screens (phase 3/4)
--
-- Adds the tbl_access rows for the three new Setting screens. Without these
-- the menu items are stripped from the sidebar for EVERY user including
-- Admin, because user_home_buttom.js removes any <li> whose data-access is
-- not in window.menu_objects.
--
-- Run AFTER Update/integration_platform_migration.sql:
--   mysql -uroot restodb < Update/integration_platform_ui_migration.sql
--
-- !! AFTER RUNNING THIS, EVERY USER MUST LOG OUT AND BACK IN. !!
-- function_access is built once at login and never refreshed.
--
-- The ids below are fixed on purpose: this codebase hard-codes tbl_access ids
-- in controllers (see Integration::__construct). They are mirrored by the
-- ACCESS_* constants in application/controllers/Integration.php - change one
-- and you must change the other. The verification query at the bottom prints
-- what actually landed; if a row is missing, ids 362-370 were already taken on
-- that install and both files need renumbering.
--
-- main_module_id = 3 is the "Setting" group (same as Tax Setting, Self Order
-- Setting, Online Order Setting).
-- =====================================================================

INSERT IGNORE INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
  (362, 'integration_settings',  '',       'integration_settings',  0,   3,    'Live'),
  (363, '',                      'view',   'view',                  362, NULL, 'Live'),
  (364, '',                      'update', 'update',                362, NULL, 'Live'),

  (365, 'integration_item_map',  '',       'integration_item_map',  0,   3,    'Live'),
  (366, '',                      'view',   'view',                  365, NULL, 'Live'),
  (367, '',                      'update', 'update',                365, NULL, 'Live'),

  (368, 'integration_order_log', '',       'integration_order_log', 0,   3,    'Live'),
  (369, '',                      'view',   'view',                  368, NULL, 'Live'),
  (370, '',                      'update', 'update',                368, NULL, 'Live');

-- ---------------------------------------------------------------------
-- Give every existing role that can already reach Online Order Setting
-- (access id 334) the same reach into Integrations. Without this, a
-- non-Admin manager would have to be re-permissioned by hand.
-- Admin bypasses tbl_role_access entirely (checkAccess returns true early).
-- ---------------------------------------------------------------------
-- tbl_role_access has no unique key beyond its PK, so INSERT IGNORE would not
-- dedupe and a second run would double every row. The LEFT JOIN ... IS NULL is
-- what makes this re-runnable. Both sides are derived tables so MySQL
-- materialises them before the insert touches the same table.
INSERT INTO tbl_role_access (role_id, access_parent_id, access_child_id, del_status)
SELECT s.role_id, s.parent_id, s.child_id, 'Live'
FROM (
  SELECT DISTINCT src.role_id, n.parent_id, n.child_id
  FROM (
    SELECT role_id FROM tbl_role_access WHERE access_parent_id = 334 AND del_status = 'Live'
  ) src
  CROSS JOIN (
    SELECT 362 AS parent_id, 363 AS child_id UNION ALL
    SELECT 362, 364 UNION ALL
    SELECT 365, 366 UNION ALL
    SELECT 365, 367 UNION ALL
    SELECT 368, 369 UNION ALL
    SELECT 368, 370
  ) n
) s
LEFT JOIN (
  SELECT role_id, access_parent_id, access_child_id FROM tbl_role_access
) existing
  ON  existing.role_id          = s.role_id
  AND existing.access_parent_id = s.parent_id
  AND existing.access_child_id  = s.child_id
WHERE existing.role_id IS NULL;

-- ---------------------------------------------------------------------
-- Status_dispatcher runs on EVERY settle, and finds the integration order by
-- sale_no (tbl_sales carries no channel columns until we backfill them).
-- Without this index that is a table scan on every bill closed in the POS.
-- ---------------------------------------------------------------------
SET @db := DATABASE();
SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_integration_orders' AND INDEX_NAME='ix_sale_no')=0,
    'ALTER TABLE tbl_integration_orders ADD INDEX ix_sale_no (sale_no)', 'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- Verification. Expect 9 access rows.
-- ---------------------------------------------------------------------
SELECT id, module_name, function_name, parent_id, main_module_id, del_status
FROM tbl_access
WHERE id BETWEEN 362 AND 370
ORDER BY id;
