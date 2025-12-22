<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Services\ProductService;
use Modules\Catalogue\Services\PreorderService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Http\Resources\ProductResource;
use Modules\Catalogue\Http\Requests\UpdateStockRequest;
use Modules\Catalogue\Http\Requests\StoreProductRequest;
use Modules\Catalogue\Http\Requests\UpdateProductRequest;

/**
 * Product Controller
 *
 * Handles all product CRUD operations, stock management, and preorder logic
 */
class ProductController extends ApiController
{
    public function __construct(
        private ProductService $productService,
        private PreorderService $preorderService
    ) {}

    /**
     * Display a listing of products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['brand', 'categories', 'variations'])
            ->withCount('variations');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('category_id')) {
            $query->inCategory($request->category_id);
        }

        if ($request->filled('is_featured')) {
            $query->featured();
        }

        if ($request->filled('in_stock')) {
            $query->inStock();
        }

        if ($request->filled('is_preorder')) {
            $query->preorder();
        }

        // Price range
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->priceBetween($request->min_price, $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return $this->successResponse(
            $products,
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return $this->createdResponse(
            $product,
            'Product created successfully'
        );
    }

    /**
     * Display the specified product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with([
            'brand',
            'categories',
            'variations',
            'attributes',
            'media'
        ])->findOrFail($id);

        return $this->successResponse(
            $product,
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product
     *
     * @param string $id
     * @param UpdateProductRequest $request
     * @return JsonResponse
     */
    public function update(string $id, UpdateProductRequest $request): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return $this->successResponse(
            $product,
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }

    /**
     * Restore a soft-deleted product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function restore(string $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return $this->successResponse(
            $product,
            'Product restored successfully'
        );
    }

    /**
     * Permanently delete a product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function forceDelete(string $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();

        return $this->successResponse(
            null,
            'Product permanently deleted'
        );
    }

    /**
     * Update product stock
     *
     * @param string $id
     * @param UpdateStockRequest $request
     * @return JsonResponse
     */
    public function updateStock(string $id, UpdateStockRequest $request): JsonResponse
    {
        Log::alert($request);
        $result = $this->productService->updateStock(
            productId: $id,
            quantity: $request->input('quantity'),
            operation: $request->input('operation', 'set'),
            reason: $request->input('reason'),
            notes: $request->input('notes')
        );

        if ($result['success']) {
            return $this->successResponse(
                $result['product'],
                'Stock mis à jour avec succès'
            );
        }

        return $this->errorResponse($result['message'], 400);
    }

    /**
     * Update variation stock
     */
    public function updateVariationStock(
        string $productId,
        string $variationId,
        UpdateStockRequest $request
    ): JsonResponse {
        $result = $this->productService->updateVariationStock(
            productId: $productId,
            variationId: $variationId,
            quantity: $request->input('quantity'),
            operation: $request->input('operation', 'set'),
            reason: $request->input('reason'),
            notes: $request->input('notes')
        );

        if ($result['success']) {
            return $this->successResponse(
                $result['variation'],
                'Variation stock updated successfully'
            );
        }

        return $this->errorResponse($result['message'], 400);
    }

    /**
     * Get stock history for a product
     */
    public function stockHistory(string $id, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $history = $this->productService->getStockHistory($id, $perPage);

        return $this->successResponse(
            $history,
            'Stock history retrieved successfully'
        );
    }

    /**
     * Enable preorder for a product
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function enablePreorder(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'available_date' => 'nullable|date|after:today',
            'limit' => 'nullable|integer|min:1',
            'message' => 'nullable|string|max:255',
            'terms' => 'nullable|string|max:1000',
        ]);

        $product = $this->preorderService->enablePreorder(
            productId: $id,
            availableDate: isset($validated['available_date'])
                ? Carbon::parse($validated['available_date'])
                : null,
            limit: $validated['limit'] ?? null,
            message: $validated['message'] ?? null,
            terms: $validated['terms'] ?? null
        );

        return $this->successResponse(
            $product,
            'Preorder enabled successfully'
        );
    }

    /**
     * Disable preorder for a product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function disablePreorder(string $id): JsonResponse
    {
        $product = $this->preorderService->disablePreorder($id, 'manual');

        return $this->successResponse(
            $product,
            'Preorder disabled successfully'
        );
    }

    /**
     * Get preorder information for a product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function preorderInfo(string $id): JsonResponse
    {
        $info = $this->preorderService->getPreorderInfo($id);

        return $this->successResponse(
            $info,
            'Preorder info retrieved successfully'
        );
    }

    /**
     * Duplicate a product
     *
     * @param string $id
     * @return JsonResponse
     */
    public function duplicate(string $id): JsonResponse
    {
        $product = $this->productService->duplicateProduct($id);

        return $this->createdResponse(
            $product,
            'Product duplicated successfully'
        );
    }

    /**
     * Bulk update products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,delete,feature,unfeature',
        ]);

        $count = $this->productService->bulkUpdate(
            $validated['product_ids'],
            $validated['action']
        );

        return $this->successResponse(
            ['updated_count' => $count],
            "{$count} products updated successfully"
        );
    }

    /**
     * Export products to CSV
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'brand_id',
            'category_id',
            'search'
        ]);

        $filePath = $this->productService->exportProducts($filters);

        return $this->successResponse(
            ['download_url' => $filePath],
            'Export completed successfully'
        );
    }

    /**
     * Get product statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('stock_quantity', 0)->count(),
            'low_stock' => Product::where('stock_quantity', '>', 0)
                ->whereRaw('stock_quantity <= low_stock_threshold')
                ->count(),
            'preorder_products' => Product::where('is_preorder_enabled', true)->count(),
            'featured_products' => Product::where('is_featured', true)->count(),
            'total_value' => Product::sum(DB::raw('price * stock_quantity')),
        ];

        return $this->successResponse($stats);
    }
}
