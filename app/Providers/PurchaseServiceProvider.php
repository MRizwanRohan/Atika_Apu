<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\Purchase\PurchaseRepositoryInterface;
use App\Repository\Purchase\PurchaseRepository;
use App\Services\Purchase\PurchaseServiceInterface;
use App\Services\Purchase\PurchaseService;

class PurchaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
        $this->app->bind(PurchaseServiceInterface::class, PurchaseService::class);

        $this->app->bind(\App\Repository\Supplier\SupplierRepositoryInterface::class, \App\Repository\Supplier\SupplierRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
