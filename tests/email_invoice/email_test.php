<?php
/*
 | Email Invoice test (docs/email_invoice.md). Local development only.
 |   php tests/email_invoice/email_test.php [base_url]     (default http://vermont-resto-2.test/)
 | Starts tests/email_invoice/fake_smtp.py, points company 1's email settings at it and
 | puts everything back at the end (settings row, queue rows, test sales, customer emails,
 | test sessions). Needs Update/email_invoice_migration.sql and one earlier browser login
 | as Admin on this database (its session row is copied, like tests/ai).
 */
require __DIR__ . '/../ai/bootstrap.php';
$db = $CI->db;
$CI->load->library('Invoice_mailer');
$M = $CI->invoice_mailer;
$base = isset($argv[1]) ? rtrim($argv[1], '/') . '/' : 'http://vermont-resto-2.test/';

$pass = $fail = 0;
function is_redirect($r) {
    return $r['code'] >= 301 && $r['code'] <= 308;
}
function ok($cond, $label) {
    global $pass, $fail;
    $cond ? $pass++ : $fail++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
}

if (!$M->tablesReady()) {
    exit("Run Update/email_invoice_migration.sql first\n");
}
$company_id = 1;
$template_row = $db->query("SELECT data FROM tbl_sessions WHERE data LIKE '%role|s:5:\"Admin\"%' AND data LIKE '%function_access%' ORDER BY timestamp DESC LIMIT 1")->row('data');
$own_login = '';
if (!$template_row) {
    // no browser login left: sign in as user 1 with its POS PIN on the local site (never printed),
    // enter outlet 1, and use that session as the template; it is deleted at the end
    $CI->load->library('custom');
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
    $first_outlet = (int) $db->query("SELECT id FROM tbl_outlets WHERE company_id = 1 AND del_status = 'Live' ORDER BY id LIMIT 1")->row('id');
    $call('Outlet/setOutletSession/' . $CI->custom->encrypt_decrypt($first_outlet, 'encrypt'));
    if (preg_match('/\tsess\t([0-9a-f]+)/', (string) file_get_contents($jar), $m)) {
        $own_login = $m[1];
        $template_row = $db->get_where('tbl_sessions', array('id' => $own_login))->row('data');
    }
    @unlink($jar);
    if (!$template_row || strpos($template_row, 'role|s:5:"Admin"') === false) {
        if ($own_login) {
            $db->delete('tbl_sessions', array('id' => $own_login));
        }
        exit("Could not log in as Admin: log in once in the browser, then run again.\n");
    }
}

