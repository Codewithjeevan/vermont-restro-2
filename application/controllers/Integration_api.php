<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'libraries/REST_Controller.php');

/**
 * Inbound webhooks for every ordering channel. Public, no session.
 *
 * One URL shape for every app - the provider code is a URL segment:
 *     POST /api/integration/<provider>/webhook
 *     GET  /api/integration/<provider>/health
 * so app #2 needs no new route and no new controller.
 *
 * Order of operations, and why:
 *   1. log the raw call          - disputes are settled with the raw payload
 *   2. classify the event        - cheap, on untrusted data, lookup only
 *   3. resolve the config        - their store id is the only routing key
 *   4. verify the signature      - nothing is trusted before this line
 *   5. claim the order (UNIQUE)  - idempotency BEFORE any other write
 *   6. ACK                       - aggregators retry if we are slow
 *   7. ingest                    - after the ACK
 */
class Integration_api extends REST_Controller
{
    /** @var string exact bytes received - HMAC must be computed over these, never re-encoded JSON */
    protected $raw_body = '';

    /** @var array lowercase header name => value */
    protected $headers = array();

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Integration_model');
        // third argument matters: without it CI assigns the library to a
        // lowercased property ($this->integration_manager)
        $this->load->library('Integration/Integration_manager', null, 'Integration_manager');
        $this->load->library('Integration/Order_ingestor', null, 'Order_ingestor');

        $this->raw_body = (string) $this->input->raw_input_stream;
        if ($this->raw_body === '') {
            $this->raw_body = (string) file_get_contents('php://input');
        }

