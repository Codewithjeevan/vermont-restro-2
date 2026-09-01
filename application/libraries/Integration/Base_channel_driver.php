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

    protected function unsupported($what)
    {
        return array('ok' => false, 'http_status' => 0, 'error' => $this->code().' does not support '.$what, 'response' => null);
    }

    /* =================================================================
     * Credentials
     * ================================================================= */

    /**
     * Decrypted credentials JSON for this config.
     * @return array
     */
    protected function credentials($config)
    {
        return Integration_crypto::decrypt_json(isset($config->credentials) ? $config->credentials : '');
    }

    protected function cred($config, $key, $default = '')
    {
        $c = $this->credentials($config);
        return (isset($c[$key]) && $c[$key] !== '') ? $c[$key] : $default;
    }

    /**
     * Decrypted webhook signing secret.
     * @return string
     */
    protected function webhook_secret($config)
    {
        return Integration_crypto::decrypt(isset($config->webhook_secret) ? $config->webhook_secret : '');
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
            '/("(?:client_secret|password|access_token|refresh_token|webhook_secret|api_key)"\s*:\s*")[^"]*(")/i' => '$1***$2',
            '/((?:client_secret|password|access_token|api_key)=)[^&\s]*/i'                                        => '$1***',
            '/(Bearer\s+)[A-Za-z0-9\._\-]+/i'                                                                     => '$1***',
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
