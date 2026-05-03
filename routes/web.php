<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CartController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\Pos\PosController;
use App\Http\Controllers\Admin\Brand\BrandController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Coupon\CouponController;
use App\Http\Controllers\Admin\Slider\SliderController;
use App\Http\Middleware\RedirectIfAuthenticatedOrGuest;
use App\Http\Controllers\Admin\Courier\CourierController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Category\CategoryController;
use App\Http\Controllers\Admin\Attribute\AttributeController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Marketing\MarketingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\GeneralSetting\GeneralSettingController;
use App\Http\Controllers\Admin\Inventory\InventoryManagementController;
use App\Http\Controllers\Admin\Courier\FraudcheckController;
use App\Http\Controllers\Admin\SitePage\SitePageController;
use App\Http\Controllers\Admin\LandingPage\LandingPageController;
use App\Http\Controllers\Admin\Employee\EmployeeController;
use App\Http\Controllers\Admin\Employee\EmployeeSalaryController;
use App\Http\Controllers\Admin\TeamMember\TeamMemberController;
use App\Http\Controllers\Admin\Expenses\ExpensesController;
use App\Http\Controllers\Admin\BusinessDashboard\BusinessDashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PromotionalSlider\PromotionalSliderController;
use App\Http\Controllers\Admin\Review\ReviewController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\Campaign\CampaignController;
use App\Http\Controllers\Admin\CorporateClient\CorporateClientController;
use App\Http\Controllers\Admin\Product\ProductVideoController;
use App\Http\Controllers\Admin\Banner\BannerController;
use App\Http\Controllers\Admin\MetaTracking\MetaTrackingController;
use App\Http\Controllers\Admin\ProductGroup\ProductGroupController;
use App\Http\Controllers\Admin\InstagramFeed\InstagramFeedController;
use App\Http\Controllers\Admin\Customer\CustomerController;
use App\Http\Controllers\Admin\Sms\SmsSettingController;
use App\Http\Controllers\Webhook\PathaoWebhookController;
use App\Http\Controllers\Webhook\SteadfastWebhookController;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
| All frontend routes are defined in routes/frontend.php
*/
require __DIR__ . '/frontend.php';



