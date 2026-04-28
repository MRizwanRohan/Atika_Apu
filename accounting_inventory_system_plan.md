# Accounting & Inventory System Improvement Plan

This plan details the upgrade of your e-commerce platform (Laravel/Vue/Inertia.js) to support strict supplier cost tracking, item-level profit calculation, and comprehensive business reporting.

## A. Analysis of Existing Structure
1. **[ProductPurchaseCost](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductPurchaseCost.php#7-20) is highly flawed:** It operates as a rudimentary logging table (`cost_name`, `quantity`, `supplier_name` as text) without foreign keys linking to actual `products` or `product_variations`. It doesn't affect actual stock.
2. **Missing Supplier Entity:** Suppliers are currently tracked by string names, which means no ledger, balance, or reliable history can be maintained.
3. **Cost Price Not Tracked Structurally:** [Product](file:///home/samrat/Desktop/samrat/xylo/app/Models/Product.php#10-143) and [ProductVariation](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductVariation.php#9-52) models only store `price` (selling price) and `previous_price`. There is no `unit_cost`.
4. **Profit Calculation Impossibility:** [OrderItem](file:///home/samrat/Desktop/samrat/xylo/app/Models/OrderItem.php#10-73) records selling price (`final_price`, `unit_price`) but does NOT snapshot the cost of the item at the moment of sale. If a product's wholesale cost changes tomorrow, all your historical profit calculations will be altered. 
5. **Basic Expenses:** The [Expense](file:///home/samrat/Desktop/samrat/xylo/app/Models/Expense.php#7-17) model lacks categorization (e.g., separating ad spend from utility bills).

---

## B. Identified Missing Tables & Refactoring
To achieve production-grade accounting without overcomplicating things:
- **Drop/Deprecate** the current [ProductPurchaseCost](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductPurchaseCost.php#7-20) in favor of a standard `Purchases` and `PurchaseItems` structure.
- **Add Tables:** `suppliers`, `purchases`, `purchase_items`, `expense_categories`.
- **Alter Tables:** Add cost columns to `products`, `product_variations`, and `order_items`.

---

## C. Improved Database Schema (Tables + Relationships)

### 1. New Tables
**`suppliers`**
- `id`, `name`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`

**`purchases`** (Tracks the overall invoice from a supplier)
- `id`, `supplier_id` (FK), `reference_no`, `purchase_date`, `status` (Pending/Received), `shipping_cost`, `total_amount`, `paid_amount`, `note`, `created_at`, `updated_at`

**`purchase_items`** (Tracks exact items inside the purchase)
- `id`, `purchase_id` (FK), `product_id` (FK), `product_variation_id` (FK, nullable), `unit_cost`, `quantity`, `subtotal`, `created_at`, `updated_at`

**`expense_categories`**
- `id`, `name` (e.g., Marketing, Packaging, Office), `is_active`

### 2. Alterations to Existing Tables
**`products` & `product_variations`**
- Add `unit_cost` (Decimal). This is the *Average Cost* or the *Last Purchase Cost*.

**`order_items`**
- Add `unit_cost` (Decimal). **Critical:** When an order is placed, copy the current `unit_cost` from the [Product](file:///home/samrat/Desktop/samrat/xylo/app/Models/Product.php#10-143) (or [Variation](file:///home/samrat/Desktop/samrat/xylo/app/Models/OrderItem.php#62-72)) into this column.

**`expenses`**
- Add `expense_category_id` (FK) to group expenses.

**`orders`** (Optional but recommended)
- Add `actual_shipping_cost` (what courier charges you) vs `shipping_cost` (what customer paid). Add `packaging_cost`.

---

## D. Step-by-Step Implementation Plan

### Step 1: Database Migrations
- Create migrations for new tables (`suppliers`, `expense_categories`, `purchases`, `purchase_items`).
- Create a migration to alter existing tables: `php artisan make:migration add_accounting_fields_to_tables`.
- Run migrations.

### Step 2: Laravel Models & Relations (See Section E below)
- Create `Supplier`, [Purchase](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductPurchaseCost.php#7-20), `PurchaseItem`, `ExpenseCategory` models.
- Update [Product](file:///home/samrat/Desktop/samrat/xylo/app/Models/Product.php#10-143), [ProductVariation](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductVariation.php#9-52), [Order](file:///home/samrat/Desktop/samrat/xylo/app/Models/Order.php#11-110), [OrderItem](file:///home/samrat/Desktop/samrat/xylo/app/Models/OrderItem.php#10-73), [Expense](file:///home/samrat/Desktop/samrat/xylo/app/Models/Expense.php#7-17) with relationships.

### Step 3: Purchase & Inventory Logic (Backend)
- Build `PurchaseController`.
- **Logic:** When a purchase `status` is marked as `Received`:
    1. Loop through `PurchaseItem`s.
    2. Increase `stock` on [Product](file:///home/samrat/Desktop/samrat/xylo/app/Models/Product.php#10-143) / [ProductVariation](file:///home/samrat/Desktop/samrat/xylo/app/Models/ProductVariation.php#9-52).
    3. **Cost Recalculation:** Update the `unit_cost` on the Product/Variation using the **Weighted Average Cost** method or **Last Purchase Cost**. (Weighted is best for strict accounting).

### Step 4: Sales & Snapshotting Cost
- Update the Checkout/Order creation logic (likely `OrderRepository` or `CheckoutController`).
- When saving an [OrderItem](file:///home/samrat/Desktop/samrat/xylo/app/Models/OrderItem.php#10-73), fetch the `$product->unit_cost` (or variation's unit_cost) and save it directly into `$orderItem->unit_cost`.

### Step 5: Profit & Reporting Views (Frontend - Vue/Inertia)
- Build Reports Dashboard.
- Profit logic relies on the snapshot `unit_cost` ensuring historical accuracy.

---

## E. Laravel Model Relationships

```php
// Supplier.php
public function purchases() { return $this->hasMany(Purchase::class); }

// Purchase.php
public function supplier() { return $this->belongsTo(Supplier::class); }
public function items() { return $this->hasMany(PurchaseItem::class); }

// PurchaseItem.php
public function purchase() { return $this->belongsTo(Purchase::class); }
public function product() { return $this->belongsTo(Product::class); }
public function variation() { return $this->belongsTo(ProductVariation::class, 'product_variation_id'); }

// Product & ProductVariation
public function purchaseItems() { return $this->hasMany(PurchaseItem::class); }

// OrderItem.php
// Belongs to Order, Product, ProductVariation (already exists)

// Expense.php
public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
```

---

## F. Calculation Logic for Profit & Cost

### 1. Stock & Cost Recalculation (On Purchase Receive)
If using **Weighted Average Cost** (Highly Recommended for accurate profit):
```php
$new_stock = $old_stock + $purchased_qty;
$new_unit_cost = (($old_stock * $old_unit_cost) + ($purchased_qty * $purchased_unit_cost)) / $new_stock;
```

### 2. Item-level Profit (Per OrderItem)
```php
// Since order_items stores the unit_cost at time of sale:
$ItemRevenue = $order_item->final_price; // Or (unit_price * qty) - item_discounts
$ItemCOGS (Cost of Goods Sold) = $order_item->unit_cost * $order_item->quantity;

$NetItemProfit = $ItemRevenue - $ItemCOGS;
```

### 3. Total Business Profit for a Period (Monthly)
```text
  Gross Revenue (Sum of Order totals)
- Total COGS (Sum of all shipped OrderItems: quantity * unit_cost)
- Total Order Discounts
- Total Expenses (Marketing, Packaging, Office, from `expenses` table)
= Net Profit
```

---

## G. Scalability Summary
- **Historical Accuracy:** Snapshotting `unit_cost` inside `order_items` ensures that past profits do not fluctuate when you add new stock at a different wholesale price.
- **Query Efficiency:** By storing aggregated data efficiently and keeping foreign keys well-indexed, generating reports will not lock your database.
- **Normalization:** Avoiding string-based suppliers and adopting a robust relational model allows you to extend into a fully-fledged ERP later (Ledgers, Accounts Payable).
- **Separation of Concerns:** Global expenses (salaries, ads) live in `expenses`, while transactional profit lives down at the `order_items` level.
