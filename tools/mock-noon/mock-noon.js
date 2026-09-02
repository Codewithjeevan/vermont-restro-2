#!/usr/bin/env node
/**
 * Mock noon Food API.
 *
 * Stands in for the half of the integration we cannot reach yet: it accepts the
 * driver's OUTBOUND calls (accept / reject / status), hands out session cookies
 * from a login endpoint, and serves order details for the thin-webhook flow, so
 * the POS side can be exercised end to end before anyone has real noon
 * credentials.
 *
 *   node tools/mock-noon/mock-noon.js [--port=4011] [--store=NOON-BR-001]
 *
 * Endpoints it serves (matching Noon_food_driver + Base_channel_driver):
 *   POST /identity/public/v1/api/login          -> Set-Cookie session
 *   GET  /food/partner/v1/orders/:id            -> full order detail (thin flow)
 *   POST /food/partner/v1/orders/:id/accept
 *   POST /food/partner/v1/orders/:id/reject
 *   POST /food/partner/v1/orders/:id/status
 *
 * Plus its own, for assertions:
 *   GET  /_calls          every call received, newest last
 *   POST /_reset          clear the call log
 *   GET  /_health
 *
 * Zero dependencies on purpose - `node mock-noon.js` and nothing else.
 */

'use strict';

const http = require('http');

const args = Object.fromEntries(
  process.argv.slice(2)
    .filter((a) => a.startsWith('--'))
    .map((a) => {
      const [k, ...rest] = a.slice(2).split('=');
      return [k, rest.length ? rest.join('=') : true];
    })
);

const PORT = Number(args.port || 4011);
const STORE = args.store || 'NOON-BR-001';
const calls = [];

function readBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (chunk) => { data += chunk; });
    req.on('end', () => resolve(data));
  });
}

function send(res, status, body, extraHeaders) {
  const payload = JSON.stringify(body);
  res.writeHead(status, Object.assign({
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(payload),
  }, extraHeaders || {}));
  res.end(payload);
}

function record(req, url, body, status) {
  const entry = {
    at: new Date().toISOString(),
    method: req.method,
    path: url.pathname,
    // what matters for assertions: did the driver authenticate, and how
    cookie: req.headers.cookie ? '***' : null,
    authorization: req.headers.authorization ? 'Bearer ***' : null,
    userAgent: req.headers['user-agent'] || null,
    body: safeJson(body),
    status,
  };
  calls.push(entry);
  console.log(`  <- ${req.method} ${url.pathname}  ${status}  ${body ? body.slice(0, 160) : ''}`);
  return entry;
}

function safeJson(text) {
  try { return JSON.parse(text); } catch (e) { return text || null; }
}

/** Full order detail for the thin-webhook flow - shape mirrors the fat fixture. */
function orderDetail(orderNr) {
  return {
    order_nr: orderNr,
    order_code: 'N-' + orderNr.slice(-4),
    outlet_code: STORE,
    created_at: new Date().toISOString(),
    order_type: 'delivery',
    currency: 'AED',
    note: '',
    customer: {
      name: 'Thin Flow Customer',
      phone: '+971500000000',
      address: { full_address: 'Detail St 7, Dubai', city: 'Dubai', lat: 25.2, lng: 55.27 },
    },
    payment: { method: 'noon_pay', status: 'paid', is_paid: true },
    items: [
      { sku: 'SKU-130', name: 'Margherita Pizza', qty: '1', unit_price: '38.00', note: '', options: [] },
      { sku: 'SKU-138', name: 'Iced Tea', qty: '2', unit_price: '19.00', note: '', options: [] },
    ],
    subtotal: '76.00',
    vat: '3.62',
    delivery_fee: '7.00',
    discount: '0.00',
    tip: '0.00',
    service_fee: '0.00',
    total: '83.00',
  };
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const body = await readBody(req);
  const path = url.pathname;

  // ---- helper endpoints -------------------------------------------------
  if (req.method === 'GET' && path === '/_calls') {
    return send(res, 200, { count: calls.length, calls });
  }
  if (req.method === 'POST' && path === '/_reset') {
    calls.length = 0;
    return send(res, 200, { status: 'ok' });
  }
  if (req.method === 'GET' && path === '/_health') {
    return send(res, 200, { status: 'ok', port: PORT });
  }

  // ---- cookie-session login --------------------------------------------
  // The real endpoint takes an RS256 JWT; the mock validates nothing and
  // just hands back session cookies, which is all the driver needs.
  if (req.method === 'POST' && path === '/identity/public/v1/api/login') {
    record(req, url, body, 200);
    return send(res, 200, { status: 'ok', session: 'established' }, {
      'Set-Cookie': [
        'nsid=mock-session-' + Date.now() + '; Path=/; HttpOnly',
        'ntok=mock-csrf-' + Date.now() + '; Path=/',
      ],
    });
  }

  // ---- order detail (thin-webhook flow) ---------------------------------
  let m;
  if (req.method === 'GET' && (m = path.match(/^\/food\/partner\/v1\/orders\/([^/]+)$/))) {
    record(req, url, body, 200);
    return send(res, 200, orderDetail(decodeURIComponent(m[1])));
  }

  // ---- order lifecycle --------------------------------------------------
  if ((m = path.match(/^\/food\/partner\/v1\/orders\/([^/]+)\/(accept|reject|status)$/))) {
    record(req, url, body, 200);
    return send(res, 200, {
      status: 'ok',
      order_nr: decodeURIComponent(m[1]),
      action: m[2],
      received: safeJson(body),
    });
  }

  record(req, url, body, 404);
  return send(res, 404, { status: 'error', message: 'No mock route for ' + path });
});

server.listen(PORT, () => {
  console.log(`Mock noon Food listening on http://127.0.0.1:${PORT}`);
  console.log('  wire the company credentials at it:');
  console.log(`    php index.php Integration_cli creds --provider=noon --company=1 \\`);
  console.log(`      --sandbox-base-url=http://127.0.0.1:${PORT} --login-url=http://127.0.0.1:${PORT}/identity/public/v1/api/login \\`);
  console.log(`      --static-token=mock --webhook-header=x-noon-token --secret=noon_test_secret_123`);
  console.log(`    php index.php Integration_cli setup --provider=noon --company=1 --outlet=1 --store=${STORE} \\`);
  console.log(`      --enabled=Yes --sandbox=Yes --ingest-on=placed`);
  console.log('');
  console.log('  waiting for outbound calls from the POS...');
});
