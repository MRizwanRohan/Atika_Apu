# Meta Purchase tracking audit — 2026-09-30

## Confirmed cause

The public configuration for Pixel `2811643535900764` contained two ACTIVE Event Setup Tool rules deriving **Purchase from URL/page activity**:

| Rule ID | URL condition value | Event |
| --- | --- | --- |
| 1749131952958258 | https://cartexbd.com/ | Purchase |
| 1083719177567521 | https://cartexbd.com/category/mens-clothing | Purchase |

The isolated Chrome reproduction used Meta's actual SDK and this public configuration. A homepage PageView produced a Purchase with no value or currency and `est_source=1749131952958258`. No order was created. This proves a source of false purchases; historical totals such as 200 orders versus 1100 reported purchases cannot be reconciled without production event/order logs.

A configured OpenBridge/CAPI Gateway also forwarded those browser events to an AWS endpoint, with a Google Cloud fallback. These are the gateway's primary/fallback destinations, not evidence of two independent installations. This explains a path for the same malformed event to appear under both Pixel and CAPI. The application also implements its own direct CAPI integration.

The marketing snippet named “Google Tag Manager” on the live homepage is actually GA4 `gtag.js` (`G-RMK3K81X8S`). No GTM container was found in that snippet. The legacy checkout dataLayer Purchase path has been removed so Purchase originates from the controlled success flow.

Public evidence inspected without executing production tracking:
- [Pixel configuration](https://connect.facebook.net/signals/config/2811643535900764?v=2.9.0)
- [Meta SDK](https://connect.facebook.net/en_US/fbevents.js)
- [Website](https://cartexbd.com/)

## Applied code changes

- Disable automatic setup and explicitly opt out of `ESTRuleEngine` before initializing the pixel. The current SDK's `autoConfig` group does **not** cover EST rules by itself.
- Use the current SDK's `skipOpenbridge` command to prevent the extra gateway path. `optOut('OpenBridge')` alone does not stop the current openbridge3 plugin. Both behaviors were checked with the actual SDK in an intercepted browser.
- Initialize before Vue mounts, so a fresh success-page load can emit its intended event.
- Send to the configured pixel with `trackSingle`; require the server Purchase identity, never a random fallback.
- Consume a checkout-only session receipt. Opening an old order from the dashboard or refreshing the success URL does not authorize another Purchase.
- Keep browser guards in memory and persistent storage, gracefully handling storage denial.
- Serialize CAPI requests on the order row; a successful event cannot be forced through again. Failed attempts reuse the unique log, identity, original payload and timestamp.
- Require positive finite value and BDT currency, reject ineligible/refunded orders and wrong triggers, and check Meta's `events_received` acknowledgement.
- Run conversion listeners after transaction commit so rolled-back orders cannot report sales.
- Save customer IP, browser and attribution cookies at checkout; later manual/confirmation/delivery events use those signals rather than administrator signals. Old orders have no saved context; their customer hashes remain available.
- Exempt browser-created `_fbp`/`_fbc` cookies from Laravel cookie decryption, and hide stored attribution from order serialization.

## BDT requirement and remaining currency limitation

The user explicitly requires the actual BDT amount. It remains unchanged: a 200 BDT order sends numeric `value: 200` and `currency: "BDT"` in browser and server payloads. No exchange-rate assumption or USD relabeling was introduced.

The inspected Meta SDK (2.9.412) does not include BDT in its browser validator's currency allowlist and prints an invalid-currency warning, even though the captured request contains BDT and the correct value. This is separate from the proven missing-value/missing-currency URL-rule bug. This patch cannot honestly guarantee that Meta's currency diagnostic clears while preserving BDT. Verify acceptance in Events Manager and raise the BDT issue with Meta if it persists; a supported reporting currency would require an explicit conversion-rate decision.

## Validation

- `php artisan test --compact tests/Unit/MetaConversionTrackingTest.php`: 9 passed, 47 assertions. Isolated in-memory SQLite, HTTP fakes, no real Meta events.
- `node --test tests/js/meta-pixel.test.mjs`: 6 passed.
- Frontend production build passed.
- Actual SDK/config in headless Chrome, with **every page request, API request and tracking beacon intercepted and fulfilled locally**:
  - Before fix: homepage → PageView + malformed Purchase + gateway forwarding.
  - After fix: homepage → PageView only; no gateway requests.
  - Explicit 200 BDT purchase called twice → one Purchase request, correct amount/currency and supplied event ID.
- Existing OrderAccessTest cannot run against SQLite because the pre-existing coupon migration uses MySQL `ALTER TABLE ... MODIFY ... ENUM`. It fails before assertions. The new tests use an isolated schema including the real tracking migrations.
- Production database locking under simultaneous workers and Meta ingestion/deduplication were not exercised against live services.

To repeat the intercepted browser check with an installed Chrome and Node 24:

```sh
curl -fsSL https://connect.facebook.net/en_US/fbevents.js -o /tmp/cartext-fbevents.js
curl -fsSL 'https://connect.facebook.net/signals/config/2811643535900764?v=2.9.0' -o /tmp/cartext-meta-config.js
node tests/browser/meta-pixel-live-config.mjs
```

This before/after test intentionally expects the original remote homepage rule to exist. Once the rule is removed in Events Manager, the baseline assertion should no longer reproduce the original bug.

## Production rollout still required

These changes are local; no deployment or account configuration changes have been made.

1. Deploy backend and rebuilt frontend together. Run the new additive migration **before checkout uses the new code**:
   `php artisan migrate --path=database/migrations/2026_09_30_000001_add_meta_tracking_context_to_orders_table.php --force`.
2. Rebuild/deploy assets with `npm run build`, rebuild the application's usual caches, and restart any queue workers with `php artisan queue:restart`. Do not replay historic purchases.
3. In Meta Events Manager for this pixel, remove the two erroneous Event Setup Tool Purchase rules listed above and turn off automatic event detection. Disconnect the obsolete CAPI Gateway connection if the Laravel direct CAPI integration is the intended owner. The browser safeguards are verified against the current SDK, but account cleanup is the durable fix.
4. Verify homepage/category/navigation/cart activity creates no Purchase. For one completed test checkout, verify exactly one logical Purchase after deduplication, with matching browser/server event ID and the exact BDT total. Refresh/reopen the order and confirm no new Purchase.
5. For confirmed/delivered/manual modes, verify no checkout Purchase is emitted and that the eligible server action emits only once. Verify incomplete/cancelled/returned/refunded orders do not emit Purchase.
6. Inspect new event logs and new Meta diagnostics after rollout. Existing inflated historical reporting is not rewritten by this code change.
