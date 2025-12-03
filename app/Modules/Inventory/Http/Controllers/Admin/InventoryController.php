<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;

class InventoryController extends ApiController
{
    public function lowStock(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Low stock items');
    }

    public function adjust(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse(null, 'Inventory adjusted');
    }

    public function movements(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Inventory movements');
    }
}
