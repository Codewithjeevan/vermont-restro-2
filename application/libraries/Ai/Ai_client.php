<?php
/*
  ###########################################################
  # This is Ai_client Library
  ###########################################################
  # The only way AI Insight talks to the Anthropic API.
  # Before a call: company switched on? monthly limit left?
  # key configured? After a call: one tbl_ai_usage row with
  # tokens and USD. Errors come back as a friendly message;
  # the key never reaches a log, the DB or the browser.
  #
  # Load it with an explicit object name (subfolder library):
  #   $this->load->library('Ai/Ai_client', null, 'Ai_client');
  # SDK: application/third_party/anthropic (own vendor folder).
  # Plan: docs/ai_insight_plan.md sections 5, 7 and 9
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;

class Ai_client {

    /** @var CI_Controller */
    private $CI;
    /** @var \Anthropic\Client|null */
    private $client = null;

    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model('Ai_model');
    }

    /**
     * Is ANTHROPIC_API_KEY set (environment or application/.env)?
     * @return bool
     */
    public function keyConfigured() {
        return (string) ai_env('ANTHROPIC_API_KEY', '') !== '';
    }

    /**
     * AI_MOCK=1: canned replies, no API call, no tokens (tests).
     * @return bool
     */
    public function mockMode() {
        return (string) ai_env('AI_MOCK', '0') === '1';
    }

    /**
     * Send one Messages API request.
     *
     * @param array $ctx company_id, outlet_id, user_id, feature (report/chat/test);
     *                   'ignore_switch' => true only for the main admin's Test connection;
     *                   'mock_text' => reply returned in AI_MOCK mode
     * @param array $params named arguments of $client->messages->create(): model, maxTokens,
     *                      messages, and optionally system, tools, outputConfig, thinking, cacheControl
     * @param int $timeout seconds
     * @param array $beta optional beta call: betas (header values) and fallbacks, e.g.
     *                    array('betas' => array('server-side-fallback-2026-07-01'), 'fallbacks' => 'default')
     * @return array ok (bool), message (Message / BetaMessage / null), text, stop_reason, model,
     *               usage (array), cost_usd, error (friendly text), error_code
     */
    public function send(array $ctx, array $params, $timeout = 30, array $beta = array()) {
        $company_id = (int) $ctx['company_id'];
        $model = (string) $params['model'];
        $base = array(
            'company_id' => $company_id,
            'outlet_id' => isset($ctx['outlet_id']) ? (int) $ctx['outlet_id'] : null,
            'user_id' => isset($ctx['user_id']) ? (int) $ctx['user_id'] : null,
            'feature' => (string) $ctx['feature'],
            'model' => $model,
        );

        $settings = $this->CI->Ai_model->getSettings($company_id);
        if (empty($ctx['ignore_switch']) && !(int) $settings->enabled) {
            return $this->refuse($base, 'disabled', lang('ai_disabled_company'));
        }
        if ($this->CI->Ai_model->monthSpend($company_id) >= (float) $settings->monthly_budget_usd) {
            return $this->refuse($base, 'budget', lang('ai_budget_reached'));
        }
        if ($this->mockMode()) {
            $this->CI->Ai_model->logUsage($base + array('status' => 'ok', 'error' => 'mock', 'duration_ms' => 0));
            $text = isset($ctx['mock_text']) ? (string) $ctx['mock_text'] : 'MOCK: ' . $base['feature'] . ' reply';
            return $this->result(true, null, $text, 'end_turn', array(), 0.0, '', '', $model);
        }
        if (!$this->keyConfigured()) {
            return $this->refuse($base, 'key_missing', lang('ai_key_missing'));
        }

        $started = microtime(true);
        try {
            $params['requestOptions'] = array(
                'timeout' => (float) $timeout,
                'maxRetries' => (int) ai_config('ai_max_retries'),
            );
            if (!empty($beta['betas'])) {
                $params['betas'] = $beta['betas'];
                if (isset($beta['fallbacks'])) {
                    $params['fallbacks'] = $beta['fallbacks'];
                }
                $message = $this->client()->beta->messages->create(...$params);
            } else {
                $message = $this->client()->messages->create(...$params);
            }
        } catch (AuthenticationException $e) {
            return $this->failed($base, $started, 'auth', $e);
        } catch (PermissionDeniedException $e) {
            return $this->failed($base, $started, 'permission', $e);
        } catch (BadRequestException $e) {
            return $this->failed($base, $started, 'bad_request', $e);
        } catch (NotFoundException $e) {
            return $this->failed($base, $started, 'not_found', $e);
        } catch (RateLimitException $e) {
            return $this->failed($base, $started, 'rate_limit', $e);
        } catch (InternalServerException $e) {
            return $this->failed($base, $started, 'server', $e);
        } catch (APIStatusException $e) {
            return $this->failed($base, $started, 'api_status', $e);
        } catch (APITimeoutException $e) {
            return $this->failed($base, $started, 'timeout', $e);
        } catch (APIConnectionException $e) {
            return $this->failed($base, $started, 'connection', $e);
        } catch (APIException $e) {
            return $this->failed($base, $started, 'api', $e);
        } catch (\Throwable $e) {
            return $this->failed($base, $started, 'internal', $e);
        }

        $usage = array(
            'input_tokens' => (int) $message->usage->inputTokens,
            'cache_read_tokens' => (int) $message->usage->cacheReadInputTokens,
            'cache_write_tokens' => (int) $message->usage->cacheCreationInputTokens,
            'output_tokens' => (int) $message->usage->outputTokens,
        );
        // a server-side fallback can answer with another model: bill the model that answered
        $answered_by = (string) $message->model ? (string) $message->model : $model;
        $cost = $this->cost($answered_by, $usage);
        $stop = (string) $message->stopReason;
        $base['model'] = mb_substr($answered_by, 0, 50);
        $this->CI->Ai_model->logUsage($base + $usage + array(
            'cost_usd' => $cost,
            'status' => $stop == 'refusal' ? 'refusal' : 'ok',
            'request_id' => mb_substr((string) $message->id, 0, 64),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ));
        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }
        return $this->result($stop != 'refusal', $message, $text, $stop, $usage, $cost, $stop == 'refusal' ? lang('ai_error_generic') : '', $stop == 'refusal' ? 'refusal' : '', $answered_by);
    }

    /**
     * Error codes worth another try on the next hourly run; the rest will fail the same way again.
     * @param string $code
     * @return bool
     */
    public function retryable($code) {
        return in_array($code, array('rate_limit', 'server', 'api_status', 'timeout', 'connection', 'api', 'internal'));
    }

    /**
     * USD for one call, from config/ai.php ai_prices (per million tokens).
     * @param string $model
     * @param array $usage
     * @return float
     */
    public function cost($model, $usage) {
        $prices = ai_config('ai_prices');
        // a model missing from the table (e.g. a fallback model) is priced high, so limits stay on the safe side
        $p = isset($prices[$model]) ? $prices[$model] : ai_config('ai_price_unknown');
        return round(($usage['input_tokens'] * $p['in'] + $usage['cache_read_tokens'] * $p['cache_read']
            + $usage['cache_write_tokens'] * $p['cache_write'] + $usage['output_tokens'] * $p['out']) / 1000000, 6);
    }

    private function client() {
        if ($this->client === null) {
            require_once APPPATH . 'third_party/anthropic/vendor/autoload.php';
            $this->client = new \Anthropic\Client(apiKey: (string) ai_env('ANTHROPIC_API_KEY'));
        }
        return $this->client;
    }

    private function refuse($base, $code, $message) {
        $this->CI->Ai_model->logUsage($base + array('status' => 'blocked', 'error' => $code));
        return $this->result(false, null, '', '', array(), 0.0, $message, $code);
    }

    private function failed($base, $started, $code, \Throwable $e) {
        $detail = $code . ': ' . ($e instanceof APIStatusException && $e->status ? 'HTTP ' . $e->status . ' ' : '') . $e->getMessage();
        $this->CI->Ai_model->logUsage($base + array(
            'status' => 'error',
            'error' => $detail,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ));
        log_message('error', 'AI call failed: ' . ai_redact($detail));
        return $this->result(false, null, '', '', array(), 0.0, lang('ai_error_generic'), $code);
    }

    private function result($ok, $message, $text, $stop, $usage, $cost, $error, $code, $model = '') {
        return array(
            'ok' => $ok,
            'message' => $message,
            'text' => $text,
            'stop_reason' => $stop,
            'model' => $model,
            'usage' => $usage,
            'cost_usd' => $cost,
            'error' => $error,
            'error_code' => $code,
        );
    }
}
