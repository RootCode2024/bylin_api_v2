<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (for CSRF cookie)
|--------------------------------------------------------------------------
*/

// CSRF cookie endpoint for Nuxt.js
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

Route::get('/test-redis', function () {
    try {
        Redis::set('mon_test', 'Succès : Redis est connecté sur WSL !');
        return Redis::get('mon_test');
    } catch (\Exception $e) {
        return "Erreur : " . $e->getMessage();
    }
});
