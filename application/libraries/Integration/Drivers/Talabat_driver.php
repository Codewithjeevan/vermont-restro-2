<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/Integration/Base_channel_driver.php';

/**
 * Talabat (Delivery Hero MENA) channel driver.
 *
 * THE ONLY FILE IN THIS PLATFORM THAT KNOWS TALABAT'S JSON.
 *
 * ---------------------------------------------------------------------------
 * Payload shape: provisional.
 * ---------------------------------------------------------------------------
 * Talabat's direct API is partner-gated, so the mapping below is written
 * against the publicly documented Delivery Hero middleware order shape
 * (token / code / products[].remoteCode / price.grandTotal / selectedToppings).
 * The same shape is what tools/mock-talabat emits, so the whole path is
 * testable today.
 *
 * When real credentials arrive, ONLY three things in this file change:
 *   1. verify_webhook()  - their real header + signing scheme
 *   2. normalize_order() - field names, if they differ
 *   3. $status_map + the endpoint paths in the outbound methods
 * Nothing outside this file moves. That is the test of the architecture.
 *
 * If the client goes through a middleware instead (Deliverect / Grubtech /
 * Otter), this file is replaced by Deliverect_driver.php and again nothing
 * else changes.
 */
class Talabat_driver extends Base_channel_driver
{
    /** header carrying the HMAC of the raw body */
    const SIG_HEADER = 'x-talabat-signature';

    /** header carrying the unix timestamp used in the signed string */
    const TS_HEADER = 'x-talabat-timestamp';

    /** canonical status -> their vocabulary. Lives here, never in shared code. */
    protected $status_map = array(
        'ACCEPTED'  => 'order_accepted',
        'REJECTED'  => 'order_rejected',
        'PREPARING' => 'preparation_started',
        'READY'     => 'ready_for_pickup',
        'PICKED_UP' => 'picked_up',
        'COMPLETED' => 'delivered',
        'CANCELLED' => 'cancelled',
    );

    public function code()
    {
        return 'talabat';
    }

    public function capabilities()
    {
        return array('order_in', 'status_out', 'store_status');
    }

    /* ================================================================= inbound */

    /**
     * HMAC-SHA256 over the raw body, keyed with the shared webhook secret.
     * When a timestamp header is present the signed string is "<ts>.<body>",
     * which is what makes the replay window meaningful - without binding the
     * timestamp into the signature an attacker could just edit the header.
     */
    public function verify_webhook($headers, $raw_body, $config)
    {
        $secret = $this->webhook_secret($config);
        if ($secret === '') {
            return false;   // unconfigured secret must fail closed, never open
        }

        $signature = isset($headers[self::SIG_HEADER]) ? $headers[self::SIG_HEADER] : '';
        if ($signature === '') {
            return false;
        }

        $timestamp = isset($headers[self::TS_HEADER]) ? $headers[self::TS_HEADER] : '';
        if ($timestamp !== '' && !$this->within_replay_window($timestamp, 300)) {
            return false;
        }

        $signed_payload = ($timestamp !== '') ? $timestamp.'.'.$raw_body : $raw_body;
        return $this->signature_matches($this->hmac_hex($signed_payload, $secret), $signature);
    }

    /**
     * Cheap classification, done before any expensive work so an irrelevant
     * event costs one json_decode and nothing else.
     */
    public function parse_event($raw_body)
    {
        $payload = json_decode($raw_body, true);
        if (!is_array($payload)) {
            return array('type' => 'unknown', 'external_order_id' => '', 'external_order_no' => '', 'external_store_id' => '', 'status' => null);
        }

        $raw_type = strtolower((string) self::dig($payload, 'eventType', self::dig($payload, 'event', '')));

        // Talabat sends the full order on the "new order" event; a store that
        // accepts on their tablet emits a second event. ingest_on decides which
        // one actually punches - see Integration_manager::should_ingest_on().
        $map = array(
            'order.created'   => 'order.created',
            'order_created'   => 'order.created',
            'neworder'        => 'order.created',
            'order.accepted'  => 'order.accepted',
            'order_accepted'  => 'order.accepted',
            'order.cancelled' => 'order.cancelled',
            'order_cancelled' => 'order.cancelled',
            'order.status'    => 'order.status',
            'ping'            => 'ping',
        );
        $type = isset($map[$raw_type]) ? $map[$raw_type] : 'unknown';

        // a bare order payload with no event field is a new order
        if ($type === 'unknown' && $raw_type === '' && self::dig($payload, 'token') !== null) {
            $type = 'order.created';
        }

        return array(
            'type'              => $type,
            'external_order_id' => (string) self::dig($payload, 'token', self::dig($payload, 'code', '')),
            'external_order_no' => (string) self::dig($payload, 'code', self::dig($payload, 'shortCode', '')),
            'external_store_id' => (string) self::dig($payload, 'platformRestaurant.id', ''),
            'status'            => self::dig($payload, 'status'),
        );
    }

