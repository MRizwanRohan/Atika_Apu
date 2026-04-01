# Partial Delivery & Return Implementation Plan

## Overview
Currently, your `orders` and `order_items` tables track the overall state of an order but lack the granularity required to handle **item-level returns** or **partial payments** (e.g., customer returning the item but paying the delivery fee).

This plan outlines the database changes, business logic, and flows needed to support your scenarios.

---

## 1. Database Migrations

### Update `orders` Table
We need to add new statuses and columns to track partial deliveries and actual collected amounts.

*   **Modify `status` ENUM**: Add `partially_delivered`.
*   **Modify `payment_status` ENUM**: Add `partially_paid`.
*   **Add Column `paid_amount`**: (decimal, default 0.00). Useful for Scenario 1 where the order is returned but you collected a 60tk/120tk delivery fee.
*   **Add Column `return_charge`**: (decimal, default 0.00). If you want to keep track of how much penalty/return fee was applied.

### Update `order_items` Table
We need to track the status of *individual* items and how many of them were returned (if the ordered quantity > 1).

*   **Add Column `status`**: ENUM(`pending`, `delivered`, `returned`). Default: `pending`.
*   **Add Column `returned_quantity`**: (integer, default 0). To handle if a customer bought 3 of the SAME product but returned 1.

---

## 2. Scenario Workflows

### Scenario 1: Full Return but Paid Partial Amount (Delivery Charge)
*Example: Customer ordered 1 item. Did not like it. Returned the item but paid 60 TK for delivery.*

1.  **Item Level**: Mark the `order_items` row `status` as `returned`.
2.  **Order Level**: Change the `orders.status` to `returned`.
3.  **Payment Level**: Change `orders.payment_status` to `partially_paid` (or keep unpaid if you treat delivery fee separate, but partially_paid is better). Change `orders.paid_amount` to `60.00`.
4.  **Stock Level**: The stock for the returned item is incremented (restored) in the inventory.

### Scenario 2: Partial Delivery (Keep 1, Return 1)
*Example: Ordered 2 different items. Kept Item A, Returned Item B.*

1.  **Item Level**: 
    *   Item A: `status` = `delivered`
    *   Item B: `status` = `returned`, `returned_quantity` = 1
2.  **Order Level**: Change the `orders.status` to `partially_delivered`.
3.  **Financials & Payment**: 
    *   Recalculate the order total mathematically (Original Total - Item B Total), or leave original total and update `paid_amount` = `(Item A price + Shipping)`.
    *   If fully paid for the kept items, `payment_status` can be marked as `paid` or `partially_paid` depending on your accounting preference.
4.  **Stock Level**: 
    *   Item A stock remains deducted.
    *   Item B stock is incremented (restored) in inventory.

---

## 3. Service Layer Updates (`OrderService.php`)

To implement this neatly, we will need to add new methods to your `OrderService.php`:

1.  `processPartialDelivery(int $orderId, array $deliveredItems, array $returnedItems, float $paidAmount)`
    *   Updating statuses for each item.
    *   Restoring stock for returned items using your existing `returnProductStock()` method.
    *   Updating the order status to `partially_delivered`.
    *   Updating `paid_amount` and `payment_status`.

2.  **Update existing `updateOrderStatus()`**:
    *   Handle transitions to `partially_delivered`.

3.  **Courier Integration**:
    *   If using Steadfast or Pathao, parse their webhook/API for `partial_delivered` status and trigger the above method.

---

## 4. Admin UI Changes (Frontend / Vue: `Index.vue` & Modals)

Based on the current `Index.vue` architecture, the status update feature currently uses a direct API call (`updateOrderStatus`). Since a partial delivery requires more complex data (item quantities, collected amounts), we need a dedicated modal rather than just a simple dropdown selection.

### 4.1. New Component: `PartialDeliveryModal.vue`
Create a new modal component (similar to `CourierSelectionModal.vue` or `AdminNotesModal.vue`) to handle the complexity.

**Modal UI Elements:**
*   **Header**: "Handle Delivery & Returns for [Order Number]"
*   **Item Breakdown Table**:
    *   Displays each item in the order (Image, Name, Variation).
    *   Input/Counters for: `Delivered Qty` and `Returned Qty`.
    *   *Logic*: The sum of delivered + returned must equal the original ordered quantity.
*   **Financial Adjustments Box**:
    *   **Original Total**: Uneditable text.
    *   **Amount Collected (TK)**: A numeric input field. Admin puts exactly how much the delivery guy brought back. (If it's a full return but they paid 60TK for shipping, put 60 here).
    *   **Return Charge/Deduction**: (Optional) input to explicitly mark the penalty.
*   **Status Selection**:
    *   A dropdown to choose final status: `partially_delivered`, `returned`, or `delivered`.

### 4.2. Updates to `Index.vue`
1.  **Status Badges/Cards**: 
    *   Add `partially_delivered` to the `availableStatuses` array so a status card appears at the top of the page.
    *   Add `partially_paid` to `paymentStatusOptions` for advanced filtering.
2.  **Intercepting the Status Dropdown (`StatusDropdown.vue`)**:
    *   Currently, clicking a status updates the backend directly.
    *   *Change*: If the admin selects `partially_delivered` or `returned` from the dropdown, **prevent the direct API call**. Instead, open the `PartialDeliveryModal` automatically.
3.  **Action Menu**:
    *   Add a dedicated button/icon (e.g., `<PackageOpen />` icon) in the action column dropdown specifically labeled **"Process Return/Partial"** which triggers the `PartialDeliveryModal`.
4.  **Data Handling**:
    *   Add `showPartialModal = ref(false)` and `selectedOrderForPartial = ref(null)`.
    *   Add a submission method `submitPartialDelivery(data)` that posts the modal form data to the new backend endpoint and reloads the page.

---

## 5. API Endpoint for UI to Consume

Create a new POST/PUT endpoint: `api/admin/orders/{id}/partial-delivery`
Payload Example:
```json
{
  "items": [
    { "order_item_id": 101, "delivered_qty": 1, "returned_qty": 1 },
    { "order_item_id": 102, "delivered_qty": 0, "returned_qty": 2 }
  ],
  "collected_amount": 1000.00,
  "return_charge": 60.00,
  "final_status": "partially_delivered"
}
```

## Instructions
Review this updated UI plan. If you agree with the backend and UI flow, reply with **"apply"**, and I will generate the code (Migrations, Service Logic, Controller, and the Vue Modal) one by one.