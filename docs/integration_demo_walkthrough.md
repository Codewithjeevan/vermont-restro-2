# Integration Platform — demo & test walkthrough

Branch: `feature/integration-platform-talabat`
Companion to [integration_setup_guide.md](integration_setup_guide.md) (the
operator's reference). **This file is the click-by-click script** for showing
the whole thing working, or for testing it yourself.

Runs entirely on Laragon. No Talabat credentials needed — a mock Talabat stands
in for their side.

---

## 0. Demo login — already created

A demo Admin account has been created on this machine's `restodb`:

| | |
|---|---|
| URL | http://trul-resto.test |
| Email | `demo@integration.test` |
| Password | `Demo@123` |
| Role | Admin, company 1, outlets 1 + 2 |

> Local demo credentials only. Delete the account before this database goes
> anywhere near production — §8 has the one-liner.

If it is missing (fresh database, or someone cleaned up), recreate it:

```bash
php -r 'echo md5("Demo@123");'
# -> paste that hash into <MD5> below
```

```sql
INSERT INTO tbl_users
  (full_name,phone,email_address,password,designation,will_login,role,
   outlet_id,outlets,company_id,language,active_status,del_status)
VALUES
  ('Integration Demo','0500000000','demo@integration.test','<MD5>','Admin','Yes','Admin',
   1,'1,2',1,'english','Active','Live');
```

Passwords in this app are plain `md5()` — see
`Authentication::loginCheck()`. That is the existing scheme, not something this
branch introduced.

---

## 1. Before you start

```bash
# 1. Laragon running (Apache + MySQL)

# 2. Both migrations applied - safe to re-run if unsure
mysql -uroot restodb < Update/integration_platform_migration.sql
mysql -uroot restodb < Update/integration_platform_ui_migration.sql

# 3. Health check - should say configured:true, enabled:true
curl http://trul-resto.test/api/integration/talabat/health/TLB-DXB-0042
```

Expected:

```json
{"status":"ok","provider":"talabat","driver_installed":true,
 "configured":true,"enabled":true,"sandbox":true,"outlet_id":1,
 "ingest_on":"placed","auto_accept":"No"}
```

**If you just ran the second migration, log out and log back in.** The sidebar
is filtered by `function_access`, which is built once at login — before that the
three new menus are invisible even to an Admin.

Start the mock Talabat in its own terminal and leave it running:

```bash
node tools/mock-talabat/mock-talabat.js --port=4010
```

---

## 2. The demo, in order

The state on this machine is already set up for it: config enabled, sandbox,
`ingest_on = placed`, `auto_accept = No`, items mapped except one deliberately
unmapped SKU.

### Step 1 — log in and pick the outlet

Log in as the demo user, then **choose outlet "DESIRE CATERING"**. This app
keeps the outlet in the session and most screens are blank-ish until one is
picked. (The Integrations screen falls back to the company's first outlet, but
the POS genuinely needs it.)

### Step 2 — show the settings screen

**Setting → Integrations**

Talabat's card shows *Enabled*, store id `TLB-DXB-0042`, orders today, and the
last order time. The other four providers are catalogue rows with no driver yet —
that is the point of the grid: it loops `tbl_integration_providers`, so adding
app #2 makes a card appear with no code change to the screen.

Open **Settings** on the Talabat card. Two things worth pointing at:

- The **webhook URL** at the top — that string is what you hand Talabat.
- **Punch the order when** (`placed` vs `accepted`) and **Auto accept**. These
  two are the whole "kab punch ho" question, as settings rather than code.

Click **Test connection** — it fetches an OAuth token from the mock and calls
their store-availability endpoint. Green = auth and network are fine.

### Step 3 — fire a real order

```bash
node tools/mock-talabat/send-order.js \
  --url=http://trul-resto.test/api/integration/talabat/webhook \
  --secret=whsec_test_123 \
  --payload=payloads/order-simple.json
```

```
-> POST http://trul-resto.test/api/integration/talabat/webhook
   products   SKU-130 x2 @ 28.35, SKU-138 x1 @ 12.60
   grandTotal 71.30
<- 202 {"status":"accepted","external_order_id":"TLB-1786..."}
```

`202` means *received and durable*. The ACK is sent **before** the order is
punched, on purpose — Talabat retries and can mark the store unreachable if we
are slow.

### Step 4 — it is waiting in the POS

Open the **POS**. Bottom-right, a red pulsing button appears with the count.
Click it: channel, their order number, the total, the drop address, and **how
many minutes it has been waiting** — the number that matters while an SLA ticks.

Click **Accept**. The order becomes a running order and the KOT prints. That is
the same DB effect the POS already uses for self-orders (`is_accept = 1`), so no
second order pipeline exists.

> Nothing appears? `auto_accept` is probably back to `Yes`, so orders never
> wait. Set it to No on the settings screen and re-send.

### Step 5 — the money is right

This is the part worth checking carefully, because it is the easiest thing to
get quietly wrong.

Talabat charged **AED 28.35** for the biryani. UAE aggregator prices are
VAT-inclusive, so the POS stores **27.00 net + 1.35 VAT** — which is exactly our
own menu price of 27.

```bash
mysql -uroot restodb -e "SELECT sale_no,sub_total,vat,total_payable,channel_code,token_number FROM tbl_kitchen_sales ORDER BY id DESC LIMIT 1\G"
```

```
      sale_no: INV-1-2x
    sub_total: 66            <- ex-VAT
          vat: 3.3
total_payable: 71.3          <- equals Talabat's grandTotal exactly
 channel_code: talabat
 token_number: 8829301       <- their order number, prints on the KOT
```

If our numbers ever disagree with theirs, the order row carries a
`Total mismatch: provider X vs computed Y` note instead of quietly booking a
different amount.

### Step 6 — settle it, and Talabat finds out

Settle the bill in the POS as normal.

```bash
php index.php Integration_cron run_queue
curl -s http://127.0.0.1:4010/_calls
```

```json
{"method":"POST","path":"/orders/TLB-.../status",
 "body":{"status":"delivered","occurredAt":"..."}}
```

`COMPLETED` is our word, `delivered` is Talabat's. That translation lives in
`$status_map` inside `Talabat_driver` and nowhere else — which is the test of
whether the architecture actually held.

In production `run_queue` is a cron entry every minute; here you run it by hand
so the demo is visible.

### Step 7 — the order log

**Setting → Channel Orders**

Every inbound order with its status. Open **Details** on one: full timeline
(received / punched / accepted / completed, all UTC), the outbound events with
their retry state, and the raw payload kept for disputes.

---

## 3. The failure cases (worth demoing — they are the interesting half)

### An item we do not sell

```bash
node tools/mock-talabat/send-order.js \
  --url=http://trul-resto.test/api/integration/talabat/webhook \
  --secret=whsec_test_123 --payload=payloads/order-unmapped.json
```

Still `202` (the event *was* received), but Channel Orders shows it as
**REJECTED — UNMAPPED_ITEM**, and a rejection is pushed back to Talabat.

The whole order is rejected rather than one line silently dropped: a missing
line item is a refund and a rating hit; a rejection is recoverable.

Now fix it live: **Setting → Channel Item Mapping**, filter *Unmapped only*,
pick a menu item for `SKU-999999`. It saves on change. Re-send and it goes
through.

### A forged webhook

```bash
node tools/mock-talabat/send-order.js --url=... --secret=whsec_test_123 --bad-sign
node tools/mock-talabat/send-order.js --url=... --secret=whsec_test_123 --no-sign
node tools/mock-talabat/send-order.js --url=... --secret=whsec_test_123 --skew=900
```

All three → `401`. Wrong signature, no signature, and a genuine-but-15-minute-old
event replayed.

### The same order twice

```bash
node tools/mock-talabat/send-order.js --url=... --secret=whsec_test_123 --token=TLB-DEMO-DUP
node tools/mock-talabat/send-order.js --url=... --secret=whsec_test_123 --token=TLB-DEMO-DUP
```

Second one → `200 {"status":"duplicate"}`, and only **one** sale exists. This is
the single most important line in the migration: aggregators retry hard, and
without it you get double KOTs on a busy Friday.

### The channel switched off

Set **Enabled = No** on the settings screen, send an order → `200 ignored`. Not
an error: a disabled channel must not look broken to them.

---

## 4. The whole thing in one shot

For a quick regression rather than a demo:

```bash
node tools/mock-talabat/run-tests.js \
  --base=http://trul-resto.test --secret=whsec_test_123 --store=TLB-DXB-0042
```

13 scenarios: auth, replay, routing, idempotency, modifiers, unmapped items,
cancellation. It asserts the HTTP contract only, then prints the CLI commands to
check what actually landed in the database.

---

## 5. Optional: a realistic settlement method

Aggregator revenue should be booked **gross** against its own payment method and
reconciled monthly against their statement — commission is never netted into the
sale.

```sql
INSERT INTO tbl_payment_methods (name,description,user_id,company_id,order_by,del_status)
VALUES ('Talabat Credit','Talabat settlement',1,1,10,'Live');
```

Then pick it under **Payment method** on the Talabat card.

---

## 6. Reset to a clean slate

Wipes every channel order and the sales they created, keeps the config and the
item mapping.

```sql
DELETE d FROM tbl_kitchen_sales_details d
  JOIN tbl_kitchen_sales k ON k.id = d.sales_id
  WHERE k.channel_code IS NOT NULL;
DELETE m FROM tbl_kitchen_sales_details_modifiers m
  JOIN tbl_kitchen_sales k ON k.id = m.sales_id
  WHERE k.channel_code IS NOT NULL;
DELETE FROM tbl_kitchen_sales WHERE channel_code IS NOT NULL;
DELETE FROM tbl_integration_events;
DELETE FROM tbl_integration_orders;
DELETE FROM tbl_integration_logs;
```

Bill numbers are **not** reset — `tbl_sale_no_counters` only ever moves forward,
by design. Gaps in the invoice sequence after a demo are expected.

To also clear the mapping so you can demo auto-map from scratch:

```sql
DELETE FROM tbl_integration_item_map WHERE config_id = 1;
```

---

## 7. CLI equivalents

Everything above can be driven headless — useful when a screen will not load, or
to script a new outlet.

```bash
php index.php Integration_cli providers                    # catalogue + which drivers exist
php index.php Integration_cli show     --config=1          # config, secrets masked
php index.php Integration_cli orders                       # inbound orders
php index.php Integration_cli unmapped --config=1
php index.php Integration_cli automap  --config=1 --apply
php index.php Integration_cli accept   --order=TLB-...
php index.php Integration_cli reject   --order=TLB-... --reason=ITEM_86
php index.php Integration_cli logs     --limit=20          # every call, in and out
php index.php Integration_cron run_queue
php index.php Integration_cron sweep_timeouts
```

Switch the demo between the two behaviours:

```bash
# order waits for a human (POS widget demo)
php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 --auto-accept=No

# order punches and prints with nobody touching the POS
php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 --auto-accept=Yes
```

---

## 8. When the demo is over

```sql
DELETE FROM tbl_users WHERE email_address = 'demo@integration.test';
```

And before anything goes live, from the setup guide §6: `$config['encryption_key']`
is currently the 6-character string `arbDbj`. Set a long random key and re-enter
the credentials afterwards — changing the key invalidates everything already
stored.

---

## 9. If something will not work

| Symptom | Cause | Fix |
|---|---|---|
| The three Setting menus are missing | `function_access` is built at login | log out, log back in |
| Still missing after re-login | ids 362–370 taken on this install | `SELECT * FROM tbl_access WHERE id BETWEEN 362 AND 370`, then renumber the UI migration **and** the `ACCESS_*` constants in `Integration.php` |
| Webhook → `404 No outlet is configured` | store id mismatch | the payload's `platformRestaurant.id` must equal the config's Store ID |
| Webhook → `401` | wrong/missing signature, or clock more than 5 min out | `--secret` must match the config's webhook secret |
| Webhook → `200 ignored — Waiting for the "accepted" event` | `ingest_on = accepted`, event was `order.created` | set *Punch the order when* → **Placed by the customer**, or send `--event=order.accepted` |
| POS widget never appears | `auto_accept = Yes`, so nothing waits | set it to No |
| Settled, but Talabat never told | no cron run | `php index.php Integration_cron run_queue` |
| A CLI command prints nothing and exits 1 | `ENVIRONMENT` is `production`, so fatals are hidden | `sed "s/'production'/'development'/" index.php > _dev.php`, run `php _dev.php ...`, then delete `_dev.php` |

Every call in and out is on `tbl_integration_logs` with the raw bodies
(credentials and bearer tokens redacted): `php index.php Integration_cli logs`.
