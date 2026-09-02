#!/usr/bin/env node
/**
 * End-to-end scenario suite for the inbound half of the noon Food integration.
 *
 *   node tools/mock-noon/run-tests.js \
 *     --base=http://trul-resto.test --secret=noon_test_secret_123 --store=NOON-BR-001
 *
 * Every scenario asserts the HTTP contract the webhook promises. What it
 * deliberately does NOT assert is the resulting database state - that needs the
 * POS's own credentials, so the runner prints the exact CLI commands to check it
 * instead of pretending to have verified something it did not.
 *
 * No replay/timestamp scenario exists here: noon's webhook auth is a static
 * header credential, so there is no signed timestamp to skew (unlike Talabat).
 *
 * Exit code 0 = every scenario matched.
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { parseArgs, sendWebhook, getJson } = require('./webhook-client');

const args = parseArgs(process.argv.slice(2));
const BASE = (args.base || 'http://localhost/trul-resto').replace(/\/$/, '');
const SECRET = args.secret || 'noon_test_secret_123';
const STORE = args.store || 'NOON-BR-001';
const WEBHOOK = `${BASE}/api/integration/noon/webhook`;
const HEALTH = `${BASE}/api/integration/noon/health/${encodeURIComponent(STORE)}`;

function load(name) {
  return JSON.parse(fs.readFileSync(path.resolve(__dirname, 'payloads', name), 'utf8'));
}

/** Re-stamp an envelope fixture: fresh order id, this run's store, now. */
function stamp(envelope, orderNr) {
  const copy = JSON.parse(JSON.stringify(envelope));
  copy.payload = copy.payload || {};
  copy.payload.order_nr = orderNr;
  copy.payload.outlet_code = STORE;
  copy.payload.created_at = new Date().toISOString();
  if (copy.metadata) copy.metadata.published_at = new Date().toISOString();
  return copy;
}

const runId = Date.now();
const results = [];

function record(name, expected, actual, body, note) {
  const ok = Array.isArray(expected) ? expected.includes(actual) : actual === expected;
  results.push({ name, ok, expected, actual, body, note });
  const mark = ok ? 'PASS' : 'FAIL';
  console.log(`  ${mark}  ${name.padEnd(42)} expected ${JSON.stringify(expected)}, got ${actual}`);
  if (!ok || args.verbose) {
    console.log(`        ${String(body).trim().slice(0, 300)}`);
  }
}

