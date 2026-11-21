<?php
/*
  ###########################################################
  # This is Email_cron Controller
  ###########################################################
  # Sends bill emails that are still waiting (docs/email_invoice.md).
  # The POS already sends right after each bill; this is the safety net
  # for retries and for bills synced while no POS screen was open.
  # Command line (cPanel cron, every 5 minutes):
  #   php index.php Email_cron run
  #   php index.php Email_cron status
  # Extends CI_Controller, not Cl_Controller: no session or licence checks,
  # and it refuses to run from the web.
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
        $this->load->model('Common_model');
        $this->Common_model->setDefaultTimezone();
        $this->load->library('Invoice_mailer');
        if (!$this->invoice_mailer->tablesReady()) {
            echo 'Email tables missing: run Update/email_invoice_migration.sql first.' . PHP_EOL;
            exit(1);
        }
    }

    public function index() {
        echo 'Usage:' . PHP_EOL;
        echo '  php index.php Email_cron run' . PHP_EOL;
        echo '  php index.php Email_cron status' . PHP_EOL;
    }

    public function run() {
        @set_time_limit(0);
        $lock = 'email_cron_' . $this->db->database;
        if ((int) $this->db->query("SELECT GET_LOCK(?, 0) AS l", array($lock))->row('l') !== 1) {
            echo date('Y-m-d H:i:s') . ' another email cron run is still working' . PHP_EOL;
            return;
        }
        $total = array('sent' => 0, 'failed' => 0, 'retry' => 0, 'left' => 0);
        // batches of 20 until nothing is due or 4 minutes are used (the next run starts at 5)
        $started = time();
        do {
            $stats = $this->invoice_mailer->process(null, 20, 60);
            foreach (array('sent', 'failed', 'retry') as $k) {
                $total[$k] += $stats[$k];
            }
            $total['left'] = $stats['left'];
            $did = $stats['sent'] + $stats['failed'] + $stats['retry'];
        } while ($did > 0 && time() - $started < 240);
        $this->db->query("SELECT RELEASE_LOCK(?)", array($lock));
        echo date('Y-m-d H:i:s') . sprintf(' sent %d, failed %d, retry later %d, still waiting %d', $total['sent'], $total['failed'], $total['retry'], $total['left']) . PHP_EOL;
    }

    public function status() {
        $rows = $this->db->query("SELECT status, COUNT(*) AS n FROM tbl_email_queue GROUP BY status")->result();
        foreach ($rows as $r) {
            echo str_pad($r->status, 10) . $r->n . PHP_EOL;
        }
        $last = $this->db->query("SELECT id, company_id, sale_no, to_email, status, last_error, created_at FROM tbl_email_queue ORDER BY id DESC LIMIT 10")->result();
        foreach ($last as $r) {
            echo '#' . $r->id . ' c' . $r->company_id . ' ' . $r->sale_no . ' ' . $r->to_email . ' ' . $r->status . ' ' . $r->created_at . ($r->last_error ? ' | ' . $r->last_error : '') . PHP_EOL;
        }
    }
}
