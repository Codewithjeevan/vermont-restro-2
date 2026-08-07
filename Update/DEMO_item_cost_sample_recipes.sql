-- ============================================================
-- DEMO DATA ONLY -- DO NOT RUN ON A PRODUCTION DATABASE
--
-- Fills sample recipes (ingredient + consumption) on the 5 food
-- menus that actually have sales, so "Item Wise Cost Report" shows
-- real numbers instead of 0.00.
--
-- How the cost chain works:
--   tbl_ingredients.consumption_unit_cost   = purchase_price / conversion_rate
--                                             (cost of ONE consumption unit)
--   tbl_food_menus_ingredients.consumption  = how much of that unit goes into
--                                             ONE plate                <-- you enter this
--   tbl_food_menus_ingredients.total        = consumption x cost
--   tbl_food_menus.total_cost               = SUM(total) of all rows   <-- report reads this
--
-- The UI does exactly the same thing: Add/Edit Food Menu -> pick an
-- ingredient -> type Consumption -> Total and Total Cost fill in by JS
-- -> Submit writes both tables.
--
-- SIDE EFFECT: these rows are also the recipe the inventory engine uses.
-- Once they exist, every NEW sale of these items deducts the listed
-- ingredients from stock. Past sales are not touched. That is why this is
-- demo-only -- replace the consumption numbers with the real ones.
--
-- Revert: see the bottom of this file.
-- ============================================================

DELETE FROM tbl_food_menus_ingredients WHERE food_menu_id IN (127, 130, 131, 137, 138);

-- ingredient_id, consumption, food_menu_id, user_id, company_id, del_status, cost, total
INSERT INTO tbl_food_menus_ingredients
    (ingredient_id, consumption, food_menu_id, user_id, company_id, del_status, cost, total) VALUES
    -- 130 Chicken Biryani (sale price 27) -> cost 8.82
    (70,   4.00, 130, 1, 1, 'Live', 1.23, 4.92),   -- WHOLE CHICKEN, 4 Pcs
    (85, 250.00, 130, 1, 1, 'Live', 0.01, 2.50),   -- Rice, 250 g
    (28,  30.00, 130, 1, 1, 'Live', 0.01, 0.30),   -- oil, 30 g
    (67,   1.00, 130, 1, 1, 'Live', 1.00, 1.00),   -- spices, 1
    (64,   5.00, 130, 1, 1, 'Live', 0.02, 0.10),   -- salt, 5 g

    -- 138 Bhajiya (sale price 12) -> cost 4.01
    (29,   5.00, 138, 1, 1, 'Live', 0.59, 2.95),   -- flour, 5
    (28,  50.00, 138, 1, 1, 'Live', 0.01, 0.50),   -- oil, 50 g
    (60,  10.00, 138, 1, 1, 'Live', 0.03, 0.30),   -- chilly powder, 10 g
    (61,  10.00, 138, 1, 1, 'Live', 0.02, 0.20),   -- masala, 10 g
    (64,   3.00, 138, 1, 1, 'Live', 0.02, 0.06),   -- salt, 3 g

    -- 137 cold Drinks (sale price 5) -> cost 2.24
    (24,   1.00, 137, 1, 1, 'Live', 2.24, 2.24),   -- Pepsi, 1 Pcs

    -- 131 Daal Makhni Jeera Rice Combo (sale price 25) -> cost 7.50
    (85, 200.00, 131, 1, 1, 'Live', 0.01, 2.00),   -- Rice, 200 g
    (17,  40.00, 131, 1, 1, 'Live', 0.04, 1.60),   -- butter, 40 g
    (67,   2.00, 131, 1, 1, 'Live', 1.00, 2.00),   -- spices, 2
    (65,  20.00, 131, 1, 1, 'Live', 0.05, 1.00),   -- seven spices, 20 g
    (66,  50.00, 131, 1, 1, 'Live', 0.01, 0.50),   -- yogurt, 50 g
    (72,  30.00, 131, 1, 1, 'Live', 0.01, 0.30),   -- cooking oil, 30 ml
    (64,   5.00, 131, 1, 1, 'Live', 0.02, 0.10),   -- salt, 5 g

    -- 127 Dahi Bhalla (sale price 15) -> cost 4.57
    (66, 200.00, 127, 1, 1, 'Live', 0.01, 2.00),   -- yogurt, 200 g
    (29,   3.00, 127, 1, 1, 'Live', 0.59, 1.77),   -- flour, 3
    (28,  40.00, 127, 1, 1, 'Live', 0.01, 0.40),   -- oil, 40 g
    (61,  15.00, 127, 1, 1, 'Live', 0.02, 0.30),   -- masala, 15 g
    (64,   5.00, 127, 1, 1, 'Live', 0.02, 0.10);   -- salt, 5 g

-- roll the per-plate cost up onto the menu, exactly like grand_total_cost does on save
UPDATE tbl_food_menus fm
SET fm.total_cost = (
        SELECT COALESCE(SUM(fmi.total), 0)
        FROM tbl_food_menus_ingredients fmi
        WHERE fmi.food_menu_id = fm.id AND fmi.del_status = 'Live'
    )
WHERE fm.id IN (127, 130, 131, 137, 138);

-- ============================================================
-- Revert (puts everything back to cost 0):
--   DELETE FROM tbl_food_menus_ingredients WHERE food_menu_id IN (127,130,131,137,138);
--   UPDATE tbl_food_menus SET total_cost = 0 WHERE id IN (127,130,131,137,138);
-- ============================================================
