'use strict';

/**
 * Shared auth + POST helper for the mock noon Food tools.
 *
 * Auth scheme mirrors Noon_food_driver::verify_webhook():
 *   x-noon-token: <shared secret>       (static header credential, no HMAC)
 *
 * Sign modes map onto that:
 *   good -> correct token, bad -> wrong token, none -> header omitted.
 *
 * When the real noon scheme is known, change it in BOTH places - here and in
 * the driver - and nothing else moves.
 */

const http = require('http');
const https = require('https');

function parseArgs(argv) {
  return Object.fromEntries(
    argv
      .filter((a) => a.startsWith('--'))
      .map((a) => {
        const [k, ...rest] = a.slice(2).split('=');
        return [k, rest.length ? rest.join('=') : true];
      })
  );
}

/**
 * @param {object} opts
 *   url, secret, payload (object), sign ('good'|'bad'|'none'), header (default x-noon-token)
 * @returns {Promise<{status:number, body:string}>}
 */
function sendWebhook(opts) {
  const raw = JSON.stringify(opts.payload);
  const headerName = opts.header || 'x-noon-token';

  const headers = {
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(raw),
    'User-Agent': 'mock-noon/1.0',
  };

  const mode = opts.sign || 'good';
  if (mode === 'good') {
    headers[headerName] = opts.secret;
  } else if (mode === 'bad') {
    headers[headerName] = 'definitely-not-the-secret';
  }
  // mode === 'none' -> no auth header at all

  const target = new URL(opts.url);
  const client = target.protocol === 'https:' ? https : http;

  return new Promise((resolve, reject) => {
    const req = client.request(
      {
        hostname: target.hostname,
        port: target.port || (target.protocol === 'https:' ? 443 : 80),
        path: target.pathname + target.search,
        method: 'POST',
        headers,
        timeout: 20000,
      },
      (res) => {
        let body = '';
        res.on('data', (c) => { body += c; });
        res.on('end', () => resolve({ status: res.statusCode, body }));
      }
    );
    req.on('timeout', () => req.destroy(new Error('timed out after 20s')));
    req.on('error', reject);
    req.write(raw);
    req.end();
  });
}

function getJson(url) {
  const target = new URL(url);
  const client = target.protocol === 'https:' ? https : http;
  return new Promise((resolve, reject) => {
    const req = client.get(
      {
        hostname: target.hostname,
        port: target.port || (target.protocol === 'https:' ? 443 : 80),
        path: target.pathname + target.search,
        timeout: 10000,
      },
      (res) => {
        let body = '';
        res.on('data', (c) => { body += c; });
        res.on('end', () => {
          let parsed = null;
          try { parsed = JSON.parse(body); } catch (e) { /* leave null */ }
          resolve({ status: res.statusCode, body, json: parsed });
        });
      }
    );
    req.on('timeout', () => req.destroy(new Error('timed out after 10s')));
    req.on('error', reject);
  });
}

module.exports = { parseArgs, sendWebhook, getJson };
