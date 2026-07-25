-- ============================================================
-- Feature: Re-order KOT delta + VOID on quantity decrease
-- Adds a per-item "void_qty" (how many units were cancelled on
-- the current KOT round) to the kitchen sale details table.
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_kitchen_sales_details
    ADD COLUMN void_qty INT NOT NULL DEFAULT 0 AFTER tmp_qty;

-- Rollback:
-- ALTER TABLE tbl_kitchen_sales_details DROP COLUMN void_qty;
