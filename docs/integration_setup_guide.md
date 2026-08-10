# Integration Platform — setup & testing guide

Branch: `feature/integration-platform-talabat`
Design doc: [integration_platform_talabat_uae.md](integration_platform_talabat_uae.md)

This is the **operator's guide**: install it, wire a channel up, prove it works
against a mock Talabat, and know what is deliberately not built yet.

Everything below was run end to end against this repo on MySQL 8.0.30 / PHP 8.2
before it was written down.

---

## 1. What this branch ships

Phase 1 + 2 of the design doc: **an order placed on Talabat auto-punches into
the POS as a running order and the KOT prints.**

```
application/
  controllers/
    Integration_api.php     REST webhook — public, no session          NEW
    Integration_cli.php     setup / mapping / accept / inspect (CLI)   NEW
    Integration_cron.php    outbound queue worker (CLI)                NEW
  models/
    Integration_model.php   every DB read/write                        NEW
  libraries/Integration/
    Channel_driver_interface.php   the contract                        NEW
    Base_channel_driver.php        HTTP, OAuth, logging, HMAC          NEW
    Integration_manager.php        registry + factory + on/off         NEW
    Integration_crypto.php         AES-256-CBC for credentials         NEW
    Order_ingestor.php             DTO -> POS tables                   NEW
    Drivers/Talabat_driver.php     the only Talabat-aware file         NEW
  config/routes.php         + 3 routes                                 EDIT
Update/
  integration_platform_migration.sql   6 tables + 6 columns            NEW
tools/mock-talabat/
  mock-talabat.js           fake Talabat API (receives our calls)      NEW
  send-order.js             send one signed webhook                    NEW
  run-tests.js              13-scenario suite                          NEW
  payloads/*.json           sample orders                              NEW
```

**Not built yet** (phase 3/4 — see §8): the admin settings screen, the item
mapping screen, the order log screen, the POS Accept/Reject buttons, and the
`Status_dispatcher` hooks inside `Sale.php` that fire on settle.

---

## 2. Prerequisites

| | |
|---|---|
| PHP | 8.2 with `curl` and `openssl` (both already on in Laragon) |
| MySQL | 8.0+ — the migration uses `information_schema` guards, not MariaDB's `ADD COLUMN IF NOT EXISTS` |
| Node | 18+ — only for the mock tooling, never at runtime |
| POS URL | anything Laragon serves, e.g. `http://trul-resto.test` |

**For real Talabat traffic you also need a public HTTPS endpoint with a valid
certificate.** A Laragon / LAN install cannot receive webhooks. This is a hard
prerequisite, not a nice-to-have — see design doc §9. Everything in this guide
works on localhost because the mock sends the webhook from the same machine.

---

## 3. Install

### 3.1 Run the migration

```bash
mysql -uroot restodb < Update/integration_platform_migration.sql
```

Additive and safe to re-run. It creates six `tbl_integration_*` tables and adds
`channel_code` / `external_order_id` / `integration_order_id` to **both**
`tbl_kitchen_sales` and `tbl_sales`, so existing reports can gain a channel
filter later without another schema change.

Check it landed:

```bash
php index.php Integration_cli providers
```

```
talabat      Talabat              active=Yes driver=installed
deliveroo    Deliveroo            active=No  driver=MISSING
noon         Noon Food            active=No  driver=MISSING
```

`MISSING` is correct — those are catalogue rows for providers whose driver is
not written yet. That is the whole point of the catalogue.

### 3.2 Create a config for one outlet

There is no settings screen yet, so this is the CLI:

```bash
php index.php Integration_cli setup \
  --provider=talabat --company=1 --outlet=1 \
  --store=TLB-DXB-0042 \
  --enabled=Yes --sandbox=Yes \
  --ingest-on=placed --auto-accept=No --prep=20 \
  --price-mode=delivery --price-source=provider --price-includes-tax=Yes \
  --partner=1 \
  --secret=whsec_test_123 \
  --sandbox-base-url=http://127.0.0.1:4010 \
  --token-url=http://127.0.0.1:4010/oauth/token
```

The flags that actually change behaviour:

