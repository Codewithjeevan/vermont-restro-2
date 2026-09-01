<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * At-rest encryption for integration credentials.
 *
 * NOTE on why this exists: the design doc said to reuse Custom::encrypt_decrypt().
 * That helper is a no-op in this codebase - it returns its input unchanged
 * (see libraries/Custom.php), so using it would have stored aggregator client
 * secrets in the clear. This is a real AES-256-CBC + HMAC implementation keyed
 * off $config['encryption_key'].
 *
 * Values are stored as  enc:v1:<base64(iv . hmac . ciphertext)>  so that a
 * plaintext value written by hand (during testing) still decrypts to itself -
 * anything without the prefix is passed through untouched.
 */
class Integration_crypto
{
    const PREFIX = 'enc:v1:';
    const CIPHER = 'aes-256-cbc';

    /**
     * 32 byte key derived from the application encryption key.
     * @return string
     */
    protected static function key()
    {
        $CI = & get_instance();
        $base = (string) $CI->config->item('encryption_key');
        if ($base === '') {
            // never silently fall back to an empty key
            $base = 'irestora-integration-fallback';
        }
        return hash('sha256', 'integration|'.$base, true);
    }

    /**
     * @param  string $plain
     * @return string  encrypted, prefixed value ('' stays '')
     */
    public static function encrypt($plain)
    {
        if ($plain === null || $plain === '') {
            return '';
        }
        $key = self::key();
        $iv  = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        $ct  = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($ct === false) {
            return '';
        }
        $mac = hash_hmac('sha256', $iv.$ct, $key, true);
        return self::PREFIX.base64_encode($iv.$mac.$ct);
    }

    /**
     * Decrypt a value produced by encrypt(). A value without the prefix is
     * returned as-is, so hand-written test credentials keep working.
     *
     * @param  string $value
     * @return string  '' when the value is corrupt or was tampered with
     */
    public static function decrypt($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (strpos($value, self::PREFIX) !== 0) {
            return $value;   // plaintext / legacy
        }
        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($raw === false) {
            return '';
        }
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($raw) <= $ivlen + 32) {
            return '';
        }
        $iv  = substr($raw, 0, $ivlen);
        $mac = substr($raw, $ivlen, 32);
        $ct  = substr($raw, $ivlen + 32);
        $key = self::key();

        if (!hash_equals(hash_hmac('sha256', $iv.$ct, $key, true), $mac)) {
            return '';   // tampered
        }
        $plain = openssl_decrypt($ct, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    /**
     * Convenience: decrypt a JSON credentials blob into an array.
     * @param  string $value
     * @return array
     */
    public static function decrypt_json($value)
    {
        $plain = self::decrypt($value);
        if ($plain === '') {
            return array();
        }
        $decoded = json_decode($plain, true);
        return is_array($decoded) ? $decoded : array();
    }

    /**
     * Convenience: encrypt an array as a JSON credentials blob.
     * @param  array $data
     * @return string
     */
    public static function encrypt_json($data)
    {
        return self::encrypt(json_encode(is_array($data) ? $data : array()));
    }
}
