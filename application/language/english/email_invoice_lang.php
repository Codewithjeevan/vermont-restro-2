<?php
/*
 | Email Invoice module (docs/email_invoice.md).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 | The bill email itself always uses this English file (Invoice_mailer::t), so a
 | bill looks the same whether the POS or the cron sent it.
 */

// module, menu and role screen
$lang['email_invoice'] = 'Email Invoice';
$lang['ei_send_email'] = 'Send / resend bill email';
$lang['ei_email_settings'] = 'Email Settings';
$lang['ei_email_log'] = 'Email Log';

// settings page
$lang['ei_on'] = 'ON';
$lang['ei_off'] = 'OFF';
$lang['ei_settings_hint'] = 'Connect the email account your bills are sent from. Save, then send a test email to check it works.';
$lang['ei_enable'] = 'Send bills by email';
$lang['ei_enable_help'] = 'When on, the POS payment screen shows an "Email bill" box.';
$lang['ei_email_account'] = 'Email account';
$lang['ei_provider'] = 'Email provider';
$lang['ei_username'] = 'Email address (login)';
$lang['ei_username_help'] = 'The email account that sends the bills.';
$lang['ei_password'] = 'Password / App password';
$lang['ei_password_help'] = 'For Gmail use an App Password, not your normal password.';
$lang['ei_password_saved'] = 'Saved - leave empty to keep';
$lang['ei_password_keep'] = 'A password is saved. Leave empty to keep it, or type a new one.';
$lang['ei_help_gmail'] = 'Gmail: turn on 2-Step Verification in your Google account, then open Google Account > Security > App passwords, create one and paste the 16 letters here. Your normal Gmail password will not work.';
$lang['ei_help_outlook'] = 'Outlook / Office 365: use the full email address and its password. For business accounts the admin must allow "Authenticated SMTP" for this mailbox.';
$lang['ei_help_zoho'] = 'Zoho: use the full email address and its password, or an application-specific password if two-factor login is on.';
$lang['ei_help_hostinger'] = 'Hostinger: use an email account made in hPanel > Emails, and its password.';
$lang['ei_help_custom'] = 'Ask your hosting / email company for the SMTP server, port and security (cPanel: Email Accounts > Connect Devices). Fill them in under Advanced.';
$lang['ei_advanced'] = 'Advanced (server, port, sender name)';
$lang['ei_host'] = 'SMTP server';
$lang['ei_port'] = 'Port';
$lang['ei_encryption'] = 'Security';
$lang['ei_none'] = 'None';
$lang['ei_from_email'] = 'Send from address';
$lang['ei_from_email_help'] = 'Empty = the login email. Most providers only allow the login email.';
$lang['ei_from_name'] = 'Sender name';
$lang['ei_from_name_help'] = 'Empty = the outlet name.';
$lang['ei_reply_to'] = 'Replies go to';
$lang['ei_reply_to_help'] = 'Empty = the outlet email.';
$lang['ei_bcc_email'] = 'Send me a copy (BCC)';
$lang['ei_bcc_help'] = 'Optional: every bill email is also sent to this address.';
$lang['ei_pos_section'] = 'POS';
$lang['ei_pos_auto_tick'] = 'Tick "Email bill" by itself when the customer has an email';
$lang['ei_pos_auto_tick_help'] = 'The cashier can still untick it, or type another address.';
$lang['ei_test_section'] = 'Test';
$lang['ei_test_to'] = 'Send test to (empty = your login email)';
$lang['ei_send_test'] = 'Send test email';
$lang['ei_test_help'] = 'Uses what is on this page now, even before you save. The test contains your latest bill as a sample.';
$lang['ei_save'] = 'Save';
$lang['ei_saved'] = 'Email settings saved.';
$lang['ei_save_error'] = 'Could not save the settings. Please try again.';
$lang['ei_last_test_ok'] = 'Last test: sent';
$lang['ei_last_test_failed'] = 'Last test: failed';
$lang['ei_pending_count'] = '%d email(s) waiting to be sent';
$lang['ei_sending'] = 'Sending...';
$lang['ei_network_error'] = 'No answer from the server. Check the internet connection and try again.';
$lang['ei_test_ok'] = 'Test email sent to %s. Check the inbox (and the spam folder).';
$lang['ei_test_subject'] = 'Test email';
$lang['ei_test_body'] = 'This is a test from your POS. If you can read this, bill emails will work. Below is a sample of how your customers will see their bill.';
$lang['ei_err_host'] = 'Enter a valid SMTP server.';
$lang['ei_err_port'] = 'Enter a port between 1 and 65535.';
$lang['ei_err_encryption'] = 'Choose a security option.';
$lang['ei_err_username'] = 'Enter the email address (login).';
$lang['ei_err_password'] = 'Enter the password.';
$lang['ei_err_email_field'] = '%s is not a valid email address.';
$lang['ei_hint_gmail'] = 'Gmail refused the login. Use an App Password (Google Account > Security > App passwords), not your normal Gmail password.';
$lang['ei_hint_auth'] = 'The email server refused the login. Check the email address and password. Some providers need an app password or SMTP to be allowed for the mailbox.';
$lang['ei_hint_connect'] = 'Could not reach the email server. Check the SMTP server and port. Many hosts block port 25 and 587; try SSL with port 465.';
$lang['ei_hint_tls'] = 'The secure connection failed. Try the other Security option (TLS with 587, or SSL with 465).';

