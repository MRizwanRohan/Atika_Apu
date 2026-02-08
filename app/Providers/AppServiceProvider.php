<?php

namespace App\Providers;

use App\Support\SystemCheck;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register any application services here
        $this->app->bind(
            \App\Contracts\PreOrderLeadInterface::class,
            \App\Services\PreOrderLeadService::class
        );

        $this->app->bind(
            \App\Contracts\ProductGroupRepositoryInterface::class,
            \App\Repository\ProductGroup\ProductGroupRepository::class
        );

        $this->app->bind(
            \App\Contracts\ProductVideoRepositoryInterface::class,
            \App\Repository\ProductVideoRepository::class
        );

        $this->app->bind(
            \App\Contracts\ProductVideoServiceInterface::class,
            \App\Services\ProductVideoService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
