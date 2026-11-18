<?php
/*
 | AI Insight calculation engine test (Phase 2).
 |   php tests/ai/fixture.php create
 |   php tests/ai/metrics_test.php
 | Expected numbers are worked out from tests/ai/fixture_spec.php with plain PHP
 | (no SQL), then compared with Ai_metrics_model::build() on the fixture outlets.
 */
require __DIR__ . '/bootstrap.php';
$spec = require __DIR__ . '/fixture_spec.php';
$db = $CI->db;
$CI->load->model('Ai_metrics_model');
$E = $CI->Ai_metrics_model;

$pass = $fail = 0;
function ok($cond, $label) {
    global $pass, $fail;
    $cond ? $pass++ : $fail++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
}
function near($a, $b, $label, $tol = 0.011) {
    ok($a !== null && $b !== null && abs((float) $a - (float) $b) <= $tol, $label . ' (got ' . var_export($a, true) . ', expected ' . round((float) $b, 4) . ')');
}

// ---------------------------------------------------------------- ids
$company = $db->query("SELECT id FROM tbl_companies WHERE business_name = ?", array($spec['company']))->row();
if (!$company) {
    exit("Fixture missing: run php tests/ai/fixture.php create\n");
}
$company_id = (int) $company->id;
$kitchen = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = ? AND outlet_name = ?", array($company_id, $spec['kitchen']))->row('id');
$warehouse = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = ? AND outlet_name = ?", array($company_id, $spec['warehouse']))->row('id');
$menu_id = $ing_id = array();
foreach ($spec['menus'] as $k => $m) {
    $menu_id[$k] = (int) $db->query("SELECT id FROM tbl_food_menus WHERE company_id = ? AND name = ?", array($company_id, $m[0]))->row('id');
}
foreach ($spec['ingredients'] as $k => $i) {
    $ing_id[$k] = (int) $db->query("SELECT id FROM tbl_ingredients WHERE company_id = ? AND name = ?", array($company_id, $i[0]))->row('id');
}
$settings = array('target_food_cost_pct' => 30, 'price_rounding' => 0.5, 'overbuy_factor' => 1.2, 'cover_days_limit' => 14, 'safety_days' => 2);
$today = $spec['today'];
$m = $E->build($company_id, $kitchen, $today, $settings);
$wh = $E->build($company_id, $warehouse, $today, $settings);

// ---------------------------------------------------------------- oracle
$vat = 1 + $spec['vat_pct'] / 100;
$unit_cost = array();
foreach ($spec['ingredients'] as $k => $i) {
    $unit_cost[$k] = $i[4] / $i[3];
}
$plate_cost = array();
foreach ($spec['menus'] as $k => $mm) {
    $c = 0;
    foreach (aifx_plate($spec, $k) as $ing => $q) {
        $c += $q * $unit_cost[$ing];
    }
    $plate_cost[$k] = $c;
}
$cur_from = aifx_date($spec, -28);
$cur_to = aifx_date($spec, -1);
$prev_from = aifx_date($spec, -56);
$prev_to = aifx_date($spec, -29);
$in = function ($d, $from, $to) {
    return $d >= $from && $d <= $to;
};

