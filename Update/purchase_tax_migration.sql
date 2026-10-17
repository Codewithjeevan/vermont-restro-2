-- ============================================================
-- Feature: overall tax on a purchase (Exclusive / Inclusive)
-- One tax type + one tax % for the whole purchase. The form
-- defaults both from the company tax settings (tax_type and
-- the tax_setting rates), the user can change them per purchase.
--   tax_type    1 = Exclusive (tax added on top of the subtotal)
--               2 = Inclusive (tax already inside the subtotal)
--   tax_percent the rate used, e.g. 5
--   tax_amount  the tax money in grand_total
-- Old purchases get 0% so their totals do not change.
-- Until this is applied the purchase screens work as before,
-- without the tax inputs.
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_purchase
    ADD COLUMN tax_type TINYINT(1) NOT NULL DEFAULT 1 AFTER subtotal,
    ADD COLUMN tax_percent FLOAT NOT NULL DEFAULT 0 AFTER tax_type,
    ADD COLUMN tax_amount FLOAT NOT NULL DEFAULT 0 AFTER tax_percent;

-- subtotal was never saved before; for old purchases it equals the grand total
UPDATE tbl_purchase SET subtotal = grand_total WHERE subtotal IS NULL;

-- Rollback:
-- ALTER TABLE tbl_purchase DROP COLUMN tax_type, DROP COLUMN tax_percent, DROP COLUMN tax_amount;
