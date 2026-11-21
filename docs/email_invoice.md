# Email Invoice (bill email to the customer)

The cashier ticks **Email bill** on the POS payment screen and the customer gets the bill by email,
from the restaurant's own email account (SMTP).

## Go live

1. Run `Update/email_invoice_migration.sql` on the database (safe to run twice).
   It adds `tbl_email_settings`, `tbl_email_queue` and the role permissions 443-446.
2. Upload the changed files (list at the end).
3. **Settings > Email Settings**: switch on, pick the provider, enter the email + password,
   **Save**, then **Send test email**.
   - Gmail / Google Workspace needs an **App Password** (Google Account > Security >
     2-Step Verification on > App passwords). The normal Gmail password is refused.
   - Other hosts: SMTP server, port and security are under *Advanced*
     (cPanel: Email Accounts > Connect Devices).
4. Optional safety net, cPanel cron every 5 minutes:
   `php /path/to/site/index.php Email_cron run`
   The POS already sends right after each bill; the cron only catches retries and bills
   synced while no POS screen was open.
5. Give other roles the permissions under **Email Invoice** in Role settings:
   `update` (Email Settings page), `Send / resend bill email` (Sales list), `Email Log`.
   Every cashier can use the box on the POS: it needs no permission.

## How it works

```
POS payment screen  --(order json + send_email_status, email_bill_to)-->  Sale/push_online
                                                                              | sale committed
                                                                              v
                                                 Invoice_mailer::queueFromPos -> tbl_email_queue (pending)
POS (pos_email_bill.js) --async--> Email_invoice/process --+
Email_cron run (every 5 min) -----------------------------+--> claim row (pending -> sending)
                                                              -> PHPMailer SMTP -> sent
                                                              -> or pending + retry time, failed after 3 tries
```

Why it cannot break billing:

- `push_online` only does one INSERT for the email, **after** the sale is committed, inside
  `try/catch`. It never talks to the SMTP server and never changes its answer to the POS.
- Every query in `Invoice_mailer` runs through `quiet()`: no CI error page, and the
  PHP 8.1+ `mysqli_sql_exception` is caught too. Missing tables = feature off.
- Offline POS: the email fields travel inside the saved order, so a bill made offline is
  emailed when it syncs.
- Same bill synced twice = one email (same sale + same address is queued once).
- Several terminals sending at the same time: a row is sent only by the worker whose
  `UPDATE ... WHERE status='pending'` changed it. A worker that died mid-send leaves a
  `sending` row; it is retried after 10 minutes.
- Retries: after 2 and 10 minutes, then **Failed** (shown in Email Log with the server's
  error and a Retry button). A refused login fails at once (retrying cannot fix it).
- The SMTP calls release the PHP session first, so other POS requests do not wait.

Security:

- The SMTP password is stored encrypted (`enc1:`, AES-256 with a key from
  `encryption_key`) and never sent back to the browser. If `encryption_key` changes,
  type the password again.
- One plain address only (no CR/LF, no lists): no header injection.
- Settings, test and resend need a per-session token and the matching permission; a
  bill can only be emailed by its own company, at most 10 times.

## The email

`views/mail-template/invoice-email.php`: tables + inline styles (works in Gmail / Outlook),
logo, outlet, TRN, *TAX INVOICE* when tax is collected and a TRN is set, items with
modifiers, discount, charges, each tax, total, paid / due, payment methods, footer text and
a **View / print bill online** link (the existing QR invoice page). A plain-text part is
included. The email text is always English (`Invoice_mailer::t`), whoever's session sends it.

## Where things are

| What | File |
|---|---|
| Queue, sending, settings, the bill | `application/libraries/Invoice_mailer.php` |
| Settings / log / send / process pages | `application/controllers/Email_invoice.php`, `views/email_invoice/*` |
| Cron | `application/controllers/Email_cron.php` (`run`, `status`) |
| Queue on sale save | `Sale::queueBillEmail()` called at the end of `Sale::push_online()` |
| Sales list "Email Invoice" | `Sale::getAjaxData()`, modal in `views/sale/sales.php` |
| POS box | `views/sale/POS/main_screen.php` (under the token field), `frequent_changing/js/pos_email_bill.js`, 4 hooks in `pos_script_v7.3.js` |
| Texts | `application/language/*/email_invoice_lang.php` |

## Tests (local only)

```
php tests/email_invoice/email_test.php          # 67 checks: queue, retries, SMTP, pages, permissions, push_online
php tests/email_invoice/pos_browser_test.php    # 14 checks: the POS in headless Chrome, end to end
```

Both start `tests/email_invoice/fake_smtp.py`, point company 1 at it and restore every
row they touch.

## Troubleshooting

| Symptom | Fix |
|---|---|
| No *Email bill* box on the POS | Email Settings switched off or incomplete; reload the POS after saving. |
| Email Log: `Could not authenticate` | Wrong password; Gmail needs an App Password. |
| `Could not connect` / timeout | Host blocks the port: try SSL + 465, or ask the host to open SMTP. |
| Arrives in spam | Send from an address on the restaurant's own domain and add SPF/DKIM for it. |
| Waiting forever | No POS open and no cron: add the cron, or Email Log > Send now. |
