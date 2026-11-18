<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 | AI Insight helpers (autoloaded). Plan: docs/ai_insight_plan.md
 | - ai_env(): secrets from a real environment variable or application/.env
 | - ai_company_enabled() / ai_is_main_admin(): who sees the AI menu
 | - ai_csrf_*(): POST protection for the AI screens (global CSRF is off)
 */

if (!function_exists('ai_env')) {
    /**
     * Read a secret. A real environment variable wins; otherwise application/.env
     * (KEY=VALUE lines, # comments, optional quotes). Never echo or log the result.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    function ai_env($name, $default = null) {
        static $file_values = null;
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }
        if ($file_values === null) {
            $file_values = array();
            $path = APPPATH . '.env';
            if (is_file($path) && is_readable($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    list($key, $val) = explode('=', $line, 2);
                    $val = trim($val);
                    if (strlen($val) > 1 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                        $val = substr($val, 1, -1);
                    }
                    $file_values[trim($key)] = $val;
                }
            }
        }
        return isset($file_values[$name]) && $file_values[$name] !== '' ? $file_values[$name] : $default;
    }
}

if (!function_exists('ai_redact')) {
    /**
     * Remove anything that looks like an Anthropic key from a message before it is logged or shown.
     * @param string $text
     * @return string
     */
    function ai_redact($text) {
        return preg_replace('/sk-ant-[A-Za-z0-9_\-]+/', 'sk-ant-***', (string) $text);
    }
}

if (!function_exists('ai_config')) {
    /**
     * An item of config/ai.php (loaded on first use).
     * @param string $item
     * @return mixed
     */
    function ai_config($item) {
        $CI = & get_instance();
        if ($CI->config->item('ai_main_company_id') === null) {
            $CI->config->load('ai', false, true);
        }
        return $CI->config->item($item);
    }
}

if (!function_exists('ai_tables_ready')) {
    /**
     * True once Update/ai_insight_migration.sql has been run, so pages never query a missing table.
     * @return bool
     */
    function ai_tables_ready() {
        static $ready = null;
        if ($ready === null) {
            $CI = & get_instance();
            $ready = $CI->db->table_exists('tbl_ai_settings');
        }
        return $ready;
    }
}

if (!function_exists('ai_company_enabled')) {
    /**
     * Is AI Insight switched on for the company (AI Companies page)?
     * @param int $company_id session company when empty
     * @return bool
     */
    function ai_company_enabled($company_id = '') {
        static $cache = array();
        $CI = & get_instance();
        $company_id = (int) ($company_id ? $company_id : $CI->session->userdata('company_id'));
        if (!$company_id || !ai_tables_ready()) {
            return false;
        }
        if (!isset($cache[$company_id])) {
            $row = $CI->db->query("SELECT enabled FROM tbl_ai_settings WHERE company_id = ?", array($company_id))->row();
            $cache[$company_id] = $row && (int) $row->enabled === 1;
        }
        return $cache[$company_id];
    }
}

if (!function_exists('ai_is_main_admin')) {
    /**
     * Admin of the main company: may switch AI on/off per company and set the monthly limits.
     * @return bool
     */
    function ai_is_main_admin() {
        $CI = & get_instance();
        return (int) $CI->session->userdata('company_id') === (int) ai_config('ai_main_company_id')
            && $CI->session->userdata('role') == 'Admin';
    }
}

if (!function_exists('ai_csrf_token')) {
    /**
     * Per-session token for AI POST requests (config.php has csrf_protection off).
     * @return string
     */
    function ai_csrf_token() {
        $CI = & get_instance();
        $token = $CI->session->userdata('ai_csrf');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $CI->session->set_userdata('ai_csrf', $token);
        }
        return $token;
    }
}

if (!function_exists('ai_csrf_ok')) {
    /**
     * Valid token (form field ai_csrf or header X-AI-CSRF) and a same-site Origin/Referer.
     * @return bool
     */
    function ai_csrf_ok() {
        $CI = & get_instance();
        $expected = (string) $CI->session->userdata('ai_csrf');
        $given = (string) $CI->input->post('ai_csrf');
        if ($given === '') {
            $given = (string) $CI->input->get_request_header('X-AI-CSRF', true);
        }
        if ($expected === '' || !hash_equals($expected, $given)) {
            return false;
        }
        $source = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        if ($source === '') {
            return true; // some browsers send neither on same-site posts; the token still has to match
        }
        $host = parse_url($source, PHP_URL_HOST);
        return $host && isset($_SERVER['HTTP_HOST']) && strcasecmp($host, preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) === 0;
    }
}

if (!function_exists('ai_csrf_field')) {
    /**
     * Hidden input for AI forms.
     * @return string
     */
    function ai_csrf_field() {
        return '<input type="hidden" name="ai_csrf" value="' . htmlspecialchars(ai_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
