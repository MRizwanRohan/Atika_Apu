# 🚀 Webhook Auto Order Status Update — Implementation Plan

**Project:** Tech In More (Laravel 11 + Vue 3 + Inertia.js)  
**Feature:** Real-time order status auto-update from Pathao & Steadfast couriers via Webhook  
**Date:** 2026-05-03  
**Status:** ANALYSIS COMPLETE — READY TO IMPLEMENT

---

## 📋 Project Analysis Summary

### Current Tech Stack
| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.2) |
| Frontend | Vue 3 + Inertia.js |
| CSS | TailwindCSS |
| Queue | Database-backed (queue:listen already running in dev) |
| Cache | Database |
| Session | Database |
| Broadcast | Log driver (NOT Pusher/Reverb — no WebSocket yet) |
| DB | MySQL (via XAMPP, port 3307) |

### Existing Courier Infrastructure
- `CourierSetting` model → `courier_settings` table (Pathao + Steadfast fields)
- `PathaoService` (`app/Services/Couriers/Pathao/PathaoService.php`) — full API integration
- `SteadfastService` (`app/Services/Couriers/Steadfast/SteadfastService.php`) — fraud check only
- `SteadfastCourierService` (`app/Services/Courier/SteadfastCourierService.php`) — order creation
- `CheckOrderDeliveryStatus` artisan command — polls Steadfast for status via consignment_id
- `Order` model has `delivery_status`, `consignment_id`, `courier_name`, `tracking_number` fields
- `OrderUpdated` event exists but **NOT implementing `ShouldBroadcast`** yet

### What's MISSING (what we need to build)
1. Webhook endpoint for Pathao (POST callback)
2. Webhook endpoint for Steadfast (POST callback)
3. Webhook configuration fields in `courier_settings` table
4. Admin UI sections: Webhook Integration + Status Mapping (for both couriers)
5. Status mapping configuration (Pathao event → our order `status`)
6. ngrok setup for local webhook testing
7. `delivery_status` → `status` sync logic when webhook fires

---

## 🗺️ Architecture Overview

```
Pathao/Steadfast Courier API
        │
        │  POST (webhook callback)
        ▼
Laravel Webhook Controller  ──► Validate Signature/Bearer Token
        │
        ▼
WebhookStatusService
  - Find order by consignment_id / invoice
  - Map courier status → our order status (from DB config)
  - Update order.delivery_status + order.status
  - Dispatch OrderStatusWebhookReceived event
        │
        ├──► Queue Job → Log webhook history
        └──► order.status updated in DB

Admin UI (Courier Settings Page)
  - Enable/Disable webhook toggle (per courier)
  - Callback URL display (copy button)  ← system generates this
  - Auth Token (Bearer) display (copy button)  ← system generates this
  - Pathao Status Mapping (event → our status dropdown)
  - Steadfast Status Mapping (status → our status dropdown)
```

---

## 🗄️ Phase 1: Database Migration

### Migration: `add_webhook_fields_to_courier_settings_table`

**New columns to add to `courier_settings`:**

```
-- Pathao Webhook
pathao_webhook_enabled      ENUM('yes','no')  DEFAULT 'no'
pathao_webhook_token        VARCHAR(255)      NULL   ← auto-generated UUID
pathao_status_mapping       JSON              NULL   ← {event: our_status}

-- Steadfast Webhook
steadfast_webhook_enabled   ENUM('yes','no')  DEFAULT 'no'
steadfast_webhook_token     VARCHAR(255)      NULL   ← auto-generated UUID (used as Bearer)
steadfast_status_mapping    JSON              NULL   ← {courier_status: our_status}
```

### Migration: `create_webhook_logs_table`

```
id, courier (pathao/steadfast), order_id (nullable FK),
event_type, payload (JSON), status (received/processed/failed),
error_message (text nullable), processed_at (timestamp nullable),
timestamps
```

---

## ⚙️ Phase 2: Backend — Webhook Endpoints

### 2.1 Pathao Webhook Controller
**File:** `app/Http/Controllers/Webhook/PathaoWebhookController.php`

```
POST /webhook/pathao
```

**Responsibilities:**
1. Verify `X-PATHAO-Signature` header matches `pathao_webhook_token` from DB
2. Check `pathao_webhook_enabled === 'yes'`
3. Parse event from request body
4. Handle `webhook_integration` test event (return 202 + special header)
5. For real events: lookup order by `consignment_id` (Merchant Order ID = order_number)
6. Apply Pathao status mapping → update `order.delivery_status` + `order.status`
7. Return HTTP 202 with required header

