<?php

namespace App\Repository\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function all($search = null)
    {
        $query = Supplier::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->select('suppliers.*')
            ->addSelect(DB::raw('COALESCE((SELECT SUM(total_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) as total_bill'))
            ->addSelect(DB::raw('COALESCE((SELECT SUM(paid_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) + COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = suppliers.id), 0) as total_paid'))
            ->addSelect(DB::raw('
                (suppliers.opening_balance + 
                COALESCE((SELECT SUM(total_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) - 
                COALESCE((SELECT SUM(paid_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) - 
                COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = suppliers.id), 0)) as dynamic_balance
            '))
            ->latest()
            ->paginate(15);
    }
    public function find($id) { return Supplier::findOrFail($id); }
    public function create(array $data) { return Supplier::create($data); }
    public function update($id, array $data)
    {
        $supplier = $this->find($id);
        $supplier->update($data);
        return $supplier;
    }
    public function delete($id) { return Supplier::destroy($id); }
}