// ---------------------------------------------------------------- fake SMTP
$port = 2526;
$closed_port = 2599;
$out = sys_get_temp_dir() . '/ei_smtp_' . getmypid();
$proc = proc_open(array('python3', __DIR__ . '/fake_smtp.py', (string) $port, $out), array(0 => array('file', '/dev/null', 'r'), 1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w')), $pipes);
for ($i = 0; $i < 50; $i++) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($fp) {
        fclose($fp);
        break;
    }
    usleep(100000);
}
function mails() {
    global $out;
    $files = glob($out . '/*.eml');
    natsort($files);
    return array_values($files);
}
/** headers + every decoded text part of a captured message */
function read_mail($file) {
    $raw = file_get_contents($file);
    $decoded = $raw;
    if (preg_match_all('/Content-Transfer-Encoding: base64\s*\n\n([A-Za-z0-9+\/=\n]+)/', str_replace("\r", '', $raw), $m)) {
        foreach ($m[1] as $b64) {
            $decoded .= "\n" . base64_decode(str_replace("\n", '', $b64));
        }
    }
    if (preg_match('/^Subject: (.*(?:\n[ \t].*)*)/m', $raw, $s)) {
        $decoded .= "\nDECODED-SUBJECT: " . iconv_mime_decode(str_replace("\n", '', $s[1]), 0, 'UTF-8');
    }
    return $decoded;
}

// ---------------------------------------------------------------- state to restore
$saved_settings = $db->get_where('tbl_email_settings', array('company_id' => $company_id))->row_array();
$queue_max = (int) $db->query("SELECT COALESCE(MAX(id),0) AS m FROM tbl_email_queue")->row('m');
$customer_emails = array();
foreach ($db->query("SELECT id, email FROM tbl_customers WHERE company_id = ?", array($company_id))->result() as $c) {
    $customer_emails[(int) $c->id] = $c->email;
}
$test_sales = array();
$guest = 'guest' . getmypid() . '@example.com';
$good = array('enabled' => 1, 'provider' => 'custom', 'host' => '127.0.0.1', 'port' => $port, 'encryption' => 'none',
    'username' => 'billing@test.local', 'password' => 'secret-pass', 'from_email' => '', 'from_name' => '',
    'reply_to' => '', 'bcc_email' => '', 'pos_auto_tick' => 1);

function sale_cleanup($sale_id) {
    global $db;
    foreach (array('tbl_sales_details' => 'sales_id', 'tbl_sales_details_modifiers' => 'sales_id', 'tbl_sale_consumptions' => 'sale_id',
                 'tbl_sale_consumptions_of_menus' => 'sales_id', 'tbl_sale_consumptions_of_modifiers_of_menus' => 'sales_id',
                 'tbl_sale_payments' => 'sale_id', 'tbl_orders_table' => 'sale_id', 'tbl_email_queue' => 'sale_id') as $t => $col) {
        $db->delete($t, array($col => $sale_id));
    }
    $db->delete('tbl_sales', array('id' => $sale_id));
}

try {
    echo "--- helpers\n";
    ok($M->validEmail($guest), 'valid address accepted');
    ok(!$M->validEmail('guest@example'), 'address without domain refused');
    ok(!$M->validEmail("guest@example.com\r\nBcc: x@evil.test"), 'header injection refused');
    ok(!$M->validEmail('a@example.com,b@example.com'), 'address list refused');
    ok(!$M->validEmail(''), 'empty refused');
    $enc = $M->encryptSecret('p@ss w0rd!');
    ok(strpos($enc, 'enc1:') === 0 && $M->decryptSecret($enc) === 'p@ss w0rd!', 'password encrypt / decrypt round trip');
    ok($M->decryptSecret('enc1:bm90IHZhbGlk') === null, 'damaged password reads as null');
    $company = $db->get_where('tbl_companies', array('id' => $company_id))->row();
    ok($M->money($company, 1234.5) === 'AED 1,234.50', 'money uses company currency + separators: ' . $M->money($company, 1234.5));

    $sale = $db->query("SELECT id, sale_no, customer_id FROM tbl_sales WHERE company_id = ? AND del_status = 'Live' AND order_status = 3 ORDER BY id DESC LIMIT 1", array($company_id))->row();
    $foreign_company = (int) $db->query("SELECT id FROM tbl_companies WHERE id <> ? ORDER BY id LIMIT 1", array($company_id))->row('id');

    echo "--- switched off\n";
    $M->saveSettings($company_id, array_merge($good, array('enabled' => 0)), 1);
    $r = $M->queueSale($sale->id, $company_id, $guest, 'pos', 1);
    ok(!$r['ok'] && $r['msg'] === lang('ei_not_enabled'), 'off: nothing queued');

    echo "--- queue\n";
    $M->saveSettings($company_id, $good, 1);
    $row = $db->get_where('tbl_email_settings', array('company_id' => $company_id))->row();
    ok($M->isEnabled($company_id), 'on: isEnabled');
    ok(strpos($row->password, 'secret-pass') === false && strpos($row->password, 'enc1:') === 0, 'password stored encrypted');
    $r1 = $M->queueSale($sale->id, $company_id, $guest, 'pos', 1);
    $r2 = $M->queueSale($sale->id, $company_id, strtoupper($guest), 'pos', 1);
    ok($r1['ok'] && $r1['queue_id'] > 0, 'queued');
    ok($r2['ok'] && $r2['queue_id'] === $r1['queue_id'], 'same sale + address from the POS again: not queued twice');
    $r = $M->queueSale($sale->id, $foreign_company, $guest, 'pos', 1);
    ok(!$r['ok'], 'sale of another company refused');
    $r = $M->queueSale($sale->id, $company_id, 'not-an-email', 'pos', 1);
    ok(!$r['ok'] && $r['msg'] === lang('ei_invalid_email'), 'bad address refused');

    echo "--- send\n";
    $before = count(mails());
    $st = $M->process($company_id, 5, 20);
    $q = $db->get_where('tbl_email_queue', array('id' => $r1['queue_id']))->row();
    ok($st['sent'] >= 1 && $q->status === 'sent' && $q->sent_at && (int) $q->attempts === 1, 'process: sent (' . json_encode($st) . ')');
    $files = mails();
    ok(count($files) === $before + 1, 'fake SMTP got one message');
    $mail = $files ? read_mail(end($files)) : '';
    ok(strpos($mail, 'X-Rcpt: <' . $guest . '>') !== false, 'to the customer');
    ok(strpos($mail, 'DECODED-SUBJECT: Your bill ' . $sale->sale_no) !== false, 'subject has the bill number');
    ok(strpos($mail, 'TAX INVOICE') !== false && strpos($mail, $sale->sale_no) !== false, 'body: tax invoice with bill number');
    ok(strpos($mail, 'invoice/') !== false, 'body: view online link');
    ok(strpos($mail, 'Content-Type: text/plain') !== false && strpos($mail, 'Content-Type: text/html') !== false, 'html + plain text parts');
    ok(stripos($mail, 'X-Mailer: PHPMailer') === false, 'no PHPMailer banner');

    echo "--- one worker per email\n";
    $db->insert('tbl_email_queue', array('company_id' => $company_id, 'sale_id' => $sale->id, 'to_email' => 'race@example.com', 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s')));
    $race_id = $db->insert_id();
    $claim = new ReflectionMethod('Invoice_mailer', 'claim');
    $claim->setAccessible(true);
    $a = $claim->invoke($M, $race_id);
    $b = $claim->invoke($M, $race_id);
    ok($a && $a->status === 'sending' && $b === null, 'second claim of the same row gets nothing');
    $db->update('tbl_email_queue', array('locked_at' => date('Y-m-d H:i:s', time() - 1200)), array('id' => $race_id));
    $M->process($company_id, 5, 20);
    ok($db->get_where('tbl_email_queue', array('id' => $race_id))->row('status') === 'sent', 'stale "sending" lock released and sent');

    echo "--- wrong password\n";
    $M->saveSettings($company_id, array_merge($good, array('password' => 'wrong')), 1);
    $r = $M->queueSale($sale->id, $company_id, $guest, 'resend', 1, true);
    ok($r['ok'] && $r['queue_id'] !== $r1['queue_id'], 'resend makes a new row');
    $M->process($company_id, 5, 20);
    $q = $db->get_where('tbl_email_queue', array('id' => $r['queue_id']))->row();
    ok($q->status === 'failed' && (int) $q->attempts === 1 && stripos($q->last_error, 'authenticate') !== false, 'refused login: failed at once, no pointless retries (' . $q->last_error . ')');
    ok(strpos((string) $q->last_error, 'wrong') === false, 'error text does not contain the password');

    echo "--- server down: retries\n";
    $M->saveSettings($company_id, array_merge($good, array('port' => $closed_port)), 1);
    ok($M->retry($q->id, $company_id), 'Retry puts a failed email back');
    $M->process($company_id, 5, 20);
    $q = $db->get_where('tbl_email_queue', array('id' => $q->id))->row();
    ok($q->status === 'pending' && (int) $q->attempts === 1 && strtotime($q->next_attempt_at) > time() && $q->last_error, 'no connection: waits for a retry');
    $M->process($company_id, 5, 20);
    ok((int) $db->get_where('tbl_email_queue', array('id' => $q->id))->row('attempts') === 1, 'not tried again before its retry time');
    for ($i = 0; $i < 2; $i++) {
        $db->update('tbl_email_queue', array('next_attempt_at' => date('Y-m-d H:i:s', time() - 5)), array('id' => $q->id));
        $M->process($company_id, 5, 20);
    }
    $q = $db->get_where('tbl_email_queue', array('id' => $q->id))->row();
    ok($q->status === 'failed' && (int) $q->attempts === 3, 'failed after 3 tries');
    $M->saveSettings($company_id, $good, 1);
    $M->retry($q->id, $company_id);
    $M->process($company_id, 5, 20);
    ok($db->get_where('tbl_email_queue', array('id' => $q->id))->row('status') === 'sent', 'Retry after the fix: sent');

    echo "--- limits\n";
    $n = (int) $db->where('sale_id', $sale->id)->count_all_results('tbl_email_queue');
    for ($i = $n; $i < Invoice_mailer::MAX_PER_SALE; $i++) {
        $db->insert('tbl_email_queue', array('company_id' => $company_id, 'sale_id' => $sale->id, 'to_email' => "x$i@example.com", 'status' => 'sent', 'created_at' => date('Y-m-d H:i:s')));
    }
    $r = $M->queueSale($sale->id, $company_id, 'one-more@example.com', 'resend', 1, true);
    ok(!$r['ok'] && $r['msg'] === lang('ei_too_many'), 'a bill cannot be emailed more than ' . Invoice_mailer::MAX_PER_SALE . ' times');
    $db->query("DELETE FROM tbl_email_queue WHERE id > ? AND to_email LIKE 'x%@example.com'", array($queue_max));

    echo "--- template\n";
    $errors = array();
    set_error_handler(function ($no, $str, $file, $line) use (&$errors) {
        if (strpos(str_replace('\\', '/', $file), '/system/') === false) {
            $errors[] = basename($file) . ":$line $str";
        }
        return true;
    });
    $all = $db->query("SELECT id FROM tbl_sales WHERE company_id = ? AND del_status = 'Live' ORDER BY id DESC LIMIT 15", array($company_id))->result();
    $built = 0;
    foreach ($all as $s) {
        if ($M->buildInvoiceEmail($s->id, $company_id)) {
            $built++;
        }
    }
    restore_error_handler();
    ok($built === count($all) && !$errors, "template renders $built bills with no PHP notice" . ($errors ? ': ' . implode(' | ', array_slice(array_unique($errors), 0, 3)) : ''));
    ok($M->buildInvoiceEmail($sale->id, $foreign_company) === null, 'bill of another company not built');

    // ------------------------------------------------------------ web
    require __DIR__ . '/../ai/web.php';
    $outlet = $db->query("SELECT id, outlet_name FROM tbl_outlets WHERE company_id = ? AND del_status = 'Live' ORDER BY id LIMIT 1", array($company_id))->row();
    $admin = array('company_id' => (string) $company_id, 'outlet_id' => (string) $outlet->id, 'outlet_name' => $outlet->outlet_name, 'role' => 'Admin');
    // this install uses 40-character session ids (tests/ai makes 32): give each copy a valid one
    $login = function ($name, $changes) use ($db) {
        global $jars, $host;
        make_session($name, $changes);
        $old = trim(substr(strrchr(trim(file_get_contents($jars[$name])), "\t"), 1));
        $new = bin2hex(random_bytes(20));
        $db->update('tbl_sessions', array('id' => $new), array('id' => $old));
        file_put_contents($jars[$name], "$host\tFALSE\t/\tFALSE\t0\tsess\t$new\n");
    };
    $login('admin', $admin);
    $login('limited', array_merge($admin, array('role' => 'Manager', 'function_access' => array('view-123'))));
    $login('sender', array_merge($admin, array('role' => 'Manager', 'function_access' => array('view-123', 'send-443'))));
    $token = function ($body) {
        return preg_match('/name="ai_csrf" value="([0-9a-f]{64})"/', $body, $m) || preg_match('/id="ei_csrf" value="([0-9a-f]{64})"/', $body, $m) ? $m[1] : '';
    };
    $clean = function ($body) {
        return strpos($body, 'A PHP Error was encountered') === false && strpos($body, 'Fatal error') === false;
    };

    echo "--- settings page\n";
    $r = http('admin', 'GET', 'Email_invoice/settings');
    $tok = $token($r['body']);
    ok($r['code'] == 200 && strpos($r['body'], 'id="ei_form"') !== false && $tok !== '' && $clean($r['body']), 'settings page renders with a form token');
    ok(strpos($r['body'], 'secret-pass') === false && strpos($r['body'], 'enc1:') === false, 'password never sent to the browser');
    $form = array('enabled' => '1', 'provider' => 'custom', 'host' => '127.0.0.1', 'port' => (string) $port, 'encryption' => 'none',
        'username' => 'billing@test.local', 'password' => '', 'from_email' => '', 'from_name' => 'Vermont Test', 'reply_to' => '',
        'bcc_email' => '', 'pos_auto_tick' => '1');
    $r = http('admin', 'POST', 'Email_invoice/saveSettings', $form);
    ok(is_redirect($r) && $db->get_where('tbl_email_settings', array('company_id' => $company_id))->row('from_name') !== 'Vermont Test', 'save without token refused');
    $r = http('admin', 'POST', 'Email_invoice/saveSettings', $form + array('ai_csrf' => $tok));
    $row = $db->get_where('tbl_email_settings', array('company_id' => $company_id))->row();
    ok(is_redirect($r) && $row->from_name === 'Vermont Test' && $M->decryptSecret($row->password) === 'secret-pass', 'saved; empty password field kept the saved one');
    $r = http('admin', 'POST', 'Email_invoice/saveSettings', array_merge($form, array('port' => '99999', 'from_name' => 'Bad')) + array('ai_csrf' => $tok));
    ok($db->get_where('tbl_email_settings', array('company_id' => $company_id))->row('from_name') === 'Vermont Test', 'bad port refused, nothing saved');

    $before = count(mails());
    $r = http('admin', 'POST', 'Email_invoice/testEmail', $form + array('ai_csrf' => $tok, 'test_to' => 'owner@example.com'));
    $j = json_decode($r['body'], true);
    ok($j && $j['ok'] && count(mails()) === $before + 1, 'Send test email: ok' . ($j && !$j['ok'] ? ' - ' . $j['error'] : ''));
    $r = http('admin', 'POST', 'Email_invoice/testEmail', array_merge($form, array('password' => 'wrong')) + array('ai_csrf' => $tok, 'test_to' => 'owner@example.com'));
    $j = json_decode($r['body'], true);
    ok($j && !$j['ok'] && $j['hint'] === lang('ei_hint_auth'), 'test with a wrong password: error + plain hint');
    ok($db->get_where('tbl_email_settings', array('company_id' => $company_id))->row('last_test_ok') === '0', 'last test result kept');

    echo "--- sales list + send\n";
    $r = http('admin', 'POST', 'Sale/getAjaxData', array('draw' => 1, 'start' => 0, 'length' => 10));
    ok(strpos($r['body'], 'ei_email_invoice') !== false, 'sales list has the Email Invoice action');
    $r = http('admin', 'GET', 'Sale/sales');
    $tok_sales = $token($r['body']);
    ok($r['code'] == 200 && strpos($r['body'], 'id="ei_send_modal"') !== false && $tok_sales !== '', 'sales page has the send modal');
    $r = http('admin', 'GET', 'Email_invoice/saleInfo?sale_id=' . $sale->id);
    $j = json_decode($r['body'], true);
    ok($j && $j['ok'] && $j['sale_no'] === $sale->sale_no && $j['last'] && $j['last']['status'] === 'sent', 'saleInfo: last email shown');
    $before = count(mails());
    $r = http('sender', 'POST', 'Email_invoice/send', array('sale_id' => $sale->id, 'email' => 'again@example.com', 'ai_csrf' => $tok_sales));
    ok($r['code'] == 400, 'send with another login\'s token refused');
    $r = http('sender', 'GET', 'Sale/sales');
    $tok_sender = $token($r['body']);
    $r = http('sender', 'POST', 'Email_invoice/send', array('sale_id' => $sale->id, 'email' => 'again@example.com', 'ai_csrf' => $tok_sender));
    $j = json_decode($r['body'], true);
    ok($j && $j['ok'] && $j['status'] === 'sent' && count(mails()) === $before + 1, 'role with "send" permission: bill emailed at once');
    $r = http('sender', 'POST', 'Email_invoice/send', array('sale_id' => $sale->id, 'email' => "x@example.com\r\nBcc: y@evil.test", 'ai_csrf' => $tok_sender));
    $j = json_decode($r['body'], true);
    ok($j && !$j['ok'], 'injected address refused');

    echo "--- permissions\n";
    $r = http('limited', 'GET', 'Email_invoice/settings');
    ok(is_redirect($r) && strpos($r['location'], 'userProfile') !== false, 'no permission: settings page refused');
    $r = http('limited', 'POST', 'Email_invoice/send', array('sale_id' => $sale->id, 'email' => 'x@example.com'));
    ok($r['code'] == 403, 'no permission: send refused (403)');
    $r = http('limited', 'POST', 'Email_invoice/process');
    $j = json_decode($r['body'], true);
    ok($j && $j['ok'], 'any cashier may run process (only sends what is queued)');
    $r = http('sender', 'GET', 'Email_invoice/settings');
    ok(is_redirect($r), '"send" permission alone does not open settings');

    echo "--- log page\n";
    $r = http('admin', 'GET', 'Email_invoice/log');
    ok($r['code'] == 200 && strpos($r['body'], $sale->sale_no) !== false && strpos($r['body'], 'again@example.com') !== false && $clean($r['body']), 'log lists the emails');

    echo "--- POS\n";
    $user_id = (int) sess_decode($template_row)['user_id'];
    $r = http('admin', 'GET', 'Sale/POS/' . $user_id . '/' . $outlet->id);
    ok($r['code'] == 200 && strpos($r['body'], 'id="email_bill_box"') !== false && strpos($r['body'], 'pos_email_bill.js') !== false, 'POS payment screen has the Email bill box');
    $M->saveSettings($company_id, array_merge($good, array('enabled' => 0)), 1);
    $r = http('admin', 'GET', 'Sale/POS/' . $user_id . '/' . $outlet->id);
    ok($r['code'] == 200 && strpos($r['body'], 'id="email_bill_box"') === false && strpos($r['body'], 'id="finalize_order_button"') !== false, 'email off: POS renders without the box');
    $M->saveSettings($company_id, $good, 1);
    $r = http('admin', 'GET', 'Email_invoice/customerEmail?id=1');
    $j = json_decode($r['body'], true);
    ok($j && $j['ok'] && $j['email'] === '', 'walk-in customer: no email offered');

    echo "--- push_online\n";
    $src = $db->query("SELECT self_order_content, customer_id FROM tbl_sales WHERE company_id = ? AND self_order_content LIKE '{%' AND customer_id > 1 ORDER BY id DESC LIMIT 1", array($company_id))->row();
    $order = json_decode($src->self_order_content, true);
    $push = function ($changes) use ($order, &$test_sales, $db) {
        $o = array_merge($order, array('send_sms_status' => '', 'random_code' => substr(md5(uniqid()), 0, 15)), $changes);
        $r = http('admin', 'POST', 'Sale/push_online', array('orders' => json_encode($o), 'sales_id' => '777'));
        $id = (int) $db->query("SELECT id FROM tbl_sales WHERE sale_no = ?", array($o['sale_no']))->row('id');
        if ($id) {
            $test_sales[] = $id;
        }
        return array($r, $id);
    };
    $db->update('tbl_customers', array('email' => ''), array('id' => $src->customer_id));
    list($r, $id) = $push(array('sale_no' => 'EITEST-' . time() . '-1', 'send_email_status' => 1, 'email_bill_to' => 'pos-guest@example.com'));
    ok($r['body'] === '777', 'push_online answer unchanged with Email bill ticked (' . substr($r['body'], 0, 60) . ')');
    $q = $db->get_where('tbl_email_queue', array('sale_id' => $id))->row();
    ok($id && $q && $q->to_email === 'pos-guest@example.com' && $q->source === 'pos', 'push_online queued the email');
    ok($db->get_where('tbl_customers', array('id' => $src->customer_id))->row('email') === 'pos-guest@example.com', 'address kept on the customer that had none');
    list($r2) = $push(array('sale_no' => $db->get_where('tbl_sales', array('id' => $id))->row('sale_no'), 'send_email_status' => 1, 'email_bill_to' => 'pos-guest@example.com'));
    ok($r2['body'] === '777' && (int) $db->where('sale_id', $id)->count_all_results('tbl_email_queue') === 1, 'same bill synced twice: one email');
    $before = count(mails());
    $r = http('admin', 'POST', 'Email_invoice/process');
    ok(count(mails()) === $before + 1 && $db->get_where('tbl_email_queue', array('id' => $q->id))->row('status') === 'sent', 'POS kick (process) sent it');

    list($r, $id) = $push(array('sale_no' => 'EITEST-' . time() . '-2', 'send_email_status' => 1, 'email_bill_to' => 'broken@'));
    ok($r['body'] === '777' && $id && !$db->get_where('tbl_email_queue', array('sale_id' => $id))->row(), 'bad address: sale saved, nothing queued');
    list($r, $id) = $push(array('sale_no' => 'EITEST-' . time() . '-3'));
    ok($r['body'] === '777' && $id && !$db->get_where('tbl_email_queue', array('sale_id' => $id))->row(), 'box not ticked: nothing queued');
    $M->saveSettings($company_id, array_merge($good, array('enabled' => 0)), 1);
    list($r, $id) = $push(array('sale_no' => 'EITEST-' . time() . '-4', 'send_email_status' => 1, 'email_bill_to' => 'pos-guest@example.com'));
    ok($r['body'] === '777' && $id && !$db->get_where('tbl_email_queue', array('sale_id' => $id))->row(), 'email switched off: sale saved, nothing queued');
    $db->query("RENAME TABLE tbl_email_queue TO tbl_email_queue_hidden");
    $M->saveSettings($company_id, $good, 1);
    list($r, $id) = $push(array('sale_no' => 'EITEST-' . time() . '-5', 'send_email_status' => 1, 'email_bill_to' => 'pos-guest@example.com'));
    $db->query("RENAME TABLE tbl_email_queue_hidden TO tbl_email_queue");
    ok($r['body'] === '777' && $id, 'queue table missing: the sale still saves');
} catch (Throwable $e) {
    ok(false, 'test stopped: ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine());
} finally {
    // every step on its own: PHP 8.1+ mysqli throws on a SQL error, one failure must not skip the rest
    $steps = array(
        function () use ($db) {
            if ($db->table_exists('tbl_email_queue_hidden')) {
                $db->query("RENAME TABLE tbl_email_queue_hidden TO tbl_email_queue");
            }
        },
        function () use (&$test_sales) {
            foreach ($test_sales as $sid) {
                sale_cleanup($sid);
            }
        },
        function () use ($db, $queue_max) {
            $db->query("DELETE FROM tbl_email_queue WHERE id > ?", array($queue_max));
        },
        function () use ($db, $company_id, $saved_settings) {
            $db->delete('tbl_email_settings', array('company_id' => $company_id));
            if ($saved_settings) {
                $db->insert('tbl_email_settings', $saved_settings);
            }
        },
        function () use ($db, $customer_emails) {
            foreach ($customer_emails as $cid => $email) {
                $db->update('tbl_customers', array('email' => $email), array('id' => $cid));
            }
        },
        function () use ($db, $own_login) {
            $db->query("DELETE FROM tbl_sessions WHERE data LIKE '%ai_page_test%'");
            if ($own_login) {
                $db->delete('tbl_sessions', array('id' => $own_login));
            }
        },
        function () use ($proc) {
            $status = proc_get_status($proc);
            if ($status['running'] && function_exists('posix_kill')) {
                posix_kill($status['pid'], 15);
            }
            proc_terminate($proc);
            proc_close($proc);
        },
        function () use ($out) {
            array_map('unlink', glob($out . '/*.eml'));
            @rmdir($out);
        },
    );
    foreach ($steps as $i => $step) {
        try {
            $step();
        } catch (Throwable $e) {
            echo "cleanup step $i: " . $e->getMessage() . PHP_EOL;
        }
    }
}
echo PHP_EOL . "$pass passed, $fail failed" . PHP_EOL;
exit($fail ? 1 : 0);
