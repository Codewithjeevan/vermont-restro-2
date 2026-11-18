<?php
/*
 | AI Insight page test (Phase 4), over HTTP against the local site.
 |   php tests/ai/fixture.php create
 |   php tests/ai/page_test.php [base_url]        (default http://trul-resto.test/)
 | Needs one earlier browser login as Admin on this database: that session row is
 | copied (company and outlet switched to the fixture) and the copies are deleted at
 | the end. No real API call: reports are made here with AI_MOCK=1, and Regenerate
 | runs with the fixture company's monthly limit at 0, so the client refuses before
 | any call.
 */
require __DIR__ . '/bootstrap.php';
$spec = require __DIR__ . '/fixture_spec.php';
$db = $CI->db;
putenv('AI_MOCK=1');
$CI->load->model('Ai_model');
$CI->load->model('Ai_metrics_model');
$CI->load->library('Ai/Ai_report', null, 'Ai_report');
$CI->load->library('Ai/Ai_narrator', null, 'Ai_narrator');
$R = $CI->Ai_report;
$base = isset($argv[1]) ? rtrim($argv[1], '/') . '/' : 'http://trul-resto.test/';

$pass = $fail = 0;
function ok($cond, $label) {
    global $pass, $fail;
    $cond ? $pass++ : $fail++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
}

$company_id = (int) $db->query("SELECT id FROM tbl_companies WHERE business_name = ?", array($spec['company']))->row('id');
if (!$company_id) {
    exit("Fixture missing: run php tests/ai/fixture.php create\n");
}
$kitchen = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = ? AND outlet_name = ?", array($company_id, $spec['kitchen']))->row('id');
$warehouse = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = ? AND outlet_name = ?", array($company_id, $spec['warehouse']))->row('id');
$foreign_outlet = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id <> ? AND del_status = 'Live' ORDER BY id LIMIT 1", array($company_id))->row('id');
$template_row = $db->query("SELECT data FROM tbl_sessions WHERE data LIKE '%role|s:5:\"Admin\"%' AND data LIKE '%function_access%' ORDER BY timestamp DESC LIMIT 1")->row('data');
if (!$template_row) {
    exit("No Admin session on this database: log in once in the browser, then run again.\n");
}
$today = $CI->Ai_metrics_model->companyToday($company_id);
$saved_settings = (array) $CI->Ai_model->getSettings($company_id);
$db->update('tbl_ai_settings', array('enabled' => 1, 'monthly_budget_usd' => 5), array('company_id' => $company_id));
$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));

require __DIR__ . '/web.php';
/**
 * The page rendered here in the CLI, where every notice or warning counts (production
 * hides them on the web, so a missing variable would only show as an empty section).
 * @return array html, errors
 */
function strict_render($outlet_id, $days, $view = 'ai_insight/index') {
    global $CI, $R, $company_id;
    $_SESSION = array('company_id' => (string) $company_id, 'outlet_id' => (string) $outlet_id, 'user_id' => '1', 'role' => 'Admin');
    $errors = array();
    set_error_handler(function ($no, $str, $file, $line) use (&$errors) {
        // CodeIgniter 3 itself raises PHP 8.2 "dynamic property" deprecations; only our code counts
        if (strpos(str_replace('\\', '/', $file), '/system/') === false) {
            $errors[] = basename($file) . ":$line $str";
        }
        return true;
    });
    $CI->load->clear_vars();
    if ($view == 'ai_insight/index') {
        $CI->load->library('Ai/Ai_chat', null, 'Ai_chat');
        $data = $R->pageData($company_id, $outlet_id, $days)
            + array('can_regenerate' => true, 'chat' => $CI->Ai_chat->panelData($company_id, $outlet_id, 1));
    } else {
        $data = array('settings' => $CI->Ai_model->getSettings($company_id), 'steps' => ai_config('ai_rounding_steps'), 'currency' => 'AED');
    }
    $html = $CI->load->view($view, $data, true);
    restore_error_handler();
    return array($html, $errors);
}
function strict_ok($label, $outlet_id, $days = 28, $view = 'ai_insight/index') {
    list($html, $errors) = strict_render($outlet_id, $days, $view);
    ok(!$errors && strlen($html) > 1000, "$label: renders with no PHP notice" . ($errors ? ': ' . implode(' | ', array_slice(array_unique($errors), 0, 4)) : ''));
}
function h4_order($body, $labels) {
    $last = -1;
    foreach ($labels as $l) {
        $pos = strpos($body, '<h4>' . $l . '</h4>');
        if ($pos === false || $pos < $last) {
            return false;
        }
        $last = $pos;
    }
    return true;
}

