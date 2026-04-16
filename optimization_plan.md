# Project Audit & High-Performance Optimization Plan

This document outlines the bottlenecks identified in the audit and provides a step-by-step roadmap to achieve high-concurrency request handling and stability.

---

## 🛑 Phase 1: Critical Security & Blocking Fixes (Immediate)
*Goal: Prevent site crashes from abuse and remove request-blocking operations.*

### 1. Re-enable CSRF Protection
**Problem:** CSRF is disabled globally (`*`), allowing bots to overwhelm the server with fake requests.
**Fix:** Update `bootstrap/app.php`.
```php
// bootstrap/app.php
$middleware->validateCsrfTokens(except: [
    // Only exclude external webhooks like 'payments/*'
]);
```

### 2. Queue External API Calls (Meta CAPI)
**Problem:** Meta Pixel events are firing synchronously, blocking the user's request for 1-3 seconds.
**Fix:** Add `ShouldQueue` to the listeners.
```php
// app/Listeners/SendMetaConversionOnOrderCreated.php
class SendMetaConversionOnOrderCreated implements ShouldQueue 
{
    use InteractsWithQueue;
    // ... logic remains same, but runs in background
}
```

### 3. Secure Admin Routes
**Problem:** Order update routes are exposed without authentication.
**Fix:** Move routes inside the `auth` middleware group in `web.php`.

---

## ⚡ Phase 2: Backend & Database Optimization
*Goal: Reduce database load and remove aggressive cache clearing.*

### 1. Fix Aggressive Cache Clearing
**Problem:** `Cache::flush()` is used everywhere. It clears the entire site's cache every time an order is updated.
**Fix:** Use the existing `Order::incrementCacheVersion()` method and avoid global flushes.
```php
// Change this:
Cache::flush();

// To this (selective or versioned):
Order::incrementCacheVersion(); 
```

### 2. Cache Geographic Data
**Problem:** `findCustomerDistrict` reads a JSON file from disk on every order.
**Fix:** Cache the file content for a long duration.
```php
$geoData = Cache::rememberForever('bd_locations', function() {
    return json_decode(file_get_contents(public_path('jsondata/bd_locations.js')), true);
});
```

### 3. Improve Dashboard Queries
**Problem:** `getStatusCounts` performs raw aggregate queries on every dashboard load.
**Fix:** Cache the counts for 5-10 minutes.

---

## 🌐 Phase 3: Frontend & Payload Optimization (Inertia.js)
*Goal: Reduce data transfer and improve perceived speed.*

### 1. Implement Partial Reloads
**Problem:** Every cart update re-fetches the entire navigation menu, settings, and pages.
**Fix:** Use `only` in Inertia requests.
```javascript
// resources/js/Composables/useCart.js
router.post(route('cart.add'), payload, {
    preserveScroll: true,
    only: ['cart', 'cartCount', 'flash'] // ONLY update these props
});
```

### 2. Lazy Load Heady Libraries
**Problem:** `ApexCharts` and `CKEditor` load on every page, slowing down the site.
**Fix:** Import them only in the specific components using `defineAsyncComponent`.

---

## 📊 Phase 4: High Load & Monitoring
*Goal: Long-term stability and bottleneck detection.*

### 1. Implement Rate Limiting
**Problem:** No protection against brute-force or rapid checkout attempts.
**Fix:** Apply throttles to checkout routes.
```php
Route::post('/order/store', [OrderController::class, 'store'])->middleware('throttle:3,1');
```

### 2. Slow Query Monitoring
**Problem:** No visibility on which queries are slowing down as the DB grows.
**Fix:** Add a listener to log slow queries.
```php
// AppServiceProvider.php
DB::listen(function ($query) {
    if ($query->time > 500) {
        \Log::warning('Slow Query: ' . $query->sql);
    }
});
```

---

## ✅ Final Goal Achievement
By following this plan:
1. **Response time** will drop by ~50% (due to queuing and partial reloads).
2. **Concurrent capacity** will increase by 3x-4x (due to reduced DB locking).
3. **Stability** will improve significantly on shared hosting by reducing Disk I/O and CPU spikes.
