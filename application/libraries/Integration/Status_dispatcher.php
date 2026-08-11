<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * POS status change  ->  outbound event on the queue.
 *
 * This is the "POS me order complete karein to udher bhi update ho jaye" half.
 *
 * Deliberately a SINGLE function called from a few places rather than scattered
 * `if ($is_talabat)` blocks in Sale.php. That is what keeps app #2 from having
 * to touch Sale.php at all.
 *
 * Two hard rules:
 *   1. NEVER make the outbound HTTP call here. Enqueue only. If Talabat is slow
 *      the cashier's screen must not freeze - Integration_cron drains the queue.
 *   2. NEVER let anything thrown in here reach the POS. A broken integration
 *      must not be able to stop a bill being settled. Everything is wrapped.
 *
 * Linking note: the ingestor tags tbl_kitchen_sales with channel_code /
 * external_order_id / integration_order_id, but the POS builds tbl_sales from
 * self_order_content at settle time, which carries no channel columns. So the
 * lookup here is by sale_no (indexed), and on first match the channel columns
 * are backfilled onto tbl_sales - which is what lets existing reports filter by
 * channel afterwards.
 */
class Status_dispatcher
{
    /** @var CI_Controller */
    protected $CI;

    /** Statuses that mean "this order is finished"; never re-enqueued after. */
    protected $terminal = array('COMPLETED', 'CANCELLED', 'REJECTED');

    public function __construct()
    {
        $this->CI = & get_instance();
        $this->CI->load->model('Integration_model');
        $this->CI->load->library('Integration/Integration_manager', null, 'Integration_manager');
    }

    /**
     * A sale in tbl_sales changed state.
     *
     * @param  int    $sale_id           tbl_sales.id
     * @param  string $canonical_status  RECEIVED|ACCEPTED|REJECTED|PREPARING|READY|PICKED_UP|COMPLETED|CANCELLED
     * @return bool   true when an event was enqueued
     */
    public function on_status_change($sale_id, $canonical_status)
    {
        try {
            return $this->dispatch($sale_id, $canonical_status);
        } catch (Exception $e) {
            // The POS must not care. Leave a trace and move on.
            log_message('error', 'Status_dispatcher: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Same, keyed by the kitchen sale (used before a bill exists - e.g. the POS
     * Accept button, which acts on a running order that has no tbl_sales row).
     *
     * @return bool
     */
    public function on_kitchen_status_change($kitchen_sale_id, $canonical_status)
    {
        try {
            $order = $this->CI->Integration_model->getOrderByKitchenSaleId($kitchen_sale_id);
            return $order ? $this->push($order, $canonical_status, null) : false;
        } catch (Exception $e) {
            log_message('error', 'Status_dispatcher: '.$e->getMessage());
            return false;
        }
    }

    /* ================================================================ private */

    protected function dispatch($sale_id, $canonical_status)
    {
        $sale_id = (int) $sale_id;
        if ($sale_id <= 0) {
            return false;
        }

        $sale = $this->CI->db->get_where('tbl_sales', array('id' => $sale_id))->row();
        if (!$sale) {
            return false;
        }

        // Fast path out for the 99% of sales that are not channel orders:
        // one indexed lookup and done.
        $order = null;
        if (!empty($sale->integration_order_id)) {
            $order = $this->CI->Integration_model->getOrderById($sale->integration_order_id);
        }
        if (!$order && !empty($sale->sale_no)) {
            $order = $this->CI->db->get_where('tbl_integration_orders', array('sale_no' => $sale->sale_no))->row();
        }
        if (!$order) {
            return false;
        }

        return $this->push($order, $canonical_status, $sale);
    }

    protected function push($order, $canonical_status, $sale)
    {
        // Already finished - a second settle-ish event must not re-notify.
        if (in_array($order->canonical_status, $this->terminal, true)) {
            return false;
        }

        $config = $this->CI->Integration_manager->config_by_id($order->config_id);

        // Backfill the channel tags onto the bill even when the channel is now
        // disabled: the sale really did come from there and the reports should
        // say so. Only the outbound push is gated.
        if ($sale) {
            $this->backfill_sale($sale, $order);
        }

        $update = array('canonical_status' => $canonical_status);
        if ($canonical_status === 'COMPLETED') {
            $update['completed_at_utc'] = gmdate('Y-m-d H:i:s');
        } elseif ($canonical_status === 'ACCEPTED') {
            $update['accepted_at_utc'] = gmdate('Y-m-d H:i:s');
        }
        if ($sale && empty($order->sale_id)) {
            $update['sale_id'] = (int) $sale->id;
        }
        $this->CI->Integration_model->updateOrder($order->id, $update);

        if (!$this->CI->Integration_manager->should_push_status($config, $canonical_status)) {
            return false;
        }
        if (!$this->CI->Integration_manager->has_capability($config, 'status_out')) {
            return false;
        }

        $this->CI->Integration_model->enqueueEvent($order->id, 'status.push', array(
            'external_order_id' => $order->external_order_id,
            'status'            => $canonical_status,
        ));
        return true;
    }

    /**
     * Copy the channel tags from the integration order onto tbl_sales, once.
     */
    protected function backfill_sale($sale, $order)
    {
        if (!empty($sale->channel_code) && !empty($sale->integration_order_id)) {
            return;
        }
        $this->CI->db->where('id', $sale->id);
        $this->CI->db->update('tbl_sales', array(
            'channel_code'         => $order->provider_code,
            'external_order_id'    => $order->external_order_id,
            'integration_order_id' => (int) $order->id,
        ));
    }
}
