<?php

namespace App\Services\Purchase;

use App\Repository\Purchase\PurchaseRepositoryInterface;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService implements PurchaseServiceInterface
{
    protected $purchaseRepository;

    public function __construct(PurchaseRepositoryInterface $purchaseRepository)
    {
        $this->purchaseRepository = $purchaseRepository;
    }

    public function getAllPurchases($search = null)
    {
        return $this->purchaseRepository->all($search);
    }

    public function getPurchaseById($id)
    {
        return $this->purchaseRepository->find($id);
    }

    public function createPurchase(array $data)
    {
        DB::beginTransaction();
        try {
            // Calculate totals
            $items = $data['items'] ?? [];
            $totalAmount = floatval($data['shipping_cost'] ?? 0);
            
            foreach ($items as $item) {
                $totalAmount += floatval($item['unit_cost']) * intval($item['quantity']);
            }
            $data['total_amount'] = $totalAmount;

            // Auto-generate reference number if not provided
            if (empty($data['reference_no'])) {
                $data['reference_no'] = 'PUR-' . date('Ymd') . '-' . substr(str_shuffle("0123456789ABCDEF"), 0, 4);
            }

            // Default status to received_no_stock (recorded but no stock update)
            if (empty($data['status'])) {
                $data['status'] = 'received_no_stock';
            }

            // Create purchase
            $purchase = $this->purchaseRepository->create($data);

            // Sync items with subtotal calculation
            if (!empty($items)) {
                $processedItems = array_map(function($item) {
                    $item['subtotal'] = floatval($item['unit_cost']) * intval($item['quantity']);
                    return $item;
                }, $items);
                $this->purchaseRepository->syncItems($purchase->id, $processedItems);
            }

            // Record payment record for accounting
            if (isset($data['paid_amount']) && floatval($data['paid_amount']) > 0) {
                \App\Models\SupplierPayment::create([
                    'supplier_id' => $purchase->supplier_id,
                    'amount' => $data['paid_amount'],
                    'payment_date' => $purchase->purchase_date,
                    'payment_method' => 'Cash',
                    'reference' => 'Payment for Purchase: ' . $purchase->reference_no,
                    'note' => 'Auto-recorded during purchase creation'
                ]);
            }

            // Only update stock when status is explicitly 'received'
            if ($purchase->status === 'received') {
                $this->processReceivedPurchase($purchase);
            }
            // 'received_no_stock' = purchase recorded/received, but stock NOT updated

            DB::commit();
            return $purchase;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePurchase($id, array $data)
    {
        DB::beginTransaction();
        try {
            $purchase = $this->purchaseRepository->find($id);

            // Prevent updating if already received with stock update (business logic)
            if ($purchase->status === 'received') {
                throw new Exception("Cannot update a purchase that has already been received.");
            }

            // Calculate totals
            $items = $data['items'] ?? [];
            $totalAmount = floatval($data['shipping_cost'] ?? 0);
            
            foreach ($items as $item) {
                $totalAmount += floatval($item['unit_cost']) * intval($item['quantity']);
            }
            $data['total_amount'] = $totalAmount;

            $purchase = $this->purchaseRepository->update($id, $data);

            if (!empty($items)) {
                $processedItems = array_map(function($item) {
                    $item['subtotal'] = floatval($item['unit_cost']) * intval($item['quantity']);
                    return $item;
                }, $items);
                $this->purchaseRepository->syncItems($purchase->id, $processedItems);
            }

            // Record/Update payment if changed (simplified - always create new for now as per user request flow)
            if (isset($data['paid_amount']) && floatval($data['paid_amount']) > 0 && floatval($data['paid_amount']) != floatval($purchase->paid_amount)) {
                \App\Models\SupplierPayment::create([
                    'supplier_id' => $purchase->supplier_id,
                    'amount' => $data['paid_amount'],
                    'payment_date' => $purchase->purchase_date,
                    'payment_method' => 'Cash',
                    'reference' => 'Payment Update for Purchase: ' . $purchase->reference_no,
                    'note' => 'Auto-recorded during purchase update'
                ]);
            }

            // Only process stock if explicitly marked as 'received'
            if (isset($data['status']) && $data['status'] === 'received') {
                $this->processReceivedPurchase($purchase);
            }
            // 'received_no_stock' skips stock update intentionally

            DB::commit();
            return $purchase;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function receivePurchase($id)
    {
        DB::beginTransaction();
        try {
            $purchase = $this->purchaseRepository->find($id);
            
            if ($purchase->status === 'received') {
                throw new Exception("Purchase is already marked as received.");
            }
            // Allow 'received_no_stock' to be re-processed and update stock via this method

            $purchase = $this->purchaseRepository->updatePurchaseStatus($id, 'received');
            $this->processReceivedPurchase($purchase);

            DB::commit();
            return $purchase;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process stock and recalculate weighted average cost
     */
    protected function processReceivedPurchase($purchase)
    {
        // Reload items to ensure we have the latest relations
        $purchase->load(['items.product', 'items.variation']);

        // Calculate shipping cost distribution (Landed Cost)
        // We distribute the total shipping cost of the purchase over the total quantity of items
        $totalQuantity = $purchase->items->sum('quantity');
        $shippingPerUnit = $totalQuantity > 0 ? (floatval($purchase->shipping_cost ?? 0) / $totalQuantity) : 0;

        foreach ($purchase->items as $item) {
            // User requested to use base unit rate (fixed the 100/110 confusion)
            // If we want landed cost, we should add $shippingPerUnit here
            $baseUnitCost = floatval($item->unit_cost);

            if ($item->product_variation_id) {
                $this->updateVariationStockAndCost($item->variation, $item->quantity, $baseUnitCost);
                $this->updateProductStockOnly($item->product, $item->quantity); 
            } else if ($item->product_id) {
                $this->updateProductStockAndCost($item->product, $item->quantity, $baseUnitCost);
            }
        }
    }

    protected function updateProductStockAndCost(Product $product, int $purchasedQty, float $purchasedUnitCost)
    {
        $oldStock = $product->stock ?? 0;
        $oldUnitCost = floatval($product->unit_cost ?? 0);
        
        $newStock = $oldStock + $purchasedQty;
        
        // Calculate new Weighted Average Cost
        $totalOldValue = $oldStock * $oldUnitCost;
        $totalNewValue = $purchasedQty * $purchasedUnitCost;
        $newUnitCost = $newStock > 0 ? ($totalOldValue + $totalNewValue) / $newStock : 0;

        $product->update([
            'stock' => $newStock,
            'unit_cost' => $newUnitCost
        ]);
    }

    protected function updateVariationStockAndCost(ProductVariation $variation, int $purchasedQty, float $purchasedUnitCost)
    {
        $oldStock = $variation->stock ?? 0;
        $oldUnitCost = floatval($variation->unit_cost ?? 0);
        
        $newStock = $oldStock + $purchasedQty;
        
        // Calculate new Weighted Average Cost
        $totalOldValue = $oldStock * $oldUnitCost;
        $totalNewValue = $purchasedQty * $purchasedUnitCost;
        $newUnitCost = $newStock > 0 ? ($totalOldValue + $totalNewValue) / $newStock : 0;

        $variation->update([
            'stock' => $newStock,
            'unit_cost' => $newUnitCost
        ]);
    }

    protected function updateProductStockOnly(Product $product, int $purchasedQty)
    {
        // Assuming parent product stock is a sum of variations stock, if that's how it's designed.
        $oldStock = $product->stock ?? 0;
        $product->update([
            'stock' => $oldStock + $purchasedQty
        ]);
    }
}
