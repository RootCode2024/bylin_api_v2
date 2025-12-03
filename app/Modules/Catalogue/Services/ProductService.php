<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Services\BaseService;

/**
 * Product Service
 * 
 * Handles complex product logic including variations, stock, and media
 */
class ProductService extends BaseService
{
    public function __construct(
        private PreorderService $preorderService,
        private ProductAuthenticityService $authenticityService
    ) {}

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        return $this->transaction(function () use ($data) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Create main product
            $product = Product::create($data);

            // Attach categories
            if (!empty($data['categories'])) {
                $product->categories()->attach($data['categories']);
            }

            // Handle variations
            if (!empty($data['variations'])) {
                foreach ($data['variations'] as $variationData) {
                    $this->createVariation($product, $variationData);
                }
            }

            // Handle media
            if (isset($data['images'])) {
                foreach ($data['images'] as $image) {
                    $product->addMedia($image)->toMediaCollection('products');
                }
            }

            // Handle Authenticity (Bylin Brand)
            if (!empty($data['requires_authenticity']) && $data['requires_authenticity']) {
                $this->authenticityService->generateAuthenticityCode(
                    $product->id,
                    $data['authenticity_codes_count'] ?? 10
                );
            }

            $this->logInfo('Product created', ['product_id' => $product->id]);

            return $product;
        });
    }

    /**
     * Update a product
     */
    public function updateProduct(string $id, array $data): Product
    {
        return $this->transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Check stock changes for preorder logic
            if (isset($data['stock_quantity'])) {
                $this->preorderService->updateStockAndCheckPreorder($id, (int)$data['stock_quantity']);
            }

            $product->update($data);

            if (isset($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }

            // Handle variations update/create
            if (isset($data['variations'])) {
                // Logic to sync variations...
            }

            $this->logInfo('Product updated', ['product_id' => $product->id]);

            return $product;
        });
    }

    /**
     * Create a product variation
     */
    public function createVariation(Product $product, array $data): ProductVariation
    {
        $variation = $product->variations()->create([
            'sku' => $data['sku'] ?? $product->sku . '-' . Str::random(4),
            'price' => $data['price'] ?? $product->price,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'attributes' => $data['attributes'] ?? [], // JSON of attribute values
        ]);

        return $variation;
    }

    /**
     * Delete a product
     */
    public function deleteProduct(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $product = Product::findOrFail($id);
            $product->delete();
            return true;
        });
    }
}