async function main() {
  console.log(`Integration platform - noon Food inbound scenarios`);
  console.log(`  POS      ${BASE}`);
  console.log(`  store id ${STORE}`);
  console.log('');

  /* --- 0. is it even wired up? ------------------------------------------ */
  console.log('Health');
  let health;
  try {
    health = await getJson(HEALTH);
  } catch (err) {
    console.error(`  Cannot reach ${HEALTH}: ${err.message}`);
    console.error('  Check Laragon is running and --base points at the POS.');
    process.exit(2);
  }
  record('health endpoint answers', 200, health.status, health.body);

  if (health.json) {
    const h = health.json;
    console.log(`        driver_installed=${h.driver_installed} configured=${h.configured} enabled=${h.enabled} sandbox=${h.sandbox} ingest_on=${h.ingest_on} auto_accept=${h.auto_accept}`);
    if (!h.driver_installed) console.log('        ! Noon_food_driver.php is missing (or driver_class mismatch - run Update/noon_food_migration.sql)');
    if (!h.configured) console.log(`        ! No config for store id ${STORE} - run Integration_cli setup --provider=noon --store=${STORE}`);
    if (h.configured && !h.enabled) console.log('        ! Config exists but is disabled - setup --enabled=Yes');
    if (h.ingest_on && h.ingest_on !== 'placed') {
      console.log(`        ! ingest_on=${h.ingest_on}: an ORDER_CREATED event will be ACKed and ignored.`);
      console.log('          These scenarios send ORDER_CREATED - set --ingest-on=placed to punch on it.');
    }
  }

  /* --- 1. authentication ------------------------------------------------- */
  console.log('\nAuthentication');
  const simple = load('order-simple.json');

  let res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, `NOON-${runId}-noauth`), sign: 'none' });
  record('missing auth header is rejected', 401, res.status, res.body);

  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, `NOON-${runId}-badtoken`), sign: 'bad' });
  record('wrong header token is rejected', 401, res.status, res.body);

  res = await sendWebhook({ url: WEBHOOK, secret: 'the-wrong-secret', payload: stamp(simple, `NOON-${runId}-wrongsecret`), sign: 'good' });
  record('token from a wrong secret is rejected', 401, res.status, res.body);

  /* --- 2. routing -------------------------------------------------------- */
  console.log('\nRouting');
  const strayStore = stamp(simple, `NOON-${runId}-stray`);
  strayStore.payload.outlet_code = 'STORE-THAT-DOES-NOT-EXIST';
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: strayStore, sign: 'good' });
  // 404 when several configs exist; 200/202 when a single sandbox config absorbs it
  record('unknown store id does not silently vanish', [404, 200, 202], res.status, res.body,
    'a single enabled sandbox config deliberately absorbs unknown store ids');

  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('ping.json'), `NOON-${runId}-ping`), sign: 'good' });
  record('ping is answered', 200, res.status, res.body);

  /* --- 3. the happy path ------------------------------------------------- */
  console.log('\nOrder ingestion');
  const happyOrder = `NOON-${runId}-happy`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, happyOrder), sign: 'good' });
  record('valid order is accepted', [202, 200], res.status, res.body);
  const happyAccepted = res.status === 202;

  /* --- 4. idempotency ---------------------------------------------------- */
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, happyOrder), sign: 'good' });
  const isDuplicate = /duplicate/i.test(res.body);
  record('retry of the same order is a no-op', 200, res.status, res.body);
  record('retry is reported as a duplicate', true, isDuplicate, res.body,
    'noon retries up to 50x - this is what stops double KOTs');

  /* --- 5. modifiers ------------------------------------------------------ */
  const modsOrder = `NOON-${runId}-mods`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('order-modifiers.json'), modsOrder), sign: 'good' });
  record('order with modifiers is accepted', [202, 200], res.status, res.body);

  /* --- 6. unmapped item -------------------------------------------------- */
  const unmappedOrder = `NOON-${runId}-unmapped`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('order-unmapped.json'), unmappedOrder), sign: 'good' });
  record('unmapped item is ACKed, not 500', [202, 200], res.status, res.body,
    'the rejection happens after the ACK and lands on tbl_integration_orders');

  /* --- 7. thin webhook --------------------------------------------------- */
  const thinOrder = `NOON-${runId}-thin`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('order-thin.json'), thinOrder), sign: 'good' });
  record('thin webhook (id only) is ACKed', [202, 200], res.status, res.body,
    'punches only with webhook_style=thin + mock-noon running (fetch_order GETs the detail); with fat it lands NORMALIZE_FAILED on the order row');

  /* --- 8. cancellation --------------------------------------------------- */
  console.log('\nCancellation');
  const cancel = stamp(load('order-cancel.json'), happyOrder);
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: cancel, sign: 'good' });
  record('cancellation of a known order is handled', 200, res.status, res.body);

  /* --- summary ----------------------------------------------------------- */
  const failed = results.filter((r) => !r.ok);
  console.log('\n' + '-'.repeat(70));
  console.log(`${results.length - failed.length}/${results.length} scenarios matched.`);

  console.log('\nNow verify what actually landed in the POS (these read the DB, this script cannot):');
  console.log('  php index.php Integration_cli orders');
  console.log('  php index.php Integration_cli unmapped --config=1');
  console.log('  php index.php Integration_cli logs --limit=10');
  console.log('');
  console.log('Expected rows in `Integration_cli orders`:');
  console.log(`  ${happyOrder.padEnd(28)} sale_no set, status ${happyAccepted ? 'RECEIVED or ACCEPTED (then CANCELLED after case 8)' : '(check ingest_on)'}`);
  console.log(`  ${modsOrder.padEnd(28)} sale_no set`);
  console.log(`  ${unmappedOrder.padEnd(28)} status REJECTED, reason UNMAPPED_ITEM`);
  console.log(`  ${thinOrder.padEnd(28)} sale_no set (thin config) or REJECTED NORMALIZE_FAILED (fat config)`);

  process.exit(failed.length === 0 ? 0 : 1);
}

main().catch((err) => {
  console.error('\nRunner crashed: ' + err.message);
  process.exit(2);
});
