<?php
/*
 | AI Insight daily report test (Phase 3): text validation, template, retries.
 |   php tests/ai/fixture.php create
 |   php tests/ai/narrator_test.php
 | No real API call is made: AI_MOCK=1, and the network-failure case points the
 | SDK at a closed local port.
 */
require __DIR__ . '/bootstrap.php';
$spec = require __DIR__ . '/fixture_spec.php';
$db = $CI->db;
putenv('AI_MOCK=1');
$CI->load->model('Ai_model');
$CI->load->model('Ai_metrics_model');
$CI->load->library('Ai/Ai_narrator', null, 'Ai_narrator');
$CI->load->library('Ai/Ai_report', null, 'Ai_report');
$E = $CI->Ai_metrics_model;
$N = $CI->Ai_narrator;
$R = $CI->Ai_report;

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
$settings = $CI->Ai_model->getSettings($company_id);
$db->update('tbl_ai_settings', array('enabled' => 1, 'monthly_budget_usd' => 5), array('company_id' => $company_id));
$settings = $CI->Ai_model->getSettings($company_id);
$today = $spec['today'];
$m = $E->build($company_id, $kitchen, $today, $settings);
$payload = $E->aiPayload($m);
$template = $N->template($m);

echo "--- the template only uses numbers the AI also gets\n";
foreach (array('kitchen' => array($company_id, $kitchen), 'warehouse' => array($company_id, $warehouse)) as $label => $o) {
    $mm = $E->build($o[0], $o[1], $today, $settings);
    $notes = array();
    $N->validate($N->template($mm), $E->aiPayload($mm), $N->template($mm), $notes);
    ok($notes === array(), "$label template passes the number check" . ($notes ? ': ' . implode(' | ', $notes) : ''));
}
foreach ($db->query("SELECT id, company_id FROM tbl_outlets WHERE del_status = 'Live' AND company_id <> ?", array($company_id))->result() as $o) {
    $mm = $E->build((int) $o->company_id, (int) $o->id, $E->companyToday($o->company_id), $CI->Ai_model->getSettings($o->company_id));
    $notes = array();
    $N->validate($N->template($mm), $E->aiPayload($mm), $N->template($mm), $notes);
    ok($notes === array(), "company {$o->company_id} outlet {$o->id} template passes the number check" . ($notes ? ': ' . implode(' | ', $notes) : ''));
}
ok(count($template['issues']) == count($m['top5']) && $template['issues'][0]['id'] == $m['top5'][0]['id'], 'template has one text per top-5 issue, same order');
ok(strpos($template['issues'][0]['explanation'], 'FX Cheese Burger') !== false, 'template explanation names the item');

echo "--- validation of an AI answer\n";
$answer = $template;
$answer['summary'] = 'Sales were AED 99,999 last month, a great result.';
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok($out['summary'] === $template['summary'], 'a made-up number replaces the summary with the template');
ok(isset($notes[0]) && strpos($notes[0], '99999') !== false, 'the note names the made-up number');

$answer = $template;
$answer['issues'][0]['explanation'] = 'The burger is under-priced: raising it could add about AED 4,224 a month.';
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok($out['issues'][0]['explanation'] === $answer['issues'][0]['explanation'] && !$notes, 'a money amount rounded to a whole number is accepted (4224.49 -> 4,224)');

$answer = $template;
$answer['headline'] = '<b>Strong</b> day <script>alert(1)</script> on 2026-10-31.';
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok(strpos($out['headline'], '<') === false && strpos($out['headline'], 'Strong day') === 0, 'HTML tags are stripped: "' . $out['headline'] . '"');
ok(!$notes, 'a date (2026-10-31) is not read as an amount');

$answer = $template;
array_shift($answer['issues']);
$answer['issues'][] = array('id' => 'made_up:1', 'explanation' => 'x', 'action' => 'y');
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok($out['issues'][0] === $template['issues'][0], 'a missing issue gets the template text');
ok(count($out['issues']) == count($template['issues']) && !in_array('made_up:1', array_column($out['issues'], 'id')), 'an issue id that is not in the data is dropped');
ok(count(preg_grep('/missing/', $notes)) == 1 && count(preg_grep('/dropped/', $notes)) == 1, 'both are noted');

$answer = $template;
$answer['sections']['menu'] = str_repeat('Biryani sells well. ', 200);
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok(mb_strlen($out['sections']['menu']) <= 900 && mb_substr($out['sections']['menu'], -1) === '…', 'long text is cut at the limit');

$answer = array('headline' => 7, 'issues' => 'nope');
$notes = array();
$out = $N->validate($answer, $payload, $template, $notes);
ok($out['headline'] === $template['headline'] && $out['sections'] === $template['sections'], 'wrong types fall back to the template');

$schema = $N->schema();
ok($schema['additionalProperties'] === false && $schema['properties']['sections']['required'] === Ai_narrator::SECTIONS, 'schema: closed objects, every section required');
ok(strpos(json_encode($payload), 'sk-ant') === false, 'no key in what the model gets');