$admin_changes = array('company_id' => (string) $company_id, 'outlet_id' => (string) $kitchen, 'outlet_name' => $spec['kitchen'],
    'business_name' => $spec['company'], 'role' => 'Admin');
make_session('admin', $admin_changes);
make_session('limited', array_merge($admin_changes, array('role' => 'Manager', 'function_access' => array('view-437'))));
make_session('foreign', array_merge($admin_changes, array('outlet_id' => (string) $foreign_outlet)));
make_session('warehouse', array_merge($admin_changes, array('outlet_id' => (string) $warehouse, 'outlet_name' => $spec['warehouse'])));

echo "--- no report yet\n";
$r = http('admin', 'GET', 'Ai_insight/index');
ok($r['code'] == 200 && strpos($r['body'], 'has no report yet') !== false, 'no snapshot: "no report yet" notice (HTTP ' . $r['code'] . ')');
ok(strpos($r['body'], '<h4>Data Health</h4>') !== false && strpos($r['body'], '<h4>Top 5 improvements</h4>') === false, 'no snapshot: Data Health only');
ok(strpos($r['body'], 'Press Regenerate report') !== false && token($r['body']) !== '', 'no snapshot: Regenerate offered, with a form token');
strict_ok('no snapshot', $kitchen);

echo "--- full report (AI text)\n";
$x = $R->runOutlet($company_id, $kitchen, $today);
$m = json_decode($R->getSnapshot($kitchen, $today)->metrics_json, true);
$r = http('admin', 'GET', 'Ai_insight/index');
$sections = array('Top 5 improvements', 'Menu performance', 'Price check', 'Purchase vs usage', 'Profit & loss', 'Sales prediction: next 7 days', 'Data Health');
ok($x['status'] == 'ok' && strpos($r['body'], 'Written by AI') !== false, 'full snapshot: "Written by AI"');
ok(h4_order($r['body'], $sections), 'sections in the planned order: ' . implode(' > ', $sections));
ok(strpos($r['body'], htmlspecialchars($m['top5'][0]['subject'])) !== false && strpos($r['body'], 'about AED ' . number_format($m['top5'][0]['impact_30d'], 0) . ' a month') !== false,
    'top issue with its monthly value: ' . $m['top5'][0]['subject'] . ', AED ' . number_format($m['top5'][0]['impact_30d'], 0));
ok(strpos($r['body'], 'AED ' . number_format($m['pnl']['cur']['net_profit'], 2)) !== false, 'P&L net profit shown: AED ' . number_format($m['pnl']['cur']['net_profit'], 2));
ok(strpos($r['body'], 'ai-more') !== false || count($m['menu']['items']) <= 10, 'long tables open with "Show all"');
ok(strpos($r['body'], 'sk-ant') === false, 'no API key in the page');
strict_ok('full snapshot', $kitchen);
strict_ok('full snapshot, 7 days', $kitchen, 7);
$cur7 = date('d M Y', strtotime($m['windows']['cur7']['from']));
$r7 = http('admin', 'GET', 'Ai_insight/index/7');
ok($r7['code'] == 200 && strpos($r7['body'], $cur7) !== false && strpos($r7['body'], 'class="active">Last 7 days') !== false, '7-day view: period starts ' . $cur7);
ok(strpos($r7['body'], 'AED ' . number_format($m['pnl7']['cur']['net_profit'], 2)) !== false, '7-day view: 7-day net profit AED ' . number_format($m['pnl7']['cur']['net_profit'], 2));

echo "--- standard text, pending, old report\n";
$t = $CI->Ai_narrator->template($m);
$t['source'] = 'template';
$db->update('tbl_ai_snapshots', array('narrative_status' => 'template', 'error' => 'call: budget', 'narrative_json' => json_encode($t)), array('outlet_id' => $kitchen, 'report_date' => $today));
$r = http('admin', 'GET', 'Ai_insight/index');
ok(strpos($r['body'], 'Standard text') !== false && strpos($r['body'], 'AI limit for your company has been reached') !== false && strpos($r['body'], 'Written by AI') === false,
    'template-only snapshot: "Standard text" + the reason in words');
