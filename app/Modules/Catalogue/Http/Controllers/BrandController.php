<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\Brand;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Brand Controller (Public)
 */
class BrandController extends ApiController
{
    /**
     * List all active brands
     */
    public function index(): JsonResponse
    {
        $brands = Brand::active()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return $this->successResponse($brands);
    }
}
