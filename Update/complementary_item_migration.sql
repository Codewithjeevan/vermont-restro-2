-- ============================================================
-- Feature: Complementary (free) item on POS cart lines
-- Adds a per-item "is_complementary" flag to both the billing
-- and kitchen sale details tables. Purely additive/tracking --
-- the actual price effect is a forced 100% item discount on
-- the existing discount pipeline; this column only tags it as
-- Complementary (not a manual discount) for UI/reload/reporting.
-- Do NOT confuse with the existing "is_free_item" column, which
-- is unrelated (BOGO-style promotion free item).
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_sales_details
    ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_free_item;

ALTER TABLE tbl_kitchen_sales_details
    ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_free_item;

-- Rollback:
-- ALTER TABLE tbl_sales_details DROP COLUMN is_complementary;
-- ALTER TABLE tbl_kitchen_sales_details DROP COLUMN is_complementary;