| Flag | Meaning |
|---|---|
| `--store` | **Their** store id. The webhook payload carries this and nothing else, so it is the only thing that resolves an inbound order to a company + outlet. Get it wrong and orders 404. |
| `--enabled` | The on/off button. Honoured in three places: the webhook (200 + ignore), the queue worker (skip), and status pushes (never enqueued). |
| `--ingest-on` | `placed` = punch the moment the customer orders. `accepted` = punch when the store accepts on the Talabat tablet. Same code path, different event filter. |
| `--auto-accept` | `Yes` = the order lands as a **running order** and the KOT prints immediately, and an accept is pushed back to Talabat. `No` = it lands at `is_accept = 2` and waits for a human. |
| `--price-source` | `provider` (default) uses Talabat's price — the customer already paid that number. `pos` uses our own menu price. |
| `--price-mode` | Which POS price to use when `price-source=pos`: `delivery` → `sale_price_delivery`, falling back to `sale_price` when the delivery price is 0 (it is 0 for every item in this dataset, so the fallback matters). |
| `--price-includes-tax` | `Yes` for UAE — aggregator menu prices are VAT-inclusive, so the 5% is back-computed out of the price rather than added on top. |
| `--secret` | Shared HMAC secret for inbound webhooks. Stored encrypted. |
| `--partner` | `tbl_delivery_partners.id`, so existing partner reports keep working. |

Re-run `setup` any time to change one flag — it merges rather than replaces.
Inspect with `php index.php Integration_cli show --config=1` (secrets masked).

### 3.3 Create a settlement payment method (recommended)

Book aggregator revenue **gross** against its own method and reconcile the
commission monthly against their statement. Do not net commission into the sale.

Create a `Talabat Credit` payment method in the POS, then:

```bash
php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 --payment=<id>
```

### 3.4 Map the menu

The mapping table starts empty. **Unmapped SKUs are recorded as they arrive**,
so the fastest route is: send one order, then map what it reported.

```bash
node tools/mock-talabat/send-order.js \
  --url=http://trul-resto.test/api/integration/talabat/webhook \
  --secret=whsec_test_123

php index.php Integration_cli unmapped --config=1
php index.php Integration_cli automap  --config=1            # dry run
php index.php Integration_cli automap  --config=1 --apply
```

`automap` matches two ways and refuses to guess beyond them:

1. a numeric suffix on the SKU that is a real `food_menu_id` (`SKU-130` → #130)
2. an exact, case-insensitive name match

Anything else prints `? no match` and stays for a human. Guessing a menu item is
how you serve the wrong food.

Manual mapping:

```bash
php index.php Integration_cli map    --config=1 --external=SKU-771 --menu=130
php index.php Integration_cli mapmod --config=1 --external=MOD-9  --modifier=1
```

> **An unmapped item rejects the whole order** rather than dropping the line.
> A silently missing line item is a refund and a rating hit; a rejection is
> recoverable and shows up in `Integration_cli orders`.

---

## 4. Testing

### 4.1 Is it wired up?

```bash
curl http://trul-resto.test/api/integration/talabat/health/TLB-DXB-0042
```

```json
{"status":"ok","provider":"talabat","driver_installed":true,
 "configured":true,"enabled":true,"sandbox":true,"outlet_id":1,
 "ingest_on":"placed","auto_accept":"No","last_order":null,
 "server_time_utc":"2026-08-10T16:41:22+00:00"}
```

Safe to expose: no secret, no payload. `configured:false` means the store id in
the URL does not match any config.

### 4.2 Send one order

```bash
node tools/mock-talabat/send-order.js \
  --url=http://trul-resto.test/api/integration/talabat/webhook \
  --secret=whsec_test_123 \
  --payload=payloads/order-simple.json
```

```
-> POST http://trul-resto.test/api/integration/talabat/webhook
   token      TLB-1786380122341
   products   SKU-130 x2 @ 28.35, SKU-138 x1 @ 12.60
   grandTotal 71.30
<- 202 {"status":"accepted","external_order_id":"TLB-1786380122341"}
```

`202` means *received and durable* — the ACK is deliberately sent **before**
ingestion, because Talabat retries (and can mark the store unreachable) if we
are slow. Whether the punch itself succeeded is on the order row:

```bash
php index.php Integration_cli orders
```

```
id   provider   external          sale_no    status     received (UTC)       note
2    talabat    TLB-1786380122341 INV-1-21   RECEIVED   2026-08-10 16:42:02
1    talabat    TLB-TEST-001                 REJECTED   2026-08-10 16:41:35  Unmapped item(s): SKU-130 (Chicken Biryani)
```

Useful flags: `--token=TLB-X` (fix the id, to test retries), `--event=...`,
`--store=...`, `--no-sign`, `--bad-sign`, `--skew=900`.

### 4.3 The scenario suite

```bash
node tools/mock-talabat/run-tests.js \
  --base=http://trul-resto.test --secret=whsec_test_123 --store=TLB-DXB-0042
```

```
Health
  PASS  health endpoint answers                    expected 200, got 200
Authentication
  PASS  unsigned request is rejected               expected 401, got 401
  PASS  wrong signature is rejected                expected 401, got 401
  PASS  signature from a wrong secret is rejected  expected 401, got 401
  PASS  replayed (15 min old) event is rejected    expected 401, got 401
Routing
  PASS  unknown store id does not silently vanish  expected [404,200,202], got 202
  PASS  ping is answered                           expected 200, got 200
Order ingestion
  PASS  valid order is accepted                    expected [202,200], got 202
  PASS  retry of the same order is a no-op         expected 200, got 200
  PASS  retry is reported as a duplicate           expected true, got true
  PASS  order with modifiers is accepted           expected [202,200], got 202
  PASS  unmapped item is ACKed, not 500            expected [202,200], got 202
Cancellation
  PASS  cancellation of a known order is handled   expected 200, got 200

13/13 scenarios matched.
```

The suite asserts the **HTTP contract only**. It does not claim to have checked
the database — it prints the CLI commands to do that instead. Run them.

Two results worth understanding rather than skimming:

- *"unknown store id"* returns `202` here because exactly one enabled sandbox
  config exists, and the resolver deliberately absorbs unknown store ids in that
  case. Otherwise a test payload with a store id nobody filled in yet would
  vanish with no trace of why. With two or more configs it returns `404`.
- *"unmapped item is ACKed"* — the ACK is honest: the event **was** received.
  The rejection happens after, and lands on `tbl_integration_orders`.

### 4.4 Verify the sale is correct

This is the check that actually matters — the money has to be right.

```bash
mysql -uroot restodb -e "SELECT sale_no,sub_total,vat,total_payable,order_type,is_accept,is_online_order,channel_code,token_number FROM tbl_kitchen_sales WHERE external_order_id='TLB-TEST-002'\G"
```

```
      sale_no: INV-1-21
    sub_total: 66            <- ex-VAT, POS convention
          vat: 3.3
total_payable: 71.3          <- equals Talabat's grandTotal exactly
   order_type: 3             <- Delivery
    is_accept: 2             <- pending (auto_accept was No)
is_online_order: Yes
 channel_code: talabat
 token_number: 8829301       <- their order number, prints on the KOT
```

And the lines, where the VAT-inclusive back-computation shows up:

```
food_menu_id  menu_name         qty  menu_unit_price  menu_price_without_discount
130           Chicken Biryani   2    27               54
138           Bhajiya           1    12               12
```

Talabat charged AED 28.35 for the biryani. 28.35 / 1.05 = **27.00 net + 1.35
VAT** — which is exactly the POS menu price. If your numbers do not line up like
this, the order row carries a warning in its `note` column
(`Total mismatch: provider X vs computed Y`) and the cause is almost always
`price_includes_tax` or `price_mode`.

The full POS cart JSON is written to `self_order_content`; the POS parses it to
rebuild the cart when the cashier opens the order. If that column is empty or
malformed, the order exists but cannot be opened.

### 4.5 Accept an order and see it in the POS

With `--auto-accept=No` the order sits at `is_accept = 2`. Accept it:

```bash
php index.php Integration_cli accept --order=TLB-TEST-002
```

```
Accepted TLB-TEST-002 (INV-1-21). It is now a running order in the POS.
```

That flips `is_accept = 1` and `self_order_status = Approved`, which is exactly
what the POS Accept button will do in phase 3. Log into the POS for outlet 1 and
the order appears as a running order; the KOT prints on the next batch
(`is_print = 1` on every line).

> **Note on this dataset:** `tbl_outlets.online_self_order_receiving_id` is `0`
> for both outlets, so the injected order is routed to the company Admin's tray
> (`order_receiving_id_admin`). If the client uses a dedicated online-order
> terminal, set that column and the order follows it.

To reject instead: `php index.php Integration_cli reject --order=TLB-X --reason=ITEM_86`

### 4.6 Test the outbound half against the mock

Start the fake Talabat:

```bash
node tools/mock-talabat/mock-talabat.js --port=4010
```

Then accept or reject something and drain the queue:

```bash
php index.php Integration_cli accept  --order=TLB-TEST-002
php index.php Integration_cron run_queue
```

```
  #4 order.accept   TLB-TEST-002           OK (200)
```

And what the mock actually received:

```bash
curl -s http://127.0.0.1:4010/_calls
```

```json
{"method":"POST","path":"/oauth/token","status":200}
{"method":"POST","path":"/orders/TLB-TEST-002/accept",
 "authorization":"Bearer ***",
 "body":{"remoteResponse":{"remoteOrderId":"TLB-TEST-002","remoteResponse":"accepted"},
         "preparationTimeMinutes":20},"status":200}
```

The OAuth token is fetched once and cached on the config row until 60s before
expiry — run the worker again and you will see the accept without a second
`/oauth/token` call.

To watch the backoff: stop the mock, enqueue something, run the worker twice.
Failures retry at 1m, 5m, 15m, 1h, 6h, 12h and then dead-letter with the error
on the event row.

### 4.7 The full "autopunch" demo

The headline behaviour, in four commands:

```bash
php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 --auto-accept=Yes
node tools/mock-talabat/mock-talabat.js --port=4010 &
node tools/mock-talabat/send-order.js --url=http://trul-resto.test/api/integration/talabat/webhook --secret=whsec_test_123
php index.php Integration_cron run_queue
```

Order lands → punched as a running order → KOT prints → accept pushed back to
Talabat. No human touched the POS.

### 4.8 Cron entries

```
php index.php Integration_cron run_queue        every 1 min   outbound pushes
php index.php Integration_cron sweep_timeouts   every 5 min   un-accepted orders past SLA -> auto reject
```

On Windows use Task Scheduler with `Start in` set to the project root. Both
commands hard-block web access (`if (!is_cli()) show_404()`), so they cannot be
triggered from a browser.

---

## 5. How it behaves — the rules worth knowing

1. **Idempotency comes first.** The row goes into `tbl_integration_orders` under
   a `UNIQUE (provider_code, external_order_id)` key **before** any other write.
   A retry hits the key and returns 200 without punching. This single line is
   what stops double KOTs on a busy Friday.
2. **ACK fast, work after.** 202 is returned as soon as the order row is
   durable. Under php-fpm the connection closes cleanly via
   `fastcgi_finish_request()`; under Apache mod_php there is no equivalent and
   the socket stays open for the few inserts that follow. The idempotency key,
   not the flush, is what actually protects against an impatient aggregator.
3. **Bill numbers come from the server counter.** `reserveCompanySaleNumbers()`
   only. A `sale_no` is never invented.
4. **Only the kitchen pair is written.** `tbl_sales` is written when the cashier
   settles, exactly as for a walk-in. Writing both here would double-count.
5. **Unmapped item rejects the order.** See §3.4.
6. **UTC in, local out.** `tbl_integration_*` stores UTC. `tbl_kitchen_sales`
   stores application-local time because POS screens assume it — this install
   has three disagreeing clocks already and adding a fourth convention inside
   one table is how reports start lying.
7. **The on/off button is honoured in three places** — webhook, queue worker,
   status pushes — so disabling a channel is immediate and total.

---

## 6. Two deliberate deviations from the design doc

Both were found while building against the real codebase.

**`Custom::encrypt_decrypt()` is a no-op.** Design doc §10 says to reuse it for
credentials at rest. It returns its input unchanged
([Custom.php:3](../application/libraries/Custom.php#L3)), so using it would have
stored aggregator client secrets in plaintext. Replaced with
`Integration_crypto` — AES-256-CBC plus an HMAC, keyed off
`$config['encryption_key']`. Values written by hand still decrypt to themselves,
so hand-editing a config for a test keeps working.

> `$config['encryption_key']` is currently `arbDbj`. That is fine for a sandbox
> and **not** fine for production — set a long random key before go-live, and
> re-enter the credentials afterwards (changing the key invalidates them).

**Item mapping moved from the driver to the ingestor.** The doc's DTO has the
driver fill in `food_menu_id`. Doing the lookup in `Order_ingestor` instead
keeps every driver a pure function with no database access, which makes a new
driver both smaller and trivially testable. A driver may still pre-resolve
`food_menu_id`; the ingestor honours it when present.

---

## 7. Going live with real Talabat

Phase 0 is commercial, not technical, and it is the real schedule risk. Before
any of this matters: the merchant account must be live, API or middleware access
granted, and a public HTTPS host ready.

When credentials arrive, **only three things in `Talabat_driver.php` change**:

1. `verify_webhook()` — their real header name and signing scheme
2. `normalize_order()` — field names, if they differ from the Delivery Hero shape
3. `$status_map` + the endpoint paths in `accept_order` / `reject_order` / `push_status`

Then:

```bash
php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 \
  --sandbox=No --base-url=https://<their-api> --token-url=https://<their-oauth> \
  --client-id=... --client-secret=... --secret=<their webhook secret> \
  --store=<their real store id>
```

Give them `https://<your-host>/api/integration/talabat/webhook` as the webhook
URL. Nothing outside the driver moves — that is the test of whether the
architecture held.

**If the client goes via a middleware** (Deliverect / Grubtech / Otter — all
live in UAE and weeks faster than direct approval), it is one new driver file
and one catalogue `INSERT`, not a redesign.

### Adding app #2

1. `INSERT` a row into `tbl_integration_providers` (or flip an existing one to `is_active='Yes'`)
2. Write `libraries/Integration/Drivers/<X>_driver.php` extending `Base_channel_driver`
3. Configure it, map items, test in sandbox

Zero changes to `Integration_api`, `Order_ingestor`, `Integration_cron`,
`Sale.php`, the routes, or the schema.

---

## 8. What is deliberately not built yet

| | Where it goes |
|---|---|
| Settings screen (provider grid, per-outlet drawer, Test Connection) | `views/integration/settings.php` + the 6-step menu checklist in design doc §8 |
| Item mapping screen | `views/integration/item_map.php` |
| Order log screen with raw payload + retry | `views/integration/order_log.php` |
| POS Accept / Reject buttons on the incoming tray | `views/sale/POS/` — the DB effect is already proven by `Integration_cli accept` |
| `Status_dispatcher` hooks so settling a bill pushes `COMPLETED` | one line at `Sale::update_order_status_ajax()` ([Sale.php:3041](../application/controllers/Sale.php#L3041)) and `Sale::change_status_of_a_sale_ajax()` |
| Channel-wise sales report | `channel_code` is already on both sale tables for exactly this |
| Menu push / item 86 / store open-close | `push_menu()` and `set_item_availability()` are declared on the interface and return an honest "unsupported" today |

The outbound queue worker (`Integration_cron`) is phase-3 work that was pulled
forward, because without a consumer the mock server has nothing to receive and
the outbound half could not be demonstrated at all.

---

## 9. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| CLI prints nothing, exits 1 | `ENVIRONMENT` is `production` in `index.php`, so a fatal error is swallowed | `sed "s/'production'/'development'/" index.php > _dev.php && php _dev.php Integration_cli ...` then delete `_dev.php` |
| Webhook returns `404 Unknown provider` | provider code not in the URL / not seeded | `php index.php Integration_cli providers` |
| Webhook returns `404 No outlet is configured for store id "..."` | `--store` does not match the payload's `platformRestaurant.id` | re-run `setup --store=<their id>` |
| Webhook returns `401` | wrong secret, missing signature header, or a timestamp more than 5 minutes old | compare `--secret` with `Integration_cli secret --config=1` |
| Webhook returns `200 ignored — Integration is disabled` | the on/off button | `setup --enabled=Yes` |
| Webhook returns `200 ignored — Waiting for the "accepted" event` | `ingest_on=accepted` but the event was `order.created` | `setup --ingest-on=placed`, or send `--event=order.accepted` |
| Order row says `REJECTED / UNMAPPED_ITEM` | menu not mapped | `Integration_cli unmapped --config=1`, then `automap --apply` |
| Order row has a `Total mismatch` note | our prices or tax mode disagree with theirs | check `price_includes_tax` and `price_mode` |
| Sale exists but the POS will not open it | `self_order_content` empty or malformed | it is written at the end of the ingest transaction — check `Integration_cli logs` |
| Queue events stay `pending` | no worker running | `php index.php Integration_cron run_queue` |
| Queue events go `dead` immediately | no `base_url`, or the provider is unreachable | `setup --sandbox-base-url=...`, check `Integration_cli logs` |

Every inbound and outbound call is on `tbl_integration_logs` with the raw bodies
(credentials and bearer tokens redacted):

```bash
php index.php Integration_cli logs --limit=20
```