        foreach ((array) $this->input->request_headers() as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    /* ================================================================= webhook */

    public function webhook_post($provider_code = '')
    {
        $started  = microtime(true);
        $endpoint = 'api/integration/'.$provider_code.'/webhook';

        $provider = $this->Integration_manager->provider($provider_code);
        if (!$provider) {
            return $this->finish($provider_code, $endpoint, 404, $started, array(
                'status' => 'error', 'message' => 'Unknown provider',
            ));
        }

        $driver = $this->Integration_manager->driver($provider);
        if (!$driver) {
            return $this->finish($provider_code, $endpoint, 501, $started, array(
                'status' => 'error', 'message' => 'No driver installed for '.$provider_code,
            ));
        }

        /* --- classify (untrusted; used for lookup only) ------------------ */
        $event = $driver->parse_event($this->raw_body);

        if ($event['type'] === 'ping') {
            return $this->finish($provider_code, $endpoint, 200, $started, array('status' => 'ok', 'message' => 'pong'));
        }

        /* --- route to an outlet ------------------------------------------ */
        $config = $this->Integration_manager->resolve_config($provider, $event['external_store_id']);
        if (!$config) {
            return $this->finish($provider_code, $endpoint, 404, $started, array(
                'status' => 'error',
                'message' => 'No outlet is configured for store id "'.$event['external_store_id'].'"',
            ));
        }

        /* --- authenticate. Nothing above this line is trusted. ----------- */
        if (!$driver->verify_webhook($this->headers, $this->raw_body, $config)) {
            return $this->finish($provider_code, $endpoint, 401, $started, array(
                'status' => 'error', 'message' => 'Signature verification failed',
            ), $config->id);
        }

        /* --- the on/off button ------------------------------------------- */
        if (!$this->Integration_manager->is_enabled($config)) {
            // 200, not an error: a disabled channel must not look broken to them
            return $this->finish($provider_code, $endpoint, 200, $started, array(
                'status' => 'ignored', 'message' => 'Integration is disabled for this outlet',
            ), $config->id);
        }

        if ($event['external_order_id'] === '') {
            return $this->finish($provider_code, $endpoint, 422, $started, array(
                'status' => 'error', 'message' => 'Event carries no order id',
            ), $config->id);
        }

        /* --- cancellations ------------------------------------------------ */
        if ($event['type'] === 'order.cancelled') {
            return $this->handle_cancel($provider_code, $endpoint, $started, $config, $event);
        }

        if ($event['type'] !== 'order.created' && $event['type'] !== 'order.accepted') {
            return $this->finish($provider_code, $endpoint, 200, $started, array(
                'status' => 'ignored', 'message' => 'Event type not handled: '.$event['type'],
            ), $config->id);
        }

        /* --- ingest_on decides which event actually punches --------------- */
        if (!$this->Integration_manager->should_ingest_on($config, $event['type'])) {
            return $this->finish($provider_code, $endpoint, 200, $started, array(
                'status'  => 'ignored',
                'message' => 'Waiting for the "'.$config->ingest_on.'" event before punching',
            ), $config->id);
        }

        /* --- idempotency, BEFORE any other write -------------------------
         * Aggregators retry aggressively. Without this you get double KOTs
         * on a busy Friday. The UNIQUE key does the work, not a SELECT. */
        $claim = $this->Integration_model->claimOrder(array(
            'config_id'         => $config->id,
            'provider_code'     => $provider_code,
            'external_order_id' => $event['external_order_id'],
            'external_order_no' => $event['external_order_no'],
            'company_id'        => $config->company_id,
            'outlet_id'         => $config->outlet_id,
            'canonical_status'  => 'RECEIVED',
            'raw_payload'       => $this->raw_body,
        ));

        if (!$claim['is_new']) {
            return $this->finish($provider_code, $endpoint, 200, $started, array(
                'status'  => 'duplicate',
                'message' => 'Order already received',
                'sale_no' => $claim['row'] ? $claim['row']->sale_no : null,
            ), $config->id);
        }

        $order_row = $claim['row'];

        /* --- ACK now, work after ------------------------------------------
         * Talabat expects a 2xx within a few seconds or it retries and can
         * mark the store unreachable. The order row is already durable, so a
         * crash after this point is recoverable, not a lost order. */
        $this->ack($endpoint, $provider_code, 202, $started, array(
            'status'            => 'accepted',
            'external_order_id' => $event['external_order_id'],
        ), $config->id);

        $this->ingest_now($driver, $config, $order_row);
        exit;
    }

    /* ================================================================== health */

    /**
     * Cheap check for "is this wired up" - safe to expose: no secret, no payload.
     */
    public function health_get($provider_code = '', $external_store_id = '')
    {
        $provider = $this->Integration_manager->provider($provider_code);
        if (!$provider) {
            $this->response(array('status' => 'error', 'message' => 'Unknown provider'), 404);
            return;
        }

        $driver = $this->Integration_manager->driver($provider);
        $config = $this->Integration_manager->resolve_config($provider, $external_store_id);

        $last = null;
        if ($config) {
            $rows = $this->Integration_model->recentOrders(1, $config->outlet_id);
            $last = !empty($rows) ? $rows[0] : null;
        }

        $this->response(array(
            'status'           => 'ok',
            'provider'         => $provider->code,
            'provider_active'  => $provider->is_active,
            'driver_installed' => $driver ? true : false,
            'capabilities'     => $driver ? $driver->capabilities() : array(),
            'configured'       => $config ? true : false,
            'enabled'          => $config ? ($config->is_enabled === 'Yes') : false,
            'sandbox'          => $config ? ($config->is_sandbox === 'Yes') : null,
            'outlet_id'        => $config ? (int) $config->outlet_id : null,
            'ingest_on'        => $config ? $config->ingest_on : null,
            'auto_accept'      => $config ? $config->auto_accept : null,
            'last_order'       => $last ? array(
                'external_order_id' => $last->external_order_id,
                'sale_no'           => $last->sale_no,
                'status'            => $last->canonical_status,
                'received_at_utc'   => $last->received_at_utc,
            ) : null,
            'server_time_utc'  => gmdate('c'),
        ), 200);
    }

    /* ================================================================ internals */

    /**
     * Normalise + write the sale. Runs after the ACK, so every failure path
     * has to land on tbl_integration_orders - there is no client left to tell.
     */
    protected function ingest_now($driver, $config, $order_row)
    {
        $payload = json_decode($this->raw_body, true);

        // Thin-webhook support: a driver whose webhook carries only an id
        // fetches the full order here - after the ACK (a slow GET must not
        // trip the aggregator's timeout) and after the idempotency claim (so
        // the fetch runs at most once per order). payload NULL = "the webhook
        // body already carries the order" (every fat-webhook driver).
        $fetched = $driver->fetch_order($order_row->external_order_id, $config);
        if (empty($fetched['ok'])) {
            $this->Integration_model->updateOrder($order_row->id, array(
                'canonical_status' => 'REJECTED',
                'reject_reason'    => 'FETCH_FAILED',
                'last_error'       => substr(isset($fetched['error']) ? $fetched['error'] : 'fetch_order failed', 0, 255),
            ));
            return;
        }
        if (!empty($fetched['payload']) && is_array($fetched['payload'])) {
            $payload = $fetched['payload'];
            // Disputes are settled with the raw payload; the thin webhook body
            // alone is useless for that, so persist what we actually booked.
            $this->Integration_model->updateOrder($order_row->id, array(
                'raw_payload' => json_encode($payload),
            ));
        }

        $normalized = $driver->normalize_order($payload, $config);

        if (empty($normalized['ok'])) {
            $this->Integration_model->updateOrder($order_row->id, array(
                'canonical_status' => 'REJECTED',
                'reject_reason'    => 'NORMALIZE_FAILED',
                'last_error'       => substr($normalized['error'], 0, 255),
            ));
            return;
        }

        $result = $this->Order_ingestor->ingest($normalized['dto'], $config, $order_row->id);

        if (!$result['ok']) {
            // An unmapped item rejects the order rather than dropping a line -
            // a silently missing line item is a refund and a rating hit.
            $this->Integration_model->updateOrder($order_row->id, array(
                'canonical_status' => 'REJECTED',
                'reject_reason'    => $result['error_code'],
                'last_error'       => substr($result['error'], 0, 255),
            ));

            if ($this->Integration_manager->has_capability($config, 'status_out')) {
                $this->Integration_model->enqueueEvent($order_row->id, 'order.reject', array(
                    'external_order_id' => $order_row->external_order_id,
                    'reason'            => $result['error_code'],
                ));
            }
            return;
        }

        $auto_accept = ($config->auto_accept === 'Yes');
        $update = array(
            'kitchen_sale_id'  => $result['kitchen_sale_id'],
            'sale_no'          => $result['sale_no'],
            'canonical_status' => $auto_accept ? 'ACCEPTED' : 'RECEIVED',
            'ingested_at_utc'  => gmdate('Y-m-d H:i:s'),
            'last_error'       => !empty($result['warnings']) ? substr(implode(' | ', $result['warnings']), 0, 255) : null,
        );
        if ($auto_accept) {
            $update['accepted_at_utc'] = gmdate('Y-m-d H:i:s');
        }
        $this->Integration_model->updateOrder($order_row->id, $update);

        // Outbound never happens inline: a slow aggregator must not hold a
        // request open. The queue worker (phase 3) drains this.
        if ($auto_accept && $this->Integration_manager->has_capability($config, 'status_out')) {
            $this->Integration_model->enqueueEvent($order_row->id, 'order.accept', array(
                'external_order_id' => $order_row->external_order_id,
                'prep_minutes'      => (int) $config->default_prep_minutes,
            ));
        }
    }

    protected function handle_cancel($provider_code, $endpoint, $started, $config, $event)
    {
        $order = $this->Integration_model->getOrderByExternalId($provider_code, $event['external_order_id']);
        if (!$order) {
            return $this->finish($provider_code, $endpoint, 200, $started, array(
                'status' => 'ignored', 'message' => 'Cancellation for an unknown order',
            ), $config->id);
        }

        $this->Integration_model->updateOrder($order->id, array(
            'canonical_status' => 'CANCELLED',
            'reject_reason'    => 'CANCELLED_BY_PROVIDER',
        ));

        // Voiding a punched sale touches stock and the kitchen panel, so it is
        // a deliberate phase-3 step rather than a silent delete here.
        return $this->finish($provider_code, $endpoint, 200, $started, array(
            'status'  => 'ok',
            'message' => 'Marked cancelled. The running order still needs voiding in the POS.',
            'sale_no' => $order->sale_no,
        ), $config->id);
    }

    /**
     * Log the call and respond. Terminal - nothing runs after it.
     */
    protected function finish($provider_code, $endpoint, $http_code, $started, $body, $config_id = null)
    {
        $this->Integration_model->logInbound(
            $provider_code, $endpoint, $http_code, $this->raw_body, json_encode($body),
            (int) round((microtime(true) - $started) * 1000), $config_id
        );
        $this->response($body, $http_code);
    }

    /**
     * Log + send the response, then keep running.
     *
     * With php-fpm (Laragon's nginx profile) fastcgi_finish_request() closes the
     * connection cleanly. Under Apache mod_php there is no equivalent, so the
     * socket stays open until ingest finishes - which is a few INSERTs. The
     * thing that actually protects us from an impatient aggregator is the
     * idempotency key, not this flush.
     */
    protected function ack($endpoint, $provider_code, $http_code, $started, $body, $config_id = null)
    {
        $this->Integration_model->logInbound(
            $provider_code, $endpoint, $http_code, $this->raw_body, json_encode($body),
            (int) round((microtime(true) - $started) * 1000), $config_id
        );

        ignore_user_abort(true);

        $this->response($body, $http_code, true);   // buffer it, do not exit
        $this->output->_display();                  // send it now
        $this->output->set_output('');              // so CI does not send it twice

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            @flush();
        }
    }
}
