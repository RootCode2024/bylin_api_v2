<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\Category;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Category Controller (Public)
 */
class CategoryController extends ApiController
{
    /**
     * List all active categories with hierarchy
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('children')
            ->active()
            ->root()
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse($categories);
    }

    /**
     * Get products in a category
     */
    public function products(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        
        $products = $category->products()
            ->with(['brand', 'categories'])
            ->where('status', 'active')
            ->paginate(15);

        return $this->paginatedResponse($products);
    }
}
