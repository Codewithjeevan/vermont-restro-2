-- ============================================================================
-- CLIENT UPDATE - database changes of this release
--
-- Covers:
--   1) Shared Running Orders  (tbl_running_orders becomes company-wide)
--   2) On-screen Numpad       (tbl_companies.is_numpad_enable)
--   3) Invoice counter safety (tbl_sale_no_counters)
--
-- HOW TO APPLY
--   Import this file once into the restaurant database (phpMyAdmin > Import,
--   or:  mysql -u root -p <database_name> < client_update_running_orders_numpad.sql)
--
-- NOTE: run it ONCE. If a step was already applied you will get an error like
--       "Duplicate column name" / "Duplicate key name" - that error is harmless,
--       just skip that single statement and continue with the next one.
--
-- Take a database backup before running.
-- ============================================================================


-- ============================================================================
-- 1) SHARED RUNNING ORDERS
--    Running orders are no longer private to the user who took them.
--    They are stored per outlet/company so every POS terminal of the
--    company sees the same "Running Orders" list.
-- ============================================================================

-- 1.1 bigger order body (large bills / long item names)
ALTER TABLE tbl_running_orders MODIFY order_content MEDIUMTEXT NOT NULL;

-- 1.2 new columns
ALTER TABLE tbl_running_orders ADD COLUMN record_meta TEXT NULL AFTER order_content;
ALTER TABLE tbl_running_orders ADD COLUMN outlet_id INT NOT NULL DEFAULT 0 AFTER user_id;
ALTER TABLE tbl_running_orders ADD COLUMN company_id INT NOT NULL DEFAULT 0 AFTER outlet_id;
ALTER TABLE tbl_running_orders ADD COLUMN version INT NOT NULL DEFAULT 1 AFTER company_id;
ALTER TABLE tbl_running_orders ADD COLUMN updated_at DATETIME NULL AFTER version;

-- 1.3 old parked orders: attach them to their outlet/company so they show up
UPDATE tbl_running_orders r
JOIN tbl_kitchen_sales k ON k.sale_no = r.sale_no AND k.del_status = 'Live'
SET r.outlet_id = k.outlet_id, r.company_id = k.company_id
WHERE r.outlet_id = 0;

-- 1.4 remove duplicate rows of the same order (keep the newest one)
DELETE r1 FROM tbl_running_orders r1
JOIN tbl_running_orders r2
  ON r2.sale_no = r1.sale_no AND r2.company_id = r1.company_id AND r2.id > r1.id;

-- 1.5 one row per order per company + lookup index
ALTER TABLE tbl_running_orders ADD UNIQUE KEY uk_running_orders_sale_no_company (sale_no, company_id);
ALTER TABLE tbl_running_orders ADD INDEX idx_running_orders_scope (company_id, outlet_id, del_status);


-- ============================================================================
-- 2) ON-SCREEN NUMPAD (POS touch keypad)
--    0 = off (default, POS works exactly as before)
--    1 = on  (keypad opens on amount / discount fields)
--    Switched from: Settings > On-screen Numpad (POS)
-- ============================================================================

ALTER TABLE tbl_companies ADD COLUMN is_numpad_enable TINYINT(1) NOT NULL DEFAULT 0;

-- turn it on for every company right away (optional - remove the -- to use):
-- UPDATE tbl_companies SET is_numpad_enable = 1;


-- ============================================================================
-- 3) INVOICE COUNTER SAFETY
--    Invoice numbers are handed out by the server (INV-<company_id>-<n>).
--    These two statements only make sure the counter table exists and is
--    never behind the bills already in the database. Safe to re-run.
-- ============================================================================

CREATE TABLE IF NOT EXISTS tbl_sale_no_counters (
    company_id INT(11) NOT NULL,
    last_no BIGINT(20) NOT NULL DEFAULT 0,
    PRIMARY KEY (company_id)
) ENGINE=InnoDB;

INSERT INTO tbl_sale_no_counters (company_id, last_no)
SELECT company_id, MAX(seq) FROM (
    SELECT company_id,
           CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
      FROM tbl_sales
     WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
    UNION ALL
    SELECT company_id,
           CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
      FROM tbl_kitchen_sales
     WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
) t
GROUP BY company_id
ON DUPLICATE KEY UPDATE last_no = GREATEST(tbl_sale_no_counters.last_no, VALUES(last_no));


-- ============================================================================
-- OPTIONAL - only if the client complains about gaps in invoice numbers
--
-- Old terminals reserved 20 numbers each in advance, so the counter can be
-- ahead of the last printed bill and the next bill continues after that gap.
-- The statement below pulls the counter back to the last number really used.
--
-- RUN IT ONLY AFTER every POS screen has been reloaded with the new version,
-- otherwise a terminal still holding an old reserved number can repeat it.
-- Remove the -- in front of the lines to use it.
-- ============================================================================

-- UPDATE tbl_sale_no_counters c
-- JOIN (
--     SELECT company_id, MAX(seq) AS max_seq FROM (
--         SELECT company_id, CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
--           FROM tbl_sales WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
--         UNION ALL
--         SELECT company_id, CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED)
--           FROM tbl_kitchen_sales WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
--         UNION ALL
--         SELECT company_id, CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED)
--           FROM tbl_running_orders WHERE company_id > 0 AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
--     ) t GROUP BY company_id
-- ) m ON m.company_id = c.company_id
-- SET c.last_no = m.max_seq
-- WHERE c.last_no > m.max_seq;


-- ============================================================================
-- OPTIONAL - only if item names are in Arabic / non-English and show as ??? 
-- ALTER TABLE tbl_running_orders CONVERT TO CHARACTER SET utf8mb4;
-- ============================================================================

-- ============================================================================
-- ROLLBACK (if ever needed)
-- ALTER TABLE tbl_companies DROP COLUMN is_numpad_enable;
-- ALTER TABLE tbl_running_orders
--     DROP INDEX uk_running_orders_sale_no_company,
--     DROP INDEX idx_running_orders_scope,
--     DROP COLUMN record_meta, DROP COLUMN outlet_id, DROP COLUMN company_id,
--     DROP COLUMN version, DROP COLUMN updated_at;
-- ============================================================================
