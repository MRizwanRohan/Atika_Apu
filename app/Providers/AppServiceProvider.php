<?php

namespace App\Providers;

use App\Support\SystemCheck;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

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

        // Meta Conversion API
        $this->app->bind(
            \App\Contracts\MetaConversionInterface::class,
            \App\Services\MetaConversion\MetaConversionService::class
        );

        $this->app->singleton(
            \App\Services\MetaConversion\MetaConversionService::class
        );

        $this->app->bind(
            \App\Services\Report\FinancialReportServiceInterface::class,
            \App\Services\Report\FinancialReportService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Monitor slow queries (> 500ms) to identify bottlenecks early
        DB::listen(function ($query) {
            if ($query->time > 500) {
                Log::warning('Slow query detected', [
                    'sql'  => $query->sql,
                    'time' => $query->time . 'ms',
                    'url'  => request()->fullUrl(),
                ]);
            }
        });
    }
}
