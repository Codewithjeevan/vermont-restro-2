<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/Integration/Base_channel_driver.php';

/**
 * noon Food channel driver.
 *
 * THE ONLY FILE IN THIS PLATFORM THAT KNOWS NOON'S JSON.
 *
 * ---------------------------------------------------------------------------
 * Payload shape: provisional.
 * ---------------------------------------------------------------------------
 * noon Food publishes no API docs (the public portal documents the noon
 * MARKETPLACE - the wrong product for a restaurant POS). Everything below is
 * written against noon's platform conventions and is confined to this file:
 *
 *   - Auth: RS256 JWT signed with a service-account private key -> POST to a
 *     login endpoint -> session cookies carried on every later call, ~30-day
 *     lifetime. Handled by Base_channel_driver::session_cookie(), cached on
 *     the COMPANY credentials row (one session for all outlets).
 *   - Webhooks: push, at-least-once, wrapped in an envelope
 *       { event_type: "FOOD::ORDER_CREATED", metadata: {...}, payload: {...} }
 *     authenticated by a static header credential (no HMAC) - registered
 *     key/value pairs noon sends on every delivery.
 *   - Fat or thin webhook is unknown; both are supported. Set the cred key
 *     webhook_style = 'thin' and fetch_order() GETs the full order after the
 *     ACK; the default 'fat' normalizes the webhook body directly.
 *
 * When the real spec lands, ONLY three things in this file change:
 *   1. the field map inside normalize_order() / parse_event()
 *   2. $status_map + the endpoint paths
 *   3. verify_webhook() header names, if they differ
 * Nothing outside this file moves. That is the test of the architecture.
 *
 * Cred keys this driver reads (company blob, outlet blob overrides):
 *   login_url, private_key, key_id, project_code, channel_identifier,
 *   base_url, sandbox_base_url, user_agent, webhook_header, webhook_style,
 *   orders_path, allowed_ips, static_token (mock/testing fallback),
 *   session_ttl_days
 */
class Noon_food_driver extends Base_channel_driver
{
    /** header noon sends its static webhook credential in (cred 'webhook_header' overrides) */
    const DEFAULT_WEBHOOK_HEADER = 'x-noon-token';

    /** REST collection the outbound calls hang off (cred 'orders_path' overrides) */
    const DEFAULT_ORDERS_PATH = '/food/partner/v1/orders';

    /** every request must carry a User-Agent - noon's gateways may reject without one */
    const DEFAULT_USER_AGENT = 'trul-resto-pos/1.0';

    /** canonical status -> their vocabulary. PROVISIONAL until noon's spec lands. */
    protected $status_map = array(
        'ACCEPTED'  => 'accepted',
        'REJECTED'  => 'rejected',
        'PREPARING' => 'preparing',
        'READY'     => 'ready_for_pickup',
        'PICKED_UP' => 'picked_up',
        'COMPLETED' => 'delivered',
        'CANCELLED' => 'cancelled',
    );

    public function code()
    {
        return 'noon';
    }

    public function capabilities()
    {
        return array('order_in', 'status_out');
    }

    /* ================================================================= inbound */

    /**
     * Static header credential, compared timing-safe. No signature scheme is
     * known for noon Food, so there is nothing to HMAC - the shared secret in
     * the header is the whole guard, which is why an empty secret must fail
     * closed. An optional IP allowlist (cred 'allowed_ips', CSV) tightens it.
     * Should Phase 0 reveal an HMAC after all, the base class already has
     * hmac_hex() / signature_matches() / within_replay_window().
     */
    public function verify_webhook($headers, $raw_body, $config)
    {
        $expected = $this->webhook_secret($config);
        if ($expected === '') {
            return false;   // unconfigured secret must fail closed, never open
        }

        $header = strtolower((string) $this->cred($config, 'webhook_header', self::DEFAULT_WEBHOOK_HEADER));
        $got    = isset($headers[$header]) ? trim((string) $headers[$header]) : '';
        if ($got === '' || !hash_equals($expected, $got)) {
            return false;
        }

        return $this->ip_allowed($config);
    }

    /**
     * Optional source-IP allowlist. Empty list = skipped (the header
     * credential is the guard). REMOTE_ADDR is read here because the
     * webhook's $headers array only carries HTTP headers - behind a proxy
     * this sees the proxy, so only enable it on a direct-facing install.
     */
    protected function ip_allowed($config)
    {
        $list = trim((string) $this->cred($config, 'allowed_ips', ''));
        if ($list === '') {
            return true;
        }
        $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        if ($remote === '') {
            return false;
        }
        return in_array($remote, array_map('trim', explode(',', $list)), true);
    }

