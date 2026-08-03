-- ============================================================
-- Feature: Staff Meal (discounted meal for own staff)
--
-- 1) tbl_companies.staff_meal_percentage
--    Global (per company/outlet) discount percentage set from
--    Settings. 0 = feature off, the POS button stays hidden.
--
-- 2) tbl_sales.is_staff_meal
--    Flag on the settled bill so the sale list / reports can tell
--    a staff meal apart from a normal discounted sale. The money
--    itself rides the existing finalize-discount pipeline
--    (sub_total_discount_finalize -> total_payable /
--    sub_total_discount_amount / total_discount_amount), so no new
--    price math and no report changes are required.
--
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_companies
    ADD COLUMN staff_meal_percentage DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER service_amount;

ALTER TABLE tbl_sales
    ADD COLUMN is_staff_meal TINYINT(1) NOT NULL DEFAULT 0 AFTER order_status;

-- Rollback:
-- ALTER TABLE tbl_companies DROP COLUMN staff_meal_percentage;
-- ALTER TABLE tbl_sales DROP COLUMN is_staff_meal;