// menu: paid qty and net revenue in the 28-day window
$exp_items = array();
foreach (aifx_bills($spec) as $b) {
    if (!$in($b['date'], $cur_from, $cur_to)) {
        continue;
    }
    $a = aifx_amounts($spec, $b);
    if (!isset($exp_items[$b['menu']])) {
        $exp_items[$b['menu']] = array('qty' => 0, 'net' => 0.0);
    }
    if ($a['complimentary'] || $a['staff_meal']) {
        continue;
    }
    $net = $a['line'];
    if ($a['sub_total'] > 0) {
        $net -= $a['line'] * $a['bill_discount'] / $a['sub_total'] + $a['vat'] * $a['line'] / $a['sub_total'];
    }
    $exp_items[$b['menu']]['qty'] += $b['qty'];
    $exp_items[$b['menu']]['net'] += $net;
}
$got_items = array();
foreach ($m['menu']['items'] as $it) {
    $got_items[$it['id']] = $it;
}
echo "--- menu performance\n";
foreach ($exp_items as $k => $e) {
    $g = isset($got_items[$menu_id[$k]]) ? $got_items[$menu_id[$k]] : null;
    ok($g !== null && $g['qty'] == $e['qty'], "$k paid qty {$e['qty']}");
    near($g ? $g['net_revenue'] : null, $e['net'], "$k net revenue");
    if ($plate_cost[$k] > 0) {
        near($g ? $g['plate_cost'] : null, $plate_cost[$k], "$k plate cost");
    }
}
// classes from the oracle numbers
$costed = array_filter(array_keys($exp_items), function ($k) use ($plate_cost) {
    return $plate_cost[$k] > 0;
});
$tq = $tcm = 0;
foreach ($costed as $k) {
    $tq += $exp_items[$k]['qty'];
    $tcm += $exp_items[$k]['net'] - $plate_cost[$k] * $exp_items[$k]['qty'];
}
$avg_cm = $tcm / $tq;
near($m['menu']['avg_cm'], $avg_cm, 'weighted average contribution margin');
foreach ($costed as $k) {
    $popular = $exp_items[$k]['qty'] / $tq >= 0.7 / count($costed);
    $high = ($exp_items[$k]['net'] / $exp_items[$k]['qty'] - $plate_cost[$k]) >= $avg_cm;
    $class = $popular ? ($high ? 'star' : 'plowhorse') : ($high ? 'puzzle' : 'dog');
    ok(isset($got_items[$menu_id[$k]]) && $got_items[$menu_id[$k]]['class'] == $class, "$k class = $class");
}
$known = array('biryani' => 'star', 'burger' => 'plowhorse', 'saffron_rice' => 'puzzle', 'fries' => 'dog', 'cola' => 'plowhorse', 'combo' => 'puzzle');
foreach ($known as $k => $class) {
    ok($got_items[$menu_id[$k]]['class'] == $class, "designed answer: $k is $class");
}
ok($got_items[$menu_id['lemon_tea']]['class'] === null, 'lemon tea (no recipe) gets no class');
ok(array_column($m['menu']['unsold'], 'id') == array($menu_id['mutton']), 'mutton curry is the only unsold item');
ok($got_items[$menu_id['burger']]['qty_change_pct'] == -25.0, 'burger quantity -25 % vs previous 28 days');
ok($got_items[$menu_id['fries']]['name'] === 'FX Fries & Dip', 'names saved HTML-escaped come back as plain text');

echo "--- price check\n";
$got_price = array();
foreach ($m['price']['items'] as $p) {
    $got_price[$p['id']] = $p;
}
foreach ($spec['menus'] as $k => $mm) {
    if ($plate_cost[$k] <= 0) {
        ok(!isset($got_price[$menu_id[$k]]), "$k without cost is not price-checked");
        continue;
    }
    $net_list = $mm[3] / $vat;
    $pct = $plate_cost[$k] / $net_list * 100;
    $band = $pct >= 100 ? 'loss' : ($pct > 50 ? 'critical' : ($pct > 35 ? 'high' : ($pct > 30 ? 'watch' : 'good')));
    $g = $got_price[$menu_id[$k]];
    ok($g['band'] == $band, "$k band $band (" . round($pct, 1) . " %)");
    near($g['list_price'], $mm[3], "$k uses the outlet price");
    if ($band != 'good') {
        $sugg = ceil(round($plate_cost[$k] / 0.30 * $vat / 0.5, 6)) * 0.5;
        $qty = isset($exp_items[$k]) ? $exp_items[$k]['qty'] : 0;
        near($g['suggested_price'], $sugg, "$k suggested price");
        near($g['impact'], max(0, $sugg / $vat - $net_list) * $qty, "$k 28-day impact");
    }
}
near($got_price[$menu_id['burger']]['suggested_price'], 22.00, 'designed answer: burger suggested 22.00');

