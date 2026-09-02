<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The contract every ordering channel implements.
 *
 * A driver is the ONLY file allowed to know a provider's JSON, its header
 * names, its status vocabulary or its endpoints. Everything downstream of
 * normalize_order() speaks the Canonical Order DTO
 * (see docs/integration_platform_talabat_uae.md section 2.1) and nothing else.
 *
 * $config is always the tbl_integration_configs row for this outlet, already
 * decrypted - see Integration_manager::hydrate_config().
 */
interface Channel_driver_interface
{
    /** @return string provider code, e.g. 'talabat' */
    public function code();

    /** @return array subset of: order_in, status_out, menu_push, store_status, item_availability */
    public function capabilities();

    /* ---------------------------------------------------------------- inbound */

    /**
     * Authenticate an inbound webhook. Return FALSE and the request is rejected
     * with 401 before anything is written.
     *
     * @param  array  $headers   normalised: lowercase keys
     * @param  string $raw_body  the exact bytes received - never re-encoded JSON
     * @param  object $config
     * @return bool
     */
    public function verify_webhook($headers, $raw_body, $config);

    /**
     * Cheap classification of the event, done before any expensive work.
     *
     * @param  string $raw_body
     * @return array  ['type'=>'order.created'|'order.cancelled'|'order.status'|'ping'|'unknown',
     *                 'external_order_id'=>string, 'external_order_no'=>string,
     *                 'external_store_id'=>string, 'status'=>string|null]
     */
    public function parse_event($raw_body);

    /**
     * Provider payload -> Canonical Order DTO.
     *
     * Must not touch the database and must not throw: on a payload it cannot
     * map, return ['ok'=>false,'error'=>'...'].
     *
     * @param  array|object $payload  decoded body
     * @param  object       $config
     * @return array  ['ok'=>bool,'error'=>string,'dto'=>array]
     */
    public function normalize_order($payload, $config);

    /* --------------------------------------------------------------- outbound */

    /** @return array ['ok'=>bool,'http_status'=>int,'error'=>string,'response'=>mixed] */
    public function accept_order($external_id, $prep_minutes, $config);

    /** @return array same shape as accept_order() */
    public function reject_order($external_id, $reason_code, $config);

    /**
     * @param string $canonical_status one of RECEIVED ACCEPTED REJECTED PREPARING
     *                                 READY PICKED_UP COMPLETED CANCELLED
     * @return array same shape as accept_order()
     */
    public function push_status($external_id, $canonical_status, $config);

    /* --------------------------- optional - declare support via capabilities() */

    public function push_menu($outlet_id, $config);
    public function set_store_status($external_store_id, $is_open, $config);
    public function set_item_availability($external_item_id, $is_available, $config);

    /**
     * Thin-webhook support (optional - Base_channel_driver defaults it).
     *
     * A fat-webhook provider returns ['ok'=>true,'payload'=>null] - NULL means
     * "normalize the webhook body you already have". A thin-webhook provider
     * GETs the full order here; Integration_api persists the fetched payload
     * onto the order row and normalizes it instead. Runs after the ACK and
     * after the idempotency claim, so at most once per order.
     *
     * @return array ['ok'=>bool, 'payload'=>array|null, 'error'=>string]
     */
    public function fetch_order($external_id, $config);
}
