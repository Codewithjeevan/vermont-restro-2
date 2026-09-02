<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin screens for the integration platform.
 *
 *   Setting -> Integrations      settings()  provider grid + per-provider drawer
 *   Setting -> Item Mapping      itemMap()   our menu <-> their SKUs
 *   Setting -> Channel Orders    orderLog()  inbound orders, raw payload, retry
 *
 * Plus the AJAX endpoints the POS incoming-orders widget calls.
 *
 * The ACCESS_* ids below are hard-coded because that is how this codebase does
 * access control (see Setting::__construct). They MUST match the rows inserted
 * by Update/integration_platform_ui_migration.sql - change one, change both.
 */
class Integration extends Cl_Controller
{
    /** tbl_access parent ids, mirrored in Update/integration_platform_ui_migration.sql */
    const ACCESS_SETTINGS  = 362;
    const ACCESS_ITEM_MAP  = 365;
    const ACCESS_ORDER_LOG = 368;

    /**
     * POS-facing endpoints. Deliberately NOT behind a tbl_access id: accepting
     * an incoming order is a normal counter action, exactly like accepting a
     * self-order today. Gating it behind a Settings permission would mean every
     * cashier needs Settings rights. A live session for the outlet is the check.
     */
    protected $pos_endpoints = array('posPendingOrders', 'posAcceptOrder', 'posRejectOrder');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Common_model');
        $this->load->model('Integration_model');
        $this->load->library('Integration/Integration_manager', null, 'Integration_manager');
        $this->Common_model->setDefaultTimezone();

        if (!$this->session->has_userdata('user_id')) {
            if ($this->isAjaxSegment()) {
                $this->jsonOut(array('status' => 'error', 'message' => 'Not logged in'), 401);
            }
            redirect('Authentication/index');
        }

        $segment_2 = $this->uri->segment(2);

        if (in_array($segment_2, $this->pos_endpoints, true)) {
            return;   // session-only, see $pos_endpoints
        }

        //start check access function
        $controller = '';
        $function   = '';

        if ($segment_2 == 'settings' || $segment_2 == 'index' || $segment_2 == '') {
            $controller = (string) self::ACCESS_SETTINGS;
            $function   = 'view';
        } elseif ($segment_2 == 'saveConfig' || $segment_2 == 'testConnection' || $segment_2 == 'saveCompanyCredentials') {
            $controller = (string) self::ACCESS_SETTINGS;
            $function   = 'update';
        } elseif ($segment_2 == 'itemMap') {
            $controller = (string) self::ACCESS_ITEM_MAP;
            $function   = 'view';
        } elseif ($segment_2 == 'saveItemMap' || $segment_2 == 'autoMap') {
            $controller = (string) self::ACCESS_ITEM_MAP;
            $function   = 'update';
        } elseif ($segment_2 == 'orderLog' || $segment_2 == 'orderDetail') {
            $controller = (string) self::ACCESS_ORDER_LOG;
            $function   = 'view';
        } elseif ($segment_2 == 'retryEvent' || $segment_2 == 'acceptOrder' || $segment_2 == 'rejectOrder') {
            $controller = (string) self::ACCESS_ORDER_LOG;
            $function   = 'update';
        } else {
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }

        if (!checkAccess($controller, $function)) {
            if ($this->isAjaxSegment()) {
                $this->jsonOut(array('status' => 'error', 'message' => lang('menu_not_permit_access')), 403);
            }
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        //end check access function
    }

    public function index()
    {
        $this->settings();
    }

    /* ============================================================== settings */

    public function settings()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $outlet_id  = (int) $this->selectedOutlet();

        $data = array();
        $data['outlets']     = $this->Integration_model->getOutlets($company_id);
        $data['outlet_id']   = $outlet_id;
        $data['providers']   = $this->Integration_model->getVisibleProviders();
        $data['configs']     = $this->Integration_model->getConfigsForOutlet($company_id, $outlet_id);
        $data['stats']       = $this->Integration_model->getChannelStats($company_id, $outlet_id);
        $data['dead_events'] = $this->Integration_model->getDeadEventCount($company_id);

