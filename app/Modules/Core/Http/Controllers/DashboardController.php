<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Modules\Reviews\Models\Review;
use Modules\Catalogue\Models\Product;
use Modules\Customer\Models\Customer;

class DashboardController extends ApiController
{
    public function stats(): JsonResponse
    {
        $stats = [
            'customers' => Customer::count(),
            'orders' => Order::whereIn('status', ['pending', 'processing'])->count(),
            'products' => Product::count(),
            'reviews' => Review::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
