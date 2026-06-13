# Project Audit Report

Date: 2026-05-14
Project: `tech_in_more`
Audit focus: user-facing flow, checkout/order handling, auth flow, delivery setup, test readiness

## Executive Summary

The codebase has several strong building blocks, but there are critical user-handling risks in authentication and order flow. The highest-impact issues are:

1. Order details can be viewed publicly by guessing an order number.
2. Order tracking can reveal all orders tied to a phone number without verification.
3. Checkout trusts client-sent shipping cost and does not enforce shipping district/thana consistency on the server.
4. Incomplete orders are auto-created from only a valid phone number, which can create noisy or accidental lead data.
5. Auth flows are only partially migrated from Breeze defaults, leaving password reset broken and multiple auth tests failing.

## Findings

### Critical

1. Public order success page exposes full order details without ownership or token verification
   Impact: Any person who knows or guesses an `order_number` can access customer name, phone, address, items, and payment-related metadata.
   Evidence:
   - [app/Http/Controllers/Frontend/Order/OrderController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Order/OrderController.php:107)
   - The action loads the order by `order_number` and immediately renders it, with no auth check, signed URL check, or one-time token.
   Risk: PII leakage, support abuse, privacy breach, regulatory exposure.
   Recommendation: Protect the success page with either a signed URL, a per-order access token, or authenticated ownership checks.

2. Public tracking endpoint allows enumeration of all orders for a phone number
   Impact: Entering a customer phone number returns every matching order, including item details.
   Evidence:
   - [app/Http/Controllers/Frontend/Order/OrderController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Order/OrderController.php:141)
   - `where('order_number', $input)->orWhere('customer_phone', $input)` returns a full order collection.
   Risk: Anyone who knows a phone number can inspect another customer's purchase history.
   Recommendation: Require order number plus a second factor, or restrict tracking to signed links / OTP verification.

### High

3. Server-side checkout accepts client-controlled shipping cost and optional location fields
   Impact: A user can submit an order with `shipping_cost=0` or with inconsistent district/thana values, because the backend validates format but does not recompute or verify the final delivery charge.
   Evidence:
   - [resources/js/Pages/Frontend/Order/Checkout.vue](/home/samrat/Desktop/samrat/tech_in_more/resources/js/Pages/Frontend/Order/Checkout.vue:232)
   - [app/Services/Order/OrderService.php](/home/samrat/Desktop/samrat/tech_in_more/app/Services/Order/OrderService.php:256)
   - [app/Services/Order/OrderService.php](/home/samrat/Desktop/samrat/tech_in_more/app/Services/Order/OrderService.php:211)
   Details:
   - Frontend fetches delivery charge over AJAX and stores it directly in the form.
   - Backend only checks `shipping_cost` is numeric and then persists it.
   - `shipping_district` and `shipping_thana` are nullable in server validation.
   Risk: undercharged orders, courier mismatch, manual correction load for admins.
   Recommendation: Recalculate shipping server-side from trusted zone data and require validated district/thana for normal checkout.

4. Incomplete orders are auto-created as soon as a valid phone number is typed
   Impact: The system creates backend records before the user intentionally submits checkout.
   Evidence:
   - [resources/js/Pages/Frontend/Order/Checkout.vue](/home/samrat/Desktop/samrat/tech_in_more/resources/js/Pages/Frontend/Order/Checkout.vue:260)
   - [app/Http/Controllers/Frontend/Order/OrderController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Order/OrderController.php:55)
   Details:
   - The watcher posts an incomplete order when the phone becomes valid.
   - Placeholder values like `"Guest"` and `"incomplete address"` are allowed.
   - This can happen before district, address, or payment intent is finalized.
   Risk: duplicate leads, noisy analytics, misleading admin queue, accidental customer records.
   Recommendation: Delay incomplete-order creation until explicit user intent such as blur + debounce + minimum required fields, or save only client-side draft state first.

