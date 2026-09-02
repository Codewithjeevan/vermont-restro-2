# noon Food — Integration Plan

Branch: `feature/integration-platform-noon` (off `feature/integration-platform-talabat`)
Status: **implemented against the mock — 13/13 inbound scenarios green, thin+fat, cookie auth, outbound queue verified. Field mapping stays provisional until noon's spec (Phase 0) lands.**
Related: `docs/integration_platform_talabat_uae.md`, `docs/integration_setup_guide.md`
See §13 for where the implementation deviates from this plan and why.

---

## 0. Executive summary

| Question | Answer |
|---|---|
| Can we reuse the Talabat platform? | **Yes — almost entirely.** noon Food = 1 new driver file + 1 SQL `UPDATE` + one small additive change to `Base_channel_driver`. |
| How many shared files change? | **Zero** in `Order_ingestor`, `Integration_manager`, `Status_dispatcher`, `Integration_cron`, the DB schema, the POS JS, or the routes. |
| Is there a public noon Food API spec? | **No.** noon publishes docs for the *marketplace* only. noon Food is partner-gated. |
| Does that block us? | **No.** Same playbook as Talabat: build against a provisional mapping behind a single file, ship a mock server, swap three things when the real spec lands. |
| Extra requirement | **Talabat must be invisible to the client.** Handled in Phase 1 with a visibility flag — one query change, no code deletion. |

The whole point of the driver architecture was that app #2 costs one file. This plan is the test of that claim, and the claim holds.

---

## 1. What we actually know about noon's API

### 1.1 The documentation situation — read this first

The client has a **noon Food** API key (confirmed by the client). Two different noon platforms exist and they are **not** the same product:

| | noon Marketplace | **noon Food** |
|---|---|---|
| Portal | `welcome.noon.partners` (Catalog, FBN, FBP, Seller Logistics) | `food-partners.noon.com` |
| Docs | Public — `noon-docs.noonpartners.dev` | **None published** |
| Domain | Physical goods, warehouses, AWBs, shipments | Restaurants, menus, riders |
| Order flow | `FBPI::ORDER_SYNC` webhook → GET order → create shipment | unknown to us |

Everything on `noon-docs.noonpartners.dev` — FBPI, Stock, Pricing, Catalog, Warehouse, Returns — is the **marketplace** product. There is no menu API, no store-hours API, no accept/reject, no prep time, no KOT anywhere in it. It is the wrong product for a restaurant POS.

So: **we cannot write the exact field mapping from documentation. We must get it from noon Food's integration team.** Phase 0 below is that request, and it runs in parallel with everything else.

### 1.2 What we can infer (to be confirmed, not assumed)

noon Food sits on noon's platform infrastructure, so these conventions are *likely* to carry over. Each one is a question in Phase 0, not a decision:

- **Auth is RS256 JWT → session cookie**, not OAuth2 Bearer. A service-account `.json` key file holds `key_id`, `private_key`, `project_code`, `channel_identifier`. You sign a JWT (`sub` = key_id, `iat`, `jti`) with the private key, `POST /identity/public/v1/api/login`, and noon sets **session cookies** you carry on every later call. Session lifetime **30 days**.
- **`User-Agent` header is mandatory** on every request. Requests without it may be rejected.
- **Hosts** follow `<app>-api-gateway.noon.partners` and `<app>-sandbox-api-gateway.noon.partners`.
- **Webhooks are push, at-least-once**, with a standard envelope:
  ```json
  { "event_schema_version": 1,
    "event_type": "NAMESPACE::EVENT",
    "metadata": { "message_id": "...", "destination_id": "...",
                  "published_at": "...", "project_code": "..." },
    "payload": { } }
  ```
- **Webhook auth is static key-value credentials, not HMAC.** You register a destination and attach up to 3 key-value pairs that noon sends as headers on every delivery. There is no signature to verify — you compare a shared secret in constant time.
- **Delivery source IPs** (marketplace side): `34.76.219.109`, `35.233.95.217`, `104.155.39.2`.
- **Retries: up to 50 attempts**, backoff 2 → 4 → 8 → 16 → 32 → 60 min, capped at one per hour. Duplicates are expected — idempotency is required. Our `uq_ext` unique key already provides it.

