<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Outbound queue worker.
 *
 * The rule this exists to enforce: NEVER make an outbound HTTP call inside a
 * POS request. If Talabat is slow the cashier's screen must not freeze. Every
 * outbound call is enqueued on tbl_integration_events and drained here.
 *
 *   php index.php Integration_cron run_queue        every 1 min
 *   php index.php Integration_cron sweep_timeouts   every 5 min
 *
 * On Windows use Task Scheduler; on Linux, cron. CLI ONLY - a queue worker
 * reachable over HTTP is a way to make the POS call arbitrary URLs.
 *
 * Scope note: this is the phase-3 worker in its minimal form. It drains the
 * queue that phase 2 already fills (accept / reject), which is what makes the
 * outbound half testable against tools/mock-talabat. The POS-side status hooks
 * (Status_dispatcher wired into Sale::update_order_status_ajax) are still to
 * come - see docs/integration_platform_talabat_uae.md section 6.
 */
class Integration_cron extends CI_Controller
{
    /** attempt N -> minutes until the next try. Past the end: dead. */
    protected $backoff = array(1, 5, 15, 60, 360, 720);

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
        $this->load->model('Integration_model');
        $this->load->library('Integration/Integration_manager', null, 'Integration_manager');
    }

    public function index()
    {
        $this->run_queue();
    }

    /**
     * Drain due events, FIFO, in a small batch so one slow provider cannot
     * starve the others for a whole minute.
     */
    public function run_queue()
    {
        $events = $this->Integration_model->dueEvents(25);
        if (empty($events)) {
            $this->out('Nothing due.');
            return;
        }

        foreach ($events as $event) {
            $this->send_one($event);
        }
    }

    protected function send_one($event)
    {
        $order = $this->Integration_model->getOrderById($event->integration_order_id);
        if (!$order) {
            $this->fail($event, 'Orphan event: no integration order '.$event->integration_order_id, true);
            return;
        }

        $config = $this->Integration_manager->config_by_id($order->config_id);

        // the on/off button is honoured here too - a disabled channel must not
        // keep calling out days after someone switched it off
        if (!$this->Integration_manager->is_enabled($config)) {
            $this->Integration_model->updateEvent($event->id, array(
                'status'     => 'dead',
                'last_error' => 'Integration disabled for this outlet',
            ));
            $this->out(sprintf('  #%d %-14s SKIPPED (disabled)', $event->id, $event->event_type));
            return;
        }

        $driver = $this->Integration_manager->driver($config->provider);
        if (!$driver) {
            $this->fail($event, 'No driver for '.$order->provider_code, true);
            return;
        }

        $payload = json_decode((string) $event->payload, true);
        $payload = is_array($payload) ? $payload : array();
        $external_id = isset($payload['external_order_id']) ? $payload['external_order_id'] : $order->external_order_id;

        switch ($event->event_type) {
            case 'order.accept':
                $prep = isset($payload['prep_minutes']) ? (int) $payload['prep_minutes'] : (int) $config->default_prep_minutes;
                $res  = $driver->accept_order($external_id, $prep, $config);
                break;

            case 'order.reject':
                $reason = isset($payload['reason']) ? $payload['reason'] : 'REJECTED_BY_STORE';
                $res    = $driver->reject_order($external_id, $reason, $config);
                break;

            case 'status.push':
                $status = isset($payload['status']) ? $payload['status'] : null;
                if (!$status) {
                    $this->fail($event, 'status.push with no status', true);
                    return;
                }
                $res = $driver->push_status($external_id, $status, $config);
                break;

            default:
                $this->fail($event, 'Unknown event type '.$event->event_type, true);
                return;
        }

        if (!empty($res['ok'])) {
            $this->Integration_model->updateEvent($event->id, array(
                'status'     => 'sent',
                'attempts'   => (int) $event->attempts + 1,
                'last_error' => null,
            ));
            $this->out(sprintf('  #%d %-14s %-22s OK (%d)', $event->id, $event->event_type, $external_id, (int) $res['http_status']));
            return;
        }

        $this->fail($event, ($res['error'] !== '' ? $res['error'] : 'HTTP '.$res['http_status']), false);
    }

    /**
     * Exponential backoff, then dead-letter. Six consecutive failures is a
     * visible flag in the order log, not a silent give-up.
     */
    protected function fail($event, $error, $terminal)
    {
        $attempts = (int) $event->attempts + 1;

        if ($terminal || $attempts >= count($this->backoff)) {
            $this->Integration_model->updateEvent($event->id, array(
                'status'     => 'dead',
                'attempts'   => $attempts,
                'last_error' => substr($error, 0, 255),
            ));
            $this->out(sprintf('  #%d %-14s DEAD after %d attempts: %s', $event->id, $event->event_type, $attempts, $error));
            return;
        }

        $minutes = $this->backoff[$attempts - 1];
        $this->Integration_model->updateEvent($event->id, array(
            'status'              => 'pending',
            'attempts'            => $attempts,
            'last_error'          => substr($error, 0, 255),
            'next_attempt_at_utc' => gmdate('Y-m-d H:i:s', time() + ($minutes * 60)),
        ));
        $this->out(sprintf('  #%d %-14s retry in %dm (attempt %d): %s', $event->id, $event->event_type, $minutes, $attempts, $error));
    }

    /**
     * An order nobody accepted within the provider's SLA is auto-rejected.
     * An unacknowledged order is worse than a rejected one: the customer waits,
     * the rating drops, and the aggregator marks the store unreliable.
     */
    public function sweep_timeouts()
    {
        $this->db->where('canonical_status', 'RECEIVED');
        $this->db->where('kitchen_sale_id IS NOT NULL', null, false);
        $orders = $this->db->get('tbl_integration_orders')->result();

        $swept = 0;
        foreach ($orders as $order) {
            $config = $this->Integration_manager->config_by_id($order->config_id);
            if (!$this->Integration_manager->is_enabled($config)) {
                continue;
            }

            $sla_minutes = max(5, (int) $config->default_prep_minutes);
            $age = (time() - strtotime($order->received_at_utc.' UTC')) / 60;
            if ($age < $sla_minutes) {
                continue;
            }

            $this->db->where('id', $order->kitchen_sale_id);
            $this->db->update('tbl_kitchen_sales', array('del_status' => 'Deleted'));

            $this->Integration_model->updateOrder($order->id, array(
                'canonical_status' => 'REJECTED',
                'reject_reason'    => 'ACCEPT_TIMEOUT',
                'last_error'       => sprintf('Not accepted within %d minutes', $sla_minutes),
            ));
            $this->Integration_model->enqueueEvent($order->id, 'order.reject', array(
                'external_order_id' => $order->external_order_id,
                'reason'            => 'ACCEPT_TIMEOUT',
            ));
            $swept++;
            $this->out('  timed out '.$order->external_order_id.' after '.round($age).'m');
        }

        $this->out($swept ? $swept.' order(s) auto-rejected.' : 'Nothing past its SLA.');
    }

    protected function out($line)
    {
        fwrite(STDOUT, $line.PHP_EOL);
    }
}