5. Delivery zone activation state is not enforced in checkout charge lookup
   Impact: Admin can mark a zone inactive, but checkout charge resolution still uses it.
   Evidence:
   - [app/Http/Controllers/Admin/GeneralSetting/DeliveryChargeController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Admin/GeneralSetting/DeliveryChargeController.php:49)
   - [app/Http/Controllers/Frontend/Order/OrderController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Order/OrderController.php:170)
   Details:
   - Admin UI stores `is_active`.
   - Frontend charge lookup does not filter `is_active = true`.
   Risk: orders accepted for disabled delivery areas, or stale charges used after admin changes.
   Recommendation: Apply `is_active` filtering in delivery lookup and decide on a safe fallback behavior for inactive zones.

6. Authentication system is partially migrated and currently inconsistent
   Impact: Login, registration, verification, password confirmation, and profile flows are not aligned with the active route structure.
   Evidence:
   - [routes/auth.php](/home/samrat/Desktop/samrat/tech_in_more/routes/auth.php:22)
   - [app/Http/Controllers/Auth/VerifyEmailController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Auth/VerifyEmailController.php:18)
   - [app/Http/Controllers/Auth/ConfirmablePasswordController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Auth/ConfirmablePasswordController.php:39)
   - [app/Http/Controllers/Frontend/Auth/UserAuthController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Auth/UserAuthController.php:42)
   - [app/Http/Controllers/Frontend/Auth/UserRegisterController.php](/home/samrat/Desktop/samrat/tech_in_more/app/Http/Controllers/Frontend/Auth/UserRegisterController.php:54)
   Details:
   - Breeze guest auth routes for password reset are commented out.
   - Verification and confirm-password controllers still redirect to `route('dashboard')`, which does not exist in current frontend routing.
   - Frontend login/register redirect to `user.dashboard`, while some framework auth controllers and tests still assume old defaults.
   Risk: broken recovery flow, failed onboarding, support tickets for login/account management.
   Recommendation: Choose one auth contract and make all controllers, views, and tests follow it consistently.

### Medium

7. Login UI still references password reset capability that is disabled at route level
   Impact: The UI prepares for a forgot-password action, but the route set is disabled.
   Evidence:
   - [resources/js/Pages/Auth/Login.vue](/home/samrat/Desktop/samrat/tech_in_more/resources/js/Pages/Auth/Login.vue:206)
   - [routes/auth.php](/home/samrat/Desktop/samrat/tech_in_more/routes/auth.php:33)
   Risk: confusing UX and dead-end recovery for users if route exposure changes unexpectedly.
   Recommendation: Either fully restore password reset routes or remove the affordance until the flow is implemented end-to-end.

8. Automated test coverage is thin for the most sensitive user flows
   Impact: Checkout, delivery charge calculation, order success protection, incomplete-order behavior, and public tracking abuse cases are not covered by tests.
   Evidence:
   - Current tests are mostly Breeze scaffolding plus one `PreOrderLead` unit test.
   - There are no feature tests for checkout, delivery zones, couponed orders, or order privacy.
   Risk: regressions will reach production silently.
   Recommendation: Add feature tests around checkout validation, order access control, delivery charge calculation, and tracking authorization.

9. Existing test suite is already red, reducing confidence in future changes
   Result from `php artisan test`:
   - 15 failed, 13 passed
   - Password reset routes return 404
   - Email verification and confirm-password reference missing `dashboard` route
   - Profile tests expect `/profile`, but active user profile lives under `/user/profile`
   - `PreOrderLeadTest` no longer matches the model fillable fields
   Risk: CI noise hides real regressions; the team may stop trusting tests.
   Recommendation: Restore a green baseline before adding more features.

## Priority Fix Order

1. Lock down order success and tracking endpoints.
2. Move shipping cost and delivery-zone validation fully server-side.
3. Redesign incomplete-order capture to avoid auto-creating noisy records.
4. Normalize auth routes/controllers and restore password reset flow.
5. Add feature tests for checkout, tracking, and order privacy.

## Test Run

Command run: `php artisan test`

Outcome:
- Passed: 13
- Failed: 15
- Notable breakpoints:
  - auth redirect mismatch
  - disabled password reset flow
  - profile route mismatch
  - outdated `PreOrderLead` expectation

## Final Assessment

This project is close to being operationally solid for catalog and admin work, but it is not yet safe enough from a user-handling perspective because privacy and checkout integrity still have important gaps. The top two order-access issues should be treated as immediate fixes before further feature work.
