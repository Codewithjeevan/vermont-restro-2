-- ============================================================
-- Feature: POS on-screen numpad (touch keypad)
--
-- tbl_companies.is_numpad_enable
--    Per company/outlet switch for the touch keypad that opens when
--    a cashier taps an amount or discount field in the POS.
--    0 = off (default). The keypad script/style is not even loaded
--        and every field behaves exactly as before.
--    1 = on. Meant for single-screen / touch-only POS terminals,
--        where there is no comfortable physical keyboard.
--
-- Set from Settings -> On-screen Numpad (POS).
--
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_companies
    ADD COLUMN is_numpad_enable TINYINT(1) NOT NULL DEFAULT 0 AFTER staff_meal_percentage;

-- Turn it on for every existing company in one go (optional):
-- UPDATE tbl_companies SET is_numpad_enable = 1;

-- Rollback:
-- ALTER TABLE tbl_companies DROP COLUMN is_numpad_enable;
