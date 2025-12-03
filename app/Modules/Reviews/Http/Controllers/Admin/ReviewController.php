<?php

declare(strict_types=1);

namespace Modules\Reviews\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
// use Modules\Reviews\Models\Review;

class ReviewController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Reviews list');
    }

    public function show(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse([], 'Review details');
    }

    public function approve(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse(null, 'Review approved');
    }

    public function reject(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse(null, 'Review rejected');
    }

    public function destroy(string $id): JsonResponse
    {
        // Placeholder
        return $this->successResponse(null, 'Review deleted');
    }
}