    /**
     * Talabat payload -> Canonical Order DTO.
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

        $external_id = (string) self::dig($payload, 'token', self::dig($payload, 'code', ''));
        if ($external_id === '') {
            return array('ok' => false, 'error' => 'Payload has no order token', 'dto' => array());
        }

        $products = self::dig($payload, 'products', array());
        if (!is_array($products) || empty($products)) {
            return array('ok' => false, 'error' => 'Payload has no products', 'dto' => array());
        }

        $items = array();
        foreach ($products as $product) {
            $modifiers = array();
            $toppings  = self::dig($product, 'selectedToppings', array());
            if (is_array($toppings)) {
                foreach ($toppings as $topping) {
                    $modifiers[] = array(
                        'external_id' => (string) self::dig($topping, 'remoteCode', self::dig($topping, 'id', '')),
                        'name'        => (string) self::dig($topping, 'name', ''),
                        'price'       => self::num(self::dig($topping, 'price', 0)),
                        'qty'         => max(1, (int) self::num(self::dig($topping, 'quantity', 1), 1)),
                    );
                }
            }

            $items[] = array(
                'external_item_id' => (string) self::dig($product, 'remoteCode', self::dig($product, 'id', '')),
                'menu_name'        => (string) self::dig($product, 'name', ''),
                'qty'              => max(1, (int) self::num(self::dig($product, 'quantity', 1), 1)),
                'unit_price'       => self::num(self::dig($product, 'unitPrice', null), null),
                'note'             => (string) self::dig($product, 'comment', ''),
                'modifiers'        => $modifiers,
            );
        }

        // delivery fees and discounts arrive as arrays of line objects
        $delivery = 0.0;
        $fees = self::dig($payload, 'price.deliveryFees', array());
        if (is_array($fees)) {
            foreach ($fees as $fee) {
                $delivery += self::num(self::dig($fee, 'value', self::dig($fee, 'amount', 0)));
            }
        }
        $discount = 0.0;
        $discounts = self::dig($payload, 'price.discounts', array());
        if (is_array($discounts)) {
            foreach ($discounts as $d) {
                $discount += self::num(self::dig($d, 'amount', self::dig($d, 'value', 0)));
            }
        }

        $expedition = strtolower((string) self::dig($payload, 'expeditionType', 'delivery'));
        $order_type = ($expedition === 'pickup' || $expedition === 'takeaway') ? 2 : 3;

        $payment_status = strtolower((string) self::dig($payload, 'payment.status', ''));
        $payment_type   = strtolower((string) self::dig($payload, 'payment.type', ''));
        $is_prepaid     = ($payment_status === 'paid' || $payment_type === 'online' || $payment_type === 'prepaid');

        $address_parts = array_filter(array(
            (string) self::dig($payload, 'delivery.address.street', ''),
            (string) self::dig($payload, 'delivery.address.number', ''),
            (string) self::dig($payload, 'delivery.address.building', ''),
            (string) self::dig($payload, 'delivery.address.city', ''),
        ), 'strlen');

        $customer_name = trim(
            (string) self::dig($payload, 'customer.firstName', '').' '.
            (string) self::dig($payload, 'customer.lastName', '')
        );

        $dto = array(
            'provider_code'      => $this->code(),
            'external_order_id'  => $external_id,
            'external_order_no'  => (string) self::dig($payload, 'code', self::dig($payload, 'shortCode', $external_id)),
            'external_store_id'  => (string) self::dig($payload, 'platformRestaurant.id', ''),
            'outlet_id'          => (int) $config->outlet_id,
            'company_id'         => (int) $config->company_id,
            'order_type'         => $order_type,
            'placed_at_utc'      => self::to_utc(self::dig($payload, 'createdAt')),
            'pickup_at_utc'      => self::dig($payload, 'preOrder') ? self::to_utc(self::dig($payload, 'delivery.expectedDeliveryTime')) : null,
            'is_prepaid'         => $is_prepaid,
            'payment_method_id'  => $config->payment_method_id ? (int) $config->payment_method_id : null,
            'currency'           => (string) self::dig($payload, 'localInfo.currencySymbol', 'AED'),
            'price_includes_tax' => (isset($config->price_includes_tax) && $config->price_includes_tax === 'Yes'),
            'note'               => (string) self::dig($payload, 'comments.customerComment', ''),
            'customer' => array(
                'name'    => $customer_name !== '' ? $customer_name : 'Talabat Customer',
                'phone'   => (string) self::dig($payload, 'customer.mobilePhone', ''),
                'address' => implode(', ', $address_parts),
                'lat'     => self::dig($payload, 'delivery.address.latitude'),
                'lng'     => self::dig($payload, 'delivery.address.longitude'),
            ),
            'items'   => $items,
            'charges' => array(
                'delivery' => round($delivery, 2),
                'service'  => round(self::num(self::dig($payload, 'price.serviceFee', 0)), 2),
                'discount' => round($discount, 2),
                'tip'      => round(self::num(self::dig($payload, 'price.riderTip', 0)), 2),
            ),
            'totals' => array(
                'sub_total'     => round(self::num(self::dig($payload, 'price.subTotal', 0)), 2),
                'tax'           => round(self::num(self::dig($payload, 'price.vatTotal', 0)), 2),
                'total_payable' => round(self::num(self::dig($payload, 'price.grandTotal', 0)), 2),
            ),
            'raw' => json_encode($payload),
        );

        return array('ok' => true, 'error' => '', 'dto' => $dto);
    }

    /* ================================================================ outbound */

