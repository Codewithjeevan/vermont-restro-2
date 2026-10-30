<?php
/*
 | AI Insight test fixture on the LOCAL database.
 |   php tests/ai/fixture.php create   -> company "AI Fixture Cafe (test)" with 2 outlets
 |   php tests/ai/fixture.php remove   -> deletes every row of that company again
 | Data and rules: tests/ai/fixture_spec.php. Never run this on a client database.
 */
require __DIR__ . '/bootstrap.php';
$spec = require __DIR__ . '/fixture_spec.php';
$db = $CI->db;
$action = isset($argv[1]) ? $argv[1] : '';

echo 'Database: ' . $db->database . PHP_EOL;
if (stripos($db->database, 'prod') !== false) {
    exit("Refusing to touch a database whose name contains 'prod'.\n");
}

function aifx_company_id($db, $spec) {
    $row = $db->query("SELECT id FROM tbl_companies WHERE business_name = ?", array($spec['company']))->row();
    return $row ? (int) $row->id : 0;
}

function aifx_remove($db, $spec) {
    $company_id = aifx_company_id($db, $spec);
    if (!$company_id) {
        echo "No fixture company found.\n";
        return;
    }
    $outlets = array_map('intval', array_column($db->query("SELECT id FROM tbl_outlets WHERE company_id = ?", array($company_id))->result_array(), 'id'));
    $o = $outlets ? implode(',', $outlets) : '0';
    $sales = "SELECT id FROM tbl_sales WHERE outlet_id IN ($o)";
    $statements = array(
        "DELETE FROM tbl_sale_consumptions_of_menus WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_sale_consumptions WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_sales_details WHERE sales_id IN (SELECT id FROM ($sales) x)",
        "DELETE FROM tbl_sales WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_purchase_ingredients WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_purchase WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_waste_ingredients WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_wastes WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_inventory_adjustment_ingredients WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_inventory_adjustment WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_transfer_ingredients WHERE from_outlet_id IN ($o) OR to_outlet_id IN ($o)",
        "DELETE FROM tbl_transfer WHERE from_outlet_id IN ($o) OR to_outlet_id IN ($o)",
        "DELETE FROM tbl_expenses WHERE outlet_id IN ($o)",
        "DELETE FROM tbl_expense_items WHERE company_id = $company_id",
        "DELETE FROM tbl_payroll_payments WHERE company_id = $company_id",
        "DELETE FROM tbl_mess_payments WHERE company_id = $company_id",
        "DELETE FROM tbl_mess_day_close WHERE company_id = $company_id",
        "DELETE FROM tbl_combo_food_menus WHERE company_id = $company_id",
        "DELETE FROM tbl_food_menus_ingredients WHERE company_id = $company_id",
        "DELETE FROM tbl_food_menus WHERE company_id = $company_id",
        "DELETE FROM tbl_ingredients WHERE company_id = $company_id",
        "DELETE FROM tbl_units WHERE company_id = $company_id",
        "DELETE FROM tbl_ai_usage WHERE company_id = $company_id",
        "DELETE FROM tbl_ai_snapshots WHERE company_id = $company_id",
        "DELETE FROM tbl_ai_settings WHERE company_id = $company_id",
        "DELETE FROM tbl_outlets WHERE company_id = $company_id",
        "DELETE FROM tbl_companies WHERE id = $company_id",
    );
    foreach ($statements as $sql) {
        $db->query($sql);
    }
    echo "Removed fixture company #$company_id (outlets $o).\n";
}

