-- Monthly Inventory report + Opening Stock + remove-item Reason / Audit Log columns + Waste qty.
-- See docs/monthly_inventory_and_stock_updates_plan.md
-- Run once, before uploading the code.

-- Opening Stock is stored as an Inventory Adjustment (Plus) row. This flag keeps it out of the
-- Inventory Adjustment list and out of Usage on the Monthly Inventory report.
ALTER TABLE tbl_inventory_adjustment
  ADD COLUMN is_opening TINYINT(1) NOT NULL DEFAULT 0;

-- Audit Log Report columns. item_name and qty hold one line per item, separated by a newline.
ALTER TABLE tbl_audit_logs
  ADD COLUMN sale_no VARCHAR(50) NULL,
  ADD COLUMN item_name TEXT NULL,
  ADD COLUMN qty TEXT NULL,
  ADD COLUMN reason VARCHAR(255) NULL;

-- A food menu waste can be a fraction of a portion (1.5); INT was cutting it.
ALTER TABLE tbl_wastes
  MODIFY food_menu_waste_qty FLOAT NULL;