echo "--- stock and purchase vs usage\n";
$usage = aifx_usage($spec);
$purchases = aifx_purchases($spec);
$stock = $spec['opening_stock'];
$pur_cur = $used_cur = array();
foreach ($purchases as $p) {
    $q = $p['units'] * $spec['ingredients'][$p['ing']][3];
    $stock[$p['ing']] = (isset($stock[$p['ing']]) ? $stock[$p['ing']] : 0) + $q;
    if ($in($p['date'], $cur_from, $cur_to)) {
        $pur_cur[$p['ing']] = (isset($pur_cur[$p['ing']]) ? $pur_cur[$p['ing']] : 0) + $q;
    }
}
foreach ($usage as $d => $u) {
    foreach ($u as $ing => $q) {
        $stock[$ing] = (isset($stock[$ing]) ? $stock[$ing] : 0) - $q;
        if ($in($d, $cur_from, $cur_to)) {
            $used_cur[$ing] = (isset($used_cur[$ing]) ? $used_cur[$ing] : 0) + $q;
        }
    }
}
$stock['oil'] = (isset($stock['oil']) ? $stock['oil'] : 0) + count(aifx_transfer_dates($spec)) * $spec['oil_transfer'] * 1000;
$stock['rice'] -= count(aifx_waste_dates($spec)) * $spec['rice_waste'] + $spec['mess_close'][2];
$used_cur['rice'] += $spec['mess_close'][2];
$stock['chicken'] -= $spec['shrink'][2];
$got_ing = array();
foreach ($m['purchase']['items'] as $r) {
    $got_ing[$r['id']] = $r;
}
foreach ($stock as $k => $q) {
    if (isset($got_ing[$ing_id[$k]])) {
        near($got_ing[$ing_id[$k]]['stock'], $q, "$k stock now", 0.01);
        near($got_ing[$ing_id[$k]]['used'], isset($used_cur[$k]) ? $used_cur[$k] : 0, "$k used in 28 days (POS + mess)", 0.01);
        near($got_ing[$ing_id[$k]]['purchased'], isset($pur_cur[$k]) ? $pur_cur[$k] : 0, "$k purchased in 28 days", 0.01);
    }
}
$engine_stock = $E->ingredientStock($company_id, $kitchen);
near($engine_stock[$ing_id['cheese']]->stock, $stock['cheese'], 'ingredientStock() = inventory formula (cheese)', 0.01);
$ch = $got_ing[$ing_id['cheese']];
$daily = $used_cur['cheese'] / 28;
ok($ch['overbuy'] === true, 'designed answer: cheese is over-bought');
near($ch['excess_value'], ($stock['cheese'] - 14 * $daily) * $unit_cost['cheese'], 'cheese excess value (stock above 14 days of use)');
$rice = $got_ing[$ing_id['rice']];
$waste_cur = 0;
foreach (aifx_waste_dates($spec) as $d) {
    $waste_cur += $in($d, $cur_from, $cur_to) ? $spec['rice_waste'] : 0;
}
near($rice['wasted'], $waste_cur, 'rice wasted in 28 days');
near($rice['waste_value'], $waste_cur * $unit_cost['rice'], 'rice waste value');
ok($rice['high_waste'] === true, 'designed answer: rice is high waste');
near($got_ing[$ing_id['chicken']]['shrink_value'], $spec['shrink'][2] * $unit_cost['chicken'], 'chicken shrinkage value');
foreach (array('chicken', 'bun', 'saffron', 'potato', 'cola', 'oil') as $k) {
    ok($got_ing[$ing_id[$k]]['overbuy'] === false, "$k is not flagged as over-bought");
}
near($got_ing[$ing_id['oil']]['transfer_in'], 4 * $spec['oil_transfer'] * 1000, 'oil transferred in during the 28 days');