**Pathao webhook events we handle:**
| Pathao Event | Suggested Default Our Status |
|---|---|
| Delivered | delivered |
| Pickup Cancelled | cancelled |
| Returned | returned |
| Order Created | processing |
| Assigned For Pickup | shipped |
| Pickup | shipped |
| Pickup Failed | on_hold |
| In Transit | shipped |
| Assigned for Delivery | shipped |
| Partial Delivery | partially_delivered |
| Return | returned |
| Delivery Failed | on_hold |
| On Hold | on_hold |

**Pathao special requirement:**
- Must return HTTP `202` status code
- Response headers must include: `X-Pathao-Merchant-Webhook-Integration-Secret: {token}`

### 2.2 Steadfast Webhook Controller
**File:** `app/Http/Controllers/Webhook/SteadfastWebhookController.php`

```
POST /webhook/steadfast
```

**Responsibilities:**
1. Verify `Authorization: Bearer {token}` header matches `steadfast_webhook_token` from DB
2. Check `steadfast_webhook_enabled === 'yes'`
3. Parse `notification_type` from body (`delivery_status` or `tracking_update`)
4. Lookup order by `consignment_id` (matches `consignment_id` in payload)
5. Apply Steadfast status mapping → update `order.delivery_status` + `order.status`
6. Return HTTP `200` with `{"status":"success","message":"Webhook received successfully."}`

**Steadfast statuses to map:**
| Steadfast Status | Suggested Default Our Status |
|---|---|
| pending | processing |
| delivered | delivered |
| partial_delivered | partially_delivered |
| cancelled | cancelled |
| unknown | — No Action — |

### 2.3 Shared WebhookStatusService
**File:** `app/Services/Webhook/WebhookStatusService.php`

```php
// Core logic used by both controllers
public function processPathaoWebhook(array $payload): void
public function processSteadfastWebhook(array $payload): void
private function findOrderByConsignmentId(string $consignmentId): ?Order
private function findOrderByInvoice(string $invoice): ?Order
private function applyStatusMapping(Order $order, string $mappedStatus): void
private function logWebhook(string $courier, ?int $orderId, array $payload, string $status): void
```

### 2.4 Webhook Token Generator
**File:** `app/Services/Webhook/WebhookTokenService.php`

Auto-generates UUID v4 tokens when webhook is enabled for the first time.

---

## 🛣️ Phase 3: Routes

### Public Webhook Routes (NO auth middleware)
**File:** `routes/web.php` (add at bottom outside auth group)

```php
// Courier Webhook Callbacks — must be publicly accessible
Route::post('/webhook/pathao', [PathaoWebhookController::class, 'handle'])
    ->name('webhook.pathao');
Route::post('/webhook/steadfast', [SteadfastWebhookController::class, 'handle'])
    ->name('webhook.steadfast');
```

> ⚠️ These endpoints must be EXCLUDED from CSRF verification.

### CSRF Exemption
**File:** `app/Http/Middleware/VerifyCsrfToken.php`
```php
protected $except = [
    'webhook/pathao',
    'webhook/steadfast',
];
```

### Admin Routes (inside existing auth group)
```php
Route::post('/courier/settings/generate-token/{courier}', [CourierController::class, 'generateWebhookToken'])
    ->name('courier.settings.generate-token');
Route::get('/courier/webhook-logs', [CourierController::class, 'webhookLogs'])
    ->name('courier.webhook-logs');
```

---

## 🖥️ Phase 4: Admin UI — Courier Settings Page Upgrade

**File:** `resources/js/Pages/Admin/Courier/Index.vue`

### New Sections to Add (for EACH courier):

#### Section A: Webhook Integration Card
```
┌─────────────────────────────────────────────────────────┐
│  WEBHOOK INTEGRATION                         [Toggle ON/OFF] │
│                                                          │
│  Webhook Callback URL                                    │
│  ┌──────────────────────────────────────────────┬──────┐│
│  │ https://yourdomain.com/webhook/pathao        │ Copy ││
│  └──────────────────────────────────────────────┴──────┘│
│                                                          │
│  Auth Token (Bearer) — copy this to Pathao panel        │
│  ┌──────────────────────────────────────────────┬──────┐│
│  │ f3992ecc-59da-4cbe-a049-a13da2018d51         │ Copy ││
│  └──────────────────────────────────────────────┴──────┘│
│  [Regenerate Token]                                      │
│                                                          │
│  ℹ️  Copy Callback URL to Pathao merchant panel >        │
│      Webhook Integration, and Auth Token to Secret field │
└─────────────────────────────────────────────────────────┘
```