ok(h4_order($r['body'], $sections), 'template-only snapshot: every section still shown');
strict_ok('template-only snapshot', $kitchen);
$db->update('tbl_ai_snapshots', array('narrative_status' => 'pending', 'error' => null, 'narrative_json' => null), array('outlet_id' => $kitchen, 'report_date' => $today));
$r = http('admin', 'GET', 'Ai_insight/index');
ok(strpos($r['body'], 'still being written') !== false && h4_order($r['body'], $sections), 'numbers saved, text not yet: template text on the fly');
strict_ok('pending snapshot', $kitchen);
$yesterday = $CI->Ai_metrics_model->addDays($today, -1);
$db->update('tbl_ai_snapshots', array('report_date' => $yesterday), array('outlet_id' => $kitchen, 'report_date' => $today));
$r = http('admin', 'GET', 'Ai_insight/index');
ok(strpos($r['body'], 'This is the report of ' . date('d M Y', strtotime($yesterday))) !== false, 'yesterday\'s report: says so');
$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));

echo "--- stock-only outlet\n";
$R->runOutlet($company_id, $warehouse, $today);
$r = http('warehouse', 'GET', 'Ai_insight/index');
ok($r['code'] == 200 && strpos($r['body'], '<h4>Purchase vs usage</h4>') !== false && strpos($r['body'], '<h4>Menu performance</h4>') === false
    && strpos($r['body'], '<h4>Price check</h4>') === false && strpos($r['body'], 'stock-only outlet') !== false, 'warehouse: stock sections only, with the stock-only note');
strict_ok('warehouse', $warehouse);
strict_ok('warehouse, 7 days', $warehouse, 7);

echo "--- names are escaped everywhere\n";
$burger = $db->query("SELECT id, name FROM tbl_food_menus WHERE company_id = ? AND name = 'FX Cheese Burger'", array($company_id))->row();
$chicken = $db->query("SELECT id, name FROM tbl_ingredients WHERE company_id = ? AND name = 'FX Chicken'", array($company_id))->row();
$db->update('tbl_food_menus', array('name' => '<script>alert(1)</script> Burger'), array('id' => $burger->id));
$db->update('tbl_ingredients', array('name' => '<img src=x onerror=alert(2)>'), array('id' => $chicken->id));
$R->runOutlet($company_id, $kitchen, $today, true);
$r = http('admin', 'GET', 'Ai_insight/index');
ok(strpos($r['body'], '<script>alert(1)</script>') === false && strpos($r['body'], '<img src=x onerror') === false, 'raw <script> / <img onerror> names never reach the HTML');
ok(substr_count($r['body'], '&lt;script&gt;alert(1)&lt;/script&gt; Burger') >= 3 && strpos($r['body'], '&lt;img src=x onerror=alert(2)&gt;') !== false,
    'the names show as text (' . substr_count($r['body'], '&lt;script&gt;alert(1)&lt;/script&gt; Burger') . ' places)');
strict_ok('odd names', $kitchen);
$db->update('tbl_food_menus', array('name' => $burger->name), array('id' => $burger->id));
$db->update('tbl_ingredients', array('name' => $chicken->name), array('id' => $chicken->id));
$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));

echo "--- Regenerate: token, origin, busy, 3 a day\n";
$db->update('tbl_ai_settings', array('monthly_budget_usd' => 0), array('company_id' => $company_id));
$r = http('admin', 'GET', 'Ai_insight/index');
$tok = token($r['body']);
$p = post_follow('admin', 'Ai_insight/regenerate', array('days' => 28));
ok(strpos($p['body'], 'could not be verified') !== false && !$R->getSnapshot($kitchen, $today), 'no token: refused, nothing made');
$p = post_follow('admin', 'Ai_insight/regenerate', array('ai_csrf' => $tok, 'days' => 28), array('Origin: http://evil.example'));
ok(strpos($p['body'], 'could not be verified') !== false && !$R->getSnapshot($kitchen, $today), 'other site\'s Origin: refused');
$lock = 'ai_regen_' . $db->database . '_' . $kitchen;
$db->query("SELECT GET_LOCK(?, 0)", array($lock));
$p = post_follow('admin', 'Ai_insight/regenerate', array('ai_csrf' => $tok, 'days' => 28));
ok(strpos($p['body'], 'already being made') !== false, 'a run in progress for the outlet: "already being made"');
$db->query("SELECT RELEASE_LOCK(?)", array($lock));
$flashes = array();
for ($i = 1; $i <= 4; $i++) {
    $p = post_follow('admin', 'Ai_insight/regenerate', array('ai_csrf' => $tok, 'days' => 7));
    $flashes[] = strpos($p['body'], 'made again with the standard text') !== false ? 'done' : (strpos($p['body'], 'times a day') !== false ? 'limit' : 'other');
}
ok($flashes == array('done', 'done', 'done', 'limit'), 'clicks 1-4: ' . implode(', ', $flashes));
ok(strpos($p['first']['location'], 'Ai_insight/index/7') !== false, 'back to the 7-day view it came from');
ok((int) $R->getSnapshot($kitchen, $today)->regen_count == 3 && preg_match('/<button type="submit" class="btn bg-blue-btn" disabled/', $p['body']), 'count 3, button disabled');
ok(strpos($p['body'], 'AI limit for your company has been reached') !== false, 'the page says why the text is standard');
ok((int) $db->query("SELECT COUNT(*) AS c FROM tbl_ai_usage WHERE company_id = ? AND user_id IS NOT NULL AND status <> 'blocked'", array($company_id))->row('c') == 0, 'no API call was made (every click stopped at the monthly limit)');
$db->update('tbl_ai_settings', array('monthly_budget_usd' => 5), array('company_id' => $company_id));

