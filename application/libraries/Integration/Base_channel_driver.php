<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/Integration/Channel_driver_interface.php';
require_once APPPATH.'libraries/Integration/Integration_crypto.php';

/**
 * The boring 80% every driver would otherwise repeat: signed HTTP with a
 * timeout, OAuth2 client-credentials token fetch + cache + refresh, JSON
 * decoding, HMAC helpers, and a log() that writes every call to
 * tbl_integration_logs.
 *
 * A concrete driver on top of this is ~200-300 lines: auth, payload mapping,
 * status mapping. That is the whole cost of app #2.
 */
abstract class Base_channel_driver implements Channel_driver_interface
{
    /** @var CI_Controller */
    protected $CI;

    /** seconds - an aggregator that is slow must never hold a POS request */
    protected $timeout = 10;

    public function __construct()
    {
        $this->CI = & get_instance();
    }

    /* =================================================================
     * Defaults for the optional capabilities. A driver that declares the
     * capability overrides these; one that does not gets an honest "no".
     * ================================================================= */

    public function push_menu($outlet_id, $config)
    {
        return $this->unsupported('menu_push');
    }

    public function set_store_status($external_store_id, $is_open, $config)
    {
        return $this->unsupported('store_status');
    }

    public function set_item_availability($external_item_id, $is_available, $config)
    {
        return $this->unsupported('item_availability');
    }

    /**
     * Thin-webhook support. Most providers (Talabat) push the full order in
     * the webhook; some push only an id and expect a GET for the details.
     * Default says "the webhook body already carries the order" - payload NULL
     * means "use the webhook body", so every existing driver behaves
     * byte-identically. A thin-webhook driver overrides this with the GET.
     *
     * @return array ['ok'=>bool, 'payload'=>array|null, 'error'=>string]
     */
    public function fetch_order($external_id, $config)
    {
        return array('ok' => true, 'payload' => null, 'error' => '');
    }

    protected function unsupported($what)
    {
        return array('ok' => false, 'http_status' => 0, 'error' => $this->code().' does not support '.$what, 'response' => null);
    }

    /* =================================================================
     * Credentials
     * ================================================================= */

