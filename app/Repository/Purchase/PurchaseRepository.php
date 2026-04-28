<?php

namespace App\Repository\Purchase;

use App\Models\Purchase;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function all($search = null)
    {
        $query = Purchase::with(['supplier', 'items.product', 'items.variation']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate(15);
    }

    public function find($id)
    {
        return Purchase::with(['supplier', 'items.product', 'items.variation'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Purchase::create($data);
    }

    public function update($id, array $data)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->update($data);
        return $purchase;
    }

    public function syncItems($purchaseId, array $items)
    {
        $purchase = Purchase::findOrFail($purchaseId);
        // Clear old items if it's an update
        $purchase->items()->delete();
        $purchase->items()->createMany($items);
    }

    public function updatePurchaseStatus($id, $status)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->status = $status;
        $purchase->save();
        return $purchase;
    }
}
