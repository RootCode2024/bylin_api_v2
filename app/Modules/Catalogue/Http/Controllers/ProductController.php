<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Services\PreorderService;
use Modules\Core\Http\Controllers\ApiController;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Product Controller (Public)
 * 
 * Handles public product browsing and search
 */
class ProductController extends ApiController
{
    public function __construct(
        private PreorderService $preorderService
    ) {}

    /**
     * List products with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([
                'name',
                'status',
                'is_featured',
                AllowedFilter::exact('brand_id'),
                AllowedFilter::scope('price_between'),
            ])
            ->allowedSorts(['name', 'price', 'created_at', 'rating_average'])
            ->with(['brand', 'categories'])
            ->where('status', 'active')
            ->paginate($request->input('per_page', 15));

        return $this->paginatedResponse($products);
    }

    /**
     * Get single product details
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with([
            'brand',
            'categories',
            'variations' => fn($q) => $q->active(),
            'attributes.values'
        ])->findOrFail($id);

        // Increment views
        $product->increment('views_count');

        return $this->successResponse($product);
    }

    /**
     * Get preorder information for a product
     */
    public function preorderInfo(string $id): JsonResponse
    {
        $info = $this->preorderService->getPreorderInfo($id);
        return $this->successResponse($info);
    }
}
