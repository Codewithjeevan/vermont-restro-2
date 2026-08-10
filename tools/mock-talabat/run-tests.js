#!/usr/bin/env node
/**
 * End-to-end scenario suite for the inbound half of the integration platform.
 *
 *   node tools/mock-talabat/run-tests.js \
 *     --base=http://trul-resto.test --secret=whsec_test_123 --store=TLB-DXB-0042
 *
 * Every scenario asserts the HTTP contract the webhook promises. What it
 * deliberately does NOT assert is the resulting database state - that needs the
 * POS's own credentials, so the runner prints the exact CLI commands to check it
 * instead of pretending to have verified something it did not.
 *
 * Exit code 0 = every scenario matched.
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { parseArgs, sendWebhook, getJson } = require('./webhook-client');

const args = parseArgs(process.argv.slice(2));
const BASE = (args.base || 'http://localhost/trul-resto').replace(/\/$/, '');
const SECRET = args.secret || 'whsec_test_123';
const STORE = args.store || 'TLB-DXB-0042';
const WEBHOOK = `${BASE}/api/integration/talabat/webhook`;
const HEALTH = `${BASE}/api/integration/talabat/health/${encodeURIComponent(STORE)}`;

function load(name) {
  return JSON.parse(fs.readFileSync(path.resolve(__dirname, 'payloads', name), 'utf8'));
}

function stamp(payload, token) {
  const copy = JSON.parse(JSON.stringify(payload));
  copy.token = token;
  copy.createdAt = new Date().toISOString();
  copy.platformRestaurant = Object.assign({}, copy.platformRestaurant, { id: STORE });
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
  console.log(`Integration platform - inbound scenarios`);
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
    if (!h.driver_installed) console.log('        ! Talabat_driver.php is missing');
    if (!h.configured) console.log(`        ! No config for store id ${STORE} - run Integration_cli setup --store=${STORE}`);
    if (h.configured && !h.enabled) console.log('        ! Config exists but is disabled - setup --enabled=Yes');
    if (h.ingest_on && h.ingest_on !== 'placed') {
      console.log(`        ! ingest_on=${h.ingest_on}: an order.created event will be ACKed and ignored.`);
      console.log('          These scenarios send order.created - set --ingest-on=placed to punch on it.');
    }
  }

  /* --- 1. authentication ------------------------------------------------- */
  console.log('\nAuthentication');
  const simple = load('order-simple.json');

  let res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, `TLB-${runId}-nosig`), sign: 'none' });
  record('unsigned request is rejected', 401, res.status, res.body);

  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, `TLB-${runId}-badsig`), sign: 'bad' });
  record('wrong signature is rejected', 401, res.status, res.body);

  res = await sendWebhook({ url: WEBHOOK, secret: 'the-wrong-secret', payload: stamp(simple, `TLB-${runId}-wrongsecret`), sign: 'good' });
  record('signature from a wrong secret is rejected', 401, res.status, res.body);

  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, `TLB-${runId}-replay`), sign: 'good', skewSeconds: 900 });
  record('replayed (15 min old) event is rejected', 401, res.status, res.body);

  /* --- 2. routing -------------------------------------------------------- */
  console.log('\nRouting');
  const strayStore = JSON.parse(JSON.stringify(simple));
  strayStore.token = `TLB-${runId}-stray`;
  strayStore.platformRestaurant = { id: 'STORE-THAT-DOES-NOT-EXIST' };
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: strayStore, sign: 'good' });
  // 404 when several configs exist; 200/202 when a single sandbox config absorbs it
  record('unknown store id does not silently vanish', [404, 200, 202], res.status, res.body,
    'a single enabled sandbox config deliberately absorbs unknown store ids');

  const ping = stamp(load('ping.json'), `TLB-${runId}-ping`);
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: ping, sign: 'good' });
  record('ping is answered', 200, res.status, res.body);

  /* --- 3. the happy path ------------------------------------------------- */
  console.log('\nOrder ingestion');
  const happyToken = `TLB-${runId}-happy`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, happyToken), sign: 'good' });
  record('valid order is accepted', [202, 200], res.status, res.body);
  const happyAccepted = res.status === 202;

  /* --- 4. idempotency ---------------------------------------------------- */
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(simple, happyToken), sign: 'good' });
  const isDuplicate = /duplicate/i.test(res.body);
  record('retry of the same order is a no-op', 200, res.status, res.body);
  record('retry is reported as a duplicate', true, isDuplicate, res.body,
    'this is what stops double KOTs on a busy Friday');

  /* --- 5. modifiers ------------------------------------------------------ */
  const modsToken = `TLB-${runId}-mods`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('order-modifiers.json'), modsToken), sign: 'good' });
  record('order with modifiers is accepted', [202, 200], res.status, res.body);

  /* --- 6. unmapped item -------------------------------------------------- */
  const unmappedToken = `TLB-${runId}-unmapped`;
  res = await sendWebhook({ url: WEBHOOK, secret: SECRET, payload: stamp(load('order-unmapped.json'), unmappedToken), sign: 'good' });
  record('unmapped item is ACKed, not 500', [202, 200], res.status, res.body,
    'the rejection happens after the ACK and lands on tbl_integration_orders');

  /* --- 7. cancellation --------------------------------------------------- */
  console.log('\nCancellation');
  const cancel = stamp(simple, happyToken);
  cancel.eventType = 'order.cancelled';
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
  console.log(`  ${happyToken.padEnd(26)} sale_no set, status ${happyAccepted ? 'RECEIVED or ACCEPTED' : '(check ingest_on)'}`);
  console.log(`  ${modsToken.padEnd(26)} sale_no set`);
  console.log(`  ${unmappedToken.padEnd(26)} status REJECTED, reason UNMAPPED_ITEM`);

  process.exit(failed.length === 0 ? 0 : 1);
}

main().catch((err) => {
  console.error('\nRunner crashed: ' + err.message);
  process.exit(2);
});
