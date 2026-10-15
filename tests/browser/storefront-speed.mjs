import assert from 'node:assert/strict';
import fs from 'node:fs';
import http from 'node:http';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

// Uses the real production bundles with an isolated fixture; never connects to a database or live site.
const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'cartext-speed-'));
const requests = [];
let html = '';
let origin;
const product = id => ({ id, name: `Test product ${id}`, slug: `test-${id}`, type: 'variable', price: 150,
    previous_price: 200, feature_image: `${origin}/fixtures/product.svg`, stock: 10, is_pre_order: false,
    avg_rating: 4, campaigns: [], gallery_images: [], variations: [{ id: id * 10, price: 150, previous_price: 200,
        stock: 10, attributes: [{ value: { id: 1, value: 'M', attribute: { id: 1, name: 'Size' } } }] }] });
const group = id => ({ id, name: `Collection ${id}`, slug: `collection-${id}`, products: [product(id)] });
const pagination = page => ({ data: page === 1 ? Array.from({ length: 6 }, (_, i) => group(i + 1)) : [group(7), group(8)],
    next_page_url: page === 1 ? `${origin}/home/product-groups?page=2` : null });
const optional = () => ({ productGroups: pagination(1), groupedProducts: [{ name: 'Test category', slug: 'test',
    products: [product(10)] }], instagramFeeds: [], productswithVideo: Array.from({ length: 10 }, (_, i) => ({
    id: i + 1, video: `${origin}/fixtures/preview.mp4?i=${i}`, product: product(i + 20), title: 'Preview' })) });
