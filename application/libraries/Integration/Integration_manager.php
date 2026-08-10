<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/Integration/Channel_driver_interface.php';
require_once APPPATH.'libraries/Integration/Base_channel_driver.php';
require_once APPPATH.'libraries/Integration/Integration_crypto.php';

/**
 * Registry + factory. The only place that turns a provider code into an object.
 *
 * Nothing above this class names a provider, and nothing below it knows there
 * are others - which is what keeps adding app #2 to one INSERT and one file.
 */
class Integration_manager
{
    /** @var CI_Controller */
    protected $CI;

    /** @var array code => Channel_driver_interface */
    protected $drivers = array();

    public function __construct()
    {
        $this->CI = & get_instance();
        $this->CI->load->model('Integration_model');
    }

    /* =============================================================== providers */

    public function provider($code)
    {
        return $this->CI->Integration_model->getProviderByCode($code);
    }

    /**
     * Instantiate the driver for a provider row, or NULL when the file or class
     * is missing - a catalogue row without a driver is a normal state (the
     * settings grid lists providers we have not built yet).
     *
     * @return Channel_driver_interface|null
     */
    public function driver($provider)
    {
        if (!$provider || empty($provider->driver_class)) {
            return null;
        }
        $code = $provider->code;
        if (isset($this->drivers[$code])) {
            return $this->drivers[$code];
        }

        $class = $provider->driver_class;
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class)) {
            return null;   // catalogue rows are admin-managed; still never include a computed path blindly
        }

        $file = APPPATH.'libraries/Integration/Drivers/'.$class.'.php';
        if (!file_exists($file)) {
            return null;
        }
        require_once $file;
        if (!class_exists($class)) {
            return null;
        }

        $instance = new $class();
        if (!($instance instanceof Channel_driver_interface)) {
            return null;
        }

        $this->drivers[$code] = $instance;
        return $instance;
    }

    public function driver_by_code($code)
    {
        return $this->driver($this->provider($code));
    }

    /* ================================================================= configs */

    /**
     * Resolve the config for an inbound webhook.
     *
     * The payload carries the provider's store id and nothing else, so that is
     * the lookup key. In sandbox, when exactly one enabled sandbox config exists
     * for the provider, fall back to it - otherwise a test payload with a store
     * id nobody filled in yet vanishes with no trace of why.
     *
     * @return object|null  hydrated config row
     */
    public function resolve_config($provider, $external_store_id)
    {
        if (!$provider) {
            return null;
        }
        $config = null;
        if ($external_store_id !== '' && $external_store_id !== null) {
            $config = $this->CI->Integration_model->getConfigByStore($provider->id, $external_store_id);
        }
        if (!$config) {
            $config = $this->CI->Integration_model->getSoleSandboxConfig($provider->id);
        }
        return $config ? $this->hydrate_config($config) : null;
    }

    public function config_for_outlet($company_id, $outlet_id, $provider_code)
    {
        $provider = $this->provider($provider_code);
        if (!$provider) {
            return null;
        }
        $config = $this->CI->Integration_model->getConfigForOutlet($company_id, $outlet_id, $provider->id);
        return $config ? $this->hydrate_config($config) : null;
    }

    public function config_by_id($id)
    {
        $config = $this->CI->Integration_model->getConfigById($id);
        return $config ? $this->hydrate_config($config) : null;
    }

    /**
     * Attach the provider row so a driver never has to look it up.
     * Credentials stay encrypted on the object; drivers decrypt through
     * Base_channel_driver::cred() so a var_dump of a config never leaks them.
     */
    protected function hydrate_config($config)
    {
        $config->provider = $this->CI->Integration_model->getProviderById($config->provider_id);
        $config->provider_code = $config->provider ? $config->provider->code : '';
        return $config;
    }

    /* ================================================================ switches */

    /** The on/off button, honoured identically everywhere. */
    public function is_enabled($config)
    {
        if (!$config || $config->is_enabled !== 'Yes') {
            return false;
        }
        return !($config->provider && $config->provider->is_active !== 'Yes');
    }

    public function has_capability($config, $capability)
    {
        if (!$config || !$config->provider) {
            return false;
        }
        $caps = array_map('trim', explode(',', (string) $config->provider->capabilities));
        return in_array($capability, $caps, true);
    }

    /**
     * Should this canonical status be pushed back for this outlet?
     */
    public function should_push_status($config, $canonical_status)
    {
        if (!$this->is_enabled($config)) {
            return false;
        }
        $wanted = array_map('trim', explode(',', (string) $config->push_status_on));
        return in_array($canonical_status, $wanted, true);
    }

    /**
     * Does an inbound event of this type mean "punch it now"?
     *
     * ingest_on = placed   -> punch the moment the customer orders
     * ingest_on = accepted -> punch when the order is accepted on their tablet
     *
     * Same code path either way; only this filter differs.
     */
    public function should_ingest_on($config, $event_type)
    {
        $mode = isset($config->ingest_on) ? $config->ingest_on : 'accepted';
        if ($event_type === 'order.created') {
            return $mode === 'placed';
        }
        if ($event_type === 'order.accepted') {
            return $mode === 'accepted';
        }
        return false;
    }
}