echo "--- profit & loss\n";
$pl = function ($from, $to) use ($spec, $in, $vat, $plate_cost, $unit_cost) {
    $r = array('orders' => 0, 'gross' => 0.0, 'vat' => 0.0, 'tips' => 0.0, 'discounts' => 0.0, 'complimentary' => 0.0, 'staff' => 0.0, 'refunds' => 0.0, 'food' => 0.0);
    foreach (aifx_bills($spec) as $b) {
        $a = aifx_amounts($spec, $b);
        if ($a['refund'] > 0 && $in($b['date'], $from, $to)) {
            $r['refunds'] += $a['refund'];
        }
        if (!$in($b['date'], $from, $to)) {
            continue;
        }
        $r['orders']++;
        $r['gross'] += $a['total_payable'];
        $r['vat'] += $a['vat'];
        $r['tips'] += $a['tips'];
        if ($a['staff_meal']) {
            $r['staff'] += $a['total_discount'];
        } elseif ($a['complimentary']) {
            $r['complimentary'] += $a['line_discount'];
        } else {
            $r['discounts'] += $a['total_discount'];
        }
        $r['food'] += $plate_cost[$b['menu']] * $b['qty'];
    }
    $d = function ($o) use ($spec) {
        return aifx_date($spec, $o);
    };
    $r['mess'] = ($in($d($spec['mess_payment'][0]), $from, $to) ? $spec['mess_payment'][1] : 0) - ($in($d($spec['mess_refund'][0]), $from, $to) ? $spec['mess_refund'][1] : 0);
    if ($in($d($spec['mess_close'][0]), $from, $to)) {
        $r['food'] += $spec['mess_close'][2] * $unit_cost[$spec['mess_close'][1]];
    }
    $r['waste'] = 0;
    foreach (aifx_waste_dates($spec) as $wd) {
        $r['waste'] += $in($wd, $from, $to) ? $spec['rice_waste'] * $unit_cost['rice'] : 0;
    }
    $r['salaries'] = ($in($d($spec['salary_posted'][0]), $from, $to) ? $spec['salary_posted'][1] : 0) + ($in($d($spec['salary_unposted'][0]), $from, $to) ? $spec['salary_unposted'][1] : 0);
    $r['expenses'] = 0;
    foreach ($spec['expenses'] as $e) {
        $r['expenses'] += $in($d($e[0]), $from, $to) ? $e[2] : 0;
    }
    $r['net'] = $r['gross'] - $r['vat'] - $r['tips'];
    $r['profit'] = $r['net'] + $r['mess'] - $r['refunds'] - $r['food'] - $r['waste'] - $r['salaries'] - $r['expenses'];
    return $r;
};
foreach (array('cur' => array($cur_from, $cur_to), 'prev' => array($prev_from, $prev_to)) as $w => $range) {
    $e = $pl($range[0], $range[1]);
    $g = $m['pnl'][$w];
    ok($g['orders'] == $e['orders'], "$w orders {$e['orders']}");
    near($g['gross_sales'], $e['gross'], "$w gross sales");
    near($g['vat'], $e['vat'], "$w VAT");
    near($g['tips'], $e['tips'], "$w tips (left out of net sales)");
    near($g['net_sales'], $e['net'], "$w net sales");
    near($g['discounts'], $e['discounts'], "$w discounts");
    near($g['complimentary'], $e['complimentary'], "$w complimentary");
    near($g['staff_meals'], $e['staff'], "$w staff meals");
    near($g['refunds'], $e['refunds'], "$w refunds");
    near($g['mess_revenue'], $e['mess'], "$w mess revenue (payments - refunds)");
    near($g['food_cost'], $e['food'], "$w food cost (POS + mess)", 0.02);
    near($g['waste'], $e['waste'], "$w waste");
    near($g['salaries'], $e['salaries'], "$w salaries");
    near($g['expenses_total'], $e['expenses'], "$w other expenses (salary expense not counted twice)");
    near($g['net_profit'], $e['profit'], "$w net profit", 0.03);
}
near($m['pnl']['change']['net_sales']['value'], $m['pnl']['cur']['net_sales'] - $m['pnl']['prev']['net_sales'], 'P&L change column');

echo "--- prediction\n";
$all_qty = array();
foreach (aifx_bills($spec) as $b) {
    $all_qty[$b['menu']][$b['date']] = (isset($all_qty[$b['menu']][$b['date']]) ? $all_qty[$b['menu']][$b['date']] : 0) + $b['qty'];
}
$first = aifx_date($spec, -$spec['history_days']);
$oracle = function ($series, $date) use ($spec, $first) {
    $vals = array();
    for ($k = 1; $k <= 8; $k++) {
        $d = date('Y-m-d', strtotime("$date -" . (7 * $k) . ' day'));
        if ($d < $first) {
            break;
        }
        $vals[] = array(isset($series[$d]) ? $series[$d] : 0, $k <= 4 ? 2 : 1, $k);
    }
    if (count($vals) < 4) {
        return null;
    }
    if (count($vals) >= 6) {
        $v = array_column($vals, 0);
        $imin = array_search(min($v), $v);
        $imax = array_search(max($v), $v);
        if ($imax === $imin) {
            $rest = $v;
            unset($rest[$imin]);
            $imax = array_search(max($rest), $rest);
        }
        unset($vals[$imin], $vals[$imax]);
    }
    $s = $w = 0;
    foreach ($vals as $x) {
        $s += $x[0] * $x[1];
        $w += $x[1];
    }
    $last = $prior = 0;
    foreach ($series as $d => $q) {
        $age = (strtotime($date) - strtotime($d)) / 86400;
        if ($age >= 1 && $age <= 28) {
            $last += $q;
        } elseif ($age >= 29 && $age <= 56) {
            $prior += $q;
        }
    }
    $trend = $prior > 0 ? min(1.2, max(0.8, $last / $prior)) : 1;
    return round($s / $w * $trend, 2);
};
ok($m['forecast']['available'] === true, 'prediction available (63 days of sales)');
foreach ($m['forecast']['days'] as $i => $day) {
    $e = 0;
    foreach ($all_qty as $series) {
        $f = $oracle($series, $day['date']);
        $e += $f > 0 ? $f : 0;
    }
    near($day['qty'], $e, "forecast {$day['date']} ({$day['weekday']}) total qty", 0.06);
}
$err = $act = 0;
for ($i = 7; $i >= 1; $i--) {
    $d = aifx_date($spec, -$i);
    $f = $a = 0;
    foreach ($all_qty as $series) {
        $f += (float) $oracle($series, $d);
        $a += isset($series[$d]) ? $series[$d] : 0;
    }
    $err += abs($f - $a);
    $act += $a;
}
near($m['forecast']['accuracy_pct'], $err / $act * 100, 'back-test accuracy over the last 7 days', 0.06);
$burger_sun = $oracle($all_qty['burger'], $today);
near($burger_sun, 10.67, 'designed answer: burger Sunday forecast = 13.33 x trend clipped at 0.8');
$buy = array_column($m['forecast']['purchase'], 'buy_qty', 'id');
ok(!isset($buy[$ing_id['cheese']]), 'no cheese in the suggested purchase (over-stocked)');
ok(isset($buy[$ing_id['chicken']]) && $buy[$ing_id['chicken']] > 0, 'chicken in the suggested purchase');