    /**
     * Unwrap the noon envelope, then classify. Tolerates an un-enveloped
     * payload too - the mock and early sandbox payloads may not wrap.
     */
    public function parse_event($raw_body)
    {
        $envelope = json_decode($raw_body, true);
        if (!is_array($envelope)) {
            return array('type' => 'unknown', 'external_order_id' => '', 'external_order_no' => '', 'external_store_id' => '', 'status' => null);
        }

        $body = (isset($envelope['payload']) && is_array($envelope['payload'])) ? $envelope['payload'] : $envelope;

        // "FOOD::ORDER_CREATED" -> "order_created"
        $raw_type = strtolower((string) self::dig($envelope, 'event_type', self::dig($envelope, 'eventType', '')));
        if (strpos($raw_type, '::') !== false) {
            $parts    = explode('::', $raw_type, 2);
            $raw_type = $parts[1];
        }

        $map = array(
            'order_created'   => 'order.created',
            'order.created'   => 'order.created',
            'neworder'        => 'order.created',
            'order_confirmed' => 'order.accepted',
            'order_accepted'  => 'order.accepted',
            'order.accepted'  => 'order.accepted',
            'order_cancelled' => 'order.cancelled',
            'order.cancelled' => 'order.cancelled',
            'cancelled'       => 'order.cancelled',
            'order_status'    => 'order.status',
            'status_update'   => 'order.status',
            'order.status'    => 'order.status',
            'ping'            => 'ping',
            'test'            => 'ping',
        );
        $type = isset($map[$raw_type]) ? $map[$raw_type] : 'unknown';

        // a bare order payload with no event field is a new order
        if ($type === 'unknown' && $raw_type === '' && $this->order_id_of($body) !== '') {
            $type = 'order.created';
        }

        return array(
            'type'              => $type,
            'external_order_id' => $this->order_id_of($body),
            'external_order_no' => $this->order_no_of($body),
            'external_store_id' => $this->store_id_of($body),
            'status'            => self::dig($body, 'status'),
        );
    }

    /**
     * Thin-webhook support: cred webhook_style='thin' means the webhook only
     * carried an order reference and the details live behind a GET. Runs after
     * the ACK and the idempotency claim - see Integration_api::ingest_now().
     * The default 'fat' returns payload NULL = "use the webhook body".
     */
    public function fetch_order($external_id, $config)
    {
        if (strtolower((string) $this->cred($config, 'webhook_style', 'fat')) !== 'thin') {
            return array('ok' => true, 'payload' => null, 'error' => '');
        }

        $res = $this->call('GET', $this->order_path($config, $external_id), '', $config);
        if (!$res['ok'] || !is_array($res['response'])) {
            return array(
                'ok'      => false,
                'payload' => null,
                'error'   => $res['error'] !== '' ? $res['error'] : 'Order fetch returned no JSON',
            );
        }

        // tolerate a wrapped detail response too
        $body = $res['response'];
        foreach (array('payload', 'data', 'order') as $wrap) {
            if (isset($body[$wrap]) && is_array($body[$wrap])) {
                $body = $body[$wrap];
                break;
            }
        }
        return array('ok' => true, 'payload' => $body, 'error' => '');
    }

    /**
     * noon payload -> Canonical Order DTO. PROVISIONAL field map - every path
     * here is a Phase 0 question, and only this method changes when the real
     * spec lands. Two rows are load-bearing and cannot be guessed: the store
     * id (or the order routes to no outlet) and the item SKU (or every order
     * rejects as UNMAPPED_ITEM).
     *
     * Never touches the database: item mapping and customer resolution belong
     * to Order_ingestor, so this stays a pure function that is trivial to test.
     */
    public function normalize_order($payload, $config)
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        if (!is_array($payload)) {
            return array('ok' => false, 'error' => 'Payload is not valid JSON', 'dto' => array());
        }

        // tolerate being handed the whole envelope instead of the business body
        if (isset($payload['payload']) && is_array($payload['payload'])
            && (isset($payload['event_type']) || isset($payload['metadata']))) {
            $payload = $payload['payload'];
        }

        $external_id = $this->order_id_of($payload);
        if ($external_id === '') {
            return array('ok' => false, 'error' => 'Payload has no order id', 'dto' => array());
        }

        $products = self::dig($payload, 'items', self::dig($payload, 'products', array()));
        if (!is_array($products) || empty($products)) {
            return array('ok' => false, 'error' => 'Payload has no items', 'dto' => array());
        }