    /**
     * Decrypted credentials JSON for this config.
     *
     * Two layers: the company-level blob (tbl_integration_company_credentials,
     * attached by Integration_manager::hydrate_config()) is the base, and the
     * outlet row's own blob overrides it key-by-key. A provider with no
     * company row (Talabat) reads the outlet blob alone, exactly as before.
     *
     * @return array
     */
    protected function credentials($config)
    {
        $merged = array();
        if (isset($config->company_credentials) && $config->company_credentials
            && !empty($config->company_credentials->credentials)) {
            $merged = Integration_crypto::decrypt_json($config->company_credentials->credentials);
        }
        $outlet = Integration_crypto::decrypt_json(isset($config->credentials) ? $config->credentials : '');
        foreach ($outlet as $key => $value) {
            if ($value !== '' && $value !== null) {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    protected function cred($config, $key, $default = '')
    {
        $c = $this->credentials($config);
        return (isset($c[$key]) && $c[$key] !== '') ? $c[$key] : $default;
    }

    /**
     * Decrypted webhook signing secret. The outlet column wins when set;
     * otherwise the company-level column - providers like noon register one
     * webhook destination (and one header credential) per partner account.
     * @return string
     */
    protected function webhook_secret($config)
    {
        $secret = Integration_crypto::decrypt(isset($config->webhook_secret) ? $config->webhook_secret : '');
        if ($secret === '' && isset($config->company_credentials) && $config->company_credentials) {
            $row    = $config->company_credentials;
            $secret = Integration_crypto::decrypt(isset($row->webhook_secret) ? $row->webhook_secret : '');
        }
        return $secret;
    }

    /**
     * Base URL for outbound calls. Sandbox and live are different hosts for
     * every aggregator, so both live in the credentials blob.
     * @return string  no trailing slash
     */
    protected function base_url($config)
    {
        $key = (isset($config->is_sandbox) && $config->is_sandbox === 'Yes') ? 'sandbox_base_url' : 'base_url';
        $url = $this->cred($config, $key, $this->cred($config, 'base_url', ''));
        return rtrim($url, '/');
    }

    /* =================================================================
     * HMAC helpers
     * ================================================================= */

    protected function hmac_hex($raw_body, $secret, $algo = 'sha256')
    {
        return hash_hmac($algo, $raw_body, $secret);
    }

    /**
     * Timing-safe compare that also tolerates the common "sha256=<hex>" prefix.
     */
    protected function signature_matches($expected_hex, $received)
    {
        $received = trim((string) $received);
        if ($received === '') {
            return false;
        }
        if (stripos($received, '=') !== false) {
            $parts    = explode('=', $received, 2);
            $received = $parts[1];
        }
        // accept base64 too - some providers send it that way
        $expected_b64 = base64_encode(hex2bin($expected_hex));
        return hash_equals($expected_hex, strtolower($received)) || hash_equals($expected_b64, $received);
    }

    /**
     * Replay guard. An event older than the window is rejected even if signed:
     * a captured-and-replayed webhook must not re-punch an order.
     *
     * @param  string|int $timestamp  unix seconds or ISO-8601
     * @param  int        $window_seconds
     * @return bool
     */
    protected function within_replay_window($timestamp, $window_seconds = 300)
    {
        if ($timestamp === null || $timestamp === '') {
            return true;   // provider does not send one - signature is the only guard
        }
        $ts = is_numeric($timestamp) ? (int) $timestamp : strtotime($timestamp);
        if (!$ts) {
            return false;
        }
        return abs(time() - $ts) <= $window_seconds;
    }

    /* =================================================================
     * OAuth2 (client credentials), cached on the config row
     * ================================================================= */

    /**
     * Returns a bearer token, refreshing it when it is inside 60s of expiry.
     * Cached on tbl_integration_configs so a POS restart does not re-auth.
     *
     * @return string  '' when the provider needs no token or auth failed
     */
    protected function oauth_token($config)
    {
        $token_url = $this->cred($config, 'token_url', '');
        if ($token_url === '') {
            return (string) $this->cred($config, 'static_token', '');
        }

        $cached  = Integration_crypto::decrypt(isset($config->oauth_token) ? $config->oauth_token : '');
        $expires = isset($config->oauth_expires_at_utc) ? $config->oauth_expires_at_utc : null;
        if ($cached !== '' && $expires && strtotime($expires.' UTC') > (time() + 60)) {
            return $cached;
        }

        $res = $this->http('POST', $token_url, array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ), http_build_query(array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->cred($config, 'client_id'),
            'client_secret' => $this->cred($config, 'client_secret'),
            'scope'         => $this->cred($config, 'scope', ''),
        )), $config);

        if (!$res['ok'] || !is_array($res['json']) || empty($res['json']['access_token'])) {
            return '';
        }

        $token   = $res['json']['access_token'];
        $ttl     = isset($res['json']['expires_in']) ? (int) $res['json']['expires_in'] : 3600;
        $this->CI->db->where('id', $config->id);
        $this->CI->db->update('tbl_integration_configs', array(
            'oauth_token'          => Integration_crypto::encrypt($token),
            'oauth_expires_at_utc' => gmdate('Y-m-d H:i:s', time() + $ttl),
        ));

        // keep the in-memory config in step so a second call in the same request hits the cache
        $config->oauth_token          = Integration_crypto::encrypt($token);
        $config->oauth_expires_at_utc = gmdate('Y-m-d H:i:s', time() + $ttl);

        return $token;
    }

    /* =================================================================
     * Cookie-session auth (RS256 JWT login), cached company-wide
     *
     * Second auth strategy NEXT TO OAuth2, not replacing it. noon's platform
     * signs a JWT with a service-account private key, POSTs it to a login
     * endpoint, and carries the session cookies it gets back on every later
     * call. The session lives ~30 days, so the cache sits on the COMPANY
     * credentials row when one exists - one login shared by every outlet
     * instead of N outlets thrashing N sessions.
     * ================================================================= */

    /**
     * RS256 JWT signed with the service-account private key.
     * openssl only - this project has no composer.
     *
     * @param  array  $claims
     * @param  string $private_key_pem  PEM; literal "\n" sequences tolerated
     *                                  (keys pasted out of a JSON file)
     * @return string '' on failure
     */
    protected function jwt_rs256(array $claims, $private_key_pem)
    {
        $pem = (string) $private_key_pem;
        if (strpos($pem, '\n') !== false && strpos($pem, "\n") === false) {
            $pem = str_replace('\n', "\n", $pem);
        }
        $key = openssl_pkey_get_private($pem);
        if (!$key) {
            return '';
        }

        $header  = self::b64url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $payload = self::b64url(json_encode($claims));
        $input   = $header.'.'.$payload;

        $signature = '';
        if (!openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return '';
        }
        return $input.'.'.self::b64url($signature);
    }

    protected static function b64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Log in and return the session cookie string ("a=1; b=2"), cached on the
     * company credentials row (falling back to the config row when no company
     * row exists). Refreshes at 24h remaining, not 60s - a login is expensive
     * relative to a token refresh and the session lasts ~30 days.
     *
     * Cred keys used: login_url, private_key, key_id, user_agent (optional),
     * session_ttl_days (optional, default 30).
     *
     * @return string '' when unconfigured or auth failed
     */
    protected function session_cookie($config)
    {
        $cache = $this->token_cache_read($config);
        if ($cache['token'] !== '' && $cache['expires']
            && strtotime($cache['expires'].' UTC') > (time() + 86400)) {
            return $cache['token'];
        }

        $login_url = $this->cred($config, 'login_url', '');
        $pem       = $this->cred($config, 'private_key', '');
        $key_id    = $this->cred($config, 'key_id', '');
        if ($login_url === '' || $pem === '' || $key_id === '') {
            return '';
        }

        $jwt = $this->jwt_rs256(array(
            'sub' => $key_id,
            'iat' => time(),
            'jti' => bin2hex(random_bytes(8)),
        ), $pem);
        if ($jwt === '') {
            return '';
        }

        $headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$jwt,
        );
        $ua = $this->cred($config, 'user_agent', '');
        if ($ua !== '') {
            $headers['User-Agent'] = $ua;
        }

        // Provisional body shape until the provider's spec lands; both the
        // header and the body carry the assertion, which is harmless.
        $res = $this->login_for_cookies($login_url, $headers, array('token' => $jwt), $config);
        if (!$res['ok'] || $res['cookies'] === '') {
            return '';
        }

        $ttl_days = (int) $this->cred($config, 'session_ttl_days', 30);
        if ($ttl_days <= 0) {
            $ttl_days = 30;
        }
        $this->token_cache_write($config, $res['cookies'], gmdate('Y-m-d H:i:s', time() + $ttl_days * 86400));

        return $res['cookies'];
    }