echo "--- top 5 and data health\n";
$expected_top = array('underpriced:menu' . $menu_id['burger'], 'overbuy:ing' . $ing_id['cheese'], 'underpriced:menu' . $menu_id['cola'], 'expense_jump', 'waste:ing' . $ing_id['rice']);
$got_top = array();
foreach ($m['top5'] as $t) {
    $got_top[] = $t['type'] == 'expense_jump' ? 'expense_jump' : $t['id'];
}
ok($got_top == $expected_top, 'top 5 order: ' . implode(', ', $got_top));
ok($m['top5'][3]['subject'] == 'Gas', 'expense jump is Gas');
$ids = array_column($m['top5'], 'subject_key');
ok(count($ids) == count(array_unique($ids)), 'one issue per item in the top 5 (burger drop folded into its price issue)');
$hc = array_map(function ($c) {
    return $c['status'];
}, $m['health']['checks']);
ok($hc == array('recipe_cover' => 'good', 'menus_recipe' => 'watch', 'ing_price' => 'good', 'purchases' => 'good', 'waste' => 'good',
        'negative' => 'good', 'history' => 'good', 'stock_count' => 'good'), 'kitchen data health: ' . json_encode($hc));
ok($m['health']['gates'] == array('menu' => 'ready', 'price' => 'limited', 'purchase' => 'ready', 'pnl' => 'ready', 'forecast' => 'ready'), 'kitchen gates');

echo "--- warehouse outlet\n";
ok($wh['health']['stock_only'] === true, 'warehouse detected as stock-only');
ok($wh['health']['gates']['menu'] == 'blocked' && $wh['health']['gates']['forecast'] == 'blocked', 'warehouse: menu and forecast blocked');
ok($wh['forecast']['available'] === false && $wh['menu']['items'] === array(), 'warehouse: no menu items, no forecast');
ok($wh['menu']['unsold'] === array() && $wh['top5'] === array(), 'warehouse: no "never sold" items and no top 5 (it sells nothing)');
$wo = null;
foreach ($wh['purchase']['items'] as $r) {
    if ($r['id'] == $ing_id['oil']) {
        $wo = $r;
    }
}
near($wo['stock'], ($spec['warehouse_oil'][1] - count(aifx_transfer_dates($spec)) * $spec['oil_transfer']) * 1000, 'warehouse oil stock');
near($wo['transfer_out'], 4 * $spec['oil_transfer'] * 1000, 'warehouse oil transferred out in 28 days');
ok($wo['daily_use'] > 0, 'warehouse: transfers out count as usage');

echo "--- AI payload\n";
$payload = json_encode($E->aiPayload($m));
ok(strlen($payload) < 20000, 'payload ' . strlen($payload) . ' chars (~' . round(strlen($payload) / 4) . ' tokens) under 5000 tokens');
ok(strpos($payload, 'fixture') === false, 'no free-text notes in the payload');
ok(strpos($payload, 'sk-ant') === false, 'no key in the payload');

echo "--- other outlets must not crash\n";
foreach ($db->query("SELECT o.id, o.company_id FROM tbl_outlets o WHERE o.del_status = 'Live' AND o.company_id <> ?", array($company_id))->result() as $o) {
    try {
        $x = $E->build((int) $o->company_id, (int) $o->id, $E->companyToday($o->company_id), $settings);
        ok(is_array($x) && isset($x['top5']), "build() on company {$o->company_id} outlet {$o->id}");
    } catch (Throwable $t) {
        ok(false, "build() on company {$o->company_id} outlet {$o->id}: " . $t->getMessage());
    }
}

echo PHP_EOL . "$pass passed, $fail failed" . PHP_EOL;
exit($fail ? 1 : 0);
