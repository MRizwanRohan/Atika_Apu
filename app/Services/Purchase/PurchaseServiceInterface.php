<?php

namespace App\Services\Purchase;

interface PurchaseServiceInterface
{
    public function getAllPurchases();
    public function getPurchaseById($id);
    public function createPurchase(array $data);
    public function updatePurchase($id, array $data);
    public function receivePurchase($id);
}
