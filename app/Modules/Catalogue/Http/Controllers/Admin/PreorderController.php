<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Services\PreorderService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Models\Product;

class PreorderController extends ApiController
{
    public function __construct(
        private PreorderService $preorderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        // List products with preorder enabled
        $products = Product::query()
            ->where('is_preorder_enabled', true)
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($products);
    }

    public function enable(string $productId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'available_date' => 'nullable|date',
            'limit' => 'nullable|integer',
        ]);

        $product = $this->preorderService->enablePreorder(
            $productId,
            $validated['available_date'] ?? null,
            $validated['limit'] ?? null
        );

        return $this->successResponse($product, 'Preorder enabled');
    }

    public function disable(string $productId): JsonResponse
    {
        $product = $this->preorderService->disablePreorder($productId);
        return $this->successResponse($product, 'Preorder disabled');
    }
}