> Two of these differ materially from Talabat and drive real code: **cookie auth instead of Bearer**, and **shared-secret header instead of HMAC signature**. Both are handled in Phase 2/3 without touching Talabat.

### 1.3 The open question that changes the driver's shape

Does noon Food send a **fat webhook** (full order JSON, like Talabat) or a **thin webhook** (an order id only, then you GET the details, like marketplace FBPI)?

- Fat → `normalize_order()` maps the payload directly. Identical to Talabat.
- Thin → the driver must call back to noon for the order before it can build the DTO.

We support both (§6.2), so the answer does not block us.

---

## 2. Reuse analysis — file by file

### 2.1 Unchanged. Zero lines.

| File | Why it needs nothing |
|---|---|
| `Update/integration_platform_migration.sql` | 6 tables + channel columns are provider-agnostic. **The `noon` provider row is already seeded** (`is_active='No'`, `driver_class='Noon_driver'`). |
| `libraries/Integration/Integration_manager.php` | Registry/factory — resolves any code to any driver class. |
| `libraries/Integration/Order_ingestor.php` | Canonical DTO → `tbl_kitchen_sales` + details + modifiers. Knows no provider name. |
| `libraries/Integration/Status_dispatcher.php` | POS status → outbound queue. Provider-agnostic. |
| `libraries/Integration/Integration_crypto.php` | Credential encryption at rest. |
| `controllers/Integration_cron.php` | Queue drain + retry/backoff. |
| `config/routes.php` | **`api/integration/(:any)/webhook` already routes noon.** No new route. |
| `views/integration/*.php` | Settings grid loops the provider table. |
| `frequent_changing/js/integration_pos.js` | Accept/Reject widget renders `order.provider` generically. |
| `language/*/*_lang.php` | All strings are generic (`integration_accept`, not `talabat_accept`). |
| `views/sale/POS/main_screen.php` | Widget mount point only. |
| `controllers/Sale.php` | The settle hook `notifyIntegrationStatus()` already fires `COMPLETED` on settle **and** on "Delivered", guarded by `file_exists()` + try/catch so a broken integration can never block a bill. **noon inherits the whole POS-to-channel status push for free.** |
| `controllers/Outlet.php`, `models/Sale_model.php` | One-line integration touch-ups from the platform commit. Nothing provider-specific. |

**One exception in the "unchanged" list.** `controllers/Integration_cli.php` is provider-agnostic in its logic but **defaults to `--provider=talabat`** in two places (the usage banner at line 41 and the `setup` command at line 84). For noon we either pass `--provider=noon` every time or change the default. Given Phase 1's requirement that nothing on the client's server says "Talabat", **change the default to `noon`** — it is a two-line edit.

Our webhook URL is therefore already decided and needs no work:

```
POST https://<host>/api/integration/noon/webhook
GET  https://<host>/api/integration/noon/health/<external_store_id>
```

### 2.2 Changed — the entire cost of this feature

| # | Change | Size | Risk |
|---|---|---|---|
| 1 | `libraries/Integration/Drivers/Noon_food_driver.php` — new | ~300 lines | mapping is provisional until Phase 0 lands |
| 2 | `Update/noon_food_migration.sql` — activate noon, hide Talabat | ~25 lines | none — additive and guarded |
| 3 | `Base_channel_driver` — cookie-session auth **next to** the existing OAuth2 | ~70 lines, additive | none — Talabat's `oauth_token()` path untouched |
| 4 | `Channel_driver_interface` + `Base_channel_driver` — optional `fetch_order()` hook | ~20 lines | none — default returns `null`, so Talabat behaves identically |
| 5 | `Integration_api::ingest_now()` — honour `fetch_order()` | 3 lines | none |
| 6 | `Integration_model::getVisibleProviders()` + 3 call sites in `Integration.php` | ~15 lines | none |
| 7 | `tools/mock-noon/` — mock server, cloned from `tools/mock-talabat/` | ~300 lines | test-only |
| 8 | `Integration_cli.php` — change the two `talabat` defaults to `noon` | 2 lines | none |

**That is the whole diff.** Nothing in the ingest path, the money path, or the KOT path moves.

---

## 3. Phase 0 — what we must get from noon (starts now, blocks nothing)

Send this to the noon Food partner / integration contact. Until it arrives we build against the mock.