**URL format:** `{APP_URL}/webhook/pathao` (auto-generated, read-only)  
**Token:** Stored in `courier_settings.pathao_webhook_token`, auto-generated UUID

#### Section B: Status Mapping Card

**Pathao Status Mapping:**
```
┌─────────────────────────────────────────────────────────────┐
│  🗺️ PATHAO STATUS MAPPING                                   │
│  Map Pathao events to your order statuses                   │
├────────────────────────────────┬────────────────────────────┤
│ PATHAO EVENT                   │ YOUR ORDER STATUS          │
├────────────────────────────────┼────────────────────────────┤
│ Delivered                      │ [delivered         ▼]      │
│ Pickup Cancelled               │ [cancelled         ▼]      │
│ Returned                       │ [returned          ▼]      │
│ Order Created                  │ [processing        ▼]      │
│ Assigned For Pickup            │ [shipped           ▼]      │
│ Pickup                         │ [shipped           ▼]      │
│ Pickup Failed                  │ [on_hold           ▼]      │
│ In Transit                     │ [shipped           ▼]      │
│ Assigned for Delivery          │ [shipped           ▼]      │
│ Partial Delivery               │ [partially_delivered▼]     │
│ Return                         │ [returned          ▼]      │
│ Delivery Failed                │ [on_hold           ▼]      │
│ On Hold                        │ [on_hold           ▼]      │
│ Payment Invoice                │ [— No Action —     ▼]      │
└────────────────────────────────┴────────────────────────────┘
            [Save Pathao Mapping]
```

**Steadfast Status Mapping:**
```
┌─────────────────────────────────────────────────────────────┐
│  🗺️ STEADFAST STATUS MAPPING                                │
│  Map Steadfast statuses to your order statuses              │
├────────────────────────────────┬────────────────────────────┤
│ STEADFAST STATUS               │ YOUR ORDER STATUS          │
├────────────────────────────────┼────────────────────────────┤
│ pending                        │ [processing        ▼]      │
│ delivered                      │ [delivered         ▼]      │
│ partial_delivered              │ [partially_delivered▼]     │
│ cancelled                      │ [cancelled         ▼]      │
│ unknown                        │ [— No Action —     ▼]      │
└────────────────────────────────┴────────────────────────────┘
            [Save Steadfast Mapping]
```

**Our available order statuses (dropdown options):**
```
— No Action —, pending, processing, confirmed, shipped,
delivered, cancelled, returned, on_hold, partially_delivered, incomplete
```

---

## 🔧 Phase 5: CourierController Updates

**File:** `app/Http/Controllers/Admin/Courier/CourierController.php`

### New Methods:

```php
// Generate/regenerate a webhook token for a courier
public function generateWebhookToken(Request $request, string $courier)

// Webhook logs list
public function webhookLogs(Request $request)

// Save status mapping
public function saveStatusMapping(Request $request, string $courier)
```

### Update `index()` to pass:
```php
'pathao_webhook_url' => route('webhook.pathao'),              // read-only
'steadfast_webhook_url' => route('webhook.steadfast'),        // read-only
'pathao_webhook_token' => $settings->pathao_webhook_token,
'steadfast_webhook_token' => $settings->steadfast_webhook_token,
'pathao_webhook_enabled' => $settings->pathao_webhook_enabled,
'steadfast_webhook_enabled' => $settings->steadfast_webhook_enabled,
'pathao_status_mapping' => $settings->pathao_status_mapping ?? [],
'steadfast_status_mapping' => $settings->steadfast_status_mapping ?? [],
```

### Update `storeOrUpdate()` to handle:
- `pathao_webhook_enabled`
- `steadfast_webhook_enabled`
- Auto-generate token if enabling for first time and token is null

---

## 📦 Phase 6: Models Update

### `CourierSetting` Model
Add new fields to `$fillable`:
```php
'pathao_webhook_enabled',
'pathao_webhook_token',
'pathao_status_mapping',
'steadfast_webhook_enabled',
'steadfast_webhook_token',
'steadfast_status_mapping',
```
Add casts:
```php
'pathao_status_mapping' => 'array',
'steadfast_status_mapping' => 'array',
```

### `WebhookLog` Model (NEW)
```php
- courier (string)
- order_id (nullable integer)
- event_type (string)
- payload (json)
- status (string: received/processed/failed)
- error_message (text nullable)
- processed_at (timestamp nullable)
```

