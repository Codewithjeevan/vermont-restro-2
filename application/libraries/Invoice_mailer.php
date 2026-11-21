<?php
/*
  ###########################################################
  # Invoice_mailer library: email bills to customers
  ###########################################################
  # Flow (docs/email_invoice.md):
  #   1. POS ticks "Email bill" -> Sale/push_online saves the sale, then
  #      queueSale() adds one row to tbl_email_queue (one INSERT, no SMTP).
  #   2. The POS calls Email_invoice/process right after the push (async), and
  #      Email_cron runs process() for everything still waiting.
  #   3. process() claims a row atomically (pending -> sending), sends it with
  #      PHPMailer, and marks it sent, or pending again with a later retry
  #      time, or failed after MAX_ATTEMPTS.
  # Nothing in here may break a sale: every public method catches its own
  # errors and runs its queries with db_debug off.
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_mailer {

    const MAX_ATTEMPTS = 3;
    const MAX_PER_SALE = 10;          // queue rows one bill may have (stops resend spam)
    const STALE_LOCK_MINUTES = 10;    // a "sending" row older than this is retried
    const SMTP_TIMEOUT = 20;          // seconds per SMTP step

    /** retry delay in minutes after attempt 1, 2 */
    private $backoff = array(1 => 2, 2 => 10);

    /** host, port, encryption filled in by the provider dropdown */
    public static $providers = array(
        'gmail' => array('label' => 'Gmail / Google Workspace', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'),
        'outlook' => array('label' => 'Outlook / Office 365', 'host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls'),
        'zoho' => array('label' => 'Zoho Mail', 'host' => 'smtp.zoho.com', 'port' => 465, 'encryption' => 'ssl'),
        'hostinger' => array('label' => 'Hostinger', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl'),
        'custom' => array('label' => 'Other / cPanel / custom SMTP', 'host' => '', 'port' => 587, 'encryption' => 'tls'),
    );

    private $CI;
    private static $tables_ready = null;
    private static $company_cache = array();
    private static $english = null;

    public function __construct() {
        $this->CI = & get_instance();
    }

    // ------------------------------------------------------------------
    // small utilities
    // ------------------------------------------------------------------

    /**
     * Run $fn with db_debug off. A SQL error never leaves this library: with db_debug on CI
     * would print an error page into the push_online answer, and PHP 8.1+ mysqli throws
     * mysqli_sql_exception. Either way the caller gets $default and the error is logged.
     */
    private function quiet($fn, $default = null) {
        $db = $this->CI->db;
        $old = $db->db_debug;
        $db->db_debug = false;
        try {
            return $fn($db);
        } catch (Throwable $e) {
            log_message('error', 'Invoice_mailer SQL: ' . $e->getMessage());
            // a failed statement can leave CI's query builder half filled
            if (method_exists($db, 'reset_query')) {
                $db->reset_query();
            }
            return $default;
        } finally {
            $db->db_debug = $old;
        }
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    private function cut($text, $len = 500) {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        return function_exists('mb_substr') ? mb_substr($text, 0, $len) : substr($text, 0, $len);
    }

    /** text for the bill email: always the English file, whoever's session sends it */
    public function t($key) {
        if (self::$english === null) {
            $lang = array();
            $file = APPPATH . 'language/english/email_invoice_lang.php';
            if (is_file($file)) {
                include $file;
            }
            self::$english = $lang;
        }
        return isset(self::$english[$key]) ? self::$english[$key] : $key;
    }

    /** true when the migration has been run */
    public function tablesReady() {
        if (self::$tables_ready === null) {
            self::$tables_ready = (bool) $this->quiet(function ($db) {
                return $db->table_exists('tbl_email_settings') && $db->table_exists('tbl_email_queue');
            }, false);
        }
        return self::$tables_ready;
    }

    /** a single, plain address: no CR/LF, no list, max 150 chars */
    public function validEmail($email) {
        $email = trim((string) $email);
        if ($email === '' || strlen($email) > 150 || preg_match('/[\r\n,;<>\s]/', $email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // ------------------------------------------------------------------
    // settings
    // ------------------------------------------------------------------

    private function defaults($company_id) {
        return (object) array(
            'id' => null, 'company_id' => (int) $company_id, 'enabled' => 0, 'provider' => 'gmail',
            'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '',
            'from_email' => '', 'from_name' => '', 'reply_to' => '', 'bcc_email' => '', 'pos_auto_tick' => 1,
            'last_test_at' => null, 'last_test_ok' => null, 'last_test_error' => null,
            'updated_by' => null, 'updated_at' => null,
        );
    }

    /** the company's settings row, or defaults; password stays encrypted */
    public function getSettings($company_id) {
        $company_id = (int) $company_id;
        if (!$this->tablesReady()) {
            return $this->defaults($company_id);
        }
        $row = $this->quiet(function ($db) use ($company_id) {
            $q = $db->get_where('tbl_email_settings', array('company_id' => $company_id), 1);
            return $q ? $q->row() : null;
        });
        return $row ? $row : $this->defaults($company_id);
    }

    /** switched on and has everything needed to log in */
    public function isEnabled($company_id) {
        $s = $this->getSettings($company_id);
        return $this->isUsable($s);
    }

    private function isUsable($s) {
        return $s && (int) $s->enabled === 1 && trim((string) $s->host) !== '' && (int) $s->port > 0
            && trim((string) $s->username) !== '' && (string) $s->password !== '';
    }

    /**
     * Save from the settings page. $data is already validated by the controller.
     * An empty password keeps the saved one.
     */
    public function saveSettings($company_id, array $data, $user_id) {
        $company_id = (int) $company_id;
        $row = array(
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'provider' => (string) $data['provider'],
            'host' => (string) $data['host'],
            'port' => (int) $data['port'],
            'encryption' => (string) $data['encryption'],
            'username' => (string) $data['username'],
            'from_email' => (string) $data['from_email'],
            'from_name' => (string) $data['from_name'],
            'reply_to' => (string) $data['reply_to'],
            'bcc_email' => (string) $data['bcc_email'],
            'pos_auto_tick' => !empty($data['pos_auto_tick']) ? 1 : 0,
            'updated_by' => (int) $user_id,
            'updated_at' => $this->now(),
        );
        if (isset($data['password']) && $data['password'] !== '') {
            $row['password'] = $this->encryptSecret($data['password']);
        }
        return $this->quiet(function ($db) use ($company_id, $row) {
            $exists = $db->get_where('tbl_email_settings', array('company_id' => $company_id), 1);
            if ($exists && $exists->row()) {
                $db->where('company_id', $company_id);
                return (bool) $db->update('tbl_email_settings', $row);
            }
            $row['company_id'] = $company_id;
            return (bool) $db->insert('tbl_email_settings', $row);
        }, false);
    }

    private function secretKey() {
        return hash('sha256', (string) $this->CI->config->item('encryption_key') . '|email_invoice|' . APPPATH, true);
    }

    public function encryptSecret($plain) {
        if (!function_exists('openssl_encrypt')) {
            return 'plain:' . base64_encode($plain);
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $this->secretKey(), OPENSSL_RAW_DATA, $iv);
        return 'enc1:' . base64_encode($iv . $cipher);
    }

    /** null when it cannot be read (key changed): the user has to type it again */
    public function decryptSecret($stored) {
        $stored = (string) $stored;
        if (strpos($stored, 'enc1:') === 0) {
            $raw = base64_decode(substr($stored, 5), true);
            if ($raw === false || strlen($raw) < 17 || !function_exists('openssl_decrypt')) {
                return null;
            }
            $plain = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $this->secretKey(), OPENSSL_RAW_DATA, substr($raw, 0, 16));
            return $plain === false ? null : $plain;
        }
        if (strpos($stored, 'plain:') === 0) {
            $plain = base64_decode(substr($stored, 6), true);
            return $plain === false ? null : $plain;
        }
        return $stored === '' ? null : $stored;
    }

    public function recordTest($company_id, $ok, $error) {
        $company_id = (int) $company_id;
        $this->quiet(function ($db) use ($company_id, $ok, $error) {
            $db->where('company_id', $company_id);
            $db->update('tbl_email_settings', array(
                'last_test_at' => $this->now(),
                'last_test_ok' => $ok ? 1 : 0,
                'last_test_error' => $ok ? null : $this->cut($error),
            ));
        });
    }

    // ------------------------------------------------------------------
    // queue
    // ------------------------------------------------------------------

    /**
     * Add a bill email to the queue. Never throws.
     * @param bool $again false (POS): the same sale + address is queued only once, so a
     *                    re-sync of the same sale does not send twice. true: explicit resend.
     * @return array ok, msg, queue_id
     */
    public function queueSale($sale_id, $company_id, $email, $source, $user_id, $again = false) {
        try {
            $sale_id = (int) $sale_id;
            $company_id = (int) $company_id;
            $email = trim((string) $email);
            if (!$this->tablesReady()) {
                return array('ok' => false, 'msg' => lang('ei_not_ready'), 'queue_id' => null);
            }
            if (!$this->validEmail($email)) {
                return array('ok' => false, 'msg' => lang('ei_invalid_email'), 'queue_id' => null);
            }
            if (!$this->isEnabled($company_id)) {
                return array('ok' => false, 'msg' => lang('ei_not_enabled'), 'queue_id' => null);
            }
            return $this->quiet(function ($db) use ($sale_id, $company_id, $email, $source, $user_id, $again) {
                $q = $db->select('id, sale_no, outlet_id, customer_id, company_id')
                    ->get_where('tbl_sales', array('id' => $sale_id, 'del_status' => 'Live'), 1);
                $sale = $q ? $q->row() : null;
                if (!$sale || (int) $sale->company_id !== $company_id) {
                    return array('ok' => false, 'msg' => lang('ei_sale_not_found'), 'queue_id' => null);
                }
                $q = $db->select('id, to_email, status')->order_by('id', 'DESC')
                    ->get_where('tbl_email_queue', array('sale_id' => $sale_id));
                $rows = $q ? $q->result() : array();
                if (!$again) {
                    foreach ($rows as $r) {
                        if (strcasecmp($r->to_email, $email) === 0) {
                            return array('ok' => true, 'msg' => lang('ei_already_queued'), 'queue_id' => (int) $r->id);
                        }
                    }
                } else {
                    foreach ($rows as $r) {
                        if (strcasecmp($r->to_email, $email) === 0 && in_array($r->status, array('pending', 'sending'), true)) {
                            return array('ok' => true, 'msg' => lang('ei_already_queued'), 'queue_id' => (int) $r->id);
                        }
                    }
                }
                if (count($rows) >= self::MAX_PER_SALE) {
                    return array('ok' => false, 'msg' => lang('ei_too_many'), 'queue_id' => null);
                }
                $ok = $db->insert('tbl_email_queue', array(
                    'company_id' => $company_id,
                    'outlet_id' => (int) $sale->outlet_id,
                    'sale_id' => $sale_id,
                    'sale_no' => (string) $sale->sale_no,
                    'customer_id' => (int) $sale->customer_id,
                    'to_email' => $email,
                    'source' => $source === 'resend' ? 'resend' : 'pos',
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_by' => (int) $user_id,
                    'created_at' => $this->now(),
                ));
                if (!$ok) {
                    return array('ok' => false, 'msg' => lang('ei_queue_error'), 'queue_id' => null);
                }
                return array('ok' => true, 'msg' => lang('ei_queued'), 'queue_id' => (int) $db->insert_id());
            }, array('ok' => false, 'msg' => lang('ei_queue_error'), 'queue_id' => null));
        } catch (Throwable $e) {
            log_message('error', 'Invoice_mailer queueSale: ' . $e->getMessage());
            return array('ok' => false, 'msg' => lang('ei_queue_error'), 'queue_id' => null);
        }
    }

    /**
     * Called by Sale/push_online after the sale is committed.
     * Queues the email and, for a real customer without an email, keeps the address on the customer.
     */
    public function queueFromPos($sale_id, $company_id, $customer_id, $email, $user_id) {
        $result = $this->queueSale($sale_id, $company_id, $email, 'pos', $user_id, false);
        if ($result['ok']) {
            $this->rememberCustomerEmail($customer_id, $company_id, $email);
        }
        return $result;
    }

    /** save the address on a real customer of this company that has no email yet */
    public function rememberCustomerEmail($customer_id, $company_id, $email) {
        $customer_id = (int) $customer_id;
        if ($customer_id <= 1 || !$this->validEmail($email)) {
            return; // walk-in customer is shared by every bill
        }
        $this->quiet(function ($db) use ($customer_id, $company_id, $email) {
            $db->where('id', $customer_id);
            $db->where('company_id', (int) $company_id);
            $db->group_start()->where('email IS NULL', null, false)->or_where('email', '')->group_end();
            $db->update('tbl_customers', array('email' => $email));
        });
    }

    /** email of a customer of this company, '' when none */
    public function customerEmail($customer_id, $company_id) {
        $customer_id = (int) $customer_id;
        if ($customer_id <= 1) {
            return '';
        }
        $email = $this->quiet(function ($db) use ($customer_id, $company_id) {
            $q = $db->select('email')->get_where('tbl_customers', array('id' => $customer_id, 'company_id' => (int) $company_id), 1);
            $row = $q ? $q->row() : null;
            return $row ? trim((string) $row->email) : '';
        }, '');
        return $this->validEmail($email) ? $email : '';
    }

    /** failed row back to pending, for the Retry button */
    public function retry($queue_id, $company_id) {
        return $this->quiet(function ($db) use ($queue_id, $company_id) {
            $db->where('id', (int) $queue_id);
            $db->where('company_id', (int) $company_id);
            $db->where('status', 'failed');
            $db->update('tbl_email_queue', array('status' => 'pending', 'attempts' => 0, 'next_attempt_at' => null, 'locked_at' => null));
            return $db->affected_rows() === 1;
        }, false);
    }

    public function recentLog($company_id, $outlet_id = null, $limit = 100) {
        if (!$this->tablesReady()) {
            return array();
        }
        return $this->quiet(function ($db) use ($company_id, $outlet_id, $limit) {
            $db->where('company_id', (int) $company_id);
            if ($outlet_id) {
                $db->where('outlet_id', (int) $outlet_id);
            }
            $q = $db->order_by('id', 'DESC')->limit((int) $limit)->get('tbl_email_queue');
            return $q ? $q->result() : array();
        }, array());
    }

    /** last queue row of a sale (for the sales list modal) */
    public function lastForSale($sale_id, $company_id) {
        if (!$this->tablesReady()) {
            return null;
        }
        return $this->quiet(function ($db) use ($sale_id, $company_id) {
            $q = $db->order_by('id', 'DESC')->get_where('tbl_email_queue', array('sale_id' => (int) $sale_id, 'company_id' => (int) $company_id), 1);
            return $q ? $q->row() : null;
        });
    }

    public function pendingCount($company_id = null) {
        if (!$this->tablesReady()) {
            return 0;
        }
        return (int) $this->quiet(function ($db) use ($company_id) {
            $db->where_in('status', array('pending', 'sending'));
            if ($company_id) {
                $db->where('company_id', (int) $company_id);
            }
            return $db->count_all_results('tbl_email_queue');
        });
    }

    // ------------------------------------------------------------------
    // sending
    // ------------------------------------------------------------------

    /**
     * Send what is due. Safe to call from several terminals at once: a row is
     * only sent by the worker whose UPDATE moved it from pending to sending.
     * @param int|null $company_id null = every company (cron)
     * @param bool $now_all true = also rows still waiting for their retry time ("Send now" button)
     * @return array sent, failed, retry, left
     */
    public function process($company_id = null, $limit = 5, $seconds = 20, $now_all = false) {
        $stats = array('sent' => 0, 'failed' => 0, 'retry' => 0, 'left' => 0);
        if (!$this->tablesReady()) {
            return $stats;
        }
        $started = microtime(true);
        try {
            $now = $this->now();
            $stale = date('Y-m-d H:i:s', time() - self::STALE_LOCK_MINUTES * 60);
            $ids = $this->quiet(function ($db) use ($company_id, $limit, $now, $stale, $now_all) {
                $db->where('status', 'sending');
                $db->where('locked_at <', $stale);
                $db->update('tbl_email_queue', array('status' => 'pending', 'locked_at' => null));

                $db->select('id');
                $db->where('status', 'pending');
                if (!$now_all) {
                    $db->group_start()->where('next_attempt_at IS NULL', null, false)->or_where('next_attempt_at <=', $now)->group_end();
                }
                if ($company_id) {
                    $db->where('company_id', (int) $company_id);
                }
                $q = $db->order_by('id', 'ASC')->limit((int) $limit)->get('tbl_email_queue');
                $ids = array();
                foreach ($q ? $q->result() : array() as $r) {
                    $ids[] = (int) $r->id;
                }
                return $ids;
            }, array());
            foreach ($ids as $id) {
                if (microtime(true) - $started > $seconds) {
                    break;
                }
                $row = $this->claim($id);
                if (!$row) {
                    continue; // another terminal took it
                }
                $result = $this->sendQueued($row);
                $stats[$result]++;
            }
            $stats['left'] = $this->pendingCount($company_id);
        } catch (Throwable $e) {
            log_message('error', 'Invoice_mailer process: ' . $e->getMessage());
        }
        return $stats;
    }

    /** pending -> sending, atomically; returns the row or null */
    private function claim($id) {
        return $this->quiet(function ($db) use ($id) {
            $db->set('status', 'sending');
            $db->set('locked_at', $this->now());
            $db->set('attempts', 'attempts + 1', false);
            $db->where('id', (int) $id);
            $db->where('status', 'pending');
            $db->update('tbl_email_queue');
            if ($db->affected_rows() !== 1) {
                return null;
            }
            $q = $db->get_where('tbl_email_queue', array('id' => (int) $id), 1);
            return $q ? $q->row() : null;
        });
    }

    /** @return string sent / retry / failed */
    private function sendQueued($row) {
        $error = '';
        $permanent = false;
        try {
            $settings = $this->getSettings($row->company_id);
            if (!$this->isUsable($settings)) {
                $error = lang('ei_not_enabled');
                $permanent = true;
            } else {
                $mail = $this->buildInvoiceEmail($row->sale_id, $row->company_id);
                if (!$mail) {
                    $error = lang('ei_sale_not_found');
                    $permanent = true;
                } else {
                    $reply_to = trim((string) $settings->reply_to) !== '' ? $settings->reply_to : $mail['outlet_email'];
                    $from_name = trim((string) $settings->from_name) !== '' ? $settings->from_name : $mail['outlet_name'];
                    $result = $this->smtpSend($settings, $row->to_email, $mail['subject'], $mail['html'], $mail['text'], $from_name, $reply_to, $settings->bcc_email);
                    if ($result['ok']) {
                        $this->finish($row->id, array('status' => 'sent', 'sent_at' => $this->now(), 'locked_at' => null, 'last_error' => null));
                        return 'sent';
                    }
                    $error = $result['error'];
                    $permanent = !empty($result['permanent']);
                }
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
        $attempts = (int) $row->attempts;
        if ($permanent || $attempts >= self::MAX_ATTEMPTS) {
            $this->finish($row->id, array('status' => 'failed', 'locked_at' => null, 'last_error' => $this->cut($error)));
            return 'failed';
        }
        $minutes = isset($this->backoff[$attempts]) ? $this->backoff[$attempts] : 30;
        $this->finish($row->id, array(
            'status' => 'pending',
            'locked_at' => null,
            'next_attempt_at' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'last_error' => $this->cut($error),
        ));
        return 'retry';
    }

    private function finish($id, array $data) {
        $this->quiet(function ($db) use ($id, $data) {
            $db->where('id', (int) $id);
            $db->update('tbl_email_queue', $data);
        });
    }

    private function loadPhpMailer() {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer', false)) {
            require_once APPPATH . 'third_party/phpmailer/Exception.php';
            require_once APPPATH . 'third_party/phpmailer/PHPMailer.php';
            require_once APPPATH . 'third_party/phpmailer/SMTP.php';
        }
    }

    /**
     * One email through the company's SMTP account.
     * @param object $s settings row (password encrypted) or the same shape with plain_password set
     * @return array ok, error, permanent
     */
    public function smtpSend($s, $to, $subject, $html, $text, $from_name = '', $reply_to = '', $bcc = '') {
        $password = isset($s->plain_password) ? (string) $s->plain_password : $this->decryptSecret($s->password);
        if ($password === null || $password === '') {
            return array('ok' => false, 'error' => lang('ei_password_unreadable'), 'permanent' => true);
        }
        if (!$this->validEmail($to)) {
            return array('ok' => false, 'error' => lang('ei_invalid_email'), 'permanent' => true);
        }
        $this->loadPhpMailer();
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = trim((string) $s->host);
            $mail->Port = (int) $s->port;
            $mail->SMTPAuth = true;
            $mail->Username = trim((string) $s->username);
            $mail->Password = $password;
            $mail->Timeout = self::SMTP_TIMEOUT;
            if ($s->encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($s->encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->XMailer = ' ';

            $from = trim((string) $s->from_email) !== '' ? trim($s->from_email) : trim((string) $s->username);
            if (!$this->validEmail($from)) {
                return array('ok' => false, 'error' => lang('ei_from_invalid'), 'permanent' => true);
            }
            $mail->setFrom($from, $this->cut($from_name, 100));
            if ($this->validEmail($reply_to) && strcasecmp($reply_to, $from) !== 0) {
                $mail->addReplyTo(trim($reply_to), $this->cut($from_name, 100));
            }
            $mail->addAddress(trim($to));
            if ($this->validEmail($bcc) && strcasecmp(trim($bcc), trim($to)) !== 0) {
                $mail->addBCC(trim($bcc));
            }
            $mail->Subject = $this->cut($subject, 200);
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
            return array('ok' => true, 'error' => '', 'permanent' => false);
        } catch (Throwable $e) {
            $error = $mail->ErrorInfo ? $mail->ErrorInfo : $e->getMessage();
            $error = str_replace($password, '***', $error);
            // a login the server refused will not work on the next try either
            $permanent = stripos($error, 'authenticate') !== false || stripos($error, 'Username and Password not accepted') !== false;
            return array('ok' => false, 'error' => $this->cut($error), 'permanent' => $permanent);
        } finally {
            try {
                $mail->smtpClose();
            } catch (Throwable $ignore) {
            }
        }
    }

    // ------------------------------------------------------------------
    // the bill
    // ------------------------------------------------------------------

    private function company($company_id) {
        $company_id = (int) $company_id;
        if (!isset(self::$company_cache[$company_id])) {
            self::$company_cache[$company_id] = $this->quiet(function ($db) use ($company_id) {
                $q = $db->get_where('tbl_companies', array('id' => $company_id), 1);
                return $q ? $q->row() : null;
            });
        }
        return self::$company_cache[$company_id];
    }

    /** amount with the company's currency, precision and separators (no session needed) */
    public function money($company, $amount) {
        $amount = is_numeric($amount) ? (float) $amount : 0.0;
        $precision = isset($company->precision) && $company->precision !== '' ? (int) $company->precision : 2;
        $dec = isset($company->decimals_separator) && $company->decimals_separator !== '' ? $company->decimals_separator : '.';
        $thousands = isset($company->thousands_separator) ? (string) $company->thousands_separator : '';
        $number = number_format($amount, $precision, $dec, $thousands);
        $currency = isset($company->currency) ? (string) $company->currency : '';
        if (isset($company->currency_position) && $company->currency_position !== 'Before Amount') {
            return $number . ' ' . $currency;
        }
        return $currency . ' ' . $number;
    }

    /** everything the template needs, read from the database only (works from cron) */
    public function saleData($sale_id, $company_id) {
        return $this->quiet(function ($db) use ($sale_id, $company_id) {
            $q = $db->query("SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
                    c.gst_number AS customer_tax_no, u.full_name AS user_name, w.full_name AS waiter_name
                FROM tbl_sales s
                LEFT JOIN tbl_customers c ON c.id = s.customer_id
                LEFT JOIN tbl_users u ON u.id = s.user_id
                LEFT JOIN tbl_users w ON w.id = s.waiter_id
                WHERE s.id = ? AND s.company_id = ? AND s.del_status = 'Live' LIMIT 1", array((int) $sale_id, (int) $company_id));
            $sale = $q ? $q->row() : null;
            if (!$sale) {
                return null;
            }
            $q = $db->query("SELECT id, food_menu_id, menu_name, qty, menu_unit_price, menu_price_with_discount, discount_amount,
                    menu_note, menu_combo_items, is_free_item
                FROM tbl_sales_details WHERE sales_id = ? AND del_status = 'Live' ORDER BY id ASC", array((int) $sale_id));
            $items = $q ? $q->result() : array();
            foreach ($items as $item) {
                $q = $db->query("SELECT m.name, sdm.modifier_price FROM tbl_sales_details_modifiers sdm
                    LEFT JOIN tbl_modifiers m ON m.id = sdm.modifier_id
                    WHERE sdm.sales_id = ? AND sdm.sales_details_id = ?", array((int) $sale_id, (int) $item->id));
                $item->modifiers = $q ? $q->result() : array();
            }
            $q = $db->query("SELECT p.payment_name, p.amount, p.currency_type, p.multi_currency, p.multi_currency_rate, pm.name AS method_name
                FROM tbl_sale_payments p LEFT JOIN tbl_payment_methods pm ON pm.id = p.payment_id
                WHERE p.sale_id = ? AND p.del_status = 'Live'", array((int) $sale_id));
            $payments = $q ? $q->result() : array();
            $q = $db->get_where('tbl_outlets', array('id' => (int) $sale->outlet_id), 1);
            $outlet = $q ? $q->row() : null;
            return array('sale' => $sale, 'items' => $items, 'payments' => $payments, 'outlet' => $outlet);
        });
    }

    /** @return array|null subject, html, text, outlet_name, outlet_email */
    public function buildInvoiceEmail($sale_id, $company_id) {
        $company = $this->company($company_id);
        $data = $this->saleData($sale_id, $company_id);
        if (!$company || !$data) {
            return null;
        }
        $outlet = $data['outlet'];
        $outlet_name = $outlet && $outlet->outlet_name ? $outlet->outlet_name : $company->business_name;
        $data['company'] = $company;
        $data['mailer'] = $this;
        $data['outlet_name'] = $outlet_name;
        $data['is_tax_invoice'] = $company->collect_tax === 'Yes' && trim((string) $company->tax_registration_no) !== '';
        $data['logo_url'] = $company->invoice_logo && is_file(FCPATH . 'images/' . $company->invoice_logo) ? base_url() . 'images/' . rawurlencode($company->invoice_logo) : '';
        $data['online_url'] = $data['sale']->random_code ? base_url() . 'invoice/' . rawurlencode($data['sale']->random_code) : '';
        $date_format = $company->date_format ? $company->date_format : 'd/m/Y';
        $stamp = strtotime($data['sale']->sale_date . ' ' . date('H:i:s', strtotime((string) $data['sale']->date_time)));
        $data['bill_date'] = $stamp ? date($date_format . ' H:i', $stamp) : $data['sale']->sale_date;
        $data['order_type_text'] = $this->orderTypeText($data['sale']->order_type);

        $html = $this->CI->load->view('mail-template/invoice-email', $data, true);
        $subject = sprintf($this->t('ei_subject'), $data['sale']->sale_no, $outlet_name);
        return array(
            'subject' => $subject,
            'html' => $html,
            'text' => $this->plainText($data),
            'outlet_name' => $outlet_name,
            'outlet_email' => $outlet && $this->validEmail($outlet->email) ? $outlet->email : '',
        );
    }

    public function orderTypeText($type) {
        $map = array('1' => $this->t('ei_dine_in'), '2' => $this->t('ei_take_away'), '3' => $this->t('ei_delivery'));
        return isset($map[(string) $type]) ? $map[(string) $type] : '';
    }

    private function plainText($d) {
        $c = $d['company'];
        $s = $d['sale'];
        $lines = array();
        $lines[] = $d['outlet_name'];
        $lines[] = ($d['is_tax_invoice'] ? $this->t('ei_tax_invoice') : $this->t('ei_invoice')) . ' ' . $s->sale_no . ' - ' . $d['bill_date'];
        $lines[] = '';
        foreach ($d['items'] as $item) {
            $lines[] = $item->qty . ' x ' . $item->menu_name . '  ' . $this->money($c, $item->menu_price_with_discount);
        }
        $lines[] = '';
        $lines[] = $this->t('ei_grand_total') . ': ' . $this->money($c, $s->total_payable);
        if ((float) $s->due_amount > 0) {
            $lines[] = $this->t('ei_due') . ': ' . $this->money($c, $s->due_amount);
        }
        if ($d['online_url']) {
            $lines[] = '';
            $lines[] = $this->t('ei_view_online') . ': ' . $d['online_url'];
        }
        if (trim(strip_tags((string) $c->invoice_footer)) !== '') {
            $lines[] = '';
            $lines[] = trim(strip_tags((string) $c->invoice_footer));
        }
        return implode("\n", $lines);
    }

    /** a sample bill for the test email: the latest sale of the company, if any */
    public function latestSaleId($company_id) {
        return (int) $this->quiet(function ($db) use ($company_id) {
            $q = $db->select('id')->order_by('id', 'DESC')->get_where('tbl_sales', array('company_id' => (int) $company_id, 'del_status' => 'Live', 'order_status' => 3), 1);
            $row = $q ? $q->row() : null;
            return $row ? $row->id : 0;
        });
    }
}
