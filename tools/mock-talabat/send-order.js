#!/usr/bin/env node
/**
 * Send one signed Talabat webhook at the POS.
 *
 *   node tools/mock-talabat/send-order.js \
 *     --url=http://trul-resto.test/api/integration/talabat/webhook \
 *     --secret=whsec_test_123 \
 *     --payload=payloads/order-simple.json
 *
 * Options
 *   --url        webhook URL           (default http://localhost/trul-resto/api/integration/talabat/webhook)
 *   --secret     shared webhook secret (default whsec_test_123)
 *   --payload    JSON payload path     (default payloads/order-simple.json, relative to this file)
 *   --token      order token           (default: a fresh TLB-<timestamp>, so re-running is a NEW order)
 *   --code       short code
 *   --store      platformRestaurant.id
 *   --event      eventType             (order.created | order.accepted | order.cancelled | ping)
 *   --no-sign    omit the signature    (expect 401)
 *   --bad-sign   wrong signature       (expect 401)
 *   --skew=600   backdate the timestamp N seconds (expect 401 past the 5 min replay window)
 *   --quiet      print only the result line
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { parseArgs, sendWebhook } = require('./webhook-client');

const args = parseArgs(process.argv.slice(2));

const URL_ = args.url || 'http://localhost/trul-resto/api/integration/talabat/webhook';
const SECRET = args.secret || 'whsec_test_123';
const PAYLOAD_PATH = path.resolve(__dirname, args.payload || 'payloads/order-simple.json');

if (!fs.existsSync(PAYLOAD_PATH)) {
  console.error('Payload not found: ' + PAYLOAD_PATH);
  process.exit(2);
}

const payload = JSON.parse(fs.readFileSync(PAYLOAD_PATH, 'utf8'));

// A fresh token per run so re-running sends a NEW order. Pass --token to
// repeat one deliberately, which is how the idempotency path is tested.
payload.token = args.token || 'TLB-' + Date.now();
if (args.code) payload.code = args.code;
if (args.event) payload.eventType = args.event;
if (args.store) {
  payload.platformRestaurant = Object.assign({}, payload.platformRestaurant, { id: args.store });
}
payload.createdAt = new Date().toISOString();

const sign = args['no-sign'] ? 'none' : args['bad-sign'] ? 'bad' : 'good';

if (!args.quiet) {
  console.log(`-> POST ${URL_}`);
  console.log(`   token      ${payload.token}`);
  console.log(`   event      ${payload.eventType || '(none)'}`);
  console.log(`   store      ${(payload.platformRestaurant || {}).id || '(none)'}`);
  console.log(`   products   ${(payload.products || []).map((p) => `${p.remoteCode} x${p.quantity} @ ${p.unitPrice}`).join(', ')}`);
  console.log(`   grandTotal ${(payload.price || {}).grandTotal}`);
  console.log(`   signature  ${sign}${args.skew ? `, skewed ${args.skew}s` : ''}`);
}

sendWebhook({ url: URL_, secret: SECRET, payload, sign, skewSeconds: Number(args.skew || 0) })
  .then((res) => {
    console.log(`<- ${res.status} ${res.body.trim().slice(0, 400)}`);
    process.exit(res.status >= 200 && res.status < 300 ? 0 : 1);
  })
  .catch((err) => {
    console.error('Request failed: ' + err.message);
    console.error('Is the POS reachable at ' + URL_ + ' ? (Laragon running, correct vhost)');
    process.exit(2);
  });