---

## 🧪 Phase 7: ngrok Setup for Local Development Testing

### Installation
```bash
# Install ngrok (already have authtoken: 38sCO6BRxsIf3QBT2tWdSoYnpTi_36mxh1VazEe7RYw3QPCF1)
curl -sSL https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | sudo tee /etc/apt/sources.list.d/ngrok.list
sudo apt update && sudo apt install ngrok

# Configure authtoken
ngrok config add-authtoken 38sCO6BRxsIf3QBT2tWdSoYnpTi_36mxh1VazEe7RYw3QPCF1

# Start tunnel (Laravel runs on port 8000)
ngrok http 8000
```

### Testing Flow
1. Start ngrok → get public URL e.g. `https://abc123.ngrok-free.app`
2. Update `APP_URL` in `.env` temporarily to ngrok URL
3. Go to Courier Settings → copy Pathao Webhook URL
4. Paste into Pathao merchant panel → Webhook Integration section
5. Use **Test** button in Pathao panel to send test event
6. Check Laravel logs: `tail -f storage/logs/laravel.log`
7. Check `webhook_logs` table for received payloads

### ngrok Dashboard
Access local inspector at `http://127.0.0.1:4040` to see all incoming webhook requests/responses.

---

## 📁 Phase 8: File Manifest (Files to Create/Modify)

### NEW Files:
| File | Purpose |
|---|---|
| `app/Http/Controllers/Webhook/PathaoWebhookController.php` | Pathao webhook handler |
| `app/Http/Controllers/Webhook/SteadfastWebhookController.php` | Steadfast webhook handler |
| `app/Services/Webhook/WebhookStatusService.php` | Shared webhook logic |
| `app/Services/Webhook/WebhookTokenService.php` | Token generation |
| `app/Models/WebhookLog.php` | Webhook log model |
| `database/migrations/XXXX_add_webhook_fields_to_courier_settings.php` | DB migration |
| `database/migrations/XXXX_create_webhook_logs_table.php` | DB migration |

### MODIFIED Files:
| File | Changes |
|---|---|
| `app/Models/CourierSetting.php` | Add new fillable + casts |
| `app/Http/Controllers/Admin/Courier/CourierController.php` | New methods + pass webhook data |
| `app/Http/Middleware/VerifyCsrfToken.php` | Exempt webhook routes |
| `routes/web.php` | Add public webhook routes + new admin routes |
| `resources/js/Pages/Admin/Courier/Index.vue` | Full UI overhaul with webhook + status mapping sections |

---

## 🔄 Phase 9: Webhook Integration Flow

### Pathao Webhook Flow
```
1. Admin enables Pathao webhook in settings
2. System auto-generates UUID token (stored as pathao_webhook_token)
3. Admin copies:
   - Callback URL: https://domain.com/webhook/pathao
   - Secret/Token (same UUID) → pastes to Pathao panel > Secret field
4. Admin selects events in Pathao panel (Delivered, Pickup Cancelled, etc.)
5. Pathao sends POST to our URL with X-PATHAO-Signature header
6. Our controller validates signature == pathao_webhook_token
7. We look up order by merchant_order_id (= order.order_number)
8. Map Pathao event to our order status using pathao_status_mapping JSON
9. Update order.delivery_status + order.status
10. Log to webhook_logs table
11. Return 202 + X-Pathao-Merchant-Webhook-Integration-Secret header
```

### Steadfast Webhook Flow
```
1. Admin enables Steadfast webhook in settings
2. System auto-generates UUID token (stored as steadfast_webhook_token)
3. Admin copies:
   - Callback URL: https://domain.com/webhook/steadfast
   - Auth Token → pastes to Steadfast panel > Auth Token field (Bearer)
4. Steadfast sends POST with Authorization: Bearer {token}
5. Our controller validates Bearer token
6. Parse notification_type: delivery_status or tracking_update
7. Look up order by consignment_id
8. Map Steadfast status using steadfast_status_mapping JSON
9. Update order.delivery_status + order.status (if not "No Action")
10. Log to webhook_logs table
11. Return 200 {"status":"success","message":"Webhook received successfully."}
```

---

## 📊 Phase 10: Order Status Values in This Project

Based on analysis of `OrderController.php` and `updateBasicInfo()`:

```php
// Valid order statuses in this project:
'pending', 'processing', 'confirmed', 'shipped', 'delivered',
'cancelled', 'returned', 'on_hold', 'incomplete',
'partially_delivered'  // added from partial delivery feature
```

