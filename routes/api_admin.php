<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
| Routes for admin users - requires authentication and admin role
*/

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'admin.auth', 'throttle:60,1'])
    ->name('api.admin.')
    ->group(function () {

    // Authentication
    Route::post('/logout', [\Modules\User\Http\Controllers\AuthController::class, 'logout'])
        ->name('logout');
    Route::post('/refresh', [\Modules\User\Http\Controllers\AuthController::class, 'refresh'])
        ->name('refresh');
    Route::get('/me', [\Modules\User\Http\Controllers\AuthController::class, 'me'])
        ->name('me');

    // User Management
    Route::apiResource('users', \Modules\User\Http\Controllers\UserController::class);

    // Product Authenticity Management
    Route::prefix('authenticity')->name('authenticity.')->group(function () {
        Route::post('/generate', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'generate'])
            ->name('generate');
        Route::get('/product/{productId}/stats', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'productStats'])
            ->name('product-stats');
        Route::get('/analytics', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'analytics'])
            ->name('analytics');
        Route::put('/{qrCode}/mark-fake', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'markAsFake'])
            ->name('mark-fake');
    });

    // Catalogue Management
    Route::apiResource('products', \Modules\Catalogue\Http\Controllers\Admin\ProductController::class);
    Route::apiResource('categories', \Modules\Catalogue\Http\Controllers\Admin\CategoryController::class);
    Route::apiResource('brands', \Modules\Catalogue\Http\Controllers\Admin\BrandController::class);
    Route::apiResource('attributes', \Modules\Catalogue\Http\Controllers\Admin\AttributeController::class);

    // Preorder Management
    Route::prefix('products/{productId}/preorder')->name('products.preorder.')->group(function () {
        Route::post('/enable', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'enable'])
            ->name('enable');
        Route::post('/disable', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'disable'])
            ->name('disable');
    });
    Route::get('/preorders', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'index'])
        ->name('preorders.index');

    // Order Management
    Route::apiResource('orders', \Modules\Order\Http\Controllers\Admin\OrderController::class);
    Route::put('/orders/{id}/status', [\Modules\Order\Http\Controllers\Admin\OrderController::class, 'updateStatus'])
        ->name('orders.update-status');
    Route::get('/orders/{id}/items', [\Modules\Order\Http\Controllers\Admin\OrderController::class, 'items'])
        ->name('orders.items');

    // Customer Management
    Route::apiResource('customers', \Modules\Customer\Http\Controllers\Admin\CustomerController::class);

    // Customer status management
    Route::prefix('customers')->name('customers.')->group(function () {

        // Status management
        Route::patch('{id}/activate', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'activate'])
            ->name('activate');
        Route::patch('{id}/deactivate', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'deactivate'])
            ->name('deactivate');
        Route::patch('{id}/suspend', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'suspend'])
            ->name('suspend');

        // Bulk status update
        Route::post('bulk/status', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkUpdateStatus'])
            ->name('bulk.status');

        // Soft delete & restore
        Route::post('bulk/destroy', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkDestroy'])
            ->name('bulk.destroy');
        Route::post('{id}/restore', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'restore'])
            ->name('restore');
        Route::post('bulk/restore', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkRestore'])
            ->name('bulk.restore');

        // Force delete
        Route::delete('{id}/force', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'forceDelete'])
            ->name('force-delete');
        Route::post('bulk/force-delete', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkForceDelete'])
            ->name('bulk.force-delete');

        // Export
        Route::post('export', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'export'])
            ->name('export');

        // Statistics
        Route::get('statistics', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'statistics'])
            ->name('statistics');
    });

    // Promotion Management
    Route::apiResource('promotions', \Modules\Promotion\Http\Controllers\Admin\PromotionController::class);
    Route::post('/promotions/{id}/deactivate', [\Modules\Promotion\Http\Controllers\Admin\PromotionController::class, 'deactivate'])
        ->name('promotions.deactivate');

    // Review Management
    Route::apiResource('reviews', \Modules\Reviews\Http\Controllers\Admin\ReviewController::class);
    Route::post('/reviews/{id}/approve', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'approve'])
        ->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'reject'])
        ->name('reviews.reject');

    // Shipping Management
    Route::apiResource('shipping-methods', \Modules\Shipping\Http\Controllers\Admin\ShippingMethodController::class);
    Route::apiResource('shipments', \Modules\Shipping\Http\Controllers\Admin\ShipmentController::class);

    // Inventory Management
    Route::get('/inventory/low-stock', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'lowStock'])
        ->name('inventory.low-stock');
    Route::post('/inventory/adjust', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'adjust'])
        ->name('inventory.adjust');
    Route::get('/inventory/movements', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'movements'])
        ->name('inventory.movements');

    // Dashboard & Analytics
    Route::get('/dashboard/stats', [\Modules\Core\Http\Controllers\DashboardController::class, 'stats'])
        ->name('dashboard.stats');
});
