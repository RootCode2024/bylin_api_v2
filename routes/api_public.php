<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
| Routes accessible without authentication
*/

Route::prefix('v1')->group(function () {
    
    // QR Code Verification (anti-counterfeit)
    Route::post('/verify-qr/{qrCode}', [\Modules\Catalogue\Http\Controllers\AuthenticityController::class, 'verify'])
        ->middleware('throttle:30,1')
        ->name('api.verify-qr');

    // Global Content
    Route::get('/content/home', [\Modules\Core\Http\Controllers\HomeContentController::class, 'index'])
        ->name('content.home');
    
    // Authentication - Stricter rate limiting
    Route::prefix('auth')->name('api.auth.')->group(function () {
        // Admin auth (stateless tokens)
        Route::post('/admin/login', [\Modules\User\Http\Controllers\AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('admin.login');
        Route::post('/admin/register', [\Modules\User\Http\Controllers\AuthController::class, 'register'])
            ->middleware('throttle:3,1')
            ->name('admin.register');
        
        // Customer auth (stateful - HTTP-only cookies with sessions)
        Route::middleware('web')->group(function () {
            Route::post('/customer/register', [\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'register'])
                ->middleware('throttle:5,1')
                ->name('customer.register');
            Route::post('/customer/login', [\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'login'])
                ->middleware('throttle:10,1')
                ->name('customer.login');
            
            // Google OAuth
            Route::get('/customer/google/redirect', [\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'googleRedirect'])
                ->middleware('throttle:10,1')
                ->name('customer.google.redirect');
            Route::get('/customer/google/callback', [\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'googleCallback'])
                ->middleware('throttle:10,1')
                ->name('customer.google.callback');
        });
    });

    
    // Public Catalog
    Route::prefix('catalog')->name('api.catalog.')->middleware('throttle:120,1')->group(function () {
        // Products
        Route::get('/products', [\Modules\Catalogue\Http\Controllers\ProductController::class, 'index'])
            ->name('products.index');
        Route::get('/products/{id}', [\Modules\Catalogue\Http\Controllers\ProductController::class, 'show'])
            ->name('products.show');
        Route::get('/products/{id}/preorder-info', [\Modules\Catalogue\Http\Controllers\ProductController::class, 'preorderInfo'])
            ->name('products.preorder-info');
        
        // Categories
        Route::get('/categories', [\Modules\Catalogue\Http\Controllers\CategoryController::class, 'index'])
            ->name('categories.index');
        Route::get('/categories/{id}/products', [\Modules\Catalogue\Http\Controllers\CategoryController::class, 'products'])
            ->name('categories.products');
        
        // Brands
        Route::get('/brands', [\Modules\Catalogue\Http\Controllers\BrandController::class, 'index'])
            ->name('brands.index');
    });
    
    // Gift Carts (public access via token)
    Route::prefix('gift-carts')->name('api.gift-carts.')->middleware('throttle:60,1')->group(function () {
        Route::get('/{token}', [\Modules\Cart\Http\Controllers\GiftCartController::class, 'show'])
            ->name('show');
        Route::post('/{token}/contribute', [\Modules\Cart\Http\Controllers\GiftCartController::class, 'contribute'])
            ->name('contribute');
        Route::get('/{token}/contributions', [\Modules\Cart\Http\Controllers\GiftCartController::class, 'contributions'])
            ->name('contributions');
    });
    
    // Payment Webhooks (signature verification in middleware)
    Route::prefix('webhooks')->name('api.webhooks.')->middleware('throttle:60,1')->group(function () {
        Route::post('/fedapay', [\Modules\Payment\Http\Controllers\PaymentWebhookController::class, 'fedapay'])
            ->middleware('fedapay.signature')
            ->name('fedapay');
    });
});
