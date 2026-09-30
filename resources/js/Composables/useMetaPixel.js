/**
 * useMetaPixel.js
 *
 * Vue 3 composable for Meta Pixel browser-side tracking.
 * Works alongside the server-side Conversion API (CAPI).
 * Uses event_id for deduplication between Pixel and CAPI.
 */

let pixelId = null;
let initialized = false;
const sentPurchases = new Set();

function getCurrentPathname() {
    if (typeof window === 'undefined') {
        return '/';
    }

    return window.location?.pathname || '/';
}

export function shouldTrackMetaPixel(pathname = getCurrentPathname()) {
    return !(pathname === '/admin' || pathname.startsWith('/admin/'));
}

/**
 * Inject the Meta Pixel base script into <head>.
 * Called once when the app boots (app.js).
 */
export function initMetaPixel(id) {
    if (!id || initialized || !shouldTrackMetaPixel()) return;
    pixelId = String(id);
    initialized = true;

    /* eslint-disable */
    !function (f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function () {
            n.callMethod
                ? n.callMethod.apply(n, arguments)
                : n.queue.push(arguments);
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */

    // Disable Event Setup Tool rules that classified page visits as purchases.
    window.fbq('set', 'autoConfig', false, pixelId);
    // The current SDK's autoConfig group excludes the Event Setup Tool engine.
    // Explicit opt-out also overrides remote opt-ins when its config loads.
    window.fbq('optOut', pixelId, 'ESTRuleEngine');
    // Laravel owns CAPI delivery; do not also forward through a legacy gateway.
    window.fbq('skipOpenbridge', pixelId);
    window.fbq('init', pixelId);
}

/**
 * Check if Pixel is ready to fire.
 */
function isReady() {
    return typeof window !== 'undefined' && typeof window.fbq === 'function' && pixelId && shouldTrackMetaPixel();
}

function waitForFbq(callback, retries = 10) {
    if (isReady()) {
        callback();
    } else if (retries > 0) {
        setTimeout(() => waitForFbq(callback, retries - 1), 300);
    } else {
        console.warn('Meta Pixel not loaded');
    }
}

/**
 * Generate a client-side event ID.
 * For deduplication with CAPI, the server-generated event_id must be passed explicitly.
 */
function generateEventId(prefix = 'evt') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

// ─────────────────────────────────────────────────────────
// COMPOSABLE
// ─────────────────────────────────────────────────────────

export function useMetaPixel() {

    /**
     * Track SPA Page View (call on every Inertia navigate event).
     */
    function trackPageView() {
        if (!isReady() || !shouldTrackMetaPixel()) return;
        window.fbq('trackSingle', pixelId, 'PageView');
    }

    /**
     * Track Product View (ViewContent).
     *
     * @param {Object} product  - Product object from Inertia props
     * @param {number} price    - Displayed price
     */
function trackViewContent(product, price) {
    if (!product?.id) return;

    waitForFbq(() => {
        const eventId = generateEventId('vc');

        window.fbq('trackSingle', pixelId, 'ViewContent', {
            content_ids:  [String(product.id)],
            content_name: product.name || '',
            content_type: 'product',
            value:        parseFloat(price) || 0,
            currency:     'BDT',
        }, { eventID: eventId });
    });
}

    /**
     * Track Add to Cart.
     *
     * @param {Object} product
     * @param {Object|null} variation
     * @param {number} quantity
     */
    function trackAddToCart(product, variation = null, quantity = 1) {
        if (!isReady() || !product) return;

        const price    = parseFloat(product.price || 0) + parseFloat(variation?.price || 0);
        const eventId  = generateEventId('atc');

        window.fbq('trackSingle', pixelId, 'AddToCart', {
            content_ids:  [String(variation?.id || product.id)],
            content_name: product.name,
            content_type: 'product',
            value:        price * quantity,
            currency:     'BDT',
        }, { eventID: eventId });
    }

    /**
     * Track Initiate Checkout.
     *
     * @param {Object} cart  - cart data from Inertia props
     */
    function trackInitiateCheckout(cart) {
        if (!isReady() || !cart?.data) return;

        const items   = cart.data.items || [];
        const total   = parseFloat(cart.data.total || 0);
        const eventId = generateEventId('ic');

        window.fbq('trackSingle', pixelId, 'InitiateCheckout', {
            content_ids:   items.map(i => String(i.product_id)),
            num_items:     items.length,
            value:         total,
            currency:      'BDT',
        }, { eventID: eventId });
    }

    /**
     * Track Purchase.
     * IMPORTANT: Pass the server-generated meta_event_id for deduplication with CAPI.
     *
     * @param {Object} order        - Order object from Success.vue props
     * @param {string} metaEventId  - event_id generated by server (CAPI)
     */
    function trackPurchase(order, metaEventId) {
        if (!isReady() || !order?.order_number || !metaEventId) return;
        const value = Number(order.total);
        if (order.total == null || !Number.isFinite(value) || value <= 0) return;
        if (['incomplete', 'cancelled', 'returned'].includes(order.status) || order.payment_status === 'refunded') return;

        const storageKey = `meta_pur_fired_${pixelId}_${metaEventId}`;
        if (sentPurchases.has(storageKey)) return;
        try {
            if (window.localStorage.getItem(storageKey) ||
                window.sessionStorage.getItem(`meta_pur_fired_${order.order_number}`)) return;
        } catch {
            // Tracking still works when browser storage is blocked.
        }

        const contentIds = (order.items || []).map(item =>
            String(item.product_variation_id || item.product_id)
        );

        window.fbq('trackSingle', pixelId, 'Purchase', {
            content_ids:  contentIds,
            content_type: 'product',
            value,
            currency:     'BDT',
            order_id:     order.order_number,
            num_items:    (order.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0),
        }, { eventID: metaEventId });

        sentPurchases.add(storageKey);
        try {
            window.localStorage.setItem(storageKey, 'true');
        } catch {
            // The in-memory guard and server checkout receipt still apply.
        }
    }

    /**
     * Track Search.
     */
    function trackSearch(searchTerm) {
        if (!isReady()) return;
        window.fbq('trackSingle', pixelId, 'Search', {
            search_string: searchTerm,
        });
    }

    return {
        trackPageView,
        trackViewContent,
        trackAddToCart,
        trackInitiateCheckout,
        trackPurchase,
        trackSearch,
    };
}