//route group for authenticated user
Route::group(['middleware' => ['auth', 'check.route.permission'], 'prefix' => 'admin', 'as' => 'admin.'], function () {


    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');


    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // User and Role Management
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
    // Use the Spatie Permission middleware by full class name to avoid alias resolution issues
    Route::resource('roles', RoleController::class)->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':manage_user_roles');



    //pos
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

    // oders
    Route::resource('/orders', OrderController::class);
    Route::get('/incomplete-orders', [OrderController::class, 'incompleteOrders'])->name('orders.incomplete');
    Route::get('/order-map', [OrderController::class, 'districtWiseOrders'])->name('orders.map');

    // customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/sms-logs', [CustomerController::class, 'smsLogs'])->name('customers.sms-logs');
    Route::post('/customers/send-bulk-sms', [CustomerController::class, 'sendBulkSms'])->name('customers.send-bulk-sms');

    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // SMS Settings
    Route::group(['prefix' => 'settings/sms', 'as' => 'settings.sms.'], function () {
        Route::get('/', [SmsSettingController::class, 'index'])->name('index');
        Route::post('/update', [SmsSettingController::class, 'updateSettings'])->name('update');
        Route::post('/templates', [SmsSettingController::class, 'storeTemplate'])->name('templates.store');
        Route::put('/templates/{template}', [SmsSettingController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [SmsSettingController::class, 'destroyTemplate'])->name('templates.destroy');
    });



    Route::get('/invoice/{order}/download', [OrderController::class, 'downloadInvoice'])->name('invoice.download');
    Route::post('/bulk-invoice/download', [OrderController::class, 'bulkDownloadInvoices'])->name('invoice.bulk-download');
    Route::get('/bulk-invoice/print', [OrderController::class, 'bulkPrintInvoices'])->name('invoice.bulk-print');
    // update new order



    Route::put('/orders/{orderId}/courier-details', [OrderController::class, 'updateCourierDetails'])->name('orders.courier-details.update');
    Route::post('/couriers/pathao/shipment', [CourierController::class, 'createShipment'])->name('couriers.pathao.shipment');
    Route::post('/couriers/pathao/shipments/bulk', [CourierController::class, 'createBulkShipment']);
    Route::get('/courier/settings', [CourierController::class, 'index'])->name('courier.settings.index');
    Route::get('/courier/dashboard', [CourierController::class, 'dashboard'])->name('courier.dashboard');
    Route::post('/courier/settings', [CourierController::class, 'storeOrUpdate'])->name('courier.settings.store');
    Route::post('/courier/settings/generate-token/{courier}', [CourierController::class, 'generateWebhookToken'])->name('courier.settings.generate-token');
    Route::post('/courier/settings/status-mapping/{courier}', [CourierController::class, 'saveStatusMapping'])->name('courier.settings.status-mapping');
    Route::get('/courier/webhook-logs', [CourierController::class, 'webhookLogs'])->name('courier.webhook-logs');

    //getAllProductsStock
    Route::get('/getAllProductsStock', [InventoryManagementController::class, 'getAllProductsStock'])->name('inventory.getAllProductsStock');

    // inventory
    Route::get('/reports', [InventoryManagementController::class, 'generateReport'])->name('reports.generateReport');
    Route::get('/reports/export', [InventoryManagementController::class, 'exportOrders'])->name('reports.export');
    Route::get('/reports/export-pdf', [InventoryManagementController::class, 'exportOrdersPDF'])->name('reports.export-pdf');

    Route::resource('products', ProductController::class);
    Route::post('/products/update-order', [ProductController::class, 'updateOrder'])->name('products.update-order');
    Route::resource('product-videos', ProductVideoController::class);
    Route::post('/products/{id}/quick-edit', [ProductController::class, 'quickEdit'])->name('products.quick-edit');
    Route::resource('attributes', AttributeController::class);
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDeleteProducts'])->name('products.bulk-delete');

    // categories
    Route::resource('categories', CategoryController::class);
    Route::put('/categories/{id}/status', [CategoryController::class, 'updateStatus'])->name('categories.updateStatus');
    Route::put('/categories/{id}/order', [CategoryController::class, 'updateOrder'])->name('categories.updateOrder');

    Route::resource('brands', BrandController::class);
    Route::put('/brands/{id}/status', [BrandController::class, 'updateStatus'])->name('brands.updateStatus');

    // Product Groups
    Route::resource('product-groups', ProductGroupController::class);
    Route::put('product-groups/{productGroup}/status', [ProductGroupController::class, 'toggleStatus'])->name('product-groups.status');
    Route::get('product-groups-search-products', [ProductGroupController::class, 'searchProducts'])->name('product-groups.search-products');

    // copon
    Route::resource('coupons', CouponController::class);

    Route::post('coupons/{coupon_id}/products', [CouponController::class, 'storeProductCoupon'])->name('coupons.storeProductCoupon');

    Route::get('/assign-coupon-to-products/{coupon_id}', [CouponController::class, 'couponAddedToProduct'])->name('coupons.addedToProducts');


        // campaigns
    Route::get('campaigns/search-products', [CampaignController::class, 'searchProducts'])->name('campaigns.search-products');
    Route::resource('campaigns', CampaignController::class);

    // corporate clients
    Route::resource('corporate-clients', CorporateClientController::class);


        // banners
    Route::resource('banners', BannerController::class)->only(['index', 'store', 'update']);
    Route::delete('/banners/random-banner', [BannerController::class, 'deleteRandomBanner']);
    Route::delete('/banners/delete-banner', [BannerController::class, 'deleteBanner']);
    Route::delete('/banners/random-banner-image', [BannerController::class, 'deleteRandomBannerImage'])->name('admin.banners.delete-random-image');
    Route::resource('instagram-feeds', InstagramFeedController::class);
    Route::put('/instagram-feeds/{id}/status', [InstagramFeedController::class, 'toggleStatus'])->name('instagram-feeds.toggleStatus');
    Route::post('/instagram-feeds/reorder', [InstagramFeedController::class, 'reorder'])->name('instagram-feeds.reorder');


    // GeneralSetting
    Route::get('/social-links', [GeneralSettingController::class, 'socialLinks'])->name('settings.socialLinks');
    Route::get('/basic-information', [GeneralSettingController::class, 'basicInformation'])->name('settings.basicInformation');
    Route::post('/settings-create', [GeneralSettingController::class, 'store'])->name('settings.store');
    Route::post('/clear-cache', [GeneralSettingController::class, 'clearCache'])->name('settings.clearCache');


    Route::post('/upload', [ProductController::class, 'productDescriptionImageUpload'])->name('upload');

    // slider
    Route::resource('sliders', SliderController::class);

    Route::resource('promotional-sliders', PromotionalSliderController::class);


        // pages
    Route::resource('site-pages', SitePageController::class);
    Route::put('/site-pages/{id}/status', [SitePageController::class, 'updateStatus'])->name('site-pages.updateStatus');

    // media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::put('/media', [MediaController::class, 'store'])->name('media.update');


    Route::get('/marketing-tools', [MarketingController::class, 'index'])->name('marketing-tools.index');
    Route::post('/marketing-tools', [MarketingController::class, 'store'])->name('marketing-tools.store');
    Route::delete('/marketing-tools/{marketingTool}', [MarketingController::class, 'destroy'])
    ->name('marketing-tools.destroy');



    //fraud check
    Route::post('/fraud-check', [FraudcheckController::class, 'fetchSuccessRate'])
        ->name('fraud-check.fetchSuccessRate');
    Route::post('/fraud-check/bulk', [FraudcheckController::class, 'bulkFetchSuccessRate'])
        ->name('fraud-check.bulk');


    Route::resource('landing-pages', LandingPageController::class);

    // Employee management
    Route::resource('employee', EmployeeController::class);
    Route::post('employee/{employee}/payment', [EmployeeController::class, 'makePayment'])->name('employee.payment');

    // Team Member management
    Route::resource('team-member', TeamMemberController::class);

    // Employee salaries
    Route::get('employee-salaries', [EmployeeSalaryController::class, 'index'])->name('employee.salary.index');
    Route::post('employee-salaries', [EmployeeSalaryController::class, 'store'])->name('employee.salary.store');

    // Expenses
    Route::resource('expenses', ExpensesController::class);
    Route::resource('expense-categories', \App\Http\Controllers\Admin\Expenses\ExpenseCategoryController::class);

    // Suppliers
    Route::get('/suppliers/ledger-report', [\App\Http\Controllers\Admin\Purchase\SupplierController::class, 'ledgerReport'])->name('suppliers.ledger-report');
    Route::post('/suppliers/record-payment', [\App\Http\Controllers\Admin\Purchase\SupplierController::class, 'recordPayment'])->name('suppliers.record-payment');
    Route::resource('suppliers', \App\Http\Controllers\Admin\Purchase\SupplierController::class);

    // Product Purchase Management (Modern system)
    Route::resource('purchases', \App\Http\Controllers\Admin\Purchase\PurchaseController::class);
    Route::post('/purchases/{id}/receive', [\App\Http\Controllers\Admin\Purchase\PurchaseController::class, 'markAsReceived'])->name('purchases.receive');

    // Product Purchase Costs (Legacy - keeping for now to avoid breakage, but should migrate)
    Route::resource('product-purchase-costs', ExpensesController::class);

    // Business Dashboard
    Route::get('/business-dashboard', [BusinessDashboardController::class, 'index'])->name('business-dashboard.index');

    // Reviews Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{reviewId}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('/reviews/{reviewId}', [ReviewController::class, 'destroy'])->name('reviews.destroy');





    // Blog Management
    Route::resource('blogs', BlogController::class);

    // Transaction History
    Route::get('/transaction-history', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'index'])->name('transaction-history.index');
    Route::get('/transaction-history/export-csv', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'exportCsv'])->name('transaction-history.export-csv');
    Route::get('/transaction-history/export-pdf', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'exportPdf'])->name('transaction-history.export-pdf');

    // ── Meta Tracking ─────────────────────────────────────────────
    Route::prefix('meta-tracking')->name('meta-tracking.')->group(function () {
        Route::get('/settings', [MetaTrackingController::class, 'settingsIndex'])->name('settings');
        Route::post('/settings', [MetaTrackingController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/test-event', [MetaTrackingController::class, 'sendTestEvent'])->name('test-event');
        Route::get('/logs', [MetaTrackingController::class, 'logsIndex'])->name('logs');
        Route::post('/orders/bulk-fire', [MetaTrackingController::class, 'bulkFire'])->name('orders.bulk-fire');
        Route::post('/orders/{order}/fire', [MetaTrackingController::class, 'manualFire'])->name('orders.fire');
        Route::post('/orders/{order}/resend', [MetaTrackingController::class, 'resend'])->name('orders.resend');
        Route::get('/orders/{order}/status', [MetaTrackingController::class, 'orderStatus'])->name('orders.status');
    });

    // Sub-routes for Order management
    Route::put('/orders/{orderId}/status', [OrderController::class, 'OderupdateStatus'])->name('orders.status.update');
    Route::put('/orders/{orderId}/partial-delivery', [OrderController::class, 'processPartialDelivery'])->name('orders.partial-delivery');
    Route::post('/orders/{orderId}/update', [OrderController::class, 'updateNewOrders'])->name('orders.update-new');
    Route::post('/orders/{orderId}/basic-info', [OrderController::class, 'updateBasicInfo'])->name('orders.basic-info');
    Route::put('/orders/{orderId}/admin-notes', [OrderController::class, 'updateAdminNotes'])->name('orders.update-notes');
    Route::put('/orders/{orderId}/advance-payment', [OrderController::class, 'verifyAdvancePayment'])->name('orders.verify-advance-payment');
    Route::put('/orders/{order}/items/{item}', [OrderController::class, 'updateItemQuantity'])->name('orders.items.update');
    Route::delete('/orders/{order}/items/{item}', [OrderController::class, 'destroyItem'])->name('orders.items.destroy');
});

// Public endpoint: pixel config for frontend (no auth needed)
Route::get('/api/meta/pixel-config', [MetaTrackingController::class, 'pixelConfig'])->name('meta.pixel-config');


// Removed insecure public routes

// Pre-Order Lead Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/leads', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{id}/status', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::delete('/leads/{id}', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'destroy'])->name('leads.destroy');

    // Admin notifications listing
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
});

// ── Courier Webhook Callbacks (public, no auth, CSRF excluded in bootstrap/app.php) ──
Route::post('/webhook/pathao', [PathaoWebhookController::class, 'handle'])->name('webhook.pathao');
Route::post('/webhook/steadfast', [SteadfastWebhookController::class, 'handle'])->name('webhook.steadfast');

require __DIR__ . '/auth.php';