        $items = array();
        foreach ($products as $product) {
            $modifiers = array();
            $options   = self::dig($product, 'options', self::dig($product, 'choices', self::dig($product, 'modifiers', array())));
            if (is_array($options)) {
                foreach ($options as $option) {
                    $modifiers[] = array(
                        'external_id' => (string) self::dig($option, 'sku', self::dig($option, 'code', self::dig($option, 'id', ''))),
                        'name'        => (string) self::dig($option, 'name', self::dig($option, 'title', '')),
                        'price'       => self::num(self::dig($option, 'price', 0)),
                        'qty'         => max(1, (int) self::num(self::dig($option, 'qty', self::dig($option, 'quantity', 1)), 1)),
                    );
                }
            }

            $items[] = array(
                'external_item_id' => (string) self::dig($product, 'sku', self::dig($product, 'partner_sku', self::dig($product, 'id', ''))),
                'menu_name'        => (string) self::dig($product, 'name', self::dig($product, 'title', '')),
                'qty'              => max(1, (int) self::num(self::dig($product, 'qty', self::dig($product, 'quantity', 1)), 1)),
                'unit_price'       => self::num(self::dig($product, 'unit_price', self::dig($product, 'price', null)), null),
                'note'             => (string) self::dig($product, 'note', self::dig($product, 'comment', '')),
                'modifiers'        => $modifiers,
            );
        }

        $fulfillment = strtolower((string) self::dig($payload, 'order_type', self::dig($payload, 'fulfillment_type', self::dig($payload, 'type', 'delivery'))));
        $order_type  = in_array($fulfillment, array('pickup', 'takeaway', 'collect', 'collection'), true) ? 2 : 3;

        $payment_status = strtolower((string) self::dig($payload, 'payment.status', ''));
        $payment_method = strtolower((string) self::dig($payload, 'payment.method', self::dig($payload, 'payment_method', '')));
        $is_prepaid     = (self::dig($payload, 'payment.is_paid') === true
                           || $payment_status === 'paid'
                           || in_array($payment_method, array('online', 'prepaid', 'card_online', 'noon_pay'), true));

        $address_parts = array_filter(array(
            (string) self::dig($payload, 'customer.address.street', ''),
            (string) self::dig($payload, 'customer.address.building', ''),
            (string) self::dig($payload, 'customer.address.area', ''),
            (string) self::dig($payload, 'customer.address.city', ''),
        ), 'strlen');
        $address = (string) self::dig($payload, 'customer.address.full_address', implode(', ', $address_parts));

        $customer_name = trim((string) self::dig($payload, 'customer.name',
            trim((string) self::dig($payload, 'customer.first_name', '').' '.(string) self::dig($payload, 'customer.last_name', ''))
        ));

        $dto = array(
            'provider_code'      => $this->code(),
            'external_order_id'  => $external_id,
            // the SHORT display code: it becomes tbl_kitchen_sales.token_number,
            // the number the rider and the customer both quote at the counter
            'external_order_no'  => $this->order_no_of($payload) !== '' ? $this->order_no_of($payload) : $external_id,
            'external_store_id'  => $this->store_id_of($payload),
            'outlet_id'          => (int) $config->outlet_id,
            'company_id'         => (int) $config->company_id,
            'order_type'         => $order_type,
            'placed_at_utc'      => self::to_utc(self::dig($payload, 'created_at', self::dig($payload, 'placed_at'))),
            'pickup_at_utc'      => self::dig($payload, 'scheduled_at') ? self::to_utc(self::dig($payload, 'scheduled_at')) : null,
            'is_prepaid'         => $is_prepaid,
            'payment_method_id'  => $config->payment_method_id ? (int) $config->payment_method_id : null,
            'currency'           => (string) self::dig($payload, 'currency', self::dig($payload, 'currency_code', 'AED')),
            'price_includes_tax' => (isset($config->price_includes_tax) && $config->price_includes_tax === 'Yes'),
            'note'               => (string) self::dig($payload, 'note', self::dig($payload, 'customer_note', '')),
            'customer' => array(
                'name'    => $customer_name !== '' ? $customer_name : 'noon Customer',
                'phone'   => (string) self::dig($payload, 'customer.phone', self::dig($payload, 'customer.mobile', '')),
                'address' => $address,
                'lat'     => self::dig($payload, 'customer.address.lat', self::dig($payload, 'customer.address.latitude')),
                'lng'     => self::dig($payload, 'customer.address.lng', self::dig($payload, 'customer.address.longitude')),
            ),
            'items'   => $items,
            'charges' => array(
                'delivery' => round(self::num(self::dig($payload, 'delivery_fee', self::dig($payload, 'charges.delivery', 0))), 2),
                'service'  => round(self::num(self::dig($payload, 'service_fee', self::dig($payload, 'charges.service', 0))), 2),
                'discount' => round(self::num(self::dig($payload, 'discount', self::dig($payload, 'charges.discount', 0))), 2),
                'tip'      => round(self::num(self::dig($payload, 'tip', self::dig($payload, 'charges.tip', 0))), 2),
            ),
            'totals' => array(
                'sub_total'     => round(self::num(self::dig($payload, 'subtotal', self::dig($payload, 'sub_total', 0))), 2),
                'tax'           => round(self::num(self::dig($payload, 'vat', self::dig($payload, 'tax', 0))), 2),
                'total_payable' => round(self::num(self::dig($payload, 'total', self::dig($payload, 'grand_total', self::dig($payload, 'total_payable', 0)))), 2),
            ),
            'raw' => json_encode($payload),
        );