**Environments & auth**
1. Sandbox and production base URLs for noon Food.
2. Is auth the same RS256-JWT → session-cookie flow as the noon platform (`POST /identity/public/v1/api/login`)? If not, what is it? Is the key we already hold the right one, or is a separate food service account needed?
3. Required headers on every call (`User-Agent`? `x-locale`? project / vendor id?).
4. Session or token lifetime and refresh behaviour.

**Inbound orders**
5. Do we register a webhook URL ourselves (self-service portal) or does noon configure it? Where?
6. **Fat or thin webhook** — full order JSON, or an id we then GET?
7. Full sample payloads: simple order, order with modifiers/options, pre-order/scheduled, pickup, and a cancellation.
8. How is the webhook authenticated on our side — static header credentials, HMAC signature, mTLS, IP allowlist? Give header names, and the exact signed string if it is an HMAC.
9. Delivery source IPs to allowlist.
10. Retry policy and the ACK timeout — how fast must we return 2xx?
11. **Which field carries their store/branch id for this outlet?** This is the only routing key we get, so it is mandatory.
12. **Which field is the item SKU / remote code** we map our menu against? Same question for modifiers/options.
13. Is the order **prepaid** on noon's side, and which field says so? Is VAT included in item prices?
14. Are there separate `order.created` and `order.accepted` events, so our `ingest_on` setting can choose which one punches?

**Outbound**
15. Accept / reject endpoints, prep-time units, and the list of valid rejection reason codes.
16. Status push endpoint and their exact status vocabulary — we map from `ACCEPTED / REJECTED / PREPARING / READY / PICKED_UP / COMPLETED / CANCELLED`.
17. Store open/close (busy mode) endpoint, if any.
18. Menu push — supported, or is the menu maintained in their portal? This decides whether item mapping stays manual.

**Certification**
19. Is there a certification / go-live checklist, and a test store we can order against?

---

## 4. Phase 1 — hide Talabat from the client

**Requirement:** the client must not see that a Talabat integration exists. Only noon Food.

**Approach: a visibility flag, not deletion.** The code stays (we need it), the UI stops mentioning it, and it can be brought back with one `UPDATE`.

MySQL 8 has no `ADD COLUMN IF NOT EXISTS`, so the ALTER is guarded through
`information_schema` exactly like the existing migration — otherwise re-running
the file fails instead of being a no-op.

```sql
SET @db := DATABASE();

SET @sql := (SELECT IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tbl_integration_providers'
      AND COLUMN_NAME='is_visible')=0,
    'ALTER TABLE tbl_integration_providers ADD COLUMN is_visible ENUM(''Yes'',''No'') NOT NULL DEFAULT ''Yes''',
    'DO 0'));
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE tbl_integration_providers SET is_visible = 'No', is_active = 'No'
 WHERE code IN ('talabat','deliveroo','careem','deliverect');

UPDATE tbl_integration_providers
   SET is_visible = 'Yes', is_active = 'Yes',
       name = 'noon Food', driver_class = 'Noon_food_driver',
       capabilities = 'order_in,status_out', sort_order = 1
 WHERE code = 'noon';
```

Then one new model method and three call sites:

```php
// Integration_model
public function getVisibleProviders()
{
    $this->db->where('is_visible', 'Yes');
    $this->db->order_by('sort_order', 'asc');
    return $this->db->get('tbl_integration_providers')->result();
}
```

Swap `getAllProviders()` → `getVisibleProviders()` in `Integration.php` at:

- `settings()` — the provider card grid
- `itemMap()` — the provider dropdown
- `orderLog()` — the provider filter

**Result:** the Settings screen goes from 5 provider cards (Talabat, Deliveroo, noon, Careem, Deliverect) to **one: noon Food**.

**Deliberately NOT filtered:**

- `Integration_api` (the webhook) resolves by `provider()` + `is_active`, never visibility. Visibility is a UI concern only — a hidden provider still works if it is active. To actually stop Talabat traffic we set `is_active='No'`, which the migration above does.
- `posPendingOrders()` reads `tbl_integration_orders`. With no enabled Talabat config, no Talabat rows exist. We add a visible-provider filter anyway as a belt-and-braces guard.

**Also part of "hidden":**

