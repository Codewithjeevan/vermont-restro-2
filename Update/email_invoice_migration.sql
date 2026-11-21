-- Email Invoice module: database changes. Select your database in phpMyAdmin, then run once.
-- Safe to run again: INSERT IGNORE, CREATE TABLE IF NOT EXISTS.
--
-- What it adds:
--   * tbl_email_settings  one row per company: the SMTP account bills are sent from
--   * tbl_email_queue     one row per bill email; the POS only queues, sending happens
--                         afterwards (Email_invoice/process from the POS, or the cron), with retries
--   * role permissions    443 Email Invoice -> 444 update (settings), 445 send (send / resend a bill),
--                         446 view (email log)
-- Docs: docs/email_invoice.md
--
-- The tbl_access ids are fixed on purpose: Email_invoice::ACCESS_EMAIL hard-codes 443 and
-- userHome.php uses it in data-access. Before running on a live database check that ids 443-446
-- are not taken by something else (SELECT * FROM tbl_access WHERE id BETWEEN 443 AND 446).

INSERT IGNORE INTO tbl_access (id, module_name, function_name, label_name, parent_id, main_module_id, del_status) VALUES
  (443, 'email_invoice', '',       'email_invoice', 0,   3,    'Live'),
  (444, '',              'update', 'update',        443, NULL, 'Live'),
  (445, '',              'send',   'ei_send_email', 443, NULL, 'Live'),
  (446, '',              'view',   'ei_email_log',  443, NULL, 'Live');
UPDATE tbl_access SET del_status = 'Live' WHERE id BETWEEN 443 AND 446;

CREATE TABLE IF NOT EXISTS tbl_email_settings (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  provider VARCHAR(30) NOT NULL DEFAULT 'custom' COMMENT 'gmail / outlook / zoho / hostinger / custom (only fills host, port and encryption)',
  host VARCHAR(150) NULL,
  port INT NOT NULL DEFAULT 587,
  encryption VARCHAR(10) NOT NULL DEFAULT 'tls' COMMENT 'tls (STARTTLS, port 587) / ssl (port 465) / none',
  username VARCHAR(150) NULL COMMENT 'SMTP login, usually the email address',
  password TEXT NULL COMMENT 'encrypted (enc1:...), never sent back to the browser',
  from_email VARCHAR(150) NULL COMMENT 'empty = username',
  from_name VARCHAR(150) NULL COMMENT 'empty = outlet name',
  reply_to VARCHAR(150) NULL COMMENT 'empty = outlet email',
  bcc_email VARCHAR(150) NULL COMMENT 'optional copy of every bill',
  pos_auto_tick TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'POS: tick Email bill by itself when the customer has an email',
  last_test_at DATETIME NULL,
  last_test_ok TINYINT(1) NULL,
  last_test_error VARCHAR(500) NULL,
  updated_by INT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email_settings_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_email_queue (
  id INT NOT NULL AUTO_INCREMENT,
  company_id INT NOT NULL,
  outlet_id INT NULL,
  sale_id INT NOT NULL,
  sale_no VARCHAR(50) NULL,
  customer_id INT NULL,
  to_email VARCHAR(150) NOT NULL,
  source VARCHAR(10) NOT NULL DEFAULT 'pos' COMMENT 'pos / resend',
  status VARCHAR(10) NOT NULL DEFAULT 'pending' COMMENT 'pending / sending / sent / failed',
  attempts INT NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NULL COMMENT 'retry not before this time',
  locked_at DATETIME NULL COMMENT 'set while one worker sends it; a stale lock is released after 10 minutes',
  sent_at DATETIME NULL,
  last_error VARCHAR(500) NULL,
  created_by INT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_email_queue_status (status, next_attempt_at),
  KEY idx_email_queue_sale (sale_id),
  KEY idx_email_queue_company (company_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