These will be the dropdown options in status mapping UI, plus `-- No Action --`.

---

## 🚦 Implementation Phases & Order

### ✅ Press OK to implement each phase separately:

| # | Phase | Description | Press OK When Ready |
|---|---|---|---|
| **1** | Database | 2 migrations: webhook fields + webhook_logs table | `[OK-1]` |
| **2** | Models | Update CourierSetting, create WebhookLog model | `[OK-2]` |
| **3** | Backend Services | WebhookStatusService + WebhookTokenService | `[OK-3]` |
| **4** | Webhook Controllers | PathaoWebhookController + SteadfastWebhookController | `[OK-4]` |
| **5** | Routes + CSRF | Public webhook routes + CSRF exemption | `[OK-5]` |
| **6** | Admin Controller | Update CourierController with new methods | `[OK-6]` |
| **7** | Admin UI | Full Courier/Index.vue overhaul | `[OK-7]` |
| **8** | ngrok Setup | Install + configure ngrok for local testing | `[OK-8]` |
| **9** | Testing & Debug | End-to-end webhook test walkthrough | `[OK-9]` |

---

## ⚠️ Important Notes

1. **No WebSockets needed** — the webhook updates the DB server-side. The admin UI shows updated delivery status when they refresh the Order list. (Real-time UI push via WebSocket/SSE can be added in a future phase.)

2. **CSRF Exemption is mandatory** — webhook routes MUST bypass CSRF or Pathao/Steadfast will get 419 errors.

3. **Token security** — tokens stored in DB (not env). Admin can regenerate if compromised. The token acts both as the secret/bearer and the verification mechanism.

4. **Queue** — The project already uses `QUEUE_CONNECTION=database` and `queue:listen` is running. Webhook processing can optionally be dispatched as a queued job for resilience.

5. **Existing `delivery_status` field** — already in `orders` table. We use both `delivery_status` (raw courier status text) and `status` (our mapped status).

6. **ngrok authtoken** — `38sCO6BRxsIf3QBT2tWdSoYnpTi_36mxh1VazEe7RYw3QPCF1` (already provided).

7. **`APP_URL` for webhook URL generation** — Use `env('APP_URL')` or `url('/')` to build the callback URL displayed in admin.

---

## 🚨 CRITICAL: Stock Restoration on Webhook Status Change

> **এটি সবচেয়ে গুরুত্বপূর্ণ বিষয় — এটি নিশ্চিত না করলে স্টক গণনা ভুল হয়ে যাবে।**
> **(This is the most important concern — if not handled correctly, stock counts will be wrong.)**

### ✅ What Already EXISTS in the Codebase

After deep analysis of `app/Services/Order/OrderService.php`, the **stock logic is already fully implemented and battle-tested** in the manual (admin-triggered) flow:

**`updateOrderStatus(int $orderId, string $newStatus): Order`** (Line 1381)
- Called when admin manually changes order status
- Loops through all order items
- Calls `adjustProductStock()` for each item
- Updates both `product.stock` + `product.sold_stock`
- Updates both `product_variation.stock` + `product_variation.sold_stock`
- Safe: `max(0, ...)` prevents negative stock

**`shouldReturnStock(oldStatus, newStatus): bool`** (Line 1188)
```
Returns TRUE (stock goes BACK to inventory) when:
  → any_status   → 'cancelled'   (e.g., processing → cancelled ✅)
  → any_status   → 'returned'    (e.g., shipped → returned ✅)
  → 'delivered'  → 'returned'    (special case: always allowed ✅)

Returns FALSE when:
  → already in cancelled/returned → cancelled/returned (no double-return ✅)
```

**`shouldReduceStock(oldStatus, newStatus): bool`** (Line 1215)
```
Returns TRUE (stock is DEDUCTED again) when:
  → 'cancelled' → active status  (e.g., cancelled → pending ✅)
  → 'returned'  → active status  (e.g., returned → shipped ✅)
```

**`returnProductStock()`** (Line 1239) — increments `stock`, decrements `sold_stock` for both product and variation  
**`reduceProductStock()`** (Line 1296) — decrements `stock`, increments `sold_stock` for both product and variation

---

### ⚡ The Webhook MUST Use the Same `updateOrderStatus()` Method

> **This is the KEY architectural decision for this feature.**

When a webhook fires and maps a courier status to our order status (e.g., Pathao "Pickup Cancelled" → `cancelled`), the `WebhookStatusService` **MUST call `OrderService::updateOrderStatus()`** — NOT do a direct `$order->update(['status' => ...])`.