- The nav labels are already generic — "Integration Settings", "Item Mapping", "Order Log". No provider name in the menu, nothing to change.
- **Exclude `docs/` and `tools/` from the client deployment.** `docs/integration_platform_talabat_uae.md`, `docs/integration_demo_walkthrough.md` and `tools/mock-talabat/` name Talabat throughout. They are developer artefacts and should not ship to the client's server. Add them to the deploy exclude list.
- The Talabat driver file lives under `libraries/Integration/Drivers/` — not reachable from any UI, never rendered.

---

## 5. Phase 2 — auth: cookie sessions alongside Bearer tokens

`Base_channel_driver::oauth_token()` today does OAuth2 client-credentials → `Authorization: Bearer`. noon's platform uses **RS256 JWT → session cookie**. We add a second strategy; we do not replace the first.

**New in `Base_channel_driver` (additive only):**

```php
/** RS256 JWT signed with the service-account private key. openssl only — no composer. */
protected function jwt_rs256(array $claims, $private_key_pem) { … }

/**
 * Log in and return the session cookie string, cached on the config row.
 * Reuses the existing oauth_token / oauth_expires_at_utc columns —
 * the column stores a cookie instead of a bearer, encrypted either way.
 */
protected function session_cookie($config) { … }
```

Notes that matter:

- **No composer in this project** (no `composer.json`, no `vendor/`). PHP 8.2 with `openssl` and `curl` loaded — so the JWT is hand-rolled with `openssl_sign()`, about 15 lines, zero dependencies.
- `http()` gains an optional `CURLOPT_COOKIE` pass-through. Talabat never sets it, so its behaviour is byte-identical.
- The private key is stored inside the encrypted `credentials` JSON blob, same as every other secret. Extend `Base_channel_driver::redact()` to cover `private_key` so a support export can never leak it.
- The session lasts 30 days; refresh at 24 h remaining rather than 60 s, since a login is expensive relative to a token refresh.

---

## 6. Phase 3 — `Noon_food_driver.php`

Same discipline as `Talabat_driver.php`: **the only file in the platform that knows noon's JSON.**

### 6.1 Skeleton

```php
class Noon_food_driver extends Base_channel_driver
{
    public function code()         { return 'noon'; }
    public function capabilities() { return array('order_in','status_out'); }  // + store_status if Phase 0 confirms

    public function verify_webhook($headers, $raw_body, $config) { … }  // shared-secret header + IP allowlist
    public function parse_event($raw_body)                       { … }  // unwrap the noon envelope
    public function fetch_order($external_id, $config)           { … }  // thin-webhook support (no-op if fat)
    public function normalize_order($payload, $config)           { … }  // → Canonical Order DTO
    public function accept_order($external_id, $prep_minutes, $config)    { … }
    public function reject_order($external_id, $reason_code, $config)     { … }
    public function push_status($external_id, $canonical_status, $config) { … }
}
```

### 6.2 The envelope, and thin/fat webhook support

noon wraps the business payload. `parse_event()` unwraps it first, then classifies:

```php
$env  = json_decode($raw_body, true);
$type = $env['event_type'] ?? '';    // e.g. "FOOD::ORDER_CREATED"
$body = $env['payload']    ?? $env;  // tolerate an un-enveloped payload too
```

For the thin case we add **one optional method** to the interface, defaulted in the base class so Talabat is unaffected:

```php
// Base_channel_driver — default: the webhook already carried the order
public function fetch_order($external_id, $config)
{
    return array('ok' => true, 'payload' => null);   // null = "use the webhook body"
}
```

`Integration_api::ingest_now()` gains three lines: if `fetch_order()` returns a payload, normalize that instead of the raw body. Talabat returns `null` and takes the exact path it takes today.

This is the one change outside the driver, and it is the difference between "we support both shapes" and "we rewrite the driver when Phase 0 answers question 6".

### 6.3 Webhook verification (no HMAC)

If noon uses static key-value credentials rather than a signature:

```php
public function verify_webhook($headers, $raw_body, $config)
{
    $expected = $this->webhook_secret($config);
    if ($expected === '') return false;                 // unconfigured must fail closed

    $header = strtolower($this->cred($config, 'webhook_header', 'x-noon-token'));
    $got    = isset($headers[$header]) ? trim($headers[$header]) : '';
    if ($got === '' || !hash_equals($expected, $got)) return false;

    return $this->ip_allowed($config);                  // optional; skipped when the list is empty
}
```

