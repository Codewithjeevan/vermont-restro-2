#!/usr/bin/env node
/**
 * Send one authenticated noon Food webhook at the POS.
 *
 *   node tools/mock-noon/send-order.js \
 *     --url=http://trul-resto.test/api/integration/noon/webhook \
 *     --secret=noon_test_secret_123 \
 *     --payload=payloads/order-simple.json
 *
 * Options
 *   --url        webhook URL           (default http://localhost/trul-resto/api/integration/noon/webhook)
 *   --secret     shared header secret  (default noon_test_secret_123)
 *   --header     auth header name      (default x-noon-token)
 *   --payload    JSON payload path     (default payloads/order-simple.json, relative to this file)
 *   --order      order_nr              (default: a fresh NOON-<timestamp>, so re-running is a NEW order)
 *   --code       short display code
 *   --store      outlet_code           (the webhook routing key)
 *   --event      event_type            (FOOD::ORDER_CREATED | FOOD::ORDER_CANCELLED | FOOD::PING)
 *   --no-sign    omit the auth header  (expect 401)
 *   --bad-sign   wrong header value    (expect 401)
 *   --quiet      print only the result line
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { parseArgs, sendWebhook } = require('./webhook-client');

const args = parseArgs(process.argv.slice(2));

const URL_ = args.url || 'http://localhost/trul-resto/api/integration/noon/webhook';
const SECRET = args.secret || 'noon_test_secret_123';
const PAYLOAD_PATH = path.resolve(__dirname, args.payload || 'payloads/order-simple.json');

if (!fs.existsSync(PAYLOAD_PATH)) {
  console.error('Payload not found: ' + PAYLOAD_PATH);
  process.exit(2);
}

const envelope = JSON.parse(fs.readFileSync(PAYLOAD_PATH, 'utf8'));
const order = envelope.payload || (envelope.payload = {});

// A fresh order_nr per run so re-running sends a NEW order. Pass --order to
// repeat one deliberately, which is how the idempotency path is tested.
order.order_nr = args.order || 'NOON-' + Date.now();
if (args.code) order.order_code = args.code;
if (args.store) order.outlet_code = args.store;
if (args.event) envelope.event_type = args.event;
order.created_at = new Date().toISOString();
if (envelope.metadata) envelope.metadata.published_at = new Date().toISOString();

const sign = args['no-sign'] ? 'none' : args['bad-sign'] ? 'bad' : 'good';

if (!args.quiet) {
  console.log(`-> POST ${URL_}`);
  console.log(`   order_nr   ${order.order_nr}`);
  console.log(`   event      ${envelope.event_type || '(none)'}`);
  console.log(`   store      ${order.outlet_code || '(none)'}`);
  console.log(`   items      ${(order.items || []).map((i) => `${i.sku} x${i.qty} @ ${i.unit_price}`).join(', ') || '(thin - none)'}`);
  console.log(`   total      ${order.total || '(n/a)'}`);
  console.log(`   auth       ${sign}`);
}

sendWebhook({ url: URL_, secret: SECRET, payload: envelope, sign, header: args.header })
  .then((res) => {
    console.log(`<- ${res.status} ${res.body.trim().slice(0, 400)}`);
    process.exit(res.status >= 200 && res.status < 300 ? 0 : 1);
  })
  .catch((err) => {
    console.error('Request failed: ' + err.message);
    console.error('Is the POS reachable at ' + URL_ + ' ? (Laragon running, correct vhost)');
    process.exit(2);
  });
