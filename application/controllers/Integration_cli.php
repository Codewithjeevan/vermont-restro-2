<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI operations for the integration platform.
 *
 * Phase 2 ships no admin screens, so this is how a config is created, items are
 * mapped and orders are accepted while the UI is still phase 3 work. It is also
 * the fastest way to inspect what a webhook actually did.
 *
 * CLI ONLY - it edits credentials and accepts orders, so it must never be
 * reachable over HTTP.
 *
 *   php index.php Integration_cli help
 */
class Integration_cli extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
        $this->load->model('Integration_model');
        // third argument matters: without it CI assigns the library to a
        // lowercased property ($this->integration_manager)
        $this->load->library('Integration/Integration_manager', null, 'Integration_manager');
        require_once APPPATH.'libraries/Integration/Integration_crypto.php';
    }

    public function index()
    {
        $this->help();
    }

    public function help()
    {
        $this->out('Integration platform CLI');
        $this->out('');
        $this->out('  providers                                     list the provider catalogue');
        $this->out('  setup --provider=noon --company=1 --outlet=1 [options]');
        $this->out('                                                create or update an outlet config');
        $this->out('  creds --provider=noon --company=1 [options]   company-wide credentials (service account)');
        $this->out('  show --config=1                               print a config (secrets masked)');
        $this->out('  secret --config=1 [--value=xxx]               show or set the webhook secret');
        $this->out('  automap --config=1 [--prefix=SKU-] [--apply]  map their SKUs to our menu ids');
        $this->out('  map --config=1 --external=SKU-771 --menu=130  map one item');
        $this->out('  mapmod --config=1 --external=MOD-9 --modifier=1');
        $this->out('  unmapped --config=1                           SKUs seen but not mapped');
        $this->out('  orders [--limit=20]                           recent inbound orders');
        $this->out('  accept --order=TLB-1                          accept a pending order');
        $this->out('  reject --order=TLB-1 --reason=ITEM_86         reject a pending order');
        $this->out('  logs [--limit=20]                             recent integration calls');
        $this->out('');
        $this->out('Options for setup:');
        $this->out('  --store=NOON-BR-001    their store id (the webhook routing key)');
        $this->out('  --enabled=Yes|No       --sandbox=Yes|No       --auto-accept=Yes|No');
        $this->out('  --ingest-on=placed|accepted                   --prep=20');
        $this->out('  --price-mode=delivery|normal                  --price-source=provider|pos');
        $this->out('  --price-includes-tax=Yes|No');
        $this->out('  --partner=1  --payment=1  --user=1  --secret=whsec_xxx  --base-url=http://127.0.0.1:4011');
        $this->out('');
        $this->out('Options for creds (company-wide, shared by every outlet):');
        $this->out('  --login-url=...  --key-id=...  --project-code=...  --channel-identifier=...');
        $this->out('  --private-key-file=/path/to/key.pem   (or --private-key=..., file wins)');
        $this->out('  --base-url=...  --sandbox-base-url=...  --user-agent=...');
        $this->out('  --webhook-header=x-noon-token  --secret=<header value>  --webhook-style=fat|thin');
        $this->out('  --orders-path=/food/partner/v1/orders  --allowed-ips=1.2.3.4,5.6.7.8  --static-token=...');
    }

    /* ============================================================== catalogue */

    public function providers()
    {
        $rows = $this->db->get('tbl_integration_providers')->result();
        if (empty($rows)) {
            $this->out('No providers. Run Update/integration_platform_migration.sql first.');
            return;
        }
        foreach ($rows as $row) {
            $driver = $this->Integration_manager->driver($row);
            $this->out(sprintf('%-12s %-20s active=%-3s driver=%s',
                $row->code, $row->name, $row->is_active, $driver ? 'installed' : 'MISSING'));
        }
    }

    /* ================================================================= config */

    public function setup()
    {
        $args     = $this->args();
        $provider = $this->Integration_manager->provider($this->arg($args, 'provider', 'noon'));
        if (!$provider) {
            $this->out('Unknown provider.');
            return;
        }

        $company_id = (int) $this->arg($args, 'company', 1);
        $outlet_id  = (int) $this->arg($args, 'outlet', 1);

        $existing = $this->Integration_model->getConfigForOutlet($company_id, $outlet_id, $provider->id);

        $data = array(
            'company_id'  => $company_id,
            'outlet_id'   => $outlet_id,
            'provider_id' => (int) $provider->id,
        );

        $simple = array(
            'store'               => 'external_store_id',
            'enabled'             => 'is_enabled',
            'sandbox'             => 'is_sandbox',
            'auto-accept'         => 'auto_accept',
            'ingest-on'           => 'ingest_on',
            'prep'                => 'default_prep_minutes',
            'price-mode'          => 'price_mode',
            'price-source'        => 'price_source',
            'price-includes-tax'  => 'price_includes_tax',
            'partner'             => 'delivery_partner_id',
            'payment'             => 'payment_method_id',
            'user'                => 'ingest_user_id',
            'counter'             => 'counter_id',
            'push-status-on'      => 'push_status_on',
            'auto-settle'         => 'auto_settle',
        );
        foreach ($simple as $flag => $column) {
            if (isset($args[$flag])) {
                $data[$column] = $args[$flag];
            }
        }

        if (isset($args['secret'])) {
            $data['webhook_secret'] = Integration_crypto::encrypt($args['secret']);
        }

        // credentials blob: merge so --base-url does not wipe --client-id
        $creds = $existing ? Integration_crypto::decrypt_json($existing->credentials) : array();
        foreach ($this->cred_flag_map() as $flag => $key) {
            if (isset($args[$flag])) {
                $creds[$key] = $args[$flag];
            }
        }
        $pem = $this->private_key_arg($args);
        if ($pem !== '') {
            $creds['private_key'] = $pem;
        }
        if (!empty($creds)) {
            $data['credentials'] = Integration_crypto::encrypt_json($creds);
        }

        $id = $this->Integration_model->saveConfig($data, $existing ? $existing->id : null);
        $this->out(($existing ? 'Updated' : 'Created').' config #'.$id.' for '.$provider->code.' outlet '.$outlet_id);
        $this->show_config($id);
    }

    /**
     * Company-wide credentials: the service-account material (private key,
     * login URL, webhook credential) every outlet of the company shares.
     * Outlet configs override key-by-key - see Base_channel_driver.
     */
    public function creds()
    {
        $args     = $this->args();
        $provider = $this->Integration_manager->provider($this->arg($args, 'provider', 'noon'));
        if (!$provider) {
            $this->out('Unknown provider.');
            return;
        }
        $company_id = (int) $this->arg($args, 'company', 1);

        $existing = $this->Integration_model->getCompanyCredentials($company_id, $provider->id);
        if ($existing === null && !$this->db->table_exists('tbl_integration_company_credentials')) {
            $this->out('tbl_integration_company_credentials is missing. Run Update/noon_food_migration.sql first.');
            return;
        }

        $creds = ($existing && !empty($existing->credentials))
            ? Integration_crypto::decrypt_json($existing->credentials)
            : array();
        foreach ($this->cred_flag_map() as $flag => $key) {
            if (isset($args[$flag])) {
                $creds[$key] = $args[$flag];
            }
        }
        $pem = $this->private_key_arg($args);
        if ($pem !== '') {
            $creds['private_key'] = $pem;
        }

        $data = array(
            'credentials' => Integration_crypto::encrypt_json($creds),
            // changing the credentials invalidates the cached session/token
            'oauth_token'          => null,
            'oauth_expires_at_utc' => null,
        );
        if (isset($args['secret'])) {
            $data['webhook_secret'] = Integration_crypto::encrypt($args['secret']);
        }

        $id = $this->Integration_model->saveCompanyCredentials($company_id, $provider->id, $data);
        $this->out(($existing ? 'Updated' : 'Created').' company credentials #'.$id.' for '.$provider->code.' company '.$company_id);

        foreach ($creds as $key => $value) {
            if (stripos($key, 'secret') !== false || stripos($key, 'token') !== false || $key === 'private_key') {
                $creds[$key] = '***';
            }
        }
        $this->out('  credentials    '.json_encode($creds));
        $row = $this->Integration_model->getCompanyCredentials($company_id, $provider->id);
        $this->out('  webhook_secret '.($row && $row->webhook_secret ? 'set' : 'NOT SET'));
    }

    /** shared --flag => credentials-blob key map for setup() and creds() */
    protected function cred_flag_map()
    {
        return array(
            'base-url'           => 'base_url',
            'sandbox-base-url'   => 'sandbox_base_url',
            'token-url'          => 'token_url',
            'client-id'          => 'client_id',
            'client-secret'      => 'client_secret',
            'static-token'       => 'static_token',
            'login-url'          => 'login_url',
            'key-id'             => 'key_id',
            'project-code'       => 'project_code',
            'channel-identifier' => 'channel_identifier',
            'webhook-header'     => 'webhook_header',
            'webhook-style'      => 'webhook_style',
            'orders-path'        => 'orders_path',
            'user-agent'         => 'user_agent',
            'allowed-ips'        => 'allowed_ips',
            'private-key'        => 'private_key',
        );
    }

    /** --private-key-file wins over --private-key; '' when neither given */
    protected function private_key_arg($args)
    {
        $file = $this->arg($args, 'private-key-file', '');
        if ($file !== '') {
            if (!is_readable($file)) {
                $this->out('Cannot read private key file: '.$file);
                return '';
            }
            return (string) file_get_contents($file);
        }
        return '';
    }

    public function show()
    {
        $this->show_config((int) $this->arg($this->args(), 'config', 0));
    }

    public function secret()
    {
        $args      = $this->args();
        $config_id = (int) $this->arg($args, 'config', 0);
        $config    = $this->Integration_model->getConfigById($config_id);
        if (!$config) {
            $this->out('No such config.');
            return;
        }

        if (isset($args['value'])) {
            $this->Integration_model->updateConfig($config_id, array(
                'webhook_secret' => Integration_crypto::encrypt($args['value']),
            ));
            $this->out('Webhook secret updated.');
            return;
        }
        $this->out('Webhook secret: '.Integration_crypto::decrypt($config->webhook_secret));
    }

    /* =============================================================== item map */

    /**
     * Match their SKUs to our menu. Two strategies, in order:
     *   1. numeric suffix on the SKU that is a real food_menu_id (SKU-130)
     *   2. exact, case-insensitive name match
     * Everything else is left for a human - guessing a menu item is how you
     * serve the wrong food.
     */
    public function automap()
    {
        $args      = $this->args();
        $config_id = (int) $this->arg($args, 'config', 0);
        $apply     = isset($args['apply']);
        $config    = $this->Integration_model->getConfigById($config_id);
        if (!$config) {
            $this->out('No such config.');
            return;
        }

        $this->db->where('config_id', $config_id);
        $this->db->where('map_type', 'item');
        $this->db->where('food_menu_id IS NULL', null, false);
        $rows = $this->db->get('tbl_integration_item_map')->result();

        if (empty($rows)) {
            $this->out('Nothing unmapped. Send a webhook first - unmapped SKUs are recorded as they arrive.');
            return;
        }

        $menus = $this->db->get_where('tbl_food_menus', array(
            'company_id' => $config->company_id, 'del_status' => 'Live',
        ))->result();

        $by_name = array();
        $by_id   = array();
        foreach ($menus as $menu) {
            $by_name[strtolower(trim($menu->name))] = $menu;
            $by_id[(int) $menu->id] = $menu;
        }

        foreach ($rows as $row) {
            $match = null;
            $how   = '';

            if (preg_match('/(\d+)$/', $row->external_item_id, $m) && isset($by_id[(int) $m[1]])) {
                $match = $by_id[(int) $m[1]];
                $how   = 'sku-suffix';
            } elseif ($row->external_name && isset($by_name[strtolower(trim($row->external_name))])) {
                $match = $by_name[strtolower(trim($row->external_name))];
                $how   = 'name';
            }

            if (!$match) {
                $this->out(sprintf('  ?  %-14s %-34s no match', $row->external_item_id, (string) $row->external_name));
                continue;
            }

            $this->out(sprintf('  %s %-14s %-34s -> #%d %s (%s)',
                $apply ? 'OK' : '..', $row->external_item_id, (string) $row->external_name,
                $match->id, $match->name, $how));

            if ($apply) {
                $this->Integration_model->saveItemMap($config_id, $row->external_item_id, $match->id, 'item', null, $row->external_name);
            }
        }

        if (!$apply) {
            $this->out('');
            $this->out('Dry run. Re-run with --apply to write these.');
        }
    }

    public function map()
    {
        $args = $this->args();
        $this->Integration_model->saveItemMap(
            (int) $this->arg($args, 'config', 0),
            $this->arg($args, 'external', ''),
            (int) $this->arg($args, 'menu', 0),
            'item', null, $this->arg($args, 'name', '')
        );
        $this->out('Mapped '.$this->arg($args, 'external', '').' -> food_menu_id '.$this->arg($args, 'menu', 0));
    }

    public function mapmod()
    {
        $args = $this->args();
        $this->Integration_model->saveItemMap(
            (int) $this->arg($args, 'config', 0),
            $this->arg($args, 'external', ''),
            null, 'modifier',
            (int) $this->arg($args, 'modifier', 0),
            $this->arg($args, 'name', '')
        );
        $this->out('Mapped modifier '.$this->arg($args, 'external', '').' -> modifier_id '.$this->arg($args, 'modifier', 0));
    }

    public function unmapped()
    {
        $config_id = (int) $this->arg($this->args(), 'config', 0);
        $this->db->where('config_id', $config_id);
        $this->db->group_start()->where('food_menu_id IS NULL', null, false)->or_where('modifier_id IS NULL', null, false)->group_end();
        $rows = $this->db->get('tbl_integration_item_map')->result();

        $any = false;
        foreach ($rows as $row) {
            if (($row->map_type === 'item' && $row->food_menu_id) || ($row->map_type === 'modifier' && $row->modifier_id)) {
                continue;
            }
            $any = true;
            $this->out(sprintf('  %-9s %-14s %s', $row->map_type, $row->external_item_id, (string) $row->external_name));
        }
        if (!$any) {
            $this->out('Everything seen so far is mapped.');
        }
    }

    /* ================================================================ orders */

    public function orders()
    {
        $limit = (int) $this->arg($this->args(), 'limit', 20);
        $rows  = $this->Integration_model->recentOrders($limit);
        if (empty($rows)) {
            $this->out('No inbound orders yet.');
            return;
        }
        $this->out(sprintf('%-4s %-10s %-16s %-14s %-12s %-19s %s', 'id', 'provider', 'external', 'sale_no', 'status', 'received (UTC)', 'note'));
        foreach ($rows as $row) {
            $this->out(sprintf('%-4d %-10s %-16s %-14s %-12s %-19s %s',
                $row->id, $row->provider_code, $row->external_order_id,
                (string) $row->sale_no, $row->canonical_status, $row->received_at_utc,
                (string) ($row->last_error ? $row->last_error : $row->reject_reason)));
        }
    }

    /**
     * Accept a pending order: flips the kitchen sale to is_accept = 1, which is
     * what makes the POS pull it in and the KOT print. The POS button that does
     * this from the UI is phase 3; the DB effect is identical.
     */
    public function accept()
    {
        $args  = $this->args();
        $order = $this->find_order($this->arg($args, 'order', ''));
        if (!$order) {
            return;
        }
        if (!$order->kitchen_sale_id) {
            $this->out('That order was never punched (status '.$order->canonical_status.'): '.(string) $order->last_error);
            return;
        }

        $this->db->where('id', $order->kitchen_sale_id);
        $this->db->update('tbl_kitchen_sales', array(
            'is_accept'         => 1,
            'self_order_status' => 'Approved',
            'pull_update'       => 1,
            'pull_update_admin' => 1,
            'pull_update_cashier' => 1,
        ));

        $config = $this->Integration_manager->config_by_id($order->config_id);
        $this->Integration_model->updateOrder($order->id, array(
            'canonical_status' => 'ACCEPTED',
            'accepted_at_utc'  => gmdate('Y-m-d H:i:s'),
        ));
        if ($config && $this->Integration_manager->has_capability($config, 'status_out')) {
            $this->Integration_model->enqueueEvent($order->id, 'order.accept', array(
                'external_order_id' => $order->external_order_id,
                'prep_minutes'      => (int) $config->default_prep_minutes,
            ));
        }

        $this->out('Accepted '.$order->external_order_id.' ('.$order->sale_no.'). It is now a running order in the POS.');
    }

    public function reject()
    {
        $args  = $this->args();
        $order = $this->find_order($this->arg($args, 'order', ''));
        if (!$order) {
            return;
        }
        $reason = $this->arg($args, 'reason', 'REJECTED_BY_STORE');

        if ($order->kitchen_sale_id) {
            $this->db->where('id', $order->kitchen_sale_id);
            $this->db->update('tbl_kitchen_sales', array('del_status' => 'Deleted', 'is_accept' => 2));
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
        $this->out('Rejected '.$order->external_order_id.' ('.$reason.').');
    }

    public function logs()
    {
        $limit = (int) $this->arg($this->args(), 'limit', 20);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit);
        foreach ($this->db->get('tbl_integration_logs')->result() as $row) {
            $this->out(sprintf('%-19s %-3s %-10s %-3s %5sms  %s',
                $row->created_at_utc, $row->direction, (string) $row->provider_code,
                (string) $row->http_status, (string) $row->duration_ms, (string) $row->endpoint));
        }
    }

    /* =============================================================== internals */

    protected function find_order($external_id)
    {
        if ($external_id === '') {
            $this->out('Pass --order=<external order id>');
            return null;
        }
        $row = $this->db->get_where('tbl_integration_orders', array('external_order_id' => $external_id))->row();
        if (!$row) {
            $this->out('No inbound order with external id '.$external_id);
            return null;
        }
        return $row;
    }

    protected function show_config($id)
    {
        $config = $this->Integration_model->getConfigById($id);
        if (!$config) {
            $this->out('No such config.');
            return;
        }
        $creds = Integration_crypto::decrypt_json($config->credentials);
        foreach ($creds as $key => $value) {
            if (stripos($key, 'secret') !== false || stripos($key, 'token') !== false || $key === 'private_key') {
                $creds[$key] = '***';
            }
        }
        foreach ($config as $key => $value) {
            if ($key === 'credentials' || $key === 'webhook_secret' || $key === 'oauth_token' || $key === 'provider') {
                continue;
            }
            $this->out(sprintf('  %-22s %s', $key, is_scalar($value) ? (string) $value : ''));
        }
        $this->out(sprintf('  %-22s %s', 'webhook_secret', $config->webhook_secret ? 'set' : 'NOT SET'));
        $this->out(sprintf('  %-22s %s', 'credentials', $creds ? json_encode($creds) : 'none'));
    }

    /**
     * Parse --key=value / --flag out of argv. CodeIgniter's CLI router only
     * passes positional segments, so flags have to be read from argv directly.
     */
    protected function args()
    {
        $out  = array();
        $argv = isset($GLOBALS['argv']) ? $GLOBALS['argv'] : array();
        foreach ($argv as $arg) {
            if (strpos($arg, '--') !== 0) {
                continue;
            }
            $arg = substr($arg, 2);
            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg, 2);
                $out[$key] = $value;
            } else {
                $out[$arg] = true;
            }
        }
        return $out;
    }

    protected function arg($args, $key, $default = '')
    {
        return (isset($args[$key]) && $args[$key] !== true) ? $args[$key] : $default;
    }

    protected function out($line)
    {
        fwrite(STDOUT, $line.PHP_EOL);
    }
}
