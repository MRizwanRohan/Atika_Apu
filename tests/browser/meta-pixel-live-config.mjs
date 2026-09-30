import fs from 'node:fs';
import { spawn } from 'node:child_process';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';

// Offline replay of public Meta SDK/config. All browser network requests are intercepted.
// Requires downloaded fixtures described in docs/meta-tracking-audit.md.
const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'cartext-pixel-chrome-'));
const chrome = spawn(process.env.CHROME_BIN || '/usr/bin/google-chrome', [
    '--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage',
    '--disable-background-networking', '--disable-component-update', '--no-first-run',
    '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });
let socket;
try {
    const endpoint = await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('Chrome startup timed out')), 15000);
        chrome.stderr.on('data', data => {
            const match = data.toString().match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timeout); resolve(match[1]); }
        });
        chrome.on('exit', code => reject(new Error('Chrome exited: ' + code)));
    });
    socket = new WebSocket(endpoint);
    await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
    let seq = 0;
    const pending = new Map();
    const listeners = new Map();
    socket.addEventListener('message', async ({ data }) => {
        const message = JSON.parse(data);
        if (message.id) {
            const task = pending.get(message.id);
            pending.delete(message.id);
            message.error ? task?.reject(message.error) : task?.resolve(message.result);
        } else {
            for (const listener of listeners.get(message.method) || []) await listener(message);
        }
    });
    const send = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        const id = ++seq;
        pending.set(id, { resolve, reject });
        socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
    const sdk = fs.readFileSync('/tmp/cartext-fbevents.js', 'utf8');
    const config = fs.readFileSync('/tmp/cartext-meta-config.js', 'utf8');
    const source = fs.readFileSync('resources/js/Composables/useMetaPixel.js', 'utf8');
    const results = [];

    for (const fixed of [false, true]) {
        const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
        const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
        const captured = [];
        const urls = [];
        const errors = [];
        const handler = async message => {
            if (message.sessionId !== sessionId) return;
            const { requestId, request } = message.params;
            const url = new URL(request.url);
            urls.push(request.url);
            let body = '', mime = 'text/plain', responseCode = 200;
            if (url.hostname === 'cartexbd.com' && url.pathname === '/') {
                mime = 'text/html';
                body = '<!doctype html><html><head><title>Isolated Pixel test</title></head><body><script type="module">import {initMetaPixel,useMetaPixel} from "/pixel.js"; window.tracker=useMetaPixel();initMetaPixel("2811643535900764");window.tracker.trackPageView();window.appReady=true;</script></body></html>';
            } else if (url.pathname === '/pixel.js') {
                mime = 'application/javascript';
                body = fixed ? source : source
                    .replace("window.fbq('set', 'autoConfig', false, pixelId);", '')
                    .replace("window.fbq('optOut', pixelId, 'ESTRuleEngine');", '')
                    .replace("window.fbq('skipOpenbridge', pixelId);", '');
            } else if (url.hostname === 'connect.facebook.net' && url.pathname.endsWith('/fbevents.js')) {
                body = sdk; mime = 'application/javascript';
            } else if (url.hostname === 'connect.facebook.net' && url.pathname.includes('/signals/config/')) {
                body = config; mime = 'application/javascript';
            } else if (url.hostname.endsWith('facebook.com') && url.pathname.startsWith('/tr')) {
                const fields = new URLSearchParams(request.postData || url.search);
                captured.push(Object.fromEntries(fields));
                responseCode = 204;
            } else {
                responseCode = 204;
            }
            // Every request is fulfilled locally, including all conversion
            // beacons. No test event can reach Meta or the production website.
            await send('Fetch.fulfillRequest', {
                requestId, responseCode,
                responseHeaders: [{ name: 'Content-Type', value: mime }],
                body: Buffer.from(body).toString('base64'),
            }, sessionId);
        };
        listeners.set('Fetch.requestPaused', [handler]);
        listeners.set('Runtime.exceptionThrown', [m => {
            if (m.sessionId === sessionId) errors.push(m.params.exceptionDetails.text);
        }]);
        listeners.set('Runtime.consoleAPICalled', [m => { if(m.sessionId===sessionId) console.log('Browser console',m.params.args.map(a=>a.value||a.description)); }]);
        await send('Network.setUserAgentOverride', {userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'},sessionId);
        await send('Fetch.enable', { patterns: [{ urlPattern: '*' }] }, sessionId);
        await send('Runtime.enable', {}, sessionId);
        await send('Page.enable', {}, sessionId);
        await send('Page.navigate', { url: 'https://cartexbd.com/' }, sessionId);
        await send('Runtime.evaluate', {
            expression: 'new Promise(resolve => { const timer = setInterval(() => { if(window.appReady && window.fbq?.callMethod) { clearInterval(timer); setTimeout(resolve, 1500); } }, 50); setTimeout(() => { clearInterval(timer); resolve(); }, 7000); })',
            awaitPromise: true,
        }, sessionId);

        const homepageEvents = captured.map(event => ({
            event: event.ev, value: event['cd[value]'], currency: event['cd[currency]'],
            rule: event.est_source, eventId: event.eid,
        }));
        if (fixed) {
            await send('Runtime.evaluate', {
                expression: 'window.tracker.trackPurchase({order_number:"ISOLATED-TEST-200",total:"200.00",status:"pending",items:[{product_id:1,quantity:1}]},"isolated-server-id");window.tracker.trackPurchase({order_number:"ISOLATED-TEST-200",total:"200.00",status:"pending",items:[]},"isolated-server-id");new Promise(r=>setTimeout(r,1000))',
                awaitPromise: true,
            }, sessionId);
        }
        results.push({ fixed, homepageEvents, gatewayRequests: urls.filter(u => u.includes('/events?cee=')), allEvents: captured.map(event => ({
            event: event.ev, value: event['cd[value]'], currency: event['cd[currency]'],
            rule: event.est_source, eventId: event.eid,
        })), errors });
        await send('Target.closeTarget', { targetId });
    }
    console.log(JSON.stringify(results, null, 2));
    assert(results[0].homepageEvents.some(e => e.event === 'Purchase'), 'Baseline must reproduce false homepage Purchase');
    assert.equal(results[1].homepageEvents.filter(e => e.event === 'Purchase').length, 0);
    assert.equal(results[1].homepageEvents.filter(e => e.event === 'PageView').length, 1);
    const purchases = results[1].allEvents.filter(e => e.event === 'Purchase');
    assert.equal(purchases.length, 1);
    assert.equal(results[1].gatewayRequests.length, 0, 'No duplicate gateway forwarding');
    assert.equal(Number(purchases[0].value), 200);
    assert.equal(purchases[0].currency, 'BDT');
    assert.equal(purchases[0].eventId, 'isolated-server-id');
    console.log('PASS: actual Meta SDK + actual remote rules reproduce the bug, and the fix blocks false purchases.');
} finally {
    socket?.close();
    chrome.kill('SIGTERM');
}