`hash_equals` because a non-constant-time compare on a shared secret is a timing oracle. An empty secret fails closed, never open. If Phase 0 says HMAC instead, `Base_channel_driver` already has `hmac_hex()`, `signature_matches()` and `within_replay_window()` — the method becomes the Talabat one with different header names.

### 6.4 Field mapping — provisional

Written against noon's platform conventions, marked clearly as provisional, and confined to this file. When the real spec lands, only this table and §6.5 change:

| Canonical DTO | Provisional noon path | Confidence |
|---|---|---|
| `external_order_id` | `payload.order_nr` / `payload.order_id` | high — `order_nr` is noon's house style |
| `external_order_no` | `payload.order_code` / short code | medium |
| `external_store_id` | `payload.outlet_code` / `branch_id` | **must confirm — routing depends on it** |
| `items[].external_item_id` | `payload.items[].sku` / `partner_sku` | **must confirm — mapping depends on it** |
| `items[].modifiers[]` | `payload.items[].options[]` / `choices[]` | low |
| `totals.total_payable` | `payload.total` / `grand_total` | medium |
| `is_prepaid` | `payload.payment.is_paid` / `payment_method` | medium |
| `order_type` | delivery vs pickup flag → `3` / `2` | high |
| `placed_at_utc` | `metadata.published_at` as fallback | high |

Two rows are load-bearing and cannot be guessed: **store id** (or the order routes to no outlet) and **item SKU** (or every order rejects as `UNMAPPED_ITEM`). They are questions 11 and 12 in Phase 0.

### 6.5 Status map

```php
protected $status_map = array(
    'ACCEPTED'  => '…',  'REJECTED'  => '…',
    'PREPARING' => '…',  'READY'     => '…',
    'PICKED_UP' => '…',  'COMPLETED' => '…',
    'CANCELLED' => '…',
);
```

Filled from Phase 0 question 16. `Integration_manager::should_push_status()` already gates which of these actually go out, per outlet, from the settings screen.

---

## 7. Phase 4 — mock server and tests

Clone `tools/mock-talabat/` → `tools/mock-noon/`. It already gives us a webhook client, order payload fixtures, and a `run-tests.js` harness.

Fixtures to add:

- `order-simple.json` — envelope + minimal order
- `order-modifiers.json` — nested options
- `order-thin.json` — envelope carrying an id only, to exercise `fetch_order()`
- `order-unmapped.json` — unknown SKU: must reject, not drop the line
- `order-cancel.json`
- `ping.json`

Cases to assert:

1. Valid order → one `tbl_kitchen_sales` row, correct KOT, `sale_no` reserved from the server counter.
2. **Replay: same order id twice → exactly one KOT.** Enforced by `uq_ext`, not a `SELECT`.
3. Bad or missing secret header → 401, nothing written.
4. Unknown store id → 404 with a message naming the store id.
5. Unmapped item → order `REJECTED` with `UNMAPPED_ITEM`, reject event enqueued, **no partial KOT**.
6. Disabled integration → `200 ignored` — never an error, a disabled channel must not look broken to noon.
7. Total mismatch beyond 0.05 → order still books, warning recorded.
8. Accept from the POS → status event enqueued → cron drains it.

---

## 8. Phase 5 — rollout

| Step | Gate |
|---|---|
| 1a. Run `Update/integration_platform_migration.sql` — tables and channel columns | Tables exist |
| 1b. Run `Update/integration_platform_ui_migration.sql` — **the access rows**, easy to forget | The three Setting menus appear |
| 1c. Run `Update/noon_food_migration.sql` | Settings screen shows **only** the noon Food card |
| 2. Configure the outlet: store id, credentials, payment method, ingest user, counter, `is_sandbox='Yes'`, `auto_accept='No'` | `GET /api/integration/noon/health/<store_id>` returns `driver_installed:true, configured:true` |
| 3. Map the menu — Item Mapping screen, `autoMap()` by name first, then fix the rest by hand | unmapped count = 0 |
| 4. Register our webhook URL with noon, sandbox | a noon sandbox order appears in the Order Log |
| 5. End-to-end in sandbox: order → KOT → Accept → prepare → settle → status back to noon | Order Log shows the full status trail, no dead events |
| 6. Flip `is_sandbox='No'`, keep `auto_accept='No'` for the first week | a real order arrives and staff accept it manually |
| 7. Turn on `auto_accept` once the staff trust it | — |