// email log
$lang['ei_log_hint'] = 'Last 200 bill emails. Emails that fail are tried 3 times, then shown as Failed.';
$lang['ei_log_empty'] = 'No bill emails yet.';
$lang['ei_send_now'] = 'Send now';
$lang['ei_outlet'] = 'Outlet';
$lang['ei_to'] = 'To';
$lang['ei_status'] = 'Status';
$lang['ei_attempts'] = 'Tries';
$lang['ei_details'] = 'Details';
$lang['ei_resend'] = 'resent';
$lang['ei_retry'] = 'Retry';
$lang['ei_retry_queued'] = 'The email will be sent again.';
$lang['ei_retry_error'] = 'This email cannot be retried.';
$lang['ei_next_try'] = 'Next try: %s';
$lang['ei_status_pending'] = 'Waiting';
$lang['ei_status_sending'] = 'Sending';
$lang['ei_status_sent'] = 'Sent';
$lang['ei_status_failed'] = 'Failed';

// POS and sales list
$lang['ei_email_bill'] = 'Email bill';
$lang['ei_email_placeholder'] = 'customer@email.com';
$lang['ei_email_invoice'] = 'Email Invoice';
$lang['ei_send'] = 'Send';
$lang['ei_customer_email'] = 'Customer email';
$lang['ei_last_email'] = 'Last email';
$lang['ei_pos_invalid'] = 'The bill email address is not valid. Fix it or untick "Email bill".';

// messages
$lang['ei_reload'] = 'Your session changed. Please reload the page.';
$lang['ei_not_ready'] = 'Email Invoice is not installed yet: run Update/email_invoice_migration.sql on the database.';
$lang['ei_not_enabled'] = 'Bill emails are off. Turn them on in Settings > Email Settings.';
$lang['ei_invalid_email'] = 'Enter a valid email address.';
$lang['ei_sale_not_found'] = 'Bill not found.';
$lang['ei_already_queued'] = 'This bill is already being sent to this address.';
$lang['ei_too_many'] = 'This bill has been emailed too many times.';
$lang['ei_queue_error'] = 'Could not queue the email.';
$lang['ei_queued'] = 'The bill will be emailed in a moment.';
$lang['ei_sent_to'] = 'Bill emailed to %s.';
$lang['ei_will_retry'] = 'Not sent yet, it will be tried again automatically.';
$lang['ei_password_unreadable'] = 'The saved email password cannot be read. Type it again in Email Settings.';
$lang['ei_from_invalid'] = 'The sender email address is not valid.';

// the bill email (always English, see top)
$lang['ei_subject'] = 'Your bill %s from %s';
$lang['ei_invoice'] = 'INVOICE';
$lang['ei_tax_invoice'] = 'TAX INVOICE';
$lang['ei_phone'] = 'Phone';
$lang['ei_trn'] = 'TRN';
$lang['ei_invoice_no'] = 'Invoice No';
$lang['ei_date'] = 'Date';
$lang['ei_customer'] = 'Customer';
$lang['ei_customer_trn'] = 'Customer TRN';
$lang['ei_order_type'] = 'Order';
$lang['ei_dine_in'] = 'Dine In';
$lang['ei_take_away'] = 'Take Away';
$lang['ei_delivery'] = 'Delivery';
$lang['ei_table'] = 'Table';
$lang['ei_waiter'] = 'Waiter';
$lang['ei_item'] = 'Item';
$lang['ei_qty'] = 'Qty';
$lang['ei_price'] = 'Price';
$lang['ei_amount'] = 'Amount';
$lang['ei_total_items'] = 'Total items';
$lang['ei_sub_total'] = 'Sub total';
$lang['ei_discount'] = 'Discount';
$lang['ei_service_charge'] = 'Service charge';
$lang['ei_delivery_charge'] = 'Delivery charge';
$lang['ei_tips'] = 'Tips';
$lang['ei_rounding'] = 'Rounding';
$lang['ei_grand_total'] = 'Grand total';
$lang['ei_paid'] = 'Paid';
$lang['ei_due'] = 'Due';
$lang['ei_change'] = 'Change';
$lang['ei_payment'] = 'Payment';
$lang['ei_view_online'] = 'View / print bill online';
$lang['ei_footer_note'] = 'You received this email because you asked %s for an e-bill.';
