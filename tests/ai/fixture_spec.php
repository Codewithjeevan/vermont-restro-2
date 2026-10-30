<?php
/*
 | AI Insight test fixture: the data and the rules that generate it.
 | Used by tests/ai/fixture.php (writes it to the database) and by
 | tests/ai/metrics_test.php (works out the expected answers from the same spec,
 | without the engine's SQL). Plan: docs/ai_insight_plan.md, Phase 2.
 |
 | One company with VAT-inclusive prices (5 %), a kitchen outlet with 63 days of
 | sales ending the day before 'today', and a warehouse outlet that only buys oil
 | and transfers it to the kitchen. Known answers built in:
 | - Biryani = star, Cheese Burger = plowhorse + under-priced (outlet price 10.50,
 |   food cost 62.4 %) + 25 % sales drop, Saffron Rice / Party Combo = puzzle,
 |   Plain Fries = dog (34 % food cost), Cola = resale item at 50 %, Lemon Tea has
 |   no recipe, Mutton Curry never sells
 | - Cheese over-bought (40 kg extra), Rice wasted every week, 500 g Chicken missing
 |   at the stock count, Gas expense +33 %, salary paid twice (one posted as expense)
 */

$spec = array(
    'company' => 'AI Fixture Cafe (test)',
    'kitchen' => 'AI Fixture Kitchen',
    'warehouse' => 'AI Fixture Warehouse',
    'today' => '2026-11-01',
    'history_days' => 63,
    'vat_pct' => 5,
    'units' => array('fx-g', 'fx-kg', 'fx-ml', 'fx-L', 'fx-pcs', 'fx-pack', 'fx-case'),
    // key => name, consumption unit, purchase unit, conversion rate, purchase price (per purchase unit)
    'ingredients' => array(
        'chicken' => array('FX Chicken', 'fx-g', 'fx-kg', 1000, 20),
        'rice' => array('FX Rice', 'fx-g', 'fx-kg', 1000, 5),
        'oil' => array('FX Oil', 'fx-ml', 'fx-L', 1000, 8),
        'bun' => array('FX Bun', 'fx-pcs', 'fx-pack', 10, 10),
        'cheese' => array('FX Cheese', 'fx-g', 'fx-kg', 1000, 40),
        'cola' => array('FX Cola can', 'fx-pcs', 'fx-case', 24, 36),
        'saffron' => array('FX Saffron', 'fx-g', 'fx-g', 1, 10),
        'potato' => array('FX Potato', 'fx-g', 'fx-kg', 1000, 3),
        'mutton' => array('FX Mutton', 'fx-g', 'fx-kg', 1000, 30),
    ),
    // key => name, product_type, tbl_food_menus.sale_price, outlet dine-in price (both VAT inclusive)
    'menus' => array(
        'biryani' => array('FX Chicken Biryani', 1, 31.50, 31.50),
        'burger' => array('FX Cheese Burger', 1, 11.55, 10.50),
        'saffron_rice' => array('FX Saffron Rice', 1, 42.00, 42.00),
        'fries' => array('FX Fries &amp; Dip', 1, 5.25, 5.25), // saved HTML-escaped, like the menu form does
        'cola' => array('FX Cola', 3, 3.15, 3.15),
        'lemon_tea' => array('FX Lemon Tea', 1, 4.20, 4.20),
        'combo' => array('FX Party Combo', 2, 25.20, 25.20),
        'mutton' => array('FX Mutton Curry', 1, 47.25, 47.25),
    ),
    // menu => ingredient => consumption per plate (consumption units)
    'recipes' => array(
        'biryani' => array('chicken' => 200, 'rice' => 150, 'oil' => 20),
        'burger' => array('bun' => 1, 'chicken' => 150, 'cheese' => 50, 'oil' => 30),
        'saffron_rice' => array('rice' => 200, 'saffron' => 0.5),
        'fries' => array('potato' => 300, 'oil' => 100),
        'mutton' => array('mutton' => 250, 'oil' => 30),
    ),
    'resale' => array('cola' => 'cola'),
    'combos' => array('combo' => array('biryani' => 1, 'fries' => 1)),
    // extra bills on top of the daily pattern: day offset from today, menu, qty, kind
    'specials' => array(
        array(-3, 'biryani', 1, 'complimentary'),
        array(-2, 'burger', 2, 'staff_meal'),
        array(-5, 'biryani', 10, 'discount10'),
        array(-4, 'cola', 2, 'tips5'),
        array(-6, 'biryani', 1, 'refund'),
    ),
    'opening_stock_offset' => -64,
    'opening_stock' => array('chicken' => 2000, 'rice' => 5000, 'cheese' => 1000, 'bun' => 20, 'potato' => 2000, 'saffron' => 20, 'cola' => 24),
    'shrink' => array(-10, 'chicken', 500),
    'rice_waste' => 3000,              // g, every 7 days from day -60
    'rice_waste_extra_purchase' => 3000,
    'cheese_overbuy' => array(-14, 40), // purchase units (kg)
    'warehouse_oil' => array(-63, 300), // L bought by the warehouse
    'oil_transfer' => 10,               // L warehouse -> kitchen every 7 days from day -63
    'expenses' => array(
        array(-15, 'Rent', 3000), array(-43, 'Rent', 3000),
        array(-20, 'Gas', 400), array(-48, 'Gas', 300),
    ),
    'salary_posted' => array(-5, 5000),   // payroll payment that also wrote an expense row
    'salary_unposted' => array(-6, 2000), // payroll payment without an expense row
    'mess_payment' => array(-8, 500),
    'mess_refund' => array(-7, 50),
    'mess_close' => array(-8, 'rice', 1000),
);