**Why this is critical:**
- Direct `$order->update()` = **SKIPS** stock restoration ❌
- `OrderService::updateOrderStatus()` = **TRIGGERS** full stock logic ✅

#### Correct Implementation in `WebhookStatusService`:

```php
private function applyStatusMapping(Order $order, string $mappedStatus): void
{
    // ⚠️ MUST use OrderService, NOT direct $order->update()
    // This ensures stock is properly restored on cancelled/returned
    // and re-deducted if an order is reactivated from those states

    if ($mappedStatus === 'no_action' || empty($mappedStatus)) {
        // Only update delivery_status (raw from courier), don't touch order.status
        $order->update(['delivery_status' => $this->rawCourierStatus]);
        return;
    }

    // Update raw delivery_status from courier
    $order->update(['delivery_status' => $this->rawCourierStatus]);

    // Use OrderService to update mapped status — this triggers stock logic
    $this->orderService->updateOrderStatus($order->id, $mappedStatus);
}
```

---

### 📋 Stock Behavior Matrix for Webhook Triggers

| Webhook Event Maps To | Previous Status | Stock Effect | Handled By |
|---|---|---|---|
| `cancelled` | pending/processing/shipped | ✅ Stock RETURNED | `shouldReturnStock()` |
| `cancelled` | cancelled (duplicate) | ✅ No double-return | `shouldReturnStock()` guards |
| `returned` | shipped/on_hold | ✅ Stock RETURNED | `shouldReturnStock()` |
| `returned` | delivered | ✅ Stock RETURNED | Special case handled |
| `returned` | returned (duplicate) | ✅ No double-return | `shouldReturnStock()` guards |
| `delivered` | any | ✅ No stock change | Delivery doesn't affect stock |
| `on_hold` | any | ✅ No stock change | Not in returning statuses |
| `shipped` | cancelled/returned | ✅ Stock RE-DEDUCTED | `shouldReduceStock()` |
| `processing` | cancelled | ✅ Stock RE-DEDUCTED | `shouldReduceStock()` |

---

### ⚠️ One Edge Case to Note: `partially_delivered`

The `updateOrderStatus()` method's `$validStatuses` array is:
```php
['pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed']
```

> **`partially_delivered` is NOT in this list — it is handled by a separate `processPartialDelivery()` method** which does its own delta-based stock restoration.

**Action Required in `WebhookStatusService`:**
- When mapped status = `partially_delivered`, call a **simplified path**:
  - Only update `order.delivery_status` + `order.status` directly (no further stock adjustment)
  - Reason: partial delivery stock restoration requires knowing *which items* were returned, and the *quantity per item* — data that webhook payloads don't provide at item level
  - This is safe because the actual stock adjustment for partial deliveries will be done manually by admin via the Partial Delivery UI

```php
// In WebhookStatusService — special handling for partially_delivered
if ($mappedStatus === 'partially_delivered') {
    $order->update([
        'delivery_status' => $rawCourierStatus,
        'status' => 'partially_delivered',
    ]);
    Log::info('Webhook: partially_delivered — stock NOT auto-adjusted. Admin must use Partial Delivery UI.');
    return;
}
```

---

### 🔒 Summary: What Webhook Implementation GUARANTEES

| Scenario | Guarantee |
|---|---|
| Pathao sends "Pickup Cancelled" → maps to `cancelled` | ✅ Stock automatically restored via `updateOrderStatus()` |
| Pathao sends "Returned" → maps to `returned` | ✅ Stock automatically restored |
| Steadfast sends `delivered` → maps to `delivered` | ✅ No incorrect stock change |
| Steadfast sends `cancelled` repeatedly (duplicate webhook) | ✅ Stock NOT double-returned (guarded) |
| Status mapped to `— No Action —` | ✅ Only `delivery_status` updated, `status` and stock untouched |
| Status mapped to `partially_delivered` | ⚠️ Stock NOT auto-adjusted — admin must use Partial Delivery UI |

---

## 📌 Summary of What Admin Will See in UI

After implementation, the Courier Settings page will have **4 new sections** (2 per courier):

**For Pathao:**
- 🔌 Webhook Integration section (toggle, callback URL copy, token copy, instructions)
- 🗺️ Pathao Status Mapping table (dropdown per event)

**For Steadfast:**
- 🔌 Webhook Integration section (toggle, callback URL copy, Bearer token copy, instructions)
- 🗺️ Steadfast Status Mapping table (dropdown per status)
