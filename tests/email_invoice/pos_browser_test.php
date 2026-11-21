<?php
/*
 | POS "Email bill" in a real headless Chrome (docs/email_invoice.md). Local development only.
 |   php tests/email_invoice/pos_browser_test.php [base_url]    (default http://vermont-resto-2.test/)
 | Needs Google Chrome and Node 22+. Signs in as user 1 with its POS PIN (never printed),
 | points company 1's email settings at tests/email_invoice/fake_smtp.py, rings up two bills
 | on the POS (pos_browser.mjs) and checks the database and the captured emails.
 | Everything is put back at the end: test sales, queue rows, settings, customer email, session.
 */
require __DIR__ . '/../ai/bootstrap.php';
$db = $CI->db;
$CI->load->library('Invoice_mailer');
$CI->load->library('custom');
$M = $CI->invoice_mailer;
$base = isset($argv[1]) ? rtrim($argv[1], '/') . '/' : 'http://vermont-resto-2.test/';

$pass = $fail = 0;
function ok($cond, $label) {
    global $pass, $fail;
    $cond ? $pass++ : $fail++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
}
if (!$M->tablesReady()) {
    exit("Run Update/email_invoice_migration.sql first\n");
}

$company_id = 1;
$port = 2527;
$out = sys_get_temp_dir() . '/ei_pos_smtp_' . getmypid();
$shots = sys_get_temp_dir() . '/ei_pos_shots';
@mkdir($shots);
$proc = proc_open(array('python3', __DIR__ . '/fake_smtp.py', (string) $port, $out), array(0 => array('file', '/dev/null', 'r'), 1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w')), $pipes);
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
    usleep(100000);
}

$saved_settings = $db->get_where('tbl_email_settings', array('company_id' => $company_id))->row_array();
$queue_max = (int) $db->query("SELECT COALESCE(MAX(id),0) AS m FROM tbl_email_queue")->row('m');
$sale_max = (int) $db->query("SELECT COALESCE(MAX(id),0) AS m FROM tbl_sales")->row('m');
$counter_no = (int) $db->query("SELECT last_no FROM tbl_sale_no_counters WHERE company_id = ?", array($company_id))->row('last_no');
$kitchen_max = (int) $db->query("SELECT COALESCE(MAX(id),0) AS m FROM tbl_kitchen_sales")->row('m');
$running_max = $db->table_exists('tbl_running_orders') ? (int) $db->query("SELECT COALESCE(MAX(id),0) AS m FROM tbl_running_orders")->row('m') : PHP_INT_MAX;
$customer = $db->query("SELECT id, email FROM tbl_customers WHERE company_id = ? AND id > 1 AND del_status = 'Live' ORDER BY id DESC LIMIT 1", array($company_id))->row();
$outlet_id = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = ? AND del_status = 'Live' ORDER BY id LIMIT 1", array($company_id))->row('id');
$sid = '';

try {
    $M->saveSettings($company_id, array('enabled' => 1, 'provider' => 'custom', 'host' => '127.0.0.1', 'port' => $port, 'encryption' => 'none',
        'username' => 'billing@test.local', 'password' => 'secret-pass', 'from_email' => '', 'from_name' => '', 'reply_to' => '',
        'bcc_email' => '', 'pos_auto_tick' => 1), 1);
    $db->update('tbl_customers', array('email' => 'saved-cust@example.com'), array('id' => $customer->id));

    // sign in with the PIN of user 1, enter the outlet
    $jar = tempnam(sys_get_temp_dir(), 'eijar');
    $call = function ($path, $post = null) use ($base, $jar) {
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_TIMEOUT => 60));
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        curl_exec($ch);
        curl_close($ch);
    };
    $call('Authentication/index');
    $call('Authentication/loginCheck', array('submit' => 'submit', 'active_login_button_hidden' => '0',
        'login_pin' => (string) $db->query("SELECT login_pin FROM tbl_users WHERE id = 1")->row('login_pin')));
    $call('Outlet/setOutletSession/' . $CI->custom->encrypt_decrypt($outlet_id, 'encrypt'));
    if (preg_match('/\tsess\t([0-9a-f]+)/', (string) file_get_contents($jar), $m)) {
        $sid = $m[1];
    }
    @unlink($jar);
    ok($sid !== '', 'signed in as user 1');

    echo "--- browser\n";
    $cmd = 'node ' . escapeshellarg(__DIR__ . '/pos_browser.mjs') . ' ' . implode(' ', array_map('escapeshellarg',
        array($base, $sid, '1', (string) $outlet_id, (string) $customer->id, $shots))) . ' 2>&1';
    $lines = array();
    exec($cmd, $lines);
    foreach ($lines as $line) {
        $j = json_decode($line, true);
        if (is_array($j) && isset($j['label'])) {
            ok($j['ok'], 'browser: ' . $j['label']);
        } elseif (isset($j['debug'])) {
            echo '  debug: ' . json_encode($j['debug']) . PHP_EOL;
        } elseif (!is_array($j)) {
            echo "  node: $line\n";
        }
    }

    echo "--- server\n";
    $sales = $db->query("SELECT id, sale_no, customer_id FROM tbl_sales WHERE id > ? ORDER BY id", array($sale_max))->result();
    ok(count($sales) === 2, 'two bills reached the server (' . count($sales) . ')');
    $q = $sales ? $db->query("SELECT * FROM tbl_email_queue WHERE id > ? ORDER BY id", array($queue_max))->result() : array();
    ok(count($q) === 1 && $sales && (int) $q[0]->sale_id === (int) $sales[0]->id && $q[0]->to_email === 'typed-cust@example.com',
        'one email queued, for bill 1, to the typed address');
    // the POS kicks process right after the push; give the cron-less path a moment
    for ($i = 0; $i < 20 && $q && $db->get_where('tbl_email_queue', array('id' => $q[0]->id))->row('status') !== 'sent'; $i++) {
        sleep(1);
    }
    ok($q && $db->get_where('tbl_email_queue', array('id' => $q[0]->id))->row('status') === 'sent', 'sent by the POS kick, without cron');
    $files = glob($out . '/*.eml');
    ok(count($files) === 1 && strpos(file_get_contents($files[0]), 'X-Rcpt: <typed-cust@example.com>') !== false, 'fake SMTP got exactly that email');
    ok($db->get_where('tbl_customers', array('id' => $customer->id))->row('email') === 'saved-cust@example.com', 'saved customer email not overwritten by the typed one');
    echo "  screenshots: $shots\n";
} finally {
    foreach ($db->query("SELECT id FROM tbl_sales WHERE id > ?", array($sale_max))->result() as $s) {
        foreach (array('tbl_sales_details' => 'sales_id', 'tbl_sales_details_modifiers' => 'sales_id', 'tbl_sale_consumptions' => 'sale_id',
                     'tbl_sale_consumptions_of_menus' => 'sales_id', 'tbl_sale_consumptions_of_modifiers_of_menus' => 'sales_id',
                     'tbl_sale_payments' => 'sale_id', 'tbl_orders_table' => 'sale_id') as $t => $col) {
            try { $db->delete($t, array($col => $s->id)); } catch (Throwable $e) { echo "cleanup $t: " . $e->getMessage() . PHP_EOL; }
        }
        $db->delete('tbl_sales', array('id' => $s->id));
    }
    // the POS may also leave kitchen / running-order rows for a bill
    foreach ($db->query("SELECT id FROM tbl_kitchen_sales WHERE id > ?", array($kitchen_max))->result() as $k) {
        try {
            $db->delete('tbl_kitchen_sales_details', array('sales_id' => $k->id));
            $db->delete('tbl_kitchen_sales_details_modifiers', array('sales_id' => $k->id));
            $db->delete('tbl_kitchen_sales', array('id' => $k->id));
        } catch (Throwable $e) {
            echo "cleanup kitchen: " . $e->getMessage() . PHP_EOL;
        }
    }
    try {
        $db->query("DELETE FROM tbl_running_orders WHERE id > ?", array($running_max));
    } catch (Throwable $e) {
    }
    // the POS reserved invoice numbers for the test bills: hand them back
    $db->update('tbl_sale_no_counters', array('last_no' => $counter_no), array('company_id' => $company_id));
    $db->query("DELETE FROM tbl_email_queue WHERE id > ?", array($queue_max));
    $db->delete('tbl_email_settings', array('company_id' => $company_id));
    if ($saved_settings) {
        $db->insert('tbl_email_settings', $saved_settings);
    }
    $db->update('tbl_customers', array('email' => $customer->email), array('id' => $customer->id));
    if ($sid) {
        $db->delete('tbl_sessions', array('id' => $sid));
    }
    $status = proc_get_status($proc);
    if ($status['running'] && function_exists('posix_kill')) {
        posix_kill($status['pid'], 15);
    }
    proc_close($proc);
    array_map('unlink', glob($out . '/*.eml'));
    @rmdir($out);
}
echo PHP_EOL . "$pass passed, $fail failed" . PHP_EOL;
exit($fail ? 1 : 0);