    /**
     * Where the cached token/session for this config lives: the company
     * credentials row when one is attached, else the config row itself.
     * @return array ['token'=>string, 'expires'=>string|null]
     */
    protected function token_cache_read($config)
    {
        $row = (isset($config->company_credentials) && $config->company_credentials)
            ? $config->company_credentials : $config;
        return array(
            'token'   => Integration_crypto::decrypt(isset($row->oauth_token) ? $row->oauth_token : ''),
            'expires' => isset($row->oauth_expires_at_utc) ? $row->oauth_expires_at_utc : null,
        );
    }

    protected function token_cache_write($config, $token, $expires_at_utc)
    {
        $data = array(
            'oauth_token'          => Integration_crypto::encrypt($token),
            'oauth_expires_at_utc' => $expires_at_utc,
        );
        if (isset($config->company_credentials) && $config->company_credentials) {
            $row = $config->company_credentials;
            $this->CI->db->where('id', $row->id);
            $this->CI->db->update('tbl_integration_company_credentials', $data);
            // keep the in-memory row in step so the next call this request hits the cache
            $row->oauth_token          = $data['oauth_token'];
            $row->oauth_expires_at_utc = $expires_at_utc;
        } else {
            $this->CI->db->where('id', $config->id);
            $this->CI->db->update('tbl_integration_configs', $data);
            $config->oauth_token          = $data['oauth_token'];
            $config->oauth_expires_at_utc = $expires_at_utc;
        }
    }

