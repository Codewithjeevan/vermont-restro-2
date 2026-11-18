<?php
/*
  ###########################################################
  # This is Ai_format Library
  ###########################################################
  # Output helpers of the AI Insight views. Every method returns
  # HTML-escaped text, so a view can echo it directly (plan S6).
  # escape_output() is not used for numbers: it prints "" for 0.
  # Load: $this->load->library('Ai/Ai_format', null, 'Ai_format');
  #       $this->Ai_format->setCurrency('AED', 2);
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_format {

    private $currency = '';
    private $precision = 2;

    public function setCurrency($currency, $precision) {
        $this->currency = (string) $currency;
        $this->precision = max(0, min(4, (int) $precision));
    }

    /** any value as escaped text; null -> '' */
    public function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** number with thousands separators, at most $dec decimals, trailing zeros dropped; null -> dash */
    public function num($value, $dec = 2) {
        if ($value === null || $value === '') {
            return '&ndash;';
        }
        $s = number_format((float) $value, $dec);
        if ($dec > 0 && strpos($s, '.') !== false) {
            $s = rtrim(rtrim($s, '0'), '.');
        }
        return $this->e($s === '-0' ? '0' : $s);
    }

    /**
     * Amount with the company currency, written like the report text ("AED 1,250.00").
     * @param int|null $dec decimals; null = the company precision
     */
    public function money($value, $dec = null) {
        if ($value === null) {
            return '&ndash;';
        }
        return $this->e(trim($this->currency . ' ' . number_format((float) $value, $dec === null ? $this->precision : (int) $dec)));
    }

    public function pct($value, $dec = 1) {
        return $value === null ? '&ndash;' : $this->num($value, $dec) . '%';
    }

    /** quantity with its unit */
    public function qty($value, $unit) {
        return $this->num($value) . ($unit !== '' && $unit !== null ? ' ' . $this->e($unit) : '');
    }

    /** "2026-10-31" -> "31 Oct 2026" */
    public function date($ymd) {
        $t = strtotime((string) $ymd);
        return $t ? $this->e(date('d M Y', $t)) : $this->e($ymd);
    }

    /**
     * Change badge for a withChange() entry: arrow, percent (or the value when there was nothing before).
     * @param array|null $change value, pct
     * @param string $good 'up' (more is better), 'down' (less is better) or '' (neutral)
     * @param bool $money show the value as money when there is no percent
     */
    public function change($change, $good = 'up', $money = true) {
        if (!$change) {
            return '&ndash;';
        }
        if ((float) $change['value'] == 0) {
            return '<span class="ai-chg">0%</span>';
        }
        $up = $change['value'] > 0;
        $class = $good === '' ? '' : (($up xor $good === 'down') ? ' is-good' : ' is-bad');
        $text = $change['pct'] !== null ? $this->pct(abs($change['pct'])) : ($money ? $this->money(abs($change['value'])) : $this->num(abs($change['value'])));
        return '<span class="ai-chg' . $class . '">' . ($up ? '&#9650; ' : '&#9660; ') . $text . '</span>';
    }

    /**
     * AI or template text as HTML: escaped first, then **bold**, "- " bullets and line breaks.
     * Nothing else of the text can become markup.
     */
    public function text($text) {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
        $html = '';
        $in_list = false;
        foreach ($lines as $line) {
            $line = trim($line);
            $safe = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $this->e($line));
            if (preg_match('/^[-*] (.+)$/u', $line)) {
                $html .= ($in_list ? '' : '<ul>') . '<li>' . substr($safe, 2) . '</li>';
                $in_list = true;
                continue;
            }
            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }
            if ($line !== '') {
                $html .= '<p>' . $safe . '</p>';
            }
        }
        return $html . ($in_list ? '</ul>' : '');
    }

    /** CSS width (0-100) of a bar: $value against $max */
    public function bar($value, $max) {
        if ($max <= 0 || $value <= 0) {
            return '0';
        }
        return $this->e(round(min(100, $value / $max * 100), 1));
    }
}
