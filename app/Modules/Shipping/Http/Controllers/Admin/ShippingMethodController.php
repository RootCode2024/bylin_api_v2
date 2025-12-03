<?php

declare(strict_types=1);

namespace Modules\Shipping\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;

class ShippingMethodController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Shipping methods list');
    }

    public function store(Request $request): JsonResponse
    {
        // Placeholder
        return $this->createdResponse([], 'Shipping method created');
    }

    public function update(string $id, Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Shipping method updated');
    }

    public function destroy(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse(null, 'Shipping method deleted');
    }
}
