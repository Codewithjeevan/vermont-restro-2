<?php
/*
 | Shared helpers of the AI Insight web tests (page_test.php, chat_test.php): copies of
 | an Admin session row as test logins, and a small HTTP client with a cookie jar per login.
 | Needs in the caller: $db, $base, $template_row (an Admin session row's data).
 | Every session made here carries the key ai_page_test, so a test deletes them with
 |   DELETE FROM tbl_sessions WHERE data LIKE '%ai_page_test%'
 */
/* CI database session driver, PHP "key|serialized" format */
function sess_decode($data) {
    $out = array();
    $p = 0;
    while ($p < strlen($data)) {
        $bar = strpos($data, '|', $p);
        $key = substr($data, $p, $bar - $p);
        $value = @unserialize(substr($data, $bar + 1));
        $len = strlen(serialize($value));
        if (substr($data, $bar + 1, $len) !== serialize($value)) {
            exit("Cannot read the session row near key $key\n");
        }
        $out[$key] = $value;
        $p = $bar + 1 + $len;
    }
    return $out;
}
function sess_encode($arr) {
    $s = '';
    foreach ($arr as $k => $v) {
        $s .= $k . '|' . serialize($v);
    }
    return $s;
}
$host = parse_url($base, PHP_URL_HOST);
$jars = array();
function make_session($name, $changes) {
    global $db, $template_row, $host, $jars;
    $s = array_merge(sess_decode($template_row), $changes, array('ai_page_test' => 1));
    unset($s['__ci_vars'], $s['exception'], $s['exception_er'], $s['exception_2'], $s['ai_csrf']);
    $id = bin2hex(random_bytes(16));
    $db->insert('tbl_sessions', array('id' => $id, 'ip_address' => '127.0.0.1', 'timestamp' => time(), 'data' => sess_encode($s)));
    $jar = tempnam(sys_get_temp_dir(), 'aijar');
    file_put_contents($jar, "$host\tFALSE\t/\tFALSE\t0\tsess\t$id\n");
    $jars[$name] = $jar;
}
function http($who, $method, $path, $post = null, $headers = array()) {
    global $base, $jars;
    $ch = curl_init(strpos($path, 'http') === 0 ? $path : $base . $path);
    curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => $jars[$who], CURLOPT_COOKIEJAR => $jars[$who], CURLOPT_TIMEOUT => 120, CURLOPT_HTTPHEADER => $headers));
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $post));
    }
    $resp = (string) curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $head = substr($resp, 0, $size);
    preg_match('/^Location:\s*(\S+)/mi', $head, $loc);
    return array('code' => $code, 'location' => isset($loc[1]) ? $loc[1] : '', 'body' => substr($resp, $size));
}
/** POST, then follow the redirect: returns the page that shows the flash message */
function post_follow($who, $path, $post, $headers = array()) {
    $r = http($who, 'POST', $path, $post, $headers);
    return $r['location'] ? http($who, 'GET', $r['location']) + array('first' => $r) : $r + array('first' => $r);
}
function token($body) {
    return preg_match('/name="ai_csrf" value="([0-9a-f]{64})"/', $body, $t) ? $t[1] : '';
}
function redirect_to($r, $where) {
    return in_array($r['code'], array(302, 303, 307)) && strpos($r['location'], $where) !== false;
}
