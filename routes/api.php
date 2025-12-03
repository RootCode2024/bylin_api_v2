<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Main API routes file - imports separated route files
*/

// Public routes
require __DIR__.'/api_public.php';

// Admin routes  
require __DIR__.'/api_admin.php';

// Customer routes
require __DIR__.'/api_customer.php';
