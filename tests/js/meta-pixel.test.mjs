import test from 'node:test';
import assert from 'node:assert/strict';

const storage = () => {
    const values = new Map();
    return { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value) };
};

async function setup({ path = '/', localStorage = storage(), blocked = false } = {}) {
    const calls = [];
    const blockedStorage = {
        getItem() { throw new Error('Storage blocked'); },
        setItem() { throw new Error('Storage blocked'); },
    };
    global.window = {
        location: { pathname: path },
        fbq: (...args) => calls.push(args),
        localStorage: blocked ? blockedStorage : localStorage,
        sessionStorage: blocked ? blockedStorage : storage(),
    };
    global.document = {};
    const pixel = await import('../../resources/js/Composables/useMetaPixel.js?test=' + Math.random());
    pixel.initMetaPixel('123456789');
    return { calls, pixel, localStorage, tracker: pixel.useMetaPixel() };
}

const order = { order_number: 'TEST-1', total: '200.00', status: 'pending',
    items: [{ product_id: 7, quantity: 2 }] };

test('disable automatic setup AND URL rule engine before initializing', async () => {
    const { calls, tracker } = await setup();
    assert.deepEqual(calls.slice(0, 4), [
        ['set', 'autoConfig', false, '123456789'],
        ['optOut', '123456789', 'ESTRuleEngine'],
        ['skipOpenbridge', '123456789'],
        ['init', '123456789'],
    ]);
    tracker.trackPageView();
    assert.deepEqual(calls.at(-1), ['trackSingle', '123456789', 'PageView']);
    assert.equal(calls.filter(c => c[2] === 'Purchase').length, 0);
});

test('a 200 BDT order fires once with server identity and proper quantity', async () => {
    const { calls, tracker } = await setup();
    tracker.trackPurchase(order, 'server-event-id');
    tracker.trackPurchase(order, 'server-event-id');
    const events = calls.filter(c => c[2] === 'Purchase');
    assert.equal(events.length, 1);
    assert.equal(events[0][0], 'trackSingle');
    assert.equal(events[0][3].value, 200);
    assert.equal(events[0][3].currency, 'BDT');
    assert.equal(events[0][3].num_items, 2);
    assert.deepEqual(events[0][4], { eventID: 'server-event-id' });
});

test('reload/new tab with persisted storage cannot repeat a purchase', async () => {
    const first = await setup();
    first.tracker.trackPurchase(order, 'server-event-id');
    const second = await setup({ localStorage: first.localStorage });
    second.tracker.trackPurchase(order, 'server-event-id');
    assert.equal(second.calls.filter(c => c[2] === 'Purchase').length, 0);
});

test('missing identity, missing/invalid amount and invalid orders do not fire', async () => {
    const { calls, tracker } = await setup();
    tracker.trackPurchase(order);
    for (const total of [null, undefined, '', '200BDT', Infinity, NaN, 0, -20]) {
        tracker.trackPurchase({ ...order, total }, 'server-event-id');
    }
    for (const status of ['cancelled', 'returned', 'incomplete']) {
        tracker.trackPurchase({ ...order, status }, 'server-event-id');
    }
    assert.equal(calls.filter(c => c[2] === 'Purchase').length, 0);
});

test('blocked storage does not break checkout or send duplicates in the same page', async () => {
    const { calls, tracker } = await setup({ blocked: true });
    assert.doesNotThrow(() => {
        tracker.trackPurchase(order, 'server-event-id');
        tracker.trackPurchase(order, 'server-event-id');
    });
    assert.equal(calls.filter(c => c[2] === 'Purchase').length, 1);
});

test('admin navigation never emits pixel events', async () => {
    const { calls, tracker } = await setup();
    const count = calls.length;
    window.location.pathname = '/admin/orders';
    tracker.trackPageView();
    tracker.trackPurchase(order, 'server-event-id');
    tracker.trackAddToCart({ id: 1, price: 200 });
    assert.equal(calls.length, count);
    const admin = await setup({ path: '/admin' });
    assert.equal(admin.calls.length, 0);
});