echo "--- narrate() and the report row\n";
$r = $N->narrate($m, $settings, array('company_id' => $company_id, 'outlet_id' => $kitchen));
ok($r['status'] == 'ok' && $r['narrative']['source'] == 'ai' && !$r['notes'], 'mock call: status ok, AI text accepted');

$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));
$x = $R->runOutlet($company_id, $kitchen, $today);
$row = $R->getSnapshot($kitchen, $today);
ok($x['action'] == 'done' && $row && $row->narrative_status == 'ok' && (int) $row->attempts == 1, 'first run: report saved, status ok, 1 attempt');
ok(json_decode($row->metrics_json, true)['meta']['outlet_id'] == $kitchen && json_decode($row->narrative_json, true)['headline'] != '', 'the row keeps the numbers and the text');
$usage_before = (int) $db->query("SELECT COUNT(*) AS c FROM tbl_ai_usage WHERE company_id = ?", array($company_id))->row('c');
$x = $R->runOutlet($company_id, $kitchen, $today);
ok($x['action'] == 'skipped', 'second run the same day: skipped');
ok((int) $db->query("SELECT COUNT(*) AS c FROM tbl_ai_usage WHERE company_id = ?", array($company_id))->row('c') == $usage_before, 'skipped run makes no API call');
$x = $R->runOutlet($company_id, $kitchen, $today, true);
ok($x['action'] == 'done' && (int) $db->query("SELECT COUNT(*) AS c FROM tbl_ai_snapshots WHERE outlet_id = ? AND report_date = ?", array($kitchen, $today))->row('c') == 1,
    'forced run updates the same row (still 1 row)');

// a run killed after the numbers were saved: status pending -> the next run finishes it
$db->update('tbl_ai_snapshots', array('narrative_status' => 'pending', 'narrative_json' => null, 'attempts' => 0), array('outlet_id' => $kitchen, 'report_date' => $today));
$x = $R->runOutlet($company_id, $kitchen, $today);
ok($x['action'] == 'done' && $R->getSnapshot($kitchen, $today)->narrative_status == 'ok', 'a report left "pending" by a killed run is finished by the next run');

echo "--- refusals before the call\n";
$db->update('tbl_ai_settings', array('enabled' => 0), array('company_id' => $company_id));
$r = $N->narrate($m, $CI->Ai_model->getSettings($company_id), array('company_id' => $company_id, 'outlet_id' => $kitchen));
ok($r['status'] == 'template' && $r['narrative']['source'] == 'template' && $r['notes'] == array('call: disabled'), 'company switched off: template text, no retry');
$db->update('tbl_ai_settings', array('enabled' => 1, 'monthly_budget_usd' => 0), array('company_id' => $company_id));
$r = $N->narrate($m, $CI->Ai_model->getSettings($company_id), array('company_id' => $company_id, 'outlet_id' => $kitchen));
ok($r['status'] == 'template' && $r['notes'] == array('call: budget'), 'monthly limit reached: template text, no retry');
$db->update('tbl_ai_settings', array('monthly_budget_usd' => 5), array('company_id' => $company_id));

echo "--- network failure: retried on the next runs, then the template\n";
putenv('AI_MOCK=0');
putenv('ANTHROPIC_BASE_URL=http://127.0.0.1:9');
$db->query("DELETE FROM tbl_ai_snapshots WHERE outlet_id = ?", array($warehouse));
$statuses = array();
for ($i = 1; $i <= 4; $i++) {
    $x = $R->runOutlet($company_id, $warehouse, $today);
    $row = $R->getSnapshot($warehouse, $today);
    $statuses[] = $x['action'] . ':' . $row->narrative_status . ':' . $row->attempts;
}
ok($statuses == array('done:failed:1', 'done:failed:2', 'done:template:3', 'skipped:template:3'), 'attempts: ' . implode(', ', $statuses));
ok(strpos($R->getSnapshot($warehouse, $today)->error, 'gave up after 3 attempts') !== false, 'the row says it gave up');
ok(json_decode($R->getSnapshot($warehouse, $today)->narrative_json, true)['source'] == 'template', 'the page still gets the template text');
$err = $db->query("SELECT status, error FROM tbl_ai_usage WHERE company_id = ? AND status = 'error' ORDER BY id DESC LIMIT 1", array($company_id))->row();
ok($err && strpos($err->error, 'connection') === 0 && strpos($err->error, 'sk-ant') === false, 'the failed call is logged without the key: ' . ($err ? $err->error : '-'));
putenv('ANTHROPIC_BASE_URL');

$db->query("DELETE FROM tbl_ai_snapshots WHERE company_id = ?", array($company_id));
$db->query("DELETE FROM tbl_ai_usage WHERE company_id = ?", array($company_id));
echo PHP_EOL . "$pass passed, $fail failed" . PHP_EOL;
exit($fail ? 1 : 0);
