#!/usr/bin/env node
/**
 * Mock Talabat API.
 *
 * Stands in for the half of the integration we cannot reach yet: it accepts the
 * driver's OUTBOUND calls (accept / reject / status / availability) and hands
 * out OAuth tokens, so the POS side can be exercised end to end before anyone
 * has real Talabat credentials.
 *
 *   node tools/mock-talabat/mock-talabat.js [--port=4010]
 *
 * Endpoints it serves (matching Talabat_driver):
 *   POST /oauth/token
 *   POST /orders/:token/accept
 *   POST /orders/:token/reject
 *   POST /orders/:token/status
 *   PUT  /chains/stores/:id/availability
 *
 * Plus two of its own, for assertions:
 *   GET  /_calls          every call received, newest last
 *   POST /_reset          clear the call log
 *
 * Zero dependencies on purpose - `node mock-talabat.js` and nothing else.
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

const PORT = Number(args.port || 4010);
const calls = [];

function readBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (chunk) => { data += chunk; });
    req.on('end', () => resolve(data));
  });
}

function send(res, status, body) {
  const payload = JSON.stringify(body);
  res.writeHead(status, {
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(payload),
  });
  res.end(payload);
}

function record(req, url, body, status) {
  const entry = {
    at: new Date().toISOString(),
    method: req.method,
    path: url.pathname,
    authorization: req.headers.authorization ? 'Bearer ***' : null,
    body: safeJson(body),
    status,
  };
  calls.push(entry);
  const label = `${req.method} ${url.pathname}`;
  console.log(`  <- ${label}  ${status}  ${body ? body.slice(0, 160) : ''}`);
  return entry;
}

function safeJson(text) {
  try { return JSON.parse(text); } catch (e) { return text || null; }
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

  // ---- OAuth2 client credentials ---------------------------------------
  if (req.method === 'POST' && path === '/oauth/token') {
    record(req, url, body, 200);
    return send(res, 200, {
      access_token: 'mock-token-' + Date.now(),
      token_type: 'Bearer',
      expires_in: 3600,
    });
  }

  // ---- order lifecycle --------------------------------------------------
  let m;
  if ((m = path.match(/^\/orders\/([^/]+)\/(accept|reject|status)$/))) {
    record(req, url, body, 200);
    return send(res, 200, {
      status: 'ok',
      orderToken: decodeURIComponent(m[1]),
      action: m[2],
      received: safeJson(body),
    });
  }

  if ((m = path.match(/^\/chains\/stores\/([^/]+)\/availability$/))) {
    record(req, url, body, 200);
    return send(res, 200, { status: 'ok', storeId: decodeURIComponent(m[1]) });
  }

  record(req, url, body, 404);
  return send(res, 404, { status: 'error', message: 'No mock route for ' + path });
});

server.listen(PORT, () => {
  console.log(`Mock Talabat listening on http://127.0.0.1:${PORT}`);
  console.log('  set this as the driver base_url:');
  console.log(`    php index.php Integration_cli setup --provider=talabat --company=1 --outlet=1 \\`);
  console.log(`      --sandbox-base-url=http://127.0.0.1:${PORT} --token-url=http://127.0.0.1:${PORT}/oauth/token`);
  console.log('');
  console.log('  waiting for outbound calls from the POS...');
});