    public function accept_order($external_id, $prep_minutes, $config)
    {
        return $this->call('POST', '/orders/'.rawurlencode($external_id).'/accept', array(
            'remoteResponse' => array(
                'remoteOrderId' => $external_id,
                'remoteResponse' => 'accepted',
            ),
            'preparationTimeMinutes' => (int) $prep_minutes,
        ), $config);
    }

    public function reject_order($external_id, $reason_code, $config)
    {
        return $this->call('POST', '/orders/'.rawurlencode($external_id).'/reject', array(
            'acceptanceStatus' => 'rejected',
            'reason'           => (string) $reason_code,
        ), $config);
    }

    public function push_status($external_id, $canonical_status, $config)
    {
        if (!isset($this->status_map[$canonical_status])) {
            return array('ok' => false, 'http_status' => 0, 'error' => 'No Talabat status for '.$canonical_status, 'response' => null);
        }
        return $this->call('POST', '/orders/'.rawurlencode($external_id).'/status', array(
            'status'    => $this->status_map[$canonical_status],
            'occurredAt' => gmdate('c'),
        ), $config);
    }

    public function set_store_status($external_store_id, $is_open, $config)
    {
        return $this->call('PUT', '/chains/stores/'.rawurlencode($external_store_id).'/availability', array(
            'availabilityState' => $is_open ? 'OPEN' : 'CLOSED',
            'closingReason'     => $is_open ? null : 'BUSY',
        ), $config);
    }

    /* ================================================================= private */

    /**
     * One authenticated outbound call. All logging, timeouts and token refresh
     * are inherited from Base_channel_driver.
     */
    protected function call($method, $path, $body, $config)
    {
        $base = $this->base_url($config);
        if ($base === '') {
            return array('ok' => false, 'http_status' => 0, 'error' => 'No base_url configured for Talabat', 'response' => null);
        }

        $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
        $token   = $this->oauth_token($config);
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $res = $this->http($method, $base.$path, $headers, $body, $config);

        return array(
            'ok'          => $res['ok'],
            'http_status' => $res['http_status'],
            'error'       => $res['error'],
            'response'    => $res['json'] !== null ? $res['json'] : $res['body'],
        );
    }
}
