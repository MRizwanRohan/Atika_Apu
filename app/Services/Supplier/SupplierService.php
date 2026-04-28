<?php

namespace App\Services\Supplier;

use App\Repository\Supplier\SupplierRepositoryInterface;

class SupplierService
{
    protected $repository;
    public function __construct(SupplierRepositoryInterface $repository) { $this->repository = $repository; }
    public function getAll($search = null) { return $this->repository->all($search); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
}
