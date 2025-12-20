<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\Attribute;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Category;
use Modules\Reviews\Models\Review;
use Modules\Catalogue\Models\Product;
use Modules\Customer\Models\Customer;
use Modules\Promotion\Models\Promotion;

class DashboardController extends ApiController
{
    public function stats(): JsonResponse
    {
        $stats = [
            'customers' => Customer::count(),
            'orders' => Order::whereIn('status', ['pending', 'processing'])->count(),
            'products' => Product::count(),
            'brands' => Brand::count(),
            'categories' => Category::count(),
            'attributes' => Attribute::count(),
            'promotions' => Promotion::count(),
            'reviews' => Review::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