        // company-wide credentials (service accounts shared by every outlet)
        $data['company_creds'] = array();
        foreach ($data['providers'] as $provider) {
            $data['company_creds'][(int) $provider->id] =
                $this->Integration_model->getCompanyCredentials($company_id, $provider->id);
        }

        // options for the per-provider drawer
        $data['payment_methods']   = $this->Integration_model->getPaymentMethods($company_id);
        $data['delivery_partners'] = $this->Integration_model->getDeliveryPartners($company_id);
        $data['counters']          = $this->Integration_model->getCounters($company_id, $outlet_id);
        $data['users']             = $this->Integration_model->getUsers($company_id);

        // a driver may exist in the catalogue with no file on disk yet
        $data['driver_installed'] = array();
        $data['unmapped_counts']  = array();
        foreach ($data['providers'] as $provider) {
            $data['driver_installed'][$provider->code] = $this->Integration_manager->driver($provider) ? true : false;
            $config = isset($data['configs'][(int) $provider->id]) ? $data['configs'][(int) $provider->id] : null;
            $data['unmapped_counts'][$provider->code] = $config
                ? $this->Integration_model->countUnmapped($config->id)
                : 0;
        }

        $data['webhook_base'] = rtrim(base_url(), '/').'/api/integration/';

        $data['main_content'] = $this->load->view('integration/settings', $data, true);
        $this->load->view('userHome', $data);
    }

    public function saveConfig()
    {
        require_once APPPATH.'libraries/Integration/Integration_crypto.php';

        $company_id  = (int) $this->session->userdata('company_id');
        $outlet_id   = (int) $this->post('outlet_id');
        $provider_id = (int) $this->post('provider_id');

        if ($outlet_id <= 0 || $provider_id <= 0) {
            $this->session->set_flashdata('exception_1', lang('integration_save_failed'));
            redirect('Integration/settings');
        }

        $existing = $this->Integration_model->getConfigForOutlet($company_id, $outlet_id, $provider_id);

        $data = array(
            'company_id'           => $company_id,
            'outlet_id'            => $outlet_id,
            'provider_id'          => $provider_id,
            'is_enabled'           => $this->enumPost('is_enabled', array('Yes', 'No'), 'No'),
            'is_sandbox'           => $this->enumPost('is_sandbox', array('Yes', 'No'), 'Yes'),
            'auto_accept'          => $this->enumPost('auto_accept', array('Yes', 'No'), 'No'),
            'auto_settle'          => $this->enumPost('auto_settle', array('Yes', 'No'), 'No'),
            'ingest_on'            => $this->enumPost('ingest_on', array('placed', 'accepted'), 'accepted'),
            'price_mode'           => $this->enumPost('price_mode', array('delivery', 'normal'), 'delivery'),
            'price_source'         => $this->enumPost('price_source', array('provider', 'pos'), 'provider'),
            'price_includes_tax'   => $this->enumPost('price_includes_tax', array('Yes', 'No'), 'Yes'),
            'external_store_id'    => $this->post('external_store_id'),
            'default_prep_minutes' => max(1, (int) $this->post('default_prep_minutes')),
            'payment_method_id'    => $this->post('payment_method_id') ? (int) $this->post('payment_method_id') : null,
            'delivery_partner_id'  => $this->post('delivery_partner_id') ? (int) $this->post('delivery_partner_id') : null,
            'ingest_user_id'       => $this->post('ingest_user_id') ? (int) $this->post('ingest_user_id') : null,
            'counter_id'           => (int) $this->post('counter_id'),
            'push_status_on'       => $this->pushStatusPost(),
        );

        // Secrets are write-only from the UI: an empty field means "leave it",
        // never "clear it". Otherwise every save with a masked field would wipe
        // the credentials.
        $webhook_secret = $this->post('webhook_secret');
        if ($webhook_secret !== '') {
            $data['webhook_secret'] = Integration_crypto::encrypt($webhook_secret);
        }

        $creds = $existing ? Integration_crypto::decrypt_json($existing->credentials) : array();
        foreach (array('base_url', 'sandbox_base_url', 'token_url', 'client_id', 'scope',
                       'login_url', 'key_id', 'project_code', 'channel_identifier',
                       'webhook_header', 'webhook_style', 'orders_path', 'user_agent', 'allowed_ips') as $key) {
            $value = $this->post($key);
            if ($value !== '') {
                $creds[$key] = $value;
            }
        }
        // secret-valued keys: write-only, never round-tripped to the form
        foreach (array('client_secret', 'private_key', 'static_token') as $key) {
            $value = $this->post($key);
            if ($value !== '') {
                $creds[$key] = $value;
            }
        }
        $data['credentials'] = Integration_crypto::encrypt_json($creds);

        // changing the credentials invalidates any cached bearer token
        $data['oauth_token']          = null;
        $data['oauth_expires_at_utc'] = null;

        $this->Integration_model->saveConfig($data, $existing ? $existing->id : null);

        $this->session->set_flashdata('exception', lang('integration_saved'));
        redirect('Integration/settings?outlet_id='.$outlet_id);
    }

    /**
     * Company-wide credentials for one provider - the service-account material
     * (private key, login URL, webhook credential) every outlet of the company
     * shares. Outlet configs override key-by-key; see
     * Base_channel_driver::credentials().
     */
    public function saveCompanyCredentials()
    {
        require_once APPPATH.'libraries/Integration/Integration_crypto.php';

        $company_id  = (int) $this->session->userdata('company_id');
        $provider_id = (int) $this->post('provider_id');
        $outlet_id   = (int) $this->post('outlet_id');   // only for the redirect

        if ($provider_id <= 0) {
            $this->session->set_flashdata('exception_1', lang('integration_save_failed'));
            redirect('Integration/settings');
        }

        $existing = $this->Integration_model->getCompanyCredentials($company_id, $provider_id);

        $creds = ($existing && !empty($existing->credentials))
            ? Integration_crypto::decrypt_json($existing->credentials)
            : array();

        foreach (array('base_url', 'sandbox_base_url', 'login_url', 'token_url', 'client_id', 'scope',
                       'key_id', 'project_code', 'channel_identifier',
                       'webhook_header', 'webhook_style', 'orders_path', 'user_agent', 'allowed_ips') as $key) {
            $value = $this->post($key);
            if ($value !== '') {
                $creds[$key] = $value;
            }
        }
        // secret-valued keys: write-only, an empty field means "leave it"
        foreach (array('client_secret', 'private_key', 'static_token') as $key) {
            $value = $this->post($key);
            if ($value !== '') {
                $creds[$key] = $value;
            }
        }

        $data = array('credentials' => Integration_crypto::encrypt_json($creds));

        $webhook_secret = $this->post('webhook_secret');
        if ($webhook_secret !== '') {
            $data['webhook_secret'] = Integration_crypto::encrypt($webhook_secret);
        }

        // changing the credentials invalidates the cached session/token
        $data['oauth_token']          = null;
        $data['oauth_expires_at_utc'] = null;

        $this->Integration_model->saveCompanyCredentials($company_id, $provider_id, $data);

        $this->session->set_flashdata('exception', lang('integration_saved'));
        redirect('Integration/settings'.($outlet_id > 0 ? '?outlet_id='.$outlet_id : ''));
    }

    /**
     * Fetch a token (or ping the base URL) so the operator gets a yes/no before
     * a real order arrives at 8pm on a Friday.
     */
    public function testConnection()
    {
        $config = $this->Integration_manager->config_by_id((int) $this->post('config_id'));
        if (!$config || (int) $config->company_id !== (int) $this->session->userdata('company_id')) {
            $this->jsonOut(array('status' => 'error', 'message' => lang('integration_no_config')), 404);
        }

        $driver = $this->Integration_manager->driver($config->provider);
        if (!$driver) {
            $this->jsonOut(array('status' => 'error', 'message' => lang('integration_driver_missing')));
        }

        // A driver may expose a dedicated probe (noon: a login round-trip,
        // since it declares no store_status capability). Otherwise store_status
        // is the cheapest authenticated round trip most channels expose.
        if (method_exists($driver, 'test_connection')) {
            $result = $driver->test_connection($config);
        } else {
            $result = $driver->set_store_status($config->external_store_id, true, $config);
        }

        $this->jsonOut(array(
            'status'      => !empty($result['ok']) ? 'ok' : 'error',
            'http_status' => isset($result['http_status']) ? $result['http_status'] : 0,
            'message'     => !empty($result['ok'])
                ? lang('integration_test_ok')
                : (isset($result['error']) && $result['error'] !== '' ? $result['error'] : lang('integration_test_failed')),
        ));
    }

    /* ============================================================== item map */

    public function itemMap()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $config_id  = (int) $this->input->get('config_id');
        $filter     = $this->input->get('filter');
        $filter     = in_array($filter, array('all', 'unmapped', 'mapped'), true) ? $filter : 'all';

        $outlet_id = (int) $this->selectedOutlet();
        $configs   = $this->Integration_model->getConfigsForOutlet($company_id, $outlet_id);

        if ($config_id <= 0) {
            $first     = reset($configs);
            $config_id = $first ? (int) $first->id : 0;
        }

        $config = $config_id ? $this->Integration_manager->config_by_id($config_id) : null;
        if ($config && (int) $config->company_id !== $company_id) {
            $config = null;   // never let a config id from another company be opened
        }

        $data = array();
        $data['outlets']    = $this->Integration_model->getOutlets($company_id);
        $data['outlet_id']  = $outlet_id;
        $data['configs']    = $configs;
        $data['providers']  = $this->Integration_model->getVisibleProviders();
        $data['config']     = $config;
        $data['filter']     = $filter;
        $data['rows']       = $config ? $this->Integration_model->getItemMapRows($config->id, $filter) : array();
        $data['menus']      = $this->Integration_model->getMenusForCompany($company_id);
        $data['modifiers']  = $this->Integration_model->getModifiersForCompany($company_id);

        $data['main_content'] = $this->load->view('integration/item_map', $data, true);
        $this->load->view('userHome', $data);
    }

    public function saveItemMap()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $config     = $this->Integration_manager->config_by_id((int) $this->post('config_id'));
        if (!$config || (int) $config->company_id !== $company_id) {
            $this->jsonOut(array('status' => 'error', 'message' => lang('integration_no_config')), 404);
        }

        $map_id = (int) $this->post('map_id');
        $row    = $this->db->get_where('tbl_integration_item_map', array(
            'id' => $map_id, 'config_id' => $config->id,
        ))->row();
        if (!$row) {
            $this->jsonOut(array('status' => 'error', 'message' => 'Unknown mapping row'), 404);
        }

        $target = (int) $this->post('target_id');
        if ($row->map_type === 'modifier') {
            $this->db->where('id', $map_id);
            $this->db->update('tbl_integration_item_map', array('modifier_id' => $target > 0 ? $target : null));
        } else {
            $this->db->where('id', $map_id);
            $this->db->update('tbl_integration_item_map', array('food_menu_id' => $target > 0 ? $target : null));
        }

        $this->jsonOut(array(
            'status'   => 'ok',
            'unmapped' => $this->Integration_model->countUnmapped($config->id),
        ));
    }

    /**
     * Same two strategies as the CLI: SKU numeric suffix, then exact name.
     * Anything else is left for a human - guessing a menu item is how you serve
     * the wrong food.
     */
    public function autoMap()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $config     = $this->Integration_manager->config_by_id((int) $this->post('config_id'));
        if (!$config || (int) $config->company_id !== $company_id) {
            $this->jsonOut(array('status' => 'error', 'message' => lang('integration_no_config')), 404);
        }

        $menus     = $this->Integration_model->getMenusForCompany($company_id);
        $modifiers = $this->Integration_model->getModifiersForCompany($company_id);

        $menu_by_name = $menu_by_id = array();
        foreach ($menus as $menu) {
            $menu_by_name[strtolower(trim($menu->name))] = $menu;
            $menu_by_id[(int) $menu->id] = $menu;
        }
        $mod_by_name = $mod_by_id = array();
        foreach ($modifiers as $mod) {
            $mod_by_name[strtolower(trim($mod->name))] = $mod;
            $mod_by_id[(int) $mod->id] = $mod;
        }

        $mapped = 0;
        foreach ($this->Integration_model->getItemMapRows($config->id, 'unmapped') as $row) {
            $is_mod   = ($row->map_type === 'modifier');
            $by_id    = $is_mod ? $mod_by_id : $menu_by_id;
            $by_name  = $is_mod ? $mod_by_name : $menu_by_name;

            $match = null;
            if (preg_match('/(\d+)$/', $row->external_item_id, $m) && isset($by_id[(int) $m[1]])) {
                $match = $by_id[(int) $m[1]];
            } elseif ($row->external_name && isset($by_name[strtolower(trim($row->external_name))])) {
                $match = $by_name[strtolower(trim($row->external_name))];
            }
            if (!$match) {
                continue;
            }

            $this->db->where('id', $row->id);
            $this->db->update('tbl_integration_item_map', $is_mod
                ? array('modifier_id' => (int) $match->id)
                : array('food_menu_id' => (int) $match->id));
            $mapped++;
        }

        $this->jsonOut(array(
            'status'   => 'ok',
            'mapped'   => $mapped,
            'unmapped' => $this->Integration_model->countUnmapped($config->id),
        ));
    }

    /* ============================================================= order log */

    public function orderLog()
    {
        $company_id = (int) $this->session->userdata('company_id');

        $filters = array(
            'outlet_id'     => (int) $this->input->get('outlet_id'),
            'provider_code' => $this->input->get('provider_code'),
            'status'        => $this->input->get('status'),
            'date_from'     => $this->input->get('date_from'),
            'date_to'       => $this->input->get('date_to'),
            'q'             => $this->input->get('q'),
        );

        $data = array();
        $data['outlets']   = $this->Integration_model->getOutlets($company_id);
        $data['providers'] = $this->Integration_model->getVisibleProviders();
        $data['filters']   = $filters;
        $data['orders']    = $this->Integration_model->getOrdersFiltered($company_id, $filters, 300);
        $data['statuses']  = array('RECEIVED', 'ACCEPTED', 'REJECTED', 'PREPARING', 'READY', 'PICKED_UP', 'COMPLETED', 'CANCELLED');

        $data['main_content'] = $this->load->view('integration/order_log', $data, true);
        $this->load->view('userHome', $data);
    }

    public function orderDetail()
    {
        $order = $this->ownedOrder((int) $this->input->get('id'));

        $pretty = $order->raw_payload;
        $decoded = json_decode((string) $order->raw_payload, true);
        if (is_array($decoded)) {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $events = array();
        foreach ($this->Integration_model->getEventsForOrder($order->id) as $event) {
            $events[] = array(
                'id'          => (int) $event->id,
                'event_type'  => $event->event_type,
                'status'      => $event->status,
                'attempts'    => (int) $event->attempts,
                'last_error'  => $event->last_error,
                'next_at'     => $event->next_attempt_at_utc,
                'created_at'  => $event->created_at_utc,
            );
        }

        $this->jsonOut(array(
            'status' => 'ok',
            'order'  => array(
                'id'                => (int) $order->id,
                'provider_code'     => $order->provider_code,
                'external_order_id' => $order->external_order_id,
                'external_order_no' => $order->external_order_no,
                'sale_no'           => $order->sale_no,
                'canonical_status'  => $order->canonical_status,
                'reject_reason'     => $order->reject_reason,
                'last_error'        => $order->last_error,
                'received_at_utc'   => $order->received_at_utc,
                'ingested_at_utc'   => $order->ingested_at_utc,
                'accepted_at_utc'   => $order->accepted_at_utc,
                'completed_at_utc'  => $order->completed_at_utc,
            ),
            'raw_payload' => $pretty,
            'events'      => $events,
        ));
    }

    /**
     * Put a dead or failed event back on the queue. Does not send it here -
     * Integration_cron does, on its next pass.
     */
    public function retryEvent()
    {
        $event_id = (int) $this->post('event_id');
        $event    = $this->db->get_where('tbl_integration_events', array('id' => $event_id))->row();
        if (!$event) {
            $this->jsonOut(array('status' => 'error', 'message' => 'Unknown event'), 404);
        }
        $this->ownedOrder((int) $event->integration_order_id);

        $this->Integration_model->updateEvent($event_id, array(
            'status'              => 'pending',
            'attempts'            => 0,
            'last_error'          => null,
            'next_attempt_at_utc' => gmdate('Y-m-d H:i:s'),
        ));

        $this->jsonOut(array('status' => 'ok', 'message' => lang('integration_retry_queued')));
    }

    public function acceptOrder()
    {
        $this->jsonOut($this->doAccept((int) $this->post('order_id')));
    }

    public function rejectOrder()
    {
        $this->jsonOut($this->doReject((int) $this->post('order_id'), $this->post('reason')));
    }

    /* ====================================================== POS widget (ajax) */

    public function posPendingOrders()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $outlet_id  = (int) $this->session->userdata('outlet_id');
        if ($outlet_id <= 0) {
            $this->jsonOut(array('status' => 'ok', 'orders' => array()));
        }

        $orders = $this->Integration_model->getPendingChannelOrders($company_id, $outlet_id);

        $out = array();
        foreach ($orders as $order) {
            $out[] = array(
                'id'            => (int) $order->id,
                'provider'      => $order->provider_code,
                'order_no'      => $order->external_order_no ? $order->external_order_no : $order->external_order_id,
                'sale_no'       => $order->sale_no,
                'total_payable' => (float) $order->total_payable,
                'total_items'   => (int) $order->total_items,
                'address'       => (string) $order->del_address,
                'order_type'    => (int) $order->order_type,
                // minutes since it arrived - the number the staff actually care about
                'waiting_mins'  => (int) floor((time() - strtotime($order->received_at_utc.' UTC')) / 60),
            );
        }

        $this->jsonOut(array('status' => 'ok', 'orders' => $out));
    }

    public function posAcceptOrder()
    {
        $this->jsonOut($this->doAccept((int) $this->post('order_id')));
    }

    public function posRejectOrder()
    {
        $this->jsonOut($this->doReject((int) $this->post('order_id'), $this->post('reason')));
    }

    /* ================================================================ shared */

    /**
     * Accept: flip the kitchen sale to a running order, tell the channel.
     * Same DB effect as the POS Accept button on a self-order.
     */
    protected function doAccept($order_id)
    {
        $order = $this->ownedOrder($order_id);

        if (!$order->kitchen_sale_id) {
            return array('status' => 'error', 'message' => lang('integration_not_punched').' '.(string) $order->last_error);
        }
        if ($order->canonical_status !== 'RECEIVED') {
            return array('status' => 'error', 'message' => lang('integration_already_handled').' ('.$order->canonical_status.')');
        }

        $this->db->where('id', $order->kitchen_sale_id);
        $this->db->update('tbl_kitchen_sales', array(
            'is_accept'           => 1,
            'self_order_status'   => 'Approved',
            'pull_update'         => 1,
            'pull_update_admin'   => 1,
            'pull_update_cashier' => 1,
        ));

        $this->Integration_model->updateOrder($order->id, array(
            'canonical_status' => 'ACCEPTED',
            'accepted_at_utc'  => gmdate('Y-m-d H:i:s'),
        ));

        $config = $this->Integration_manager->config_by_id($order->config_id);
        if ($config && $this->Integration_manager->has_capability($config, 'status_out')) {
            $this->Integration_model->enqueueEvent($order->id, 'order.accept', array(
                'external_order_id' => $order->external_order_id,
                'prep_minutes'      => (int) $config->default_prep_minutes,
            ));
        }

        return array(
            'status'  => 'ok',
            'sale_no' => $order->sale_no,
            'message' => lang('integration_accepted').' '.$order->sale_no,
        );
    }

    /**
     * Reject: void the punched sale and tell the channel why.
     */
    protected function doReject($order_id, $reason)
    {
        $order  = $this->ownedOrder($order_id);
        $reason = $reason !== '' ? substr($reason, 0, 100) : 'REJECTED_BY_STORE';

        if ($order->canonical_status !== 'RECEIVED') {
            return array('status' => 'error', 'message' => lang('integration_already_handled').' ('.$order->canonical_status.')');
        }

        if ($order->kitchen_sale_id) {
            $this->db->where('id', $order->kitchen_sale_id);
            $this->db->update('tbl_kitchen_sales', array('del_status' => 'Deleted', 'is_accept' => 2));
            $this->db->where('sales_id', $order->kitchen_sale_id);
            $this->db->update('tbl_kitchen_sales_details', array('del_status' => 'Deleted'));
        }

        $this->Integration_model->updateOrder($order->id, array(
            'canonical_status' => 'REJECTED',
            'reject_reason'    => $reason,
        ));

        $config = $this->Integration_manager->config_by_id($order->config_id);
        if ($config && $this->Integration_manager->has_capability($config, 'status_out')) {
            $this->Integration_model->enqueueEvent($order->id, 'order.reject', array(
                'external_order_id' => $order->external_order_id,
                'reason'            => $reason,
            ));
        }

        return array('status' => 'ok', 'message' => lang('integration_reject_done'));
    }

    /**
     * Load an order and prove it belongs to the logged-in company. Every action
     * takes an id straight from the browser, so this is the only thing standing
     * between a curious user and another tenant's orders.
     */
    protected function ownedOrder($order_id)
    {
        $order = $this->Integration_model->getOrderById($order_id);
        if (!$order || (int) $order->company_id !== (int) $this->session->userdata('company_id')) {
            $this->jsonOut(array('status' => 'error', 'message' => lang('integration_no_order')), 404);
        }
        return $order;
    }

    protected function selectedOutlet()
    {
        $company_id = (int) $this->session->userdata('company_id');
        $outlets    = $this->Integration_model->getOutlets($company_id);

        $outlet_id = (int) $this->input->get('outlet_id');
        if ($outlet_id > 0) {
            foreach ($outlets as $outlet) {
                if ((int) $outlet->id === $outlet_id) {
                    return $outlet_id;   // never trust an outlet id from the query string
                }
            }
        }

        $session_outlet = (int) $this->session->userdata('outlet_id');
        if ($session_outlet > 0) {
            return $session_outlet;
        }

        // The session only carries an outlet after one has been picked
        // (Outlet::setOutletSession). Reaching Settings straight from the menu
        // before that would otherwise show an empty grid with no explanation,
        // so fall back to the company's first outlet - the dropdown still lets
        // them switch.
        return !empty($outlets) ? (int) $outlets[0]->id : 0;
    }

    protected function post($key)
    {
        $value = $this->input->post($this->security->xss_clean($key));
        return $value === null ? '' : trim(htmlspecialcharscustom($value));
    }

    protected function enumPost($key, $allowed, $default)
    {
        $value = $this->post($key);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    protected function pushStatusPost()
    {
        $allowed  = array('ACCEPTED', 'REJECTED', 'PREPARING', 'READY', 'PICKED_UP', 'COMPLETED', 'CANCELLED');
        $selected = $this->input->post('push_status_on');
        $selected = is_array($selected) ? $selected : array();

        $clean = array();
        foreach ($selected as $status) {
            if (in_array($status, $allowed, true)) {
                $clean[] = $status;
            }
        }
        return implode(',', $clean);
    }

    protected function isAjaxSegment()
    {
        $ajax = array_merge($this->pos_endpoints, array(
            'saveItemMap', 'autoMap', 'orderDetail', 'retryEvent',
            'acceptOrder', 'rejectOrder', 'testConnection',
        ));
        return in_array($this->uri->segment(2), $ajax, true);
    }

    protected function jsonOut($payload, $http_code = 200)
    {
        $this->output
            ->set_status_header($http_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
        // these are called from ajax handlers mid-flow; stop here so the caller
        // does not also render a view on top of the JSON
        $this->output->_display();
        exit;
    }
}
