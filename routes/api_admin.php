<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'admin.auth', 'throttle:60,1'])
    ->name('api.admin.')
    ->group(function () {

        // Authentification
        Route::get('/me', [\Modules\User\Http\Controllers\AuthController::class, 'me'])->name('me');
        Route::post('/logout', [\Modules\User\Http\Controllers\AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [\Modules\User\Http\Controllers\AuthController::class, 'refresh'])->name('refresh');

        // Management Utilisateurs
        Route::apiResource('users', \Modules\User\Http\Controllers\UserController::class);

        Route::prefix('authenticity')->name('authenticity.')->group(function () {
            Route::post('/generate', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'generate'])->name('generate');
            Route::get('/analytics', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'analytics'])->name('analytics');
            Route::put('/{qrCode}/mark-fake', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'markAsFake'])->name('mark-fake');
            Route::get('/product/{productId}/stats', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'productStats'])->name('product-stats');
        });

        Route::apiResource('collections', \Modules\Catalogue\Http\Controllers\Admin\CollectionController::class);

        Route::prefix('collections')->name('collections.')->group(function () {

            // Toggle statuses
            Route::post('{id}/toggle-featured', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'toggleFeatured'])
                ->name('toggle-featured');
            Route::post('{id}/toggle-active', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'toggleActive'])
                ->name('toggle-active');

            // Gestion des produits
            Route::post('{id}/products/add', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'addProducts'])
                ->name('products.add');
            Route::post('{id}/products/remove', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'removeProducts'])
                ->name('products.remove');
            Route::post('{id}/products/sync', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'syncProducts'])
                ->name('products.sync');
            Route::get('{id}/products/statistics', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'productsStatistics'])
                ->name('products.statistics');

            // Statistics & analytics
            Route::get('{id}/statistics', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'statistics'])
                ->name('statistics');

            // Maintenance
            Route::post('{id}/refresh-counts', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'refreshCounts'])
                ->name('refresh-counts');
            Route::post('{id}/archive', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'archive'])
                ->name('archive');
        });

        // Collections utilities (outside resourceful routes)
        Route::get('collections-seasons', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'seasons'])->name('collections.seasons');
        Route::get('collections-featured', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'featured'])->name('collections.featured');

        Route::get('collections/products/available', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'availableProducts'])->name('collections.products.available');
        Route::post('collections/products/bulk-move', [\Modules\Catalogue\Http\Controllers\Admin\CollectionController::class, 'bulkMoveProducts'])->name('collections.products.bulk-move');


        Route::prefix('products')->name('products.')->group(function () {
            // Statistics (doit être avant {id})
            Route::get('statistics', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'statistics'])->name('statistics');

            // Bulk operations
            Route::post('bulk/update', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'bulkUpdate'])->name('bulk.update');
            Route::post('bulk/destroy', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'bulkDestroy'])->name('bulk.destroy');

            // Export
            Route::post('export', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'export'])->name('export');

            // Routes spécifiques avec {id}
            Route::post('{id}/duplicate', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'duplicate'])->name('duplicate');
            Route::post('{id}/restore', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'restore'])->name('restore');
            Route::delete('{id}/force', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'forceDelete'])->name('force-delete');

            // Stock management
            Route::post('{id}/stock', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'updateStock'])->name('update-stock');
            Route::get('{id}/stock-history', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'stockHistory'])->name('stock-history');
            Route::post('{productId}/variations/{variationId}/stock', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'updateVariationStock'])->name('variations.update-stock');

            // Preorder management
            Route::post('{id}/enable-preorder', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'enablePreorder'])->name('enable-preorder');
            Route::post('{id}/disable-preorder', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'disablePreorder'])->name('disable-preorder');
            Route::get('{id}/preorder-info', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'preorderInfo'])->name('preorder-info');
            Route::get('{id}/authenticity/stats', [\Modules\Catalogue\Http\Controllers\Admin\ProductController::class, 'authenticityStats'])->name('authenticity.stats');
        });
        Route::apiResource('products', \Modules\Catalogue\Http\Controllers\Admin\ProductController::class);


        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('tree', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'tree'])->name('tree');
            Route::patch('{id}/move', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'move'])->name('move');
            Route::post('reorder', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'reorder'])->name('reorder');
            Route::post('{id}/restore', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'restore'])->name('restore');
            Route::get('statistics', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'statistics'])->name('statistics');
            Route::get('{id}/breadcrumb', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'breadcrumb'])->name('breadcrumb');
            Route::delete('{id}/force', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'forceDelete'])->name('force-delete');

            Route::prefix('bulk')->name('bulk.')->group(function () {
                Route::post('destroy', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'bulkDestroy'])->name('destroy');
                Route::post('restore', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'bulkRestore'])->name('restore');
                Route::post('force-delete', [\Modules\Catalogue\Http\Controllers\Admin\CategoryController::class, 'bulkForceDelete'])->name('force-delete');
            });
        });
        Route::apiResource('categories', \Modules\Catalogue\Http\Controllers\Admin\CategoryController::class);

        Route::post('/brands/{id}/restore', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'restore'])->name('brands.restore');
        Route::get('/brands/statistics', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'statistics'])->name('brands.statistics');
        Route::delete('/brands/{id}/force', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'forceDelete'])->name('brands.force-delete');

        Route::prefix('brands/bulk')->name('brands.bulk.')->group(function () {
            Route::post('destroy', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'bulkDestroy'])->name('destroy');
            Route::post('restore', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'bulkRestore'])->name('restore');
            Route::post('force-delete', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'bulkForceDelete'])->name('force-delete');
        });

        Route::post('/brands/{brand}', [\Modules\Catalogue\Http\Controllers\Admin\BrandController::class, 'update'])->name('brands.update-with-files');
        Route::apiResource('brands', \Modules\Catalogue\Http\Controllers\Admin\BrandController::class);

        Route::apiResource('attributes', \Modules\Catalogue\Http\Controllers\Admin\AttributeController::class);

        Route::prefix('products/{productId}/preorder')->name('products.preorder.')->group(function () {
            Route::post('/enable', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'enable'])->name('enable');
            Route::post('/disable', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'disable'])->name('disable');
        });
        Route::get('/preorders', [\Modules\Catalogue\Http\Controllers\Admin\PreorderController::class, 'index'])->name('preorders.index');

        // Order Management
        Route::apiResource('orders', \Modules\Order\Http\Controllers\Admin\OrderController::class);
        Route::get('/orders/{id}/items', [\Modules\Order\Http\Controllers\Admin\OrderController::class, 'items'])->name('orders.items');
        Route::put('/orders/{id}/status', [\Modules\Order\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status');

        // Customer Management
        Route::apiResource('customers', \Modules\Customer\Http\Controllers\Admin\CustomerController::class);

        // Customer status management
        Route::prefix('customers')->name('customers.')->group(function () {

            // Status management
            Route::patch('{id}/suspend', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'suspend'])->name('suspend');
            Route::patch('{id}/activate', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'activate'])->name('activate');
            Route::patch('{id}/deactivate', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'deactivate'])->name('deactivate');

            // Bulk status update
            Route::post('bulk/status', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkUpdateStatus'])->name('bulk.status');

            // Soft delete & restore
            Route::post('{id}/restore', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'restore'])->name('restore');
            Route::post('bulk/restore', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkRestore'])->name('bulk.restore');
            Route::post('bulk/destroy', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkDestroy'])->name('bulk.destroy');

            // Force delete
            Route::delete('{id}/force', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'forceDelete'])->name('force-delete');
            Route::post('bulk/force-delete', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'bulkForceDelete'])->name('bulk.force-delete');

            // Export
            Route::post('export', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'export'])->name('export');

            // Statistics
            Route::get('statistics', [\Modules\Customer\Http\Controllers\Admin\CustomerController::class, 'statistics'])->name('statistics');
        });

        // Promotion Management
        Route::get('/promotions/statistics', [\Modules\Promotion\Http\Controllers\Admin\PromotionController::class, 'statistics'])->name('promotions.statistics');
        Route::post('/promotions/bulk/destroy', [\Modules\Promotion\Http\Controllers\Admin\PromotionController::class, 'bulkDestroy'])->name('promotions.bulk.destroy');
        Route::post('/promotions/bulk/restore', [\Modules\Promotion\Http\Controllers\Admin\PromotionController::class, 'bulkRestore'])->name('promotions.bulk.restore');
        Route::post('/promotions/{id}/restore', [\Modules\Promotion\Http\Controllers\Admin\PromotionController::class, 'restore'])->name('promotions.restore');
        Route::apiResource('promotions', \Modules\Promotion\Http\Controllers\Admin\PromotionController::class);

        // Review Management
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('statistics', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'statistics'])->name('statistics');
            Route::post('bulk/approve', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'bulkApprove'])->name('bulk.approve');
            Route::post('bulk/reject', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'bulkReject'])->name('bulk.reject');
            Route::post('bulk/destroy', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'bulkDestroy'])->name('bulk.destroy');
            Route::post('{id}/restore', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'restore'])->name('restore');
        });
        Route::apiResource('reviews', \Modules\Reviews\Http\Controllers\Admin\ReviewController::class);
        Route::post('/reviews/{id}/approve', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{id}/reject', [\Modules\Reviews\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reviews.reject');

        // Shipping Management
        Route::apiResource('shipments', \Modules\Shipping\Http\Controllers\Admin\ShipmentController::class);
        Route::apiResource('shipping-methods', \Modules\Shipping\Http\Controllers\Admin\ShippingMethodController::class);

        // Inventory Management
        Route::prefix('inventory')->name('inventory.')->group(function () {

            // Stock queries
            Route::get('low-stock', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'lowStock'])->name('low-stock');
            Route::get('movements', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'movements'])->name('movements');
            Route::get('statistics', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'statistics'])->name('statistics');
            Route::get('out-of-stock', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'outOfStock'])->name('out-of-stock');

        // Stock adjustments
            Route::get('', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'index'])->name('index');
            Route::post('adjust', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('adjust');
            Route::post('bulk-adjust', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'bulk-adjust'])->name('bulk-adjust');

            // Export
            Route::post('export', [\Modules\Inventory\Http\Controllers\Admin\InventoryController::class, 'export'])->name('export');
        });

        // Dashboard & Analytics
        Route::get('/dashboard/stats', [\Modules\Core\Http\Controllers\DashboardController::class, 'stats'])->name('dashboard.stats');
    });
