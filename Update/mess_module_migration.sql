-- =====================================================================
-- Feature: Mess module (monthly tiffin subscriptions)
-- Plan: docs/mess_system_plan.html
--
--   mysql -uroot restodb < Update/mess_module_migration.sql
--
-- What it does
--   1. New tables tbl_mess_* (all additive, utf8mb4 so Arabic names work).
--   2. tbl_register gets two columns so a closed register keeps its
--      "Mess Collection" split (register report / history never recompute).
--   3. tbl_main_modules row 12 "mess" = the "Mess" block on the Role screen.
--   4. tbl_access rows 371-411. The ids are hard coded in the Mess*
--      controllers (MESS_ACCESS_* constants), change one and change both.
--
-- Admin sees the new menu straight away (userHome.php builds Admin's menu
-- from tbl_access on every page). Other roles get it once the permission
-- is ticked on Settings > Role and the user logs in again.
--
-- Money columns are DECIMAL (not FLOAT like the legacy tables).
-- Dates the user picks (subscription start/end, attendance date, menu date)
-- are plain DATE values and never derived from MySQL NOW(): PHP runs on the
-- company timezone while MySQL may sit in another one.
--
-- Safe to run once per database. Rollback block at the bottom.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Settings (one row per company, created on first save)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_settings (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  breakfast_on TINYINT(1) NOT NULL DEFAULT 1,
  breakfast_from VARCHAR(5) NOT NULL DEFAULT '06:30',
  breakfast_to VARCHAR(5) NOT NULL DEFAULT '10:00',
  lunch_on TINYINT(1) NOT NULL DEFAULT 1,
  lunch_from VARCHAR(5) NOT NULL DEFAULT '12:00',
  lunch_to VARCHAR(5) NOT NULL DEFAULT '15:00',
  dinner_on TINYINT(1) NOT NULL DEFAULT 1,
  dinner_from VARCHAR(5) NOT NULL DEFAULT '19:00',
  dinner_to VARCHAR(5) NOT NULL DEFAULT '23:00',
  cycle_days INT NOT NULL DEFAULT 30,
  expiring_days INT NOT NULL DEFAULT 3,
  skip_credit_policy VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'none / informed / always',
  require_open_register TINYINT(1) NOT NULL DEFAULT 1,
  member_code_prefix VARCHAR(10) NOT NULL DEFAULT 'M-',
  whatsapp_country_code VARCHAR(6) NOT NULL DEFAULT '971',
  whatsapp_menu_template TEXT NULL,
  whatsapp_due_template TEXT NULL,
  whatsapp_expiry_template TEXT NULL,
  updated_by INT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mess_settings_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Plans = price list entries, not contracts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_plans (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  diet VARCHAR(10) NOT NULL DEFAULT 'nonveg' COMMENT 'veg / nonveg / both',
  has_breakfast TINYINT(1) NOT NULL DEFAULT 0,
  has_lunch TINYINT(1) NOT NULL DEFAULT 0,
  has_dinner TINYINT(1) NOT NULL DEFAULT 1,
  price_per_person DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'one person, one full cycle, all included slots',
  cycle_days INT NOT NULL DEFAULT 30,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  company_id INT NOT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_plans_company (company_id, del_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Members = one person or one room, created once
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_members (
  id INT NOT NULL AUTO_INCREMENT,
  member_code VARCHAR(20) NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  phone2 VARCHAR(30) NULL,
  building_room VARCHAR(150) NULL,
  address VARCHAR(255) NULL,
  diet_pref VARCHAR(10) NOT NULL DEFAULT 'nonveg' COMMENT 'veg / nonveg / both',
  delivery_mode VARCHAR(10) NOT NULL DEFAULT 'pickup' COMMENT 'pickup / delivery',
  note VARCHAR(255) NULL,
  customer_id INT NULL COMMENT 'optional link to tbl_customers',
  status VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active / inactive',
  company_id INT NOT NULL,
  outlet_id INT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_members_company (company_id, del_status),
  KEY idx_mess_members_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Subscriptions = one cycle of one member. Status is derived from the
--    dates at read time; only "cancelled" is stored.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_subscriptions (
  id INT NOT NULL AUTO_INCREMENT,
  member_id INT NOT NULL,
  plan_id INT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL COMMENT 'includes the pause extension',
  cycle_days INT NOT NULL DEFAULT 30,
  diet VARCHAR(10) NOT NULL DEFAULT 'nonveg',
  delivery_mode VARCHAR(10) NOT NULL DEFAULT 'pickup',
  delivery_address VARCHAR(255) NULL,
  persons_total INT NOT NULL DEFAULT 0 COMMENT 'sum of persons over all slots',
  suggested_price DECIMAL(12,3) NOT NULL DEFAULT 0,
  agreed_price DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'the charge on the member ledger',
  price_note VARCHAR(150) NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'active' COMMENT 'active / cancelled',
  paused_days INT NOT NULL DEFAULT 0,
  renewed_from_id INT NULL,
  note VARCHAR(255) NULL,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_sub_member (member_id),
  KEY idx_mess_sub_dates (outlet_id, start_date, end_date),
  KEY idx_mess_sub_company (company_id, del_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Lunch + 2 Dinner" = two rows: (slot 2, 1 person) and (slot 3, 2 persons)
CREATE TABLE IF NOT EXISTS tbl_mess_subscription_meals (
  id INT NOT NULL AUTO_INCREMENT,
  subscription_id INT NOT NULL,
  slot TINYINT NOT NULL COMMENT '1 breakfast, 2 lunch, 3 dinner',
  persons INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mess_sub_slot (subscription_id, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leave periods. A pause moves the subscription end date forward by `days`.
CREATE TABLE IF NOT EXISTS tbl_mess_pauses (
  id INT NOT NULL AUTO_INCREMENT,
  subscription_id INT NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  days INT NOT NULL,
  reason VARCHAR(150) NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_pause_sub (subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Payments. Stamped like tbl_customer_due_receives (counter_id,
--    outlet_id, date_time) so Sale::closeRegister picks them up.
--    type: payment (money in), refund (money out), adjustment (no money,
--    signed: + reduces the member's due, - adds a charge).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_payments (
  id INT NOT NULL AUTO_INCREMENT,
  reference_no VARCHAR(30) NOT NULL,
  member_id INT NOT NULL,
  subscription_id INT NULL,
  extra_id INT NULL COMMENT 'set when an extra was paid on the spot',
  type VARCHAR(12) NOT NULL DEFAULT 'payment',
  amount DECIMAL(12,3) NOT NULL DEFAULT 0,
  payment_id INT NULL COMMENT 'tbl_payment_methods.id, NULL for adjustments',
  date_time DATETIME NOT NULL COMMENT 'server time of entry, used by the register',
  only_date DATE NOT NULL COMMENT 'date chosen by the user, used by reports',
  note VARCHAR(255) NULL,
  counter_id INT NOT NULL DEFAULT 0,
  user_id INT NULL,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_pay_member (member_id),
  KEY idx_mess_pay_register (outlet_id, counter_id, payment_id, date_time),
  KEY idx_mess_pay_date (outlet_id, only_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Daily operations
-- ---------------------------------------------------------------------
-- Planner rows: which dish goes to which slot and diet on a date.
CREATE TABLE IF NOT EXISTS tbl_mess_menus (
  id INT NOT NULL AUTO_INCREMENT,
  menu_date DATE NOT NULL,
  slot TINYINT NOT NULL,
  diet VARCHAR(10) NOT NULL COMMENT 'veg / nonveg',
  food_menu_id INT NULL COMMENT 'dish with a recipe, NULL for a free text dish',
  dish_name VARCHAR(150) NOT NULL,
  portion_per_person DECIMAL(8,2) NOT NULL DEFAULT 1,
  buffer_portions DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_menu_date (outlet_id, menu_date, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per subscription per slot per day. Generated as "pending" from
-- the active subscriptions, then marked. A marked row is never regenerated.
CREATE TABLE IF NOT EXISTS tbl_mess_attendance (
  id INT NOT NULL AUTO_INCREMENT,
  att_date DATE NOT NULL,
  slot TINYINT NOT NULL,
  subscription_id INT NOT NULL,
  member_id INT NOT NULL,
  persons_expected INT NOT NULL DEFAULT 1,
  persons_served INT NOT NULL DEFAULT 0,
  status VARCHAR(12) NOT NULL DEFAULT 'pending' COMMENT 'pending / served / partial / not_taken / skipped',
  diet VARCHAR(10) NOT NULL DEFAULT 'nonveg' COMMENT 'menu this row is counted against (veg / nonveg)',
  delivery_mode VARCHAR(10) NOT NULL DEFAULT 'pickup',
  informed_at DATETIME NULL COMMENT 'when an advance skip was entered',
  marked_by INT NULL,
  marked_at DATETIME NULL,
  note VARCHAR(150) NULL,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mess_att (att_date, slot, subscription_id),
  KEY idx_mess_att_day (outlet_id, att_date, slot),
  KEY idx_mess_att_member (member_id, att_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Aaj mujhe ye chahiye": one-off items for a member on a date and slot.
CREATE TABLE IF NOT EXISTS tbl_mess_extras (
  id INT NOT NULL AUTO_INCREMENT,
  extra_date DATE NOT NULL,
  slot TINYINT NOT NULL,
  member_id INT NOT NULL,
  subscription_id INT NULL,
  food_menu_id INT NULL,
  item_name VARCHAR(150) NOT NULL,
  qty DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,3) NOT NULL DEFAULT 0,
  total DECIMAL(12,3) NOT NULL DEFAULT 0,
  settle_type VARCHAR(12) NOT NULL DEFAULT 'on_account' COMMENT 'pay_now / on_account',
  status VARCHAR(12) NOT NULL DEFAULT 'requested' COMMENT 'requested / delivered / cancelled',
  note VARCHAR(150) NULL,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  user_id INT NULL,
  added_date_time DATETIME NULL,
  del_status VARCHAR(10) NOT NULL DEFAULT 'Live',
  PRIMARY KEY (id),
  KEY idx_mess_extra_day (outlet_id, extra_date, slot),
  KEY idx_mess_extra_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6b. Day close (stock). One row per outlet + date + slot. Closing posts
--     the cooked portions into the same consumption ledger the POS uses
--     (tbl_sale_consumptions_of_menus, sales_id = 0, mess_close_id set),
--     and wasted leftover as a normal waste entry, so every stock screen
--     picks it up. Reopen soft-deletes both and allows a new close.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_mess_day_close (
  id INT NOT NULL AUTO_INCREMENT,
  close_date DATE NOT NULL,
  slot TINYINT NOT NULL,
  expected INT NOT NULL DEFAULT 0,
  served INT NOT NULL DEFAULT 0,
  not_taken INT NOT NULL DEFAULT 0,
  skipped INT NOT NULL DEFAULT 0,
  pending INT NOT NULL DEFAULT 0,
  extras_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
  food_cost DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'recipe cost of everything cooked',
  waste_cost DECIMAL(12,3) NOT NULL DEFAULT 0,
  waste_id INT NULL COMMENT 'tbl_wastes row of the wasted leftover',
  status VARCHAR(10) NOT NULL DEFAULT 'closed' COMMENT 'closed / reopened',
  note VARCHAR(255) NULL,
  closed_by INT NULL,
  closed_at DATETIME NULL,
  reopened_by INT NULL,
  reopened_at DATETIME NULL,
  outlet_id INT NOT NULL,
  company_id INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mess_close (outlet_id, close_date, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tbl_mess_day_close_items (
  id INT NOT NULL AUTO_INCREMENT,
  day_close_id INT NOT NULL,
  food_menu_id INT NULL,
  dish_name VARCHAR(150) NOT NULL,
  planned DECIMAL(10,2) NOT NULL DEFAULT 0,
  served DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'portions eaten by members + extras',
  cooked DECIMAL(10,2) NOT NULL DEFAULT 0,
  leftover DECIMAL(10,2) NOT NULL DEFAULT 0,
  leftover_action VARCHAR(10) NOT NULL DEFAULT 'waste' COMMENT 'waste / carry / staff',
  unit_cost DECIMAL(12,4) NOT NULL DEFAULT 0 COMMENT 'recipe cost of one portion',
  food_cost DECIMAL(12,3) NOT NULL DEFAULT 0,
  has_recipe TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_mess_close_item (day_close_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tbl_sale_consumptions_of_menus
    ADD COLUMN mess_close_id INT NULL DEFAULT NULL AFTER production_id,
    ADD KEY idx_mess_close_id (mess_close_id);

-- ---------------------------------------------------------------------
-- 7. Register: keep the Mess Collection of a closed register
-- ---------------------------------------------------------------------
ALTER TABLE tbl_register
    ADD COLUMN total_mess_collection FLOAT NULL DEFAULT NULL AFTER total_expense,
    ADD COLUMN mess_collection_json TEXT NULL AFTER total_mess_collection;

-- ---------------------------------------------------------------------
-- 8. Role screen block + access rows
-- ---------------------------------------------------------------------
INSERT IGNORE INTO tbl_main_modules (id, name, del_status) VALUES (12, 'mess', 'Live');

INSERT IGNORE INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
  (371, 'mess_dashboard',     '',       'mess_dashboard',     0,   12,   'Live'),
  (372, '',                   'view',   'view',               371, NULL, 'Live'),

  (373, 'mess_plans',         '',       'mess_plans',         0,   12,   'Live'),
  (374, '',                   'add',    'add',                373, NULL, 'Live'),
  (375, '',                   'update', 'update',             373, NULL, 'Live'),
  (376, '',                   'view',   'view',               373, NULL, 'Live'),
  (377, '',                   'delete', 'delete',             373, NULL, 'Live'),

  (378, 'mess_members',       '',       'mess_members',       0,   12,   'Live'),
  (379, '',                   'add',    'add',                378, NULL, 'Live'),
  (380, '',                   'update', 'update',             378, NULL, 'Live'),
  (381, '',                   'view',   'view',               378, NULL, 'Live'),
  (382, '',                   'delete', 'delete',             378, NULL, 'Live'),

  (383, 'mess_subscriptions', '',       'mess_subscriptions', 0,   12,   'Live'),
  (384, '',                   'add',    'add',                383, NULL, 'Live'),
  (385, '',                   'update', 'update',             383, NULL, 'Live'),
  (386, '',                   'view',   'view',               383, NULL, 'Live'),
  (387, '',                   'delete', 'delete',             383, NULL, 'Live'),

  (388, 'mess_payments',      '',       'mess_payments',      0,   12,   'Live'),
  (389, '',                   'add',    'add',                388, NULL, 'Live'),
  (390, '',                   'view',   'view',               388, NULL, 'Live'),
  (391, '',                   'delete', 'delete',             388, NULL, 'Live'),

  (392, 'mess_daily_menu',    '',       'mess_daily_menu',    0,   12,   'Live'),
  (393, '',                   'view',   'view',               392, NULL, 'Live'),
  (394, '',                   'update', 'update',             392, NULL, 'Live'),

  (395, 'mess_meal_register', '',       'mess_meal_register', 0,   12,   'Live'),
  (396, '',                   'view',   'view',               395, NULL, 'Live'),
  (397, '',                   'update', 'update',             395, NULL, 'Live'),

  (398, 'mess_extras',        '',       'mess_extras',        0,   12,   'Live'),
  (399, '',                   'add',    'add',                398, NULL, 'Live'),
  (400, '',                   'view',   'view',               398, NULL, 'Live'),
  (401, '',                   'delete', 'delete',             398, NULL, 'Live'),

  (402, 'mess_daily_sheet',   '',       'mess_daily_sheet',   0,   12,   'Live'),
  (403, '',                   'view',   'view',               402, NULL, 'Live'),

  (404, 'mess_day_close',     '',       'mess_day_close',     0,   12,   'Live'),
  (405, '',                   'view',   'view',               404, NULL, 'Live'),
  (406, '',                   'update', 'update',             404, NULL, 'Live'),
  (407, '',                   'reopen', 'reopen',             404, NULL, 'Live'),

  (408, 'mess_reports',       '',       'mess_reports',       0,   12,   'Live'),
  (409, '',                   'view',   'view',               408, NULL, 'Live'),

  (410, 'mess_settings',      '',       'mess_settings',      0,   12,   'Live'),
  (411, '',                   'update', 'update',             410, NULL, 'Live');

-- Verification: expect 41 rows.
SELECT COUNT(*) AS mess_access_rows FROM tbl_access WHERE id BETWEEN 371 AND 411;

-- ---------------------------------------------------------------------
-- Rollback
-- ---------------------------------------------------------------------
-- DELETE FROM tbl_role_access WHERE access_parent_id BETWEEN 371 AND 411;
-- DELETE FROM tbl_access WHERE id BETWEEN 371 AND 411;
-- DELETE FROM tbl_main_modules WHERE id = 12;
-- ALTER TABLE tbl_register DROP COLUMN mess_collection_json, DROP COLUMN total_mess_collection;
-- UPDATE tbl_sale_consumptions_of_menus SET del_status = 'Deleted' WHERE mess_close_id IS NOT NULL;
-- ALTER TABLE tbl_sale_consumptions_of_menus DROP KEY idx_mess_close_id, DROP COLUMN mess_close_id;
-- DROP TABLE IF EXISTS tbl_mess_day_close_items, tbl_mess_day_close, tbl_mess_extras, tbl_mess_attendance, tbl_mess_menus,
--   tbl_mess_payments, tbl_mess_pauses, tbl_mess_subscription_meals,
--   tbl_mess_subscriptions, tbl_mess_members, tbl_mess_plans, tbl_mess_settings;