    /**
     * Like http(), but POST-only and also captures Set-Cookie response
     * headers - the whole point of a cookie-session login. Kept separate so
     * http() stays byte-identical for every existing driver.
     *
     * @return array ['ok','http_status','body','json','cookies','error']
     *               cookies = "name=value; name2=value2"
     */
    protected function login_for_cookies($url, $headers, $body, $config = null)
    {
        if (is_array($body)) {
            $body = json_encode($body);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
        }
        $body = (string) $body;

        $header_lines = array();
        foreach ($headers as $k => $v) {
            $header_lines[] = $k.': '.$v;
        }

        $cookies = array();
        $started = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $header_lines,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$cookies) {
                if (stripos($line, 'Set-Cookie:') === 0) {
                    $value = trim(substr($line, 11));
                    $semi  = strpos($value, ';');
                    $pair  = ($semi === false) ? $value : substr($value, 0, $semi);
                    if (trim($pair) !== '') {
                        $cookies[] = trim($pair);
                    }
                }
                return strlen($line);
            },
        ));

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        $duration = (int) round((microtime(true) - $started) * 1000);
        $response = ($response === false) ? '' : $response;

        $this->log_call('out', $url, $status, $body, ($err !== '' ? 'CURL: '.$err : $response), $duration, $config);

        return array(
            'ok'          => ($err === '' && $status >= 200 && $status < 300),
            'http_status' => $status,
            'body'        => $response,
            'json'        => json_decode($response, true),
            'cookies'     => implode('; ', $cookies),
            'error'       => $err !== '' ? $err : ($status >= 400 ? 'HTTP '.$status : ''),
        );
    }

    /* =================================================================
     * HTTP + logging
     * ================================================================= */

    /**
     * One outbound call, always logged, never allowed to throw.
     *
     * @param  string       $method
     * @param  string       $url
     * @param  array        $headers  ['Name' => 'value']
     * @param  string|array $body     array is JSON-encoded
     * @param  object|null  $config
     * @return array ['ok'=>bool,'http_status'=>int,'body'=>string,'json'=>mixed,'error'=>string]
     */
    protected function http($method, $url, $headers, $body, $config = null)
    {
        if (is_array($body)) {
            $body = json_encode($body);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
        }
        $body = (string) $body;

        $header_lines = array();
        foreach ($headers as $k => $v) {
            $header_lines[] = $k.': '.$v;
        }

        $started = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $header_lines,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));
        if (strtoupper($method) !== 'GET' && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        $duration = (int) round((microtime(true) - $started) * 1000);
        $response = ($response === false) ? '' : $response;

        $this->log_call('out', $url, $status, $body, ($err !== '' ? 'CURL: '.$err : $response), $duration, $config);

        $json = json_decode($response, true);

        return array(
            'ok'          => ($err === '' && $status >= 200 && $status < 300),
            'http_status' => $status,
            'body'        => $response,
            'json'        => $json,
            'error'       => $err !== '' ? $err : ($status >= 400 ? 'HTTP '.$status : ''),
        );
    }

    /**
     * Write one row to tbl_integration_logs. Secrets are stripped first -
     * a support export must never leak a client secret or a bearer token.
     */
    public function log_call($direction, $endpoint, $http_status, $request_body, $response_body, $duration_ms, $config = null)
    {
        $this->CI->db->insert('tbl_integration_logs', array(
            'config_id'      => ($config && isset($config->id)) ? $config->id : null,
            'provider_code'  => $this->code(),
            'direction'      => $direction,
            'endpoint'       => substr((string) $endpoint, 0, 190),
            'http_status'    => $http_status ? $http_status : null,
            'request_body'   => self::redact($request_body),
            'response_body'  => self::redact($response_body),
            'duration_ms'    => $duration_ms,
            'created_at_utc' => gmdate('Y-m-d H:i:s'),
        ));
    }

    /**
     * Blank out anything that looks like a credential.
     * @return string
     */
    public static function redact($text)
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }
        $patterns = array(
            '/("(?:client_secret|password|access_token|refresh_token|webhook_secret|api_key|private_key|static_token)"\s*:\s*")(?:[^"\\\\]|\\\\.)*(")/i' => '$1***$2',
            '/((?:client_secret|password|access_token|api_key)=)[^&\s]*/i'                                        => '$1***',
            '/(Bearer\s+)[A-Za-z0-9\._\-]+/i'                                                                     => '$1***',
            // JWT assertions posted in a login body ("token":"eyJ...") - the eyJ
            // prefix keeps this from eating aggregator order tokens in payloads
            '/("token"\s*:\s*")eyJ[^"]*(")/i'                                                                     => '$1***$2',
            // session cookies, wherever a header line lands in a logged body
            '/((?:^|[\r\n])set-cookie:\s*)[^\r\n]+/i'                                                             => '$1***',
            '/((?:^|[\r\n])cookie:\s*)[^\r\n]+/i'                                                                 => '$1***',
            // raw PEM blocks pasted into any logged body
            '/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/s'                            => '***',
        );
        foreach ($patterns as $re => $to) {
            $text = preg_replace($re, $to, $text);
        }
        return $text;
    }

    /* =================================================================
     * Small shared parsing helpers
     * ================================================================= */

    /** Aggregator payloads quote numbers as strings more often than not. */
    protected static function num($value, $default = 0.0)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (float) str_replace(',', '', (string) $value);
    }

    /** Read a dotted path out of a decoded payload without a pile of isset(). */
    protected static function dig($data, $path, $default = null)
    {
        $node = $data;
        foreach (explode('.', $path) as $key) {
            if (is_array($node) && array_key_exists($key, $node)) {
                $node = $node[$key];
            } elseif (is_object($node) && isset($node->$key)) {
                $node = $node->$key;
            } else {
                return $default;
            }
        }
        return $node;
    }

    /** Any provider timestamp -> 'Y-m-d H:i:s' in UTC. */
    protected static function to_utc($value)
    {
        if ($value === null || $value === '') {
            return gmdate('Y-m-d H:i:s');
        }
        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
        return $ts ? gmdate('Y-m-d H:i:s', $ts) : gmdate('Y-m-d H:i:s');
    }
}
