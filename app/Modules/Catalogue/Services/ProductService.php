<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Modules\Catalogue\Models\Product;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Models\StockMovement;
use Modules\Catalogue\Models\ProductVariation;

class ProductService extends BaseService
{
    public function __construct(
        private ?ProductAuthenticityService $authenticityService = null
    ) {}

    public function createProduct(array $data): Product
    {
        return $this->transaction(function () use ($data) {

            $categories = $data['categories'] ?? [];
            $variations = $data['variations'] ?? [];
            $images = $data['images'] ?? [];
            unset($data['categories'], $data['variations'], $data['images']);

            $product = Product::create($data);

            if (!empty($categories)) $product->categories()->attach($categories);

            if (!empty($variations) && $product->is_variable) $this->createVariations($product, $variations);

            if (!empty($images)) {
                foreach ($images as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }

            if (
                $this->authenticityService
                && !empty($data['requires_authenticity'])
                && $data['requires_authenticity']
            ) {
                $this->authenticityService->generateAuthenticityCode(
                    $product->id,
                    $data['authenticity_codes_count'] ?? 10
                );
            }

            $this->logInfo('Product created', ['product_id' => $product->id]);

            return $product->fresh(['brand', 'categories', 'variations', 'media']);
        });
    }

    public function updateProduct(string $id, array $data): Product
    {
        return $this->transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);

            $variationsData = $data['variations'] ?? null;

            $categories = $data['categories'] ?? null;
            $imagesToDelete = $data['images_to_delete'] ?? [];
            $newImages = $data['images'] ?? [];

            unset($data['categories'], $data['variations'], $data['images'], $data['images_to_delete']);

            $product->update($data);

            if ($categories !== null) $product->categories()->sync($categories);
            if ($product->is_variable && $variationsData !== null) $this->syncVariations($product, $variationsData);
            if (!empty($imagesToDelete)) $product->media()->whereIn('id', $imagesToDelete)->each->delete();

            if (!empty($newImages)) {
                foreach ($newImages as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }

            $this->logInfo('Product updated', ['product_id' => $product->id]);

            return $product->fresh(['brand', 'categories', 'variations', 'media']);
        });
    }

    protected function createVariations(Product $product, array $variationsData): void
    {
        foreach ($variationsData as $variationData) {
            $stockQuantity = $variationData['stock_quantity'] ?? 0;

            $product->variations()->create([
                'variation_name' => $variationData['variation_name'],
                'price' => $variationData['price'],
                'compare_price' => $variationData['compare_price'] ?? null,
                'cost_price' => $variationData['cost_price'] ?? null,
                'stock_quantity' => $stockQuantity,
                'stock_status' => $this->determineStockStatus($stockQuantity),
                'is_active' => $variationData['is_active'] ?? true,
                'attributes' => $variationData['attributes'] ?? [],
                'barcode' => $variationData['barcode'] ?? null,
                'sku' => $variationData['sku'] ?? $this->generateVariationSku($product, $variationData),
            ]);
        }
    }

    protected function syncVariations(Product $product, array $variationsData): void
    {
        $this->logInfo('Syncing variations', [
            'product_id' => $product->id,
            'variations_count' => count($variationsData),
        ]);

        $existingVariationIds = $product->variations()->pluck('id')->toArray();
        $processedIds = [];

        foreach ($variationsData as $index => $variationData) {
            $variationId = $variationData['id'] ?? null;

            $stockQuantity = (int) ($variationData['stock_quantity'] ?? 0);

            if ($variationId && in_array($variationId, $existingVariationIds)) {

                $variation = ProductVariation::find($variationId);

                if ($variation) {
                    $updateData = [
                        'variation_name' => $variationData['variation_name'],
                        'price' => (float) $variationData['price'],
                        'compare_price' => isset($variationData['compare_price']) ? (float) $variationData['compare_price'] : null,
                        'cost_price' => isset($variationData['cost_price']) ? (float) $variationData['cost_price'] : null,
                        'stock_quantity' => $stockQuantity,
                        'stock_status' => $this->determineStockStatus($stockQuantity),
                        'is_active' => (bool) ($variationData['is_active'] ?? true),
                        'attributes' => $variationData['attributes'] ?? [],
                        'barcode' => $variationData['barcode'] ?? null,
                    ];

                    if (!empty($variationData['sku']) && $variationData['sku'] !== $variation->sku) {
                        $updateData['sku'] = $variationData['sku'];
                    }

                    $variation->update($updateData);

                    $processedIds[] = $variationId;

                    $this->logInfo('Variation updated', [
                        'variation_id' => $variationId,
                        'variation_name' => $updateData['variation_name'],
                    ]);
                }
            } else {

                $newVariation = $product->variations()->create([
                    'variation_name' => $variationData['variation_name'],
                    'price' => (float) $variationData['price'],
                    'compare_price' => isset($variationData['compare_price']) ? (float) $variationData['compare_price'] : null,
                    'cost_price' => isset($variationData['cost_price']) ? (float) $variationData['cost_price'] : null,
                    'stock_quantity' => $stockQuantity,
                    'stock_status' => $this->determineStockStatus($stockQuantity),
                    'is_active' => (bool) ($variationData['is_active'] ?? true),
                    'attributes' => $variationData['attributes'] ?? [],
                    'barcode' => $variationData['barcode'] ?? null,
                    'sku' => $variationData['sku'] ?? $this->generateVariationSku($product, $variationData),
                ]);

                $processedIds[] = $newVariation->id;

                $this->logInfo('Variation created', [
                    'variation_id' => $newVariation->id,
                    'sku' => $newVariation->sku,
                ]);
            }
        }

        $idsToDelete = array_diff($existingVariationIds, $processedIds);

        if (!empty($idsToDelete)) {
            $this->logInfo('Deleting variations', [
                'ids_to_delete' => $idsToDelete,
            ]);

            ProductVariation::whereIn('id', $idsToDelete)->delete();
        }

        $this->logInfo('Variations sync completed', [
            'product_id' => $product->id,
            'processed_count' => count($processedIds),
            'deleted_count' => count($idsToDelete),
        ]);
    }

    protected function generateVariationSku(Product $product, array $variationData): string
    {
        $baseSku = $product->sku;
        $suffix = Str::upper(Str::random(4));

        if (!empty($variationData['attributes'])) {
            $attrString = implode('-', array_values($variationData['attributes']));
            $suffix = Str::slug($attrString) . '-' . $suffix;
        }

        $sku = "{$baseSku}-{$suffix}";

        $count = 1;
        $originalSku = $sku;

        while (ProductVariation::where('sku', $sku)->exists()) {
            $sku = "{$originalSku}-{$count}";
            $count++;
        }

        return $sku;
    }

    public function deleteProduct(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $product = Product::findOrFail($id);
            $product->delete();

            $this->logInfo('Product deleted', ['product_id' => $id]);

            return true;
        });
    }

    public function duplicateProduct(string $id): Product
    {
        return $this->transaction(function () use ($id) {
            $original = Product::with(['categories', 'variations'])->findOrFail($id);

            $data = $original->toArray();

            $data['name'] = $data['name'] . ' (Copy)';
            $data['slug'] = Str::slug($data['name'] . '-copy-' . Str::random(4));
            $data['sku'] = $data['sku'] . '-COPY-' . strtoupper(Str::random(4));
            $data['is_featured'] = false;

            unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

            $duplicate = Product::create($data);
            $duplicate->categories()->attach($original->categories->pluck('id'));

            foreach ($original->variations as $variation) {
                $varData = $variation->toArray();
                $varData['sku'] = $varData['sku'] . '-COPY';
                unset($varData['id'], $varData['product_id']);
                $duplicate->variations()->create($varData);
            }

            $this->logInfo('Product duplicated', [
                'original_id' => $id,
                'duplicate_id' => $duplicate->id,
            ]);

            return $duplicate->fresh();
        });
    }

    public function bulkUpdate(array $productIds, string $action): int
    {
        return $this->transaction(function () use ($productIds, $action) {
            $query = Product::whereIn('id', $productIds);

            $count = match ($action) {
                'activate' => $query->update(['status' => 'active']),
                'deactivate' => $query->update(['status' => 'inactive']),
                'delete' => $query->delete(),
                'feature' => $query->update(['is_featured' => true]),
                'unfeature' => $query->update(['is_featured' => false]),
                default => 0,
            };

            $this->logInfo('Bulk product update', [
                'action' => $action,
                'count' => $count,
            ]);

            return $count;
        });
    }

    public function exportProducts(array $filters = []): string
    {
        $this->logInfo('Products exported', ['filters' => $filters]);

        return '/exports/products-' . now()->format('d/m/Y') . '.csv';
    }

    private function determineStockStatus(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }

        return 'in_stock';
    }

    public function updateStock(
        string $productId,
        int $quantity,
        string $operation = 'set',
        ?string $reason = null,
        ?string $notes = null
    ): array {
        try {
            $product = Product::findOrFail($productId);

            if ($product->is_variable) {
                return [
                    'success' => false,
                    'message' => 'Impossible de mettre à jour directement le stock des produits variables. Veuillez plutôt mettre à jour les variantes.'
                ];
            }

            if (!$product->track_inventory) {
                return [
                    'success' => false,
                    'message' => 'Ce produit ne permet pas de suivre les stocks.'
                ];
            }

            $currentStock = $product->stock_quantity;

            $finalQuantity = match ($operation) {
                'set' => $quantity,
                'add' => $currentStock + $quantity,
                'sub' => $currentStock - $quantity,
                default => $currentStock
            };

            if ($finalQuantity < 0) {
                return [
                    'success' => false,
                    'message' => 'La quantité en stock ne peut pas être négative.'
                ];
            }

            $movementType = $this->determineMovementType($operation, $reason);
            $movementReason = $this->mapReasonToStockMovementReason($reason);

            $movement = StockMovement::createMovement([
                'product_id' => $productId,
                'variation_id' => null,
                'type' => $movementType,
                'reason' => $movementReason,
                'quantity' => $finalQuantity - $currentStock,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'product' => $product->fresh(),
                'movement' => $movement
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateVariationStock(
        string $productId,
        string $variationId,
        int $quantity,
        string $operation = 'set',
        ?string $reason = null,
        ?string $notes = null
    ): array {
        try {
            Product::findOrFail($productId);
            $variation = ProductVariation::where('product_id', $productId)->where('id', $variationId)->firstOrFail();

            $currentStock = $variation->stock_quantity;

            $finalQuantity = match ($operation) {
                'set' => $quantity,
                'add' => $currentStock + $quantity,
                'sub' => $currentStock - $quantity,
                default => $currentStock
            };

            if ($finalQuantity < 0) {
                return [
                    'success' => false,
                    'message' => 'Stock quantity cannot be negative'
                ];
            }

            $movementType = $this->determineMovementType($operation, $reason);
            $movementReason = $this->mapReasonToStockMovementReason($reason);

            $movement = StockMovement::createMovement([
                'product_id' => $productId,
                'variation_id' => $variationId,
                'type' => $movementType,
                'reason' => $movementReason,
                'quantity' => $finalQuantity - $currentStock,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'variation' => $variation->fresh(),
                'movement' => $movement
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getStockHistory(string $productId, int $perPage = 15)
    {
        return StockMovement::with(['creator', 'variation'])->forProduct($productId)->latest()->paginate($perPage);
    }

    public function reserveStock(string $productId, ?string $variationId, int $quantity, ?string $orderId = null): bool
    {
        try {
            if ($variationId) {
                StockMovement::recordSale(
                    productId: $productId,
                    quantity: $quantity,
                    variationId: $variationId,
                    orderId: $orderId
                );
            } else {
                StockMovement::recordSale(
                    productId: $productId,
                    quantity: $quantity,
                    variationId: null,
                    orderId: $orderId
                );
            }

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to reserve stock', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function releaseStock(string $productId, ?string $variationId, int $quantity, ?string $orderId = null): bool
    {
        try {
            if ($variationId) {
                StockMovement::recordReturn(
                    productId: $productId,
                    quantity: $quantity,
                    variationId: $variationId,
                    orderId: $orderId
                );
            } else {
                StockMovement::recordReturn(
                    productId: $productId,
                    quantity: $quantity,
                    variationId: null,
                    orderId: $orderId
                );
            }

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to release stock', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function determineMovementType(string $operation, ?string $reason): string
    {
        if ($operation === 'set') return StockMovement::TYPE_ADJUSTMENT;
        if ($reason === 'purchase' || $reason === 'return') return StockMovement::TYPE_IN;
        if ($reason === 'sale' || $reason === 'damage' || $reason === 'theft') return StockMovement::TYPE_OUT;

        return match ($operation) {
            'add' => StockMovement::TYPE_IN,
            'sub' => StockMovement::TYPE_OUT,
            default => StockMovement::TYPE_ADJUSTMENT
        };
    }

    private function mapReasonToStockMovementReason(?string $reason): string
    {
        if (!$reason) return StockMovement::REASON_ADJUSTMENT;

        return match ($reason) {
            'sale' => StockMovement::REASON_SALE,
            'theft' => StockMovement::REASON_LOST,
            'return' => StockMovement::REASON_RETURN,
            'damage' => StockMovement::REASON_DAMAGED,
            'purchase' => StockMovement::REASON_PURCHASE,
            'transfer' => StockMovement::REASON_ADJUSTMENT,
            'adjustment' => StockMovement::REASON_ADJUSTMENT,
            'initial_stock' => StockMovement::REASON_INITIAL_STOCK,
            default => StockMovement::REASON_ADJUSTMENT
        };
    }
}
