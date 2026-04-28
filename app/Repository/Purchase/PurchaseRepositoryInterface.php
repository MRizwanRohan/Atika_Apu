<?php

namespace App\Repository\Purchase;

interface PurchaseRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function syncItems($purchaseId, array $items);
    public function updatePurchaseStatus($id, $status);
}