**Kill switch:** `is_enabled='No'` on the config. The webhook then answers `200 ignored` — noon sees a healthy endpoint and nothing punches. No deploy needed.

---

## 9. What noon does NOT get — limitations inherited from the platform

noon reuses the platform, so it also inherits everything the platform does not
do yet. None of this is a noon problem; all of it will be asked about, so it is
better written down than discovered live.

| Gap | What it means day to day | Where it lands |
|---|---|---|
| **Cancellation does not void the sale** | The webhook marks the order `CANCELLED` and returns `200`, but the running order **still sits in the POS and must be voided by hand**. Food-app cancellations are frequent, so staff must be told this explicitly. | `Integration_api::handle_cancel()` — deliberate, because voiding touches stock and the kitchen panel |
| **No menu push** | Item mapping is **manual, forever**, until someone builds it. Every new dish needs a mapping row before noon can sell it. | driver capability `menu_push` not declared |
| **No item 86 / availability sync** | Running out of a dish does not stop noon selling it. Staff must disable it in noon's own portal. | `set_item_availability()` unimplemented |
| **No store open/close automation** | Closing the register does not put the store on busy/closed at noon. Manual, in their portal. | `set_store_status()` — Talabat declares it, noon's support is question 17 |
| **No `READY` push from the kitchen panel** | noon only ever learns `ACCEPTED` and `COMPLETED`. The rider is not told "food is ready". | `Status_dispatcher` is only wired to settle and "Delivered" |
| **No channel-wise sales report** | The channel columns exist on `tbl_sales` / `tbl_kitchen_sales` and are indexed, but **no report reads them yet**. "How much did we do on noon this month?" has no screen. | `ix_channel` exists; the report does not |
| **`auto_settle` is a column, not a feature** | Prepaid noon orders still have to be settled by a cashier. | `tbl_integration_configs.auto_settle` is read nowhere |

Deciding which of these the client actually needs is worth doing **before**
Phase 3, because the channel-wise sales report is the one they will ask for
first and it is the cheapest to build.

---

## 10. Deployment gotchas that will bite

These are install-time traps in this codebase, not noon problems. Every one of
them fails **silently**, which is what makes them expensive.

### 10.1 Hard prerequisites — check before anything else

- **`Update/sale_no_format_migration.sql` must already be applied.** The
  ingestor calls `reserveCompanySaleNumbers()` to get a bill number; without
  `tbl_sale_no_counters` that returns empty and **every inbound order fails with
  `NO_SALE_NO`**. Bills are `INV-<company_id>-<n>`, server-assigned — the
  integration never invents a number.
- **Three migrations, in order**, not one:
  `integration_platform_migration.sql` → `integration_platform_ui_migration.sql`
  → `noon_food_migration.sql`.

### 10.2 The access-id trap

`tbl_access` ids **362–370** are hard-coded in *two* places that must agree:

- `Update/integration_platform_ui_migration.sql` — the `INSERT` rows
- `Integration.php` — `ACCESS_SETTINGS = 362`, `ACCESS_ITEM_MAP = 365`,
  `ACCESS_ORDER_LOG = 368`

The insert is `INSERT IGNORE`, so **if those ids are already taken on the
client's database it silently inserts nothing** and the three Setting screens
return "no access" with no error anywhere. Verify the rows landed after
migrating; if they did not, renumber **both** files.

### 10.3 Everyone must log out and log back in

`function_access` is only refreshed at login. After the migration the new menus
stay **invisible even for Admin** until every user re-authenticates. This looks
exactly like a broken deploy and is not one.

### 10.4 Loading the libraries

`$this->load->library('Integration/Integration_manager')` assigns a
**lowercased** property (`$this->integration_manager`), so
`$this->Integration_manager->…` is an undefined-property fatal. Always pass the
third argument:

```php
$this->load->library('Integration/Integration_manager', null, 'Integration_manager');
```

