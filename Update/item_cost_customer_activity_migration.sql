-- ============================================================
-- Feature: two new reports under Report
--
--   Report/itemWiseCostReport      -> access parent id 358 (view = 359)
--   Report/customerActivityReport  -> access parent id 360 (view = 361)
--
-- No schema change. Both reports read existing tables
-- (tbl_sales, tbl_sales_details, tbl_food_menus, tbl_customers).
-- The only thing this migration does is register the two menus in
-- tbl_access so that:
--   * Report::__construct()'s checkAccess() can find them
--     (the ids 358/360 are hard coded there),
--   * they show up on the Add/Edit Role permission screen,
--   * Admin gets them automatically (Admin's function_access is
--     built from every tbl_access row at login).
--
-- main_module_id 8 = the "report" module group.
-- Ids are explicit because the controller references them.
-- Users already logged in must log out and back in before the new
-- menus appear -- function_access is cached in the session.
--
-- Safe / additive. Apply once per database.
-- ============================================================

INSERT INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
    (358, 'item_wise_cost_report',    '',     'item_wise_cost_report',    0,   8,    'Live'),
    (359, '',                         'view', 'view',                     358, NULL, 'Live'),
    (360, 'customer_activity_report', '',     'customer_activity_report', 0,   8,    'Live'),
    (361, '',                         'view', 'view',                     360, NULL, 'Live');

-- Optional: grant both reports to every role that can already see the
-- detailed sale report (access parent 167). Skip this if permissions
-- should be handed out by hand from Settings > Roles.
-- INSERT INTO tbl_role_access (role_id, access_parent_id, access_child_id, del_status)
-- SELECT role_id, 358, 359, 'Live' FROM tbl_role_access WHERE access_parent_id = 167 AND del_status = 'Live';
-- INSERT INTO tbl_role_access (role_id, access_parent_id, access_child_id, del_status)
-- SELECT role_id, 360, 361, 'Live' FROM tbl_role_access WHERE access_parent_id = 167 AND del_status = 'Live';

-- Rollback:
-- DELETE FROM tbl_role_access WHERE access_parent_id IN (358, 360);
-- DELETE FROM tbl_access WHERE id IN (358, 359, 360, 361);
