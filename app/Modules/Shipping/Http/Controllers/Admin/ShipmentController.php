<?php

declare(strict_types=1);

namespace Modules\Shipping\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;

class ShipmentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Shipments list');
    }

    public function store(Request $request): JsonResponse
    {
        // Placeholder
        return $this->createdResponse([], 'Shipment created');
    }

    public function show(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Shipment details');
    }
}
