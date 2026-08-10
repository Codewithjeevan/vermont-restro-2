<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Every database read/write the integration platform needs.
 *
 * Deliberately provider-agnostic: nothing in here knows what "Talabat" is.
 */
class Integration_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ================================================================ providers */

    public function getProviderByCode($code)
    {
        return $this->db->get_where('tbl_integration_providers', array('code' => $code))->row();
    }

    public function getProviderById($id)
    {
        return $this->db->get_where('tbl_integration_providers', array('id' => (int) $id))->row();
    }

    public function getActiveProviders()
    {
        $this->db->order_by('sort_order', 'ASC');
        return $this->db->get_where('tbl_integration_providers', array('is_active' => 'Yes'))->result();
    }

    /* ================================================================== configs */

    /**
     * The resolution path for an inbound webhook: the payload carries the
     * provider's own store id and nothing else, so this is what turns it into
     * a company_id / outlet_id.
     */
    public function getConfigByStore($provider_id, $external_store_id)
    {
        return $this->db->get_where('tbl_integration_configs', array(
            'provider_id'       => (int) $provider_id,
            'external_store_id' => $external_store_id,
        ))->row();
    }

    public function getConfigById($id)
    {
        return $this->db->get_where('tbl_integration_configs', array('id' => (int) $id))->row();
    }

    public function getConfigForOutlet($company_id, $outlet_id, $provider_id)
    {
        return $this->db->get_where('tbl_integration_configs', array(
            'company_id'  => (int) $company_id,
            'outlet_id'   => (int) $outlet_id,
            'provider_id' => (int) $provider_id,
        ))->row();
    }

    /**
     * The only config for a provider, when exactly one exists. Used as a
     * fallback in sandbox so a test payload with an unknown store id still
     * lands somewhere instead of silently disappearing.
     */
    public function getSoleSandboxConfig($provider_id)
    {
        $rows = $this->db->get_where('tbl_integration_configs', array(
            'provider_id' => (int) $provider_id,
            'is_sandbox'  => 'Yes',
            'is_enabled'  => 'Yes',
        ))->result();
        return (count($rows) === 1) ? $rows[0] : null;
    }

    public function saveConfig($data, $id = null)
    {
        $now = gmdate('Y-m-d H:i:s');
        if ($id) {
            $data['updated_at_utc'] = $now;
            $this->db->where('id', (int) $id);
            $this->db->update('tbl_integration_configs', $data);
            return (int) $id;
        }
        $data['created_at_utc'] = $now;
        $data['updated_at_utc'] = $now;
        $this->db->insert('tbl_integration_configs', $data);
        return (int) $this->db->insert_id();
    }

    public function updateConfig($id, $data)
    {
        $data['updated_at_utc'] = gmdate('Y-m-d H:i:s');
        $this->db->where('id', (int) $id);
        $this->db->update('tbl_integration_configs', $data);
    }

    /* =============================================================== item map */

    /**
     * Resolve a batch of the provider's SKUs to our ids in one query.
     *
     * @param  int    $config_id
     * @param  array  $external_ids
     * @param  string $map_type  'item' | 'modifier'
     * @return array  external_id => row
     */
    public function getItemMap($config_id, $external_ids, $map_type = 'item')
    {
        $external_ids = array_values(array_unique(array_filter(array_map('strval', $external_ids), 'strlen')));
        if (empty($external_ids)) {
            return array();
        }
        $this->db->where('config_id', (int) $config_id);
        $this->db->where('map_type', $map_type);
        $this->db->where_in('external_item_id', $external_ids);
        $rows = $this->db->get('tbl_integration_item_map')->result();

        $out = array();
        foreach ($rows as $row) {
            $out[(string) $row->external_item_id] = $row;
        }
        return $out;
    }

    /**
     * Remember the provider's name for a SKU even when it is not mapped yet -
     * the mapping screen needs something human-readable to show.
     */
    public function touchUnmapped($config_id, $external_item_id, $external_name, $map_type = 'item')
    {
        $existing = $this->db->get_where('tbl_integration_item_map', array(
            'config_id'        => (int) $config_id,
            'map_type'         => $map_type,
            'external_item_id' => (string) $external_item_id,
        ))->row();

        if ($existing) {
            if ($external_name !== '' && $existing->external_name !== $external_name) {
                $this->db->where('id', $existing->id);
                $this->db->update('tbl_integration_item_map', array('external_name' => $external_name));
            }
            return (int) $existing->id;
        }

        $this->db->insert('tbl_integration_item_map', array(
            'config_id'        => (int) $config_id,
            'map_type'         => $map_type,
            'external_item_id' => (string) $external_item_id,
            'external_name'    => $external_name,
        ));
        return (int) $this->db->insert_id();
    }

    public function saveItemMap($config_id, $external_item_id, $food_menu_id, $map_type = 'item', $modifier_id = null, $external_name = null)
    {
        $this->touchUnmapped($config_id, $external_item_id, (string) $external_name, $map_type);
        $this->db->where(array(
            'config_id'        => (int) $config_id,
            'map_type'         => $map_type,
            'external_item_id' => (string) $external_item_id,
        ));
        $this->db->update('tbl_integration_item_map', array(
            'food_menu_id' => $food_menu_id ? (int) $food_menu_id : null,
            'modifier_id'  => $modifier_id ? (int) $modifier_id : null,
        ));
    }

    /* ================================================================== orders */

    /**
     * The idempotency gate. Inserts the order row, or reports that this
     * external id was already seen.
     *
     * Relies on the uq_ext UNIQUE key rather than a SELECT-then-INSERT, so two
     * concurrent retries of the same webhook cannot both get through.
     *
     * @return array ['is_new'=>bool,'row'=>object|null]
     */
    public function claimOrder($data)
    {
        $data['received_at_utc'] = gmdate('Y-m-d H:i:s');

        // db_debug is on in this install, so a duplicate-key error would render
        // a CI error page instead of returning. Suppress just for this insert.
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $ok = $this->db->insert('tbl_integration_orders', $data);
        $this->db->db_debug = $prev_debug;

        if ($ok && $this->db->insert_id()) {
            return array('is_new' => true, 'row' => $this->getOrderById($this->db->insert_id()));
        }

        return array(
            'is_new' => false,
            'row'    => $this->getOrderByExternalId($data['provider_code'], $data['external_order_id']),
        );
    }

    public function getOrderById($id)
    {
        return $this->db->get_where('tbl_integration_orders', array('id' => (int) $id))->row();
    }

    public function getOrderByExternalId($provider_code, $external_order_id)
    {
        return $this->db->get_where('tbl_integration_orders', array(
            'provider_code'     => $provider_code,
            'external_order_id' => $external_order_id,
        ))->row();
    }

    public function getOrderByKitchenSaleId($kitchen_sale_id)
    {
        return $this->db->get_where('tbl_integration_orders', array('kitchen_sale_id' => (int) $kitchen_sale_id))->row();
    }

    public function updateOrder($id, $data)
    {
        $this->db->where('id', (int) $id);
        $this->db->update('tbl_integration_orders', $data);
    }

    public function recentOrders($limit = 50, $outlet_id = null)
    {
        if ($outlet_id) {
            $this->db->where('outlet_id', (int) $outlet_id);
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get('tbl_integration_orders')->result();
    }

    /* ============================================================ event queue */

    /**
     * Enqueue an outbound call. Never sends it here: an aggregator being slow
     * must not freeze the cashier's screen.
     */
    public function enqueueEvent($integration_order_id, $event_type, $payload = array())
    {
        $this->db->insert('tbl_integration_events', array(
            'integration_order_id' => (int) $integration_order_id,
            'direction'            => 'out',
            'event_type'           => $event_type,
            'payload'              => json_encode($payload),
            'status'               => 'pending',
            'attempts'             => 0,
            'next_attempt_at_utc'  => gmdate('Y-m-d H:i:s'),
            'created_at_utc'       => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }

    public function dueEvents($limit = 25)
    {
        $this->db->where('status', 'pending');
        $this->db->where('next_attempt_at_utc <=', gmdate('Y-m-d H:i:s'));
        $this->db->order_by('id', 'ASC');
        $this->db->limit((int) $limit);
        return $this->db->get('tbl_integration_events')->result();
    }

    public function updateEvent($id, $data)
    {
        $this->db->where('id', (int) $id);
        $this->db->update('tbl_integration_events', $data);
    }

    /* ================================================================ helpers */

    /**
     * One synthetic customer per provider per company. Aggregator phone numbers
     * are masked and change per order, so a real customer record per order would
     * be noise; the drop address goes on the sale instead.
     */
    public function getOrCreateChannelCustomer($company_id, $display_name)
    {
        $row = $this->db->get_where('tbl_customers', array(
            'company_id' => (int) $company_id,
            'name'       => $display_name,
            'del_status' => 'Live',
        ))->row();

        if ($row) {
            return (int) $row->id;
        }

        $this->db->insert('tbl_customers', array(
            'name'       => $display_name,
            'phone'      => '',
            'email'      => '',
            'address'    => '',
            'company_id' => (int) $company_id,
            'user_id'    => 1,
            'del_status' => 'Live',
        ));
        return (int) $this->db->insert_id();
    }

    /**
     * Menu rows needed by the ingestor, in one query.
     * @return array food_menu_id => row
     */
    public function getMenusByIds($ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return array();
        }
        $this->db->where_in('id', $ids);
        $rows = $this->db->get('tbl_food_menus')->result();

        $out = array();
        foreach ($rows as $row) {
            $out[(int) $row->id] = $row;
        }
        return $out;
    }

    public function getModifiersByIds($ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return array();
        }
        $this->db->where_in('id', $ids);
        $rows = $this->db->get('tbl_modifiers')->result();

        $out = array();
        foreach ($rows as $row) {
            $out[(int) $row->id] = $row;
        }
        return $out;
    }

    /**
     * Log an inbound call. Outbound logging lives in Base_channel_driver, which
     * has the provider code to hand; inbound is logged before a driver exists.
     */
    public function logInbound($provider_code, $endpoint, $http_status, $request_body, $response_body, $duration_ms, $config_id = null)
    {
        require_once APPPATH.'libraries/Integration/Base_channel_driver.php';
        $this->db->insert('tbl_integration_logs', array(
            'config_id'      => $config_id ? (int) $config_id : null,
            'provider_code'  => $provider_code,
            'direction'      => 'in',
            'endpoint'       => substr((string) $endpoint, 0, 190),
            'http_status'    => $http_status ? (int) $http_status : null,
            'request_body'   => Base_channel_driver::redact($request_body),
            'response_body'  => Base_channel_driver::redact($response_body),
            'duration_ms'    => $duration_ms,
            'created_at_utc' => gmdate('Y-m-d H:i:s'),
        ));
        return (int) $this->db->insert_id();
    }
}