if (!function_exists('aifx_date')) {
    function aifx_date($spec, $offset) {
        return date('Y-m-d', strtotime($spec['today'] . ' ' . ($offset >= 0 ? '+' : '') . (int) $offset . ' day'));
    }

    /** daily quantity of a menu on a date (the regular bills) */
    function aifx_qty($spec, $menu, $date) {
        $friday = date('N', strtotime($date)) == 5;
        $recent = $date >= aifx_date($spec, -28);
        switch ($menu) {
            case 'biryani':
                return $friday ? 30 : 20;
            case 'burger':
                return $recent ? ($friday ? 18 : 12) : ($friday ? 24 : 16);
            case 'saffron_rice':
            case 'fries':
                return 2;
            case 'cola':
                return 10;
            case 'lemon_tea':
                return 3;
            case 'combo':
                return 1;
        }
        return 0;
    }

    /** every bill of the history: date, menu, qty, kind */
    function aifx_bills($spec) {
        $bills = array();
        for ($o = -$spec['history_days']; $o <= -1; $o++) {
            $date = aifx_date($spec, $o);
            foreach ($spec['menus'] as $menu => $m) {
                $q = aifx_qty($spec, $menu, $date);
                if ($q > 0) {
                    $bills[] = array('date' => $date, 'menu' => $menu, 'qty' => $q, 'kind' => 'regular');
                }
            }
        }
        foreach ($spec['specials'] as $s) {
            $bills[] = array('date' => aifx_date($spec, $s[0]), 'menu' => $s[1], 'qty' => $s[2], 'kind' => $s[3]);
        }
        return $bills;
    }

    /** the money columns of one bill (one line per bill) */
    function aifx_amounts($spec, $bill) {
        $price = $spec['menus'][$bill['menu']][3];
        $list = round($price * $bill['qty'], 2);
        $a = array('list' => $list, 'line' => $list, 'line_discount' => 0.0, 'sub_total' => $list, 'bill_discount' => 0.0,
            'tips' => 0.0, 'complimentary' => 0, 'staff_meal' => 0, 'refund' => 0.0);
        if ($bill['kind'] == 'complimentary') {
            $a['complimentary'] = 1;
            $a['line'] = 0.0;
            $a['line_discount'] = $list;
            $a['sub_total'] = 0.0;
        } elseif ($bill['kind'] == 'staff_meal') {
            $a['staff_meal'] = 1;
            $a['bill_discount'] = $list;
        } elseif ($bill['kind'] == 'discount10') {
            $a['bill_discount'] = round($list * 0.10, 2);
        } elseif ($bill['kind'] == 'tips5') {
            $a['tips'] = 5.0;
        } elseif ($bill['kind'] == 'refund') {
            $a['refund'] = $list;
        }
        $after = $a['sub_total'] - $a['bill_discount'];
        $a['vat'] = round($after - $after / (1 + $spec['vat_pct'] / 100), 2);
        $a['total_payable'] = round($after + $a['tips'], 2);
        $a['total_discount'] = round($a['line_discount'] + $a['bill_discount'], 2);
        return $a;
    }

    /** ingredient use of one plate: recipe, combo items, or the resale ingredient */
    function aifx_plate($spec, $menu) {
        if (isset($spec['resale'][$menu])) {
            return array($spec['resale'][$menu] => 1);
        }
        if (isset($spec['combos'][$menu])) {
            $use = array();
            foreach ($spec['combos'][$menu] as $child => $n) {
                foreach (aifx_plate($spec, $child) as $ing => $q) {
                    $use[$ing] = (isset($use[$ing]) ? $use[$ing] : 0) + $q * $n;
                }
            }
            return $use;
        }
        return isset($spec['recipes'][$menu]) ? $spec['recipes'][$menu] : array();
    }

    /** POS ingredient use per date: date => ingredient => qty */
    function aifx_usage($spec) {
        $use = array();
        foreach (aifx_bills($spec) as $b) {
            foreach (aifx_plate($spec, $b['menu']) as $ing => $q) {
                $use[$b['date']][$ing] = (isset($use[$b['date']][$ing]) ? $use[$b['date']][$ing] : 0) + $q * $b['qty'];
            }
        }
        return $use;
    }

    /** kitchen purchases: every 7 days, enough whole purchase units for the next 7 days (+ rice waste) */
    function aifx_purchases($spec) {
        $use = aifx_usage($spec);
        $list = array();
        for ($k = 0; $k <= 8; $k++) {
            $start = -$spec['history_days'] + 7 * $k;
            $need = array();
            for ($o = $start; $o < $start + 7 && $o <= -1; $o++) {
                foreach (isset($use[aifx_date($spec, $o)]) ? $use[aifx_date($spec, $o)] : array() as $ing => $q) {
                    $need[$ing] = (isset($need[$ing]) ? $need[$ing] : 0) + $q;
                }
            }
            $need['rice'] = (isset($need['rice']) ? $need['rice'] : 0) + $spec['rice_waste_extra_purchase'];
            foreach ($need as $ing => $q) {
                if ($ing == 'oil' || $q <= 0) {
                    continue; // oil comes from the warehouse
                }
                $list[] = array('date' => aifx_date($spec, $start), 'ing' => $ing, 'units' => (int) ceil($q / $spec['ingredients'][$ing][3]));
            }
        }
        $list[] = array('date' => aifx_date($spec, $spec['cheese_overbuy'][0]), 'ing' => 'cheese', 'units' => $spec['cheese_overbuy'][1]);
        return $list;
    }

    /** dates of the rice waste entries */
    function aifx_waste_dates($spec) {
        $dates = array();
        for ($o = -60; $o <= -1; $o += 7) {
            $dates[] = aifx_date($spec, $o);
        }
        return $dates;
    }

    /** dates of the warehouse -> kitchen oil transfers */
    function aifx_transfer_dates($spec) {
        $dates = array();
        for ($k = 0; $k <= 8; $k++) {
            $dates[] = aifx_date($spec, -$spec['history_days'] + 7 * $k);
        }
        return $dates;
    }
}

return $spec;
