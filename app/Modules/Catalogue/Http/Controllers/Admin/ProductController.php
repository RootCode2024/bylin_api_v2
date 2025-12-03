<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Services\ProductService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Models\Product;

class ProductController extends ApiController
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($products);
    }

    public function store(Request $request): JsonResponse
    {
        // Validation would typically be in a FormRequest
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product = $this->productService->createProduct($request->all());

        return $this->createdResponse($product, 'Product created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['brand', 'categories', 'variations', 'attributes'])->findOrFail($id);
        return $this->successResponse($product);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->all());
        return $this->successResponse($product, 'Product updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        return $this->successResponse(null, 'Product deleted successfully');
    }
}