        return array('ok' => true, 'error' => '', 'dto' => $dto);
    }

    /* ================================================================ outbound */

    public function accept_order($external_id, $prep_minutes, $config)
    {
        $res = $this->call('POST', $this->order_path($config, $external_id, '/accept'), array(
            'preparation_time_minutes' => (int) $prep_minutes,
        ), $config);
        return $this->conflict_is_benign($res);
    }

    public function reject_order($external_id, $reason_code, $config)
    {
        $res = $this->call('POST', $this->order_path($config, $external_id, '/reject'), array(
            'reason' => (string) $reason_code,
        ), $config);
        return $this->conflict_is_benign($res);
    }

    public function push_status($external_id, $canonical_status, $config)
    {
        if (!isset($this->status_map[$canonical_status])) {
            return array('ok' => false, 'http_status' => 0, 'error' => 'No noon status for '.$canonical_status, 'response' => null);
        }
        return $this->call('POST', $this->order_path($config, $external_id, '/status'), array(
            'status'      => $this->status_map[$canonical_status],
            'occurred_at' => gmdate('c'),
        ), $config);
    }

    /**
     * Cheap authenticated probe for the settings screen's Test button. noon
     * declares no store_status capability, so the base's set_store_status()
     * would answer "unsupported" without ever exercising auth - this does the
     * login round-trip instead. Integration::testConnection() prefers this
     * method when a driver has it.
     */
    public function test_connection($config)
    {
        $cookie = $this->session_cookie($config);
        if ($cookie !== '') {
            return array('ok' => true, 'http_status' => 200, 'error' => '', 'response' => 'Login OK - session established');
        }
        $static = (string) $this->cred($config, 'static_token', '');
        if ($static !== '') {
            return array('ok' => true, 'http_status' => 0, 'error' => '', 'response' => 'Static token configured (no login round-trip attempted)');
        }
        return array('ok' => false, 'http_status' => 0, 'error' => 'Login failed - check login_url, key_id and private_key', 'response' => null);
    }

    /* ================================================================= private */

    /**
     * The accept-after-reject race: the SLA sweeper can auto-reject an order
     * while its order.accept event is still in the retry queue (backoff runs
     * ~19h). The provider answers such a late call with a conflict - that is
     * terminal and benign, not a failure to retry five more times.
     */
    protected function conflict_is_benign($res)
    {
        if (!$res['ok'] && (int) $res['http_status'] === 409) {
            return array('ok' => true, 'http_status' => 409, 'error' => '', 'response' => $res['response']);
        }
        return $res;
    }

    /** /food/partner/v1/orders/<id><suffix>, collection overridable via cred 'orders_path' */
    protected function order_path($config, $external_id, $suffix = '')
    {
        $collection = rtrim((string) $this->cred($config, 'orders_path', self::DEFAULT_ORDERS_PATH), '/');
        return $collection.'/'.rawurlencode($external_id).$suffix;
    }

    /**
     * One authenticated outbound call: session cookie (or static token in
     * sandbox/mock) plus the mandatory User-Agent, both flowing through the
     * base http() headers verbatim - no curl changes needed.
     */
    protected function call($method, $path, $body, $config)
    {
        $base = $this->base_url($config);
        if ($base === '') {
            return array('ok' => false, 'http_status' => 0, 'error' => 'No base_url configured for noon', 'response' => null);
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'User-Agent'   => (string) $this->cred($config, 'user_agent', self::DEFAULT_USER_AGENT),
        );

        $cookie = $this->session_cookie($config);
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        } else {
            $static = (string) $this->cred($config, 'static_token', '');
            if ($static !== '') {
                $headers['Authorization'] = 'Bearer '.$static;
            }
        }

        $res = $this->http($method, $base.$path, $headers, $body, $config);

        return array(
            'ok'          => $res['ok'],
            'http_status' => $res['http_status'],
            'error'       => $res['error'],
            'response'    => $res['json'] !== null ? $res['json'] : $res['body'],
        );
    }

    /* ------------------------------------------------ provisional id extractors */

    protected function order_id_of($body)
    {
        return (string) self::dig($body, 'order_nr', self::dig($body, 'order_id', self::dig($body, 'id', '')));
    }

    protected function order_no_of($body)
    {
        return (string) self::dig($body, 'order_code', self::dig($body, 'short_code', self::dig($body, 'code', '')));
    }

    protected function store_id_of($body)
    {
        return (string) self::dig($body, 'outlet_code', self::dig($body, 'branch_id', self::dig($body, 'store_id', self::dig($body, 'outlet_id', ''))));
    }
}