const server = http.createServer((request, response) => {
    requests.push({ url: request.url, partial: request.headers['x-inertia-partial-data'] });
    const url = new URL(request.url, origin);
    if (url.pathname === '/' && request.headers['x-inertia']) {
        const data = optional();
        const props = Object.fromEntries((request.headers['x-inertia-partial-data'] || '').split(',').filter(key => key in data).map(key => [key, data[key]]));
        response.writeHead(200, { 'Content-Type': 'application/json', 'X-Inertia': 'true' });
        response.end(JSON.stringify({ component: 'Frontend/Home/Home', props, url: '/', version: null }));
    } else if (url.pathname === '/') {
        response.writeHead(200, { 'Content-Type': 'text/html' }); response.end(html);
    } else if (url.pathname === '/home/product-groups') {
        response.writeHead(200, { 'Content-Type': 'application/json' }); response.end(JSON.stringify(pagination(2)));
    } else if (url.pathname.startsWith('/build/')) {
        const filename = path.resolve('public', '.' + url.pathname);
        if (!filename.startsWith(path.resolve('public/build') + path.sep) || !fs.existsSync(filename)) {
            response.writeHead(404); response.end(); return;
        }
        response.writeHead(200, { 'Content-Type': filename.endsWith('.js') ? 'application/javascript' : 'text/css' });
        response.end(fs.readFileSync(filename));
    } else if (url.pathname.endsWith('.svg')) {
        const mobile = url.pathname.includes('mobile');
        response.writeHead(200, { 'Content-Type': 'image/svg+xml' });
        response.end(`<svg xmlns="http://www.w3.org/2000/svg" width="${mobile ? 800 : 1200}" height="${mobile ? 1600 : 800}"><rect width="100%" height="100%" fill="#e7e5e4"/></svg>`);
    } else {
        response.writeHead(204); response.end();
    }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
origin = `http://127.0.0.1:${server.address().port}`;
const page = { component: 'Frontend/Home/Home', url: '/', version: null, props: {
    auth: { user: null }, flash: {}, cart: null, cartCount: 0, cartTotal: 0, wishlistIds: [], compareIds: [],
    metaPixel: { pixel_enabled: false }, settings: { generalSettings: { home_page_title: 'Speed fixture' }, media: {}, marketingTools: [] },
    categories: [{ id: 1, name: 'Test category', slug: 'test', status: 'active', image: `${origin}/fixtures/category.svg`, children: [] }],
    sliders: [{ id: 1, image: `${origin}/fixtures/desktop.svg`, mobile_image: `${origin}/fixtures/mobile.svg` }],
} };
const fixture = path.join(temporary, 'page.json');
fs.writeFileSync(fixture, JSON.stringify(page));
const renderer = spawnSync('php', ['-r', String.raw`
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.url'=>getenv('SPEED_TEST_ORIGIN'),'cache.default'=>'array','session.driver'=>'array','inertia.ssr.enabled'=>false]);
$app->instance('request', Illuminate\Http\Request::create(getenv('SPEED_TEST_ORIGIN')));
Illuminate\Support\Facades\URL::forceRootUrl(getenv('SPEED_TEST_ORIGIN'));
echo view('app', ['page'=>json_decode(file_get_contents(getenv('SPEED_TEST_FIXTURE')),true)])->render();
`], { encoding: 'utf8', env: { ...process.env, DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:', CACHE_STORE: 'array',
    SESSION_DRIVER: 'array', SPEED_TEST_ORIGIN: origin, SPEED_TEST_FIXTURE: fixture } });
assert.equal(renderer.status, 0, renderer.stderr);
html = renderer.stdout;
assert.match(html, /<picture>/, 'server HTML does not contain the critical hero');
assert.match(html, /aria-label="Search products"/, 'server HTML does not contain usable navigation');
const chrome = spawn(process.env.CHROME_BIN || '/usr/bin/google-chrome', ['--headless=new', '--no-sandbox', '--disable-gpu',
    '--disable-dev-shm-usage', '--disable-background-networking', '--no-first-run', '--remote-debugging-port=0',
    `--user-data-dir=${temporary}/chrome`, 'about:blank'], { stdio: ['ignore', 'ignore', 'pipe'] });
let socket;
try {
    const endpoint = await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('Chrome startup timeout')), 15000);
        chrome.stderr.on('data', data => { const match = data.toString().match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timeout); resolve(match[1]); } });
        chrome.on('error', reject);
    });
    socket = new WebSocket(endpoint);
    await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
    let sequence = 0;
    const pending = new Map();
    const errors = [];
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.id) { const task = pending.get(message.id); pending.delete(message.id);
            message.error ? task.reject(message.error) : task.resolve(message.result); }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text + ': ' + message.params.exceptionDetails.exception?.description);
    });
    const send = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const command = (method, params = {}) => send(method, params, sessionId);
    const evaluate = async expression => {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.equal(result.exceptionDetails, undefined, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    const until = async expression => {
        for (let attempt = 0; attempt < 100; attempt++) {
            if (await evaluate(expression)) return;
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        throw new Error('Timed out: ' + expression);
    };
    await command('Runtime.enable');
    await command('Page.enable');
    await command('Network.enable');
    await command('Network.setBlockedURLs', { urls: ['*fonts.googleapis.com*', '*fonts.gstatic.com*', '*facebook.com*', '*facebook.net*'] });
    for (const [width, height, mobile] of [[390, 844, true], [1280, 800, false]]) {
        requests.length = 0;
        await command('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
        await command('Network.clearBrowserCache');
        await command('Page.navigate', { url: origin });
        await until('!!document.querySelector(".main-slider.swiper-initialized")');
        assert.equal(await evaluate('document.querySelector(".main-slider img").fetchPriority'), 'high');
        assert.match(await evaluate('document.querySelector(".main-slider img").currentSrc'), mobile ? /mobile\.svg/ : /desktop\.svg/);
        assert.ok(!requests.some(request => request.url.includes(mobile ? '/fixtures/desktop.svg' : '/fixtures/mobile.svg')), 'wrong hero preload downloaded');
        assert.ok(!requests.some(request => request.url.includes('/api/products/recommended')), 'hidden cart fetched recommendations');
        assert.equal(await evaluate('document.querySelectorAll("video").length'), 0);
        await evaluate('window.scrollTo(0,document.querySelector(".main-slider").getBoundingClientRect().bottom + window.scrollY)');
        await until('document.querySelectorAll("video").length > 0');
        assert.ok(await evaluate('document.querySelectorAll("video").length <= 6'), 'offscreen previews were started');
        await evaluate('window.scrollTo(0,0)');
        await until('document.querySelectorAll("video").length === 0');
        await evaluate('window.scrollTo(0,1200)');
        await until('[...document.querySelectorAll("h2")].some(el=>el.textContent.includes("Collection 1"))');
        await evaluate("document.querySelector('a[href$=\"/product/test-1\"] button').click()");
        await until('document.body.style.overflow === "hidden" && [...document.querySelectorAll("button")].some(el=>el.textContent.includes("ADD TO CART"))');
        await evaluate('document.dispatchEvent(new KeyboardEvent("keydown",{key:"Escape"}))');
        await until('document.body.style.overflow !== "hidden"');
        await evaluate('[...document.querySelectorAll("button")].find(el=>el.textContent.includes("View More Collections")).click()');
        await until('[...document.querySelectorAll("h2")].some(el=>el.textContent.includes("Collection 8"))');
        assert.equal(await evaluate('[...document.querySelectorAll("button")].some(el=>el.textContent.includes("View More Collections"))'), false);
        console.log(`${mobile ? 'Mobile' : 'Desktop'}: correct hero, deferred sections, visible-only video, quick view and collection pagination passed.`);
    }
    assert.deepEqual(errors, []);
} finally {
    socket?.close();
    chrome.kill('SIGTERM');
    server.close();
    await new Promise(resolve => chrome.once('exit', resolve));
    fs.rmSync(temporary, { recursive: true, force: true });
}
