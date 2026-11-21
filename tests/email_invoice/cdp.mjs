// Minimal Chrome DevTools Protocol client for the POS browser test (no npm packages).
// Node 22+ (global WebSocket). Local development only.
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export async function launch(port = 9333) {
    const dir = mkdtempSync(join(tmpdir(), 'ei-chrome-'));
    const proc = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${dir}`,
        '--no-first-run', '--no-default-browser-check', '--window-size=1600,1000', 'about:blank'], { stdio: 'ignore' });
    let pages = null;
    for (let i = 0; i < 100 && !pages; i++) {
        await sleep(100);
        try {
            pages = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
        } catch (e) { /* not up yet */ }
    }
    const page = pages.find((p) => p.type === 'page');
    const ws = new WebSocket(page.webSocketDebuggerUrl);
    await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });
    let id = 0;
    const waiting = new Map();
    const listeners = [];
    ws.onmessage = (ev) => {
        const msg = JSON.parse(ev.data);
        if (msg.id && waiting.has(msg.id)) {
            const { res, rej } = waiting.get(msg.id);
            waiting.delete(msg.id);
            msg.error ? rej(new Error(msg.error.message)) : res(msg.result);
        } else if (msg.method) {
            listeners.forEach((fn) => fn(msg));
        }
    };
    const send = (method, params = {}) => new Promise((res, rej) => {
        const n = ++id;
        waiting.set(n, { res, rej });
        ws.send(JSON.stringify({ id: n, method, params }));
    });
    const errors = [];
    listeners.push((m) => {
        if (m.method === 'Runtime.exceptionThrown') {
            const d = m.params.exceptionDetails;
            errors.push((d.exception && d.exception.description ? d.exception.description.split('\n')[0] : d.text) + ' @' + (d.url || '') + ':' + d.lineNumber);
        }
    });
    await send('Page.enable');
    await send('Runtime.enable');
    await send('Network.enable');

    const api = {
        send,
        errors,
        on: (fn) => listeners.push(fn),
        async eval(expr) {
            const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true });
            if (r.exceptionDetails) {
                throw new Error('eval failed: ' + (r.exceptionDetails.exception ? r.exceptionDetails.exception.description : r.exceptionDetails.text));
            }
            return r.result.value;
        },
        async waitFor(expr, ms = 15000, label = expr) {
            const end = Date.now() + ms;
            while (Date.now() < end) {
                try {
                    if (await api.eval(expr)) return true;
                } catch (e) { /* page busy / navigating */ }
                await sleep(200);
            }
            throw new Error('timeout waiting for ' + label);
        },
        async goto(url) {
            const loaded = new Promise((res) => listeners.push((m) => { if (m.method === 'Page.loadEventFired') res(); }));
            await send('Page.navigate', { url });
            await Promise.race([loaded, sleep(30000)]);
        },
        async shot(file) {
            const r = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(file, Buffer.from(r.data, 'base64'));
        },
        async close() {
            try { ws.close(); } catch (e) { /* ignore */ }
            proc.kill('SIGTERM');
            await sleep(300);
            try { rmSync(dir, { recursive: true, force: true }); } catch (e) { /* ignore */ }
        },
    };
    return api;
}
