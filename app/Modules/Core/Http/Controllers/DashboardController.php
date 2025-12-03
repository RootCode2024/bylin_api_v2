<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function stats(Request $request): JsonResponse
    {
        // Placeholder stats
        return $this->successResponse([
            'total_orders' => 150,
            'total_revenue' => 15000000,
            'new_customers' => 12,
            'low_stock_products' => 5,
        ], 'Dashboard stats');
    }
}