### 10.5 Never use `Custom::encrypt_decrypt()` for noon's credentials

It is a **no-op** — it returns the input unchanged for both `'encrypt'` and
`'decrypt'`, so anything "encrypted" through it sits in the database in
plaintext. noon's private key and webhook secret go through
`Integration_crypto` (AES-256-CBC + HMAC), like every other integration
credential. This matters more for noon than for Talabat, because noon's
credential is an **RS256 private key**, not a rotatable shared secret.

### 10.6 Debugging when something fails silently

- `ENVIRONMENT` is hard-coded to `'production'` in `index.php`, so a fatal in
  `Integration_cli` prints **nothing** and exits 1. `php -d display_errors=1`
  does not help — `index.php` overrides it. Copy `index.php`, swap the constant,
  run, delete the copy.
- A webhook returning HTTP 500 shows a blank page. Leave `ENVIRONMENT` on
  `production`, set `log_threshold = 1`, replay the request, and read
  `application/logs/log-<date>.php`.
- `tbl_integration_logs` records **every** inbound and outbound call with the
  raw body, already redacted of secrets. Check it before checking anything else
  — it usually holds the answer.

### 10.7 Architecture facts the ingestor depends on

- There are **two parallel sale storages**. The ingestor writes only the
  **kitchen** pair (`tbl_kitchen_sales` + `_details` + `_modifiers`). The
  billing pair is written by the POS at settle time, exactly as for a walk-in.
  Do not "fix" this by writing both.
- A **running order lives in `tbl_kitchen_sales`, not `tbl_sales`.** Any check
  for open noon orders must query the kitchen table; querying `tbl_sales` for
  running orders silently matches nothing.
- `self_order_content` on the kitchen sale is what the POS parses to rebuild the
  cart. Without it the order exists but **cannot be opened**. The ingestor
  writes it in a second `UPDATE` inside the same transaction.

---

## 11. Risks

| Risk | Impact | Mitigation |
|---|---|---|
| **No noon Food spec yet** | mapping is guesswork | Everything else is built and tested against the mock. When the spec lands, only §6.4 and §6.5 change — one file. |
| noon Food auth differs from the marketplace platform | Phase 2 wasted | Phase 2 is ~70 additive lines, and the OAuth path already exists. Whichever scheme it is, one of the two already works. |
| Store id field unknown | orders route nowhere | `getSoleSandboxConfig()` already covers this in sandbox — a test order with an unfilled store id still lands instead of vanishing. |
| Duplicate deliveries (noon retries up to 50×) | double KOTs on a busy service | Already solved: `uq_ext (provider_code, external_order_id)` claims the order *before* any other write. |
| Menu drift — items renamed on noon | orders reject | Unmapped count is on the settings card; the Item Mapping screen shows the last name noon sent. |
| Clock skew (app, MySQL and OS clocks disagree on this install) | `received_at_utc` maths, "waiting mins" wrong | `tbl_integration_*` is UTC throughout; `Order_ingestor` deliberately switches to local for `tbl_kitchen_sales`. Keep that split — do not "fix" it. |
| Talabat leaks into a client screen | trust issue | Phase 1 visibility flag, plus excluding `docs/` and `tools/` from the deploy. Verify on the demo build before handover. |

---

## 12. Effort

| Phase | Work | Estimate |
|---|---|---|
| 0 | Spec request to noon (write and send) | 1 h + their turnaround |
| 1 | Hide Talabat, activate noon (migration + 3 call sites) | 2 h |
| 2 | Cookie-session auth in `Base_channel_driver` | 3–4 h |
| 3 | `Noon_food_driver.php`, provisional mapping | 6–8 h |
| 4 | `tools/mock-noon` + test suite | 4–5 h |
| 5 | Sandbox certification with noon | 1–2 days, mostly waiting |
| — | Rework once the real spec arrives | 3–4 h, one file |

**About 3 development days** to a mock-green integration, then whatever noon's sandbox turnaround costs.

Phases 1, 2, 3 and 4 do not depend on Phase 0 and can start immediately.

---

## 13. Implementation addendum — where reality deviated from this plan

The build followed this document except where a code audit contradicted it.
Deviations, in the order they bit:

1. **Driver naming trap (new).** The base migration seeded
   `driver_class='Noon_driver'` while this plan builds `Noon_food_driver` —
   and the seed's `ON DUPLICATE KEY UPDATE` refreshes `driver_class` on every
   re-run, so the noon migration alone would be silently reverted and the
   webhook would answer `501 No driver installed`. Fixed by changing the seed
   row in `Update/integration_platform_migration.sql` to `Noon_food_driver`
   in the same commit as `Update/noon_food_migration.sql`. Re-run verified.

2. **Credentials are company-wise with per-outlet override (client
   requirement, absent from this plan).** New table
   `tbl_integration_company_credentials` (one row per company × provider)
   holds the service-account blob (`private_key`, `key_id`, `project_code`,
   `login_url`, …), the company webhook secret, and the cached session
   (`oauth_token` / `oauth_expires_at_utc`) — **one 30-day login shared by
   every outlet** instead of N outlets thrashing N sessions.
   `Integration_manager::hydrate_config()` attaches the row;
   `Base_channel_driver::credentials()` merges company → outlet with outlet
   keys winning; `webhook_secret()` falls back outlet → company. Talabat has
   no company row and keeps its per-outlet path byte-identical.

3. **Credential entry surfaces (missing from §2.2 entirely).** The credential
   key set was hardcoded in three places (saveConfig whitelist, settings form,
   CLI merge) and none could store a private key. Extended all three, plus:
   a company-credentials form per provider drawer
   (`Integration::saveCompanyCredentials`), a CLI `creds` command
   (`--private-key-file=…` reads the PEM), and 17 new language keys × 4 packs.

4. **No `CURLOPT_COOKIE` change (§5 was wrong).** `http()` passes the headers
   array verbatim, so `Cookie:` and the mandatory `User-Agent` flow through
   untouched. The cookie login lives in `login_for_cookies()` (captures
   `Set-Cookie` via `CURLOPT_HEADERFUNCTION`), kept separate so `http()` stays
   byte-identical.

5. **`ip_allowed()` did not exist (§6.3 assumed it did).** Implemented inside
   the noon driver (cred `allowed_ips` CSV, empty = skip, reads
   `REMOTE_ADDR`) — not in the base class, and off by default because a
   proxied install would see the proxy's IP.

6. **`fetch_order()` is more than 3 lines (§2.2 item 5).** The fetched payload
   is persisted onto `tbl_integration_orders.raw_payload` (disputes are
   settled with it) and a fetch failure lands as `REJECTED / FETCH_FAILED`.
   Thin vs fat is the cred key `webhook_style` (`fat` default).

7. **Test Connection would always fail for noon (missing from plan).**
   `testConnection()` probed via `set_store_status()`, which noon does not
   declare. The driver now exposes `test_connection()` (a login round-trip)
   and the controller prefers it via `method_exists()`.

8. **`redact()` extended beyond `private_key` (§5 understated).** Also masks
   `static_token`, JWT assertions (`"token":"eyJ…"`), `Cookie`/`Set-Cookie`
   lines, and raw PEM blocks. Verified: the login exchange logs as
   `{"token":"***"}`, zero unredacted JWTs in `tbl_integration_logs`.

9. **Accept-after-reject race (missing from plan).** The SLA sweeper can
   auto-reject while an `order.accept` is still in the ~19 h retry backoff.
   The driver treats HTTP 409 on accept/reject as terminal-benign.

10. **CLI defaults + POS guard.** Both `talabat` defaults changed to `noon`;
    `getPendingChannelOrders()` gained the visible-provider guard §4 promised
    but §2.2 never listed.

**Verified locally (Laragon, restodb):** migrations run + re-run safe; CLI
`creds`/`setup`; 13/13 `tools/mock-noon/run-tests.js`; fat order punched
`INV-1-30` (token `N-4821`, `channel_code='noon'`, 2 lines,
`self_order_content` written); thin order fetched + punched `INV-1-31` with
the fetched detail persisted; RS256 JWT → mock login → session cookie cached
encrypted on the company row (expiry +30 d); queue drained accept/rejects to
the mock with the cookie attached; Talabat webhook answers
`200 ignored` (hidden + deactivated, kill switch intact).

**Still open:** everything in Phase 0 (real field map, status vocabulary,
webhook registration, certification) and the §9 platform limitations.
