-- ============================================================
-- Feature: Shared running orders (company-wide POS sidebar)
--
-- tbl_running_orders becomes the live mirror of every running order
-- of an outlet. Each POS terminal pushes its running orders here on
-- every change and pulls the outlet's list every few seconds, so any
-- user of the company sees the same "Running Orders" sidebar and the
-- logout "save / pull your running orders" step is gone.
--
--  - outlet_id / company_id : scope of the list (was per user_id)
--  - record_meta            : the browser-side record minus the order
--                             blob (kot_print, is_invoice, ...) so a
--                             terminal can rebuild the exact record
--  - version                : bumped on every save; terminals use it
--                             to detect changes made elsewhere
--  - order_content          : MEDIUMTEXT + utf8mb4 (large orders,
--                             non-latin item names)
--
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_running_orders CONVERT TO CHARACTER SET utf8mb4;

ALTER TABLE tbl_running_orders
    MODIFY order_content MEDIUMTEXT NOT NULL,
    ADD COLUMN record_meta TEXT NULL AFTER order_content,
    ADD COLUMN outlet_id INT NOT NULL DEFAULT 0 AFTER user_id,
    ADD COLUMN company_id INT NOT NULL DEFAULT 0 AFTER outlet_id,
    ADD COLUMN version INT NOT NULL DEFAULT 1 AFTER company_id,
    ADD COLUMN updated_at DATETIME NULL AFTER version;

-- Orders parked by the old per-user "pull" flow: attach them to their
-- outlet so they appear in the shared list instead of being orphaned.
UPDATE tbl_running_orders r
JOIN tbl_kitchen_sales k ON k.sale_no = r.sale_no AND k.del_status = 'Live'
SET r.outlet_id = k.outlet_id, r.company_id = k.company_id
WHERE r.outlet_id = 0;

-- One row per order per company; Sale::save_running_order upserts on
-- this key. Drop leftover duplicates from the old flow first (keep newest).
DELETE r1 FROM tbl_running_orders r1
JOIN tbl_running_orders r2
  ON r2.sale_no = r1.sale_no AND r2.company_id = r1.company_id AND r2.id > r1.id;

ALTER TABLE tbl_running_orders
    ADD UNIQUE KEY uk_running_orders_sale_no_company (sale_no, company_id),
    ADD INDEX idx_running_orders_scope (company_id, outlet_id, del_status);

-- Rollback:
-- ALTER TABLE tbl_running_orders
--     DROP INDEX uk_running_orders_sale_no_company,
--     DROP INDEX idx_running_orders_scope,
--     DROP COLUMN record_meta, DROP COLUMN outlet_id, DROP COLUMN company_id,
--     DROP COLUMN version, DROP COLUMN updated_at;