echo "--- permissions and outlet ownership\n";
$r = http('limited', 'GET', 'Ai_insight/index');
ok($r['code'] == 200 && strpos($r['body'], 'ai-regen-form') === false && strpos($r['body'], 'data-access="update-441" data-menu__cid="irp_ai" href') === false,
    'view-only role: page without the Regenerate button or the Settings tab');
$p = http('limited', 'POST', 'Ai_insight/regenerate', array('ai_csrf' => $tok));
ok(redirect_to($p, 'Authentication/userProfile'), 'view-only role: Regenerate POST refused');
$p = http('limited', 'GET', 'Ai_insight/settings');
ok(redirect_to($p, 'Authentication/userProfile'), 'view-only role: Settings refused');
$r = http('foreign', 'GET', 'Ai_insight/index');
ok(redirect_to($r, 'Outlet/outlets'), 'session outlet of another company: sent to the outlet list');
$db->update('tbl_ai_settings', array('enabled' => 0), array('company_id' => $company_id));
$r = http('admin', 'GET', 'Ai_insight/index');
ok(strpos($r['body'], 'not switched on for your company') !== false && strpos($r['body'], '<h4>') === false, 'company switched off: message only');
$db->update('tbl_ai_settings', array('enabled' => 1), array('company_id' => $company_id));

echo "--- AI Settings\n";
$r = http('admin', 'GET', 'Ai_insight/settings');
$tok = token($r['body']);
ok($r['code'] == 200 && strpos($r['body'], 'name="target_food_cost_pct"') !== false && $tok, 'settings page with its form');
strict_ok('settings page', $kitchen, 28, 'ai_insight/settings');
$good = array('ai_csrf' => $tok, 'target_food_cost_pct' => '32.5', 'price_rounding' => '1', 'overbuy_pct' => '25', 'cover_days_limit' => '10', 'safety_days' => '3');
$p = post_follow('admin', 'Ai_insight/saveSettings', $good);
$s = $CI->Ai_model->getSettings($company_id);
ok(strpos($p['body'], 'AI settings saved') !== false && (float) $s->target_food_cost_pct == 32.5 && (float) $s->price_rounding == 1
    && (float) $s->overbuy_factor == 1.25 && (int) $s->cover_days_limit == 10 && (int) $s->safety_days == 3, 'valid values saved (over-purchase 25% -> factor 1.25)');
foreach (array('target_food_cost_pct' => '99', 'price_rounding' => '0.3', 'cover_days_limit' => '2.5', 'safety_days' => '-1', 'overbuy_pct' => 'abc') as $field => $bad) {
    $p = post_follow('admin', 'Ai_insight/saveSettings', array_merge($good, array($field => $bad, 'target_food_cost_pct' => $field == 'target_food_cost_pct' ? $bad : '40')));
    ok(strpos($p['body'], 'Please check the values') !== false && (float) $CI->Ai_model->getSettings($company_id)->target_food_cost_pct == 32.5, "$field = $bad: refused, nothing saved");
}
$p = post_follow('admin', 'Ai_insight/saveSettings', array_merge($good, array('ai_csrf' => 'x')));
ok(strpos($p['body'], 'could not be verified') !== false, 'settings without a valid token: refused');

/* ---------- clean up ---------- */
$db->query("DELETE FROM tbl_sessions WHERE data LIKE '%ai_page_test%'");
foreach ($jars as $jar) {
    @unlink($jar);
}
$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));
$db->query("DELETE FROM tbl_ai_usage WHERE company_id = ?", array($company_id));
unset($saved_settings['id']);
$db->update('tbl_ai_settings', $saved_settings, array('company_id' => $company_id));
echo PHP_EOL . "$pass passed, $fail failed" . PHP_EOL;
exit($fail ? 1 : 0);