function aifx_create($db, $spec) {
    if (aifx_company_id($db, $spec)) {
        exit("Fixture already exists: run 'remove' first.\n");
    }
    $db->trans_start();
    $db->insert('tbl_companies', array('business_name' => $spec['company'], 'currency' => 'AED', 'precision' => 2,
        'tax_type' => 2, 'collect_tax' => 'Yes', 'zone_name' => 'Asia/Dubai', 'del_status' => 'Live'));
    $company_id = (int) $db->insert_id();

    $units = array();
    foreach ($spec['units'] as $u) {
        $db->insert('tbl_units', array('unit_name' => $u, 'company_id' => $company_id, 'del_status' => 'Live'));
        $units[$u] = (int) $db->insert_id();
    }

    $ing = array();
    foreach ($spec['ingredients'] as $key => $i) {
        $unit_cost = $i[4] / $i[3];
        $db->insert('tbl_ingredients', array('code' => 'FX-' . $key, 'name' => $i[0], 'unit_id' => $units[$i[1]], 'purchase_unit_id' => $units[$i[2]],
            'conversion_rate' => $i[3], 'purchase_price' => $i[4], 'consumption_unit_cost' => $unit_cost, 'average_consumption_per_unit' => $unit_cost,
            'company_id' => $company_id, 'del_status' => 'Live', 'food_id' => 0, 'is_direct_food' => 0));
        $ing[$key] = (int) $db->insert_id();
    }

    $tax = json_encode(array(array('tax_field_id' => '1', 'tax_field_company_id' => (string) $company_id, 'tax_field_name' => 'VAT', 'tax_field_percentage' => (string) $spec['vat_pct'])));
    $menu = array();
    foreach ($spec['menus'] as $key => $m) {
        $db->insert('tbl_food_menus', array('code' => 'FX-' . $key, 'name' => $m[0], 'product_type' => $m[1], 'sale_price' => $m[2],
            'tax_information' => $tax, 'company_id' => $company_id, 'parent_id' => 0, 'is_variation' => 0, 'del_status' => 'Live'));
        $menu[$key] = (int) $db->insert_id();
    }
    foreach ($spec['recipes'] as $mkey => $lines) {
        $total = 0;
        foreach ($lines as $ikey => $q) {
            $cost = $q * $spec['ingredients'][$ikey][4] / $spec['ingredients'][$ikey][3];
            $total += $cost;
            $db->insert('tbl_food_menus_ingredients', array('ingredient_id' => $ing[$ikey], 'consumption' => $q, 'food_menu_id' => $menu[$mkey],
                'company_id' => $company_id, 'del_status' => 'Live', 'cost' => $cost, 'total' => $cost));
        }
        $db->update('tbl_food_menus', array('total_cost' => $total), array('id' => $menu[$mkey]));
    }
    foreach ($spec['resale'] as $mkey => $ikey) {
        $db->update('tbl_ingredients', array('food_id' => $menu[$mkey], 'is_direct_food' => 1), array('id' => $ing[$ikey]));
    }
    foreach ($spec['combos'] as $mkey => $children) {
        foreach ($children as $child => $n) {
            $db->insert('tbl_combo_food_menus', array('name' => $spec['menus'][$child][0], 'quantity' => $n, 'food_menu_id' => $menu[$mkey],
                'added_food_menu_id' => $menu[$child], 'company_id' => $company_id, 'del_status' => 'Live'));
        }
    }

    $prices = array();
    foreach ($spec['menus'] as $key => $m) {
        $prices['tmp' . $menu[$key]] = $m[3] . '||' . $m[3] . '||';
    }
    $db->insert('tbl_outlets', array('outlet_name' => $spec['kitchen'], 'outlet_code' => 'FXK', 'company_id' => $company_id, 'has_kitchen' => 'Yes',
        'food_menus' => implode(',', $menu), 'food_menu_prices' => json_encode($prices), 'active_status' => 'active', 'del_status' => 'Live'));
    $kitchen = (int) $db->insert_id();
    $db->insert('tbl_outlets', array('outlet_name' => $spec['warehouse'], 'outlet_code' => 'FXW', 'company_id' => $company_id, 'has_kitchen' => 'No',
        'food_menus' => '', 'food_menu_prices' => '{}', 'active_status' => 'active', 'del_status' => 'Live'));
    $warehouse = (int) $db->insert_id();

    // sales: one bill per menu per day + the special bills
    $n = 0;
    foreach (aifx_bills($spec) as $b) {
        $a = aifx_amounts($spec, $b);
        $n++;
        $db->insert('tbl_sales', array('customer_id' => '1', 'sale_no' => 'FX-' . $n, 'total_items' => 1, 'sub_total' => $a['sub_total'],
            'paid_amount' => $a['total_payable'], 'due_amount' => 0, 'disc' => '', 'disc_actual' => 0, 'vat' => $a['vat'], 'total_payable' => $a['total_payable'],
            'payment_method_id' => 0, 'close_time' => '12:00:00', 'table_id' => 0, 'total_item_discount_amount' => $a['line_discount'],
            'sub_total_with_discount' => $a['sub_total'] - $a['bill_discount'], 'sub_total_discount_amount' => $a['bill_discount'],
            'total_discount_amount' => $a['total_discount'], 'charge_type' => '', 'delivery_charge' => '0', 'tips_amount' => (string) $a['tips'],
            'tips_amount_actual_charge' => $a['tips'], 'delivery_charge_actual_charge' => 0, 'sub_total_discount_value' => '', 'sub_total_discount_type' => '',
            'sale_date' => $b['date'], 'date_time' => $b['date'] . ' 12:00:00', 'order_time' => '12:00:00', 'user_id' => 0, 'waiter_id' => 0,
            'outlet_id' => $kitchen, 'company_id' => $company_id, 'order_status' => 3, 'is_staff_meal' => $a['staff_meal'], 'order_type' => 1,
            'del_status' => 'Live', 'counter_id' => 0, 'total_refund' => $a['refund'], 'refund_date_time' => $a['refund'] > 0 ? $b['date'] : null));
        $sale_id = (int) $db->insert_id();
        $db->insert('tbl_sales_details', array('food_menu_id' => $menu[$b['menu']], 'menu_name' => $spec['menus'][$b['menu']][0], 'qty' => $b['qty'],
            'tmp_qty' => $b['qty'], 'menu_price_without_discount' => $a['list'], 'menu_price_with_discount' => $a['line'],
            'menu_unit_price' => $spec['menus'][$b['menu']][3], 'menu_vat_percentage' => 0, 'discount_type' => '', 'discount_amount' => $a['line_discount'],
            'previous_id' => 0, 'sales_id' => $sale_id, 'order_status' => 3, 'user_id' => 0, 'outlet_id' => $kitchen,
            'is_free_item' => 0, 'is_complementary' => $a['complimentary'], 'del_status' => 'Live'));
        $db->insert('tbl_sale_consumptions', array('sale_id' => $sale_id, 'order_status' => 3, 'user_id' => 0, 'outlet_id' => $kitchen, 'del_status' => 'Live'));
        $consumption_id = (int) $db->insert_id();
        foreach (aifx_plate($spec, $b['menu']) as $ikey => $q) {
            $db->insert('tbl_sale_consumptions_of_menus', array('ingredient_id' => $ing[$ikey], 'consumption' => $q * $b['qty'],
                'sale_consumption_id' => $consumption_id, 'sales_id' => $sale_id, 'order_status' => 3, 'food_menu_id' => $menu[$b['menu']],
                'user_id' => 0, 'outlet_id' => $kitchen, 'del_status' => 'Live', 'cost' => 0));
        }
    }

    // opening stock count and the chicken shrinkage
    $adjust = function ($date, $lines, $status) use ($db, $ing, $kitchen) {
        $db->insert('tbl_inventory_adjustment', array('reference_no' => 'FX-ADJ', 'date' => $date, 'note' => 'fixture', 'employee_id' => 0,
            'user_id' => 0, 'outlet_id' => $kitchen, 'del_status' => 'Live'));
        $adj_id = (int) $db->insert_id();
        foreach ($lines as $ikey => $q) {
            $db->insert('tbl_inventory_adjustment_ingredients', array('ingredient_id' => $ing[$ikey], 'consumption_amount' => $q,
                'inventory_adjustment_id' => $adj_id, 'consumption_status' => $status, 'outlet_id' => $kitchen, 'del_status' => 'Live'));
        }
    };
    $adjust(aifx_date($spec, $spec['opening_stock_offset']), $spec['opening_stock'], 'Plus');
    $adjust(aifx_date($spec, $spec['shrink'][0]), array($spec['shrink'][1] => $spec['shrink'][2]), 'Minus');

    // purchases: kitchen weekly, warehouse oil once
    $buy = function ($outlet, $date, $ikey, $units) use ($db, $ing, $spec) {
        $price = $spec['ingredients'][$ikey][4];
        $db->insert('tbl_purchase', array('reference_no' => 'FX-PUR', 'supplier_id' => 0, 'date' => $date, 'subtotal' => $units * $price,
            'grand_total' => $units * $price, 'paid' => $units * $price, 'due' => 0, 'user_id' => 0, 'outlet_id' => $outlet, 'del_status' => 'Live',
            'payment_id' => 0, 'counter_id' => 0));
        $db->insert('tbl_purchase_ingredients', array('ingredient_id' => $ing[$ikey], 'unit_price' => $price, 'quantity_amount' => $units,
            'total' => $units * $price, 'purchase_id' => (int) $db->insert_id(), 'outlet_id' => $outlet, 'del_status' => 'Live'));
    };
    foreach (aifx_purchases($spec) as $p) {
        $buy($kitchen, $p['date'], $p['ing'], $p['units']);
    }
    $buy($warehouse, aifx_date($spec, $spec['warehouse_oil'][0]), 'oil', $spec['warehouse_oil'][1]);

    // oil transfers warehouse -> kitchen
    foreach (aifx_transfer_dates($spec) as $date) {
        $db->insert('tbl_transfer', array('transfer_type' => 1, 'reference_no' => 'FX-TR', 'date' => $date, 'received_date' => $date,
            'user_id' => 0, 'outlet_id' => $warehouse, 'from_outlet_id' => $warehouse, 'to_outlet_id' => $kitchen, 'status' => 1, 'del_status' => 'Live'));
        $db->insert('tbl_transfer_ingredients', array('transfer_type' => 1, 'status' => 1, 'ingredient_id' => $ing['oil'],
            'quantity_amount' => $spec['oil_transfer'], 'unit_price' => $spec['ingredients']['oil'][4], 'transfer_id' => (int) $db->insert_id(),
            'from_outlet_id' => $warehouse, 'to_outlet_id' => $kitchen, 'total_cost' => $spec['oil_transfer'] * $spec['ingredients']['oil'][4], 'del_status' => 'Live'));
    }

    // rice waste every week
    foreach (aifx_waste_dates($spec) as $date) {
        $loss = $spec['rice_waste'] * $spec['ingredients']['rice'][4] / $spec['ingredients']['rice'][3];
        $db->insert('tbl_wastes', array('reference_no' => 'FX-W', 'date' => $date, 'total_loss' => $loss, 'note' => 'fixture',
            'employee_id' => 0, 'user_id' => 0, 'outlet_id' => $kitchen, 'del_status' => 'Live'));
        $db->insert('tbl_waste_ingredients', array('ingredient_id' => $ing['rice'], 'waste_amount' => $spec['rice_waste'],
            'last_purchase_price' => $spec['ingredients']['rice'][4], 'loss_amount' => $loss, 'waste_id' => (int) $db->insert_id(),
            'outlet_id' => $kitchen, 'del_status' => 'Live'));
    }

    // expenses, salaries (one posted as an expense too), mess
    $category = array();
    foreach (array('Rent', 'Gas', 'Salary & Wages') as $c) {
        $db->insert('tbl_expense_items', array('name' => $c, 'description' => 'fixture', 'user_id' => 0, 'company_id' => $company_id, 'del_status' => 'Live'));
        $category[$c] = (int) $db->insert_id();
    }
    $expense = function ($offset, $cat, $amount) use ($db, $spec, $category, $kitchen) {
        $db->insert('tbl_expenses', array('date' => aifx_date($spec, $offset), 'amount' => $amount, 'category_id' => $category[$cat], 'employee_id' => 0,
            'note' => 'fixture', 'user_id' => 0, 'outlet_id' => $kitchen, 'payment_id' => 0, 'del_status' => 'Live', 'counter_id' => 0));
        return (int) $db->insert_id();
    };
    foreach ($spec['expenses'] as $e) {
        $expense($e[0], $e[1], $e[2]);
    }
    $salary_expense = $expense($spec['salary_posted'][0], 'Salary & Wages', $spec['salary_posted'][1]);
    foreach (array(array($spec['salary_posted'], $salary_expense), array($spec['salary_unposted'], 0)) as $p) {
        $db->insert('tbl_payroll_payments', array('company_id' => $company_id, 'run_id' => 0, 'run_item_id' => 0, 'employee_id' => 0,
            'pay_date' => aifx_date($spec, $p[0][0]), 'amount' => $p[0][1], 'outlet_id' => $kitchen, 'expense_id' => $p[1],
            'note' => 'fixture', 'del_status' => 'Live', 'user_id' => 0, 'counter_id' => 0));
    }
    foreach (array(array($spec['mess_payment'], 'payment'), array($spec['mess_refund'], 'refund')) as $p) {
        $date = aifx_date($spec, $p[0][0]);
        $db->insert('tbl_mess_payments', array('reference_no' => 'FX-M', 'member_id' => 0, 'date_time' => $date . ' 12:00:00', 'only_date' => $date,
            'outlet_id' => $kitchen, 'company_id' => $company_id, 'amount' => $p[0][1], 'type' => $p[1], 'del_status' => 'Live'));
    }
    $close_date = aifx_date($spec, $spec['mess_close'][0]);
    $db->insert('tbl_mess_day_close', array('close_date' => $close_date, 'slot' => 1, 'outlet_id' => $kitchen, 'company_id' => $company_id, 'status' => 'closed'));
    $db->insert('tbl_sale_consumptions_of_menus', array('ingredient_id' => $ing[$spec['mess_close'][1]], 'consumption' => $spec['mess_close'][2],
        'sale_consumption_id' => null, 'sales_id' => 0, 'order_status' => 3, 'food_menu_id' => 0, 'user_id' => 0, 'outlet_id' => $kitchen,
        'del_status' => 'Live', 'cost' => 0, 'status' => 1, 'mess_close_id' => (int) $db->insert_id()));

    $db->trans_complete();
    if ($db->trans_status() === false) {
        exit("Fixture insert failed and was rolled back.\n");
    }
    echo "Created fixture company #$company_id: kitchen outlet #$kitchen, warehouse outlet #$warehouse, $n bills.\n";
}

if ($action == 'create') {
    aifx_create($db, $spec);
} elseif ($action == 'remove') {
    aifx_remove($db, $spec);
} else {
    echo "Usage: php tests/ai/fixture.php create|remove\n";
}
