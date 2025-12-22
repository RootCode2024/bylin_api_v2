<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Product;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Models\StockMovement;
use Modules\Catalogue\Models\ProductVariation;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryService extends BaseService
{
    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $threshold = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['brand', 'categories'])
            ->where('track_inventory', true)
            ->where('is_variable', false)
            ->where(function ($q) use ($threshold) {
                if ($threshold) {
                    $q->where('stock_quantity', '<=', $threshold);
                } else {
                    $q->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                }
            })
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems(int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['brand', 'categories'])
            ->where('track_inventory', true)
            ->where('stock_quantity', '<=', 0)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get inventory movements
     */
    public function getMovements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockMovement::with([
            'product',
            'variation',
            'creator' // Using 'creator' relationship from your StockMovement model
        ])->latest();

        if (!empty($filters['product_id'])) {
            $query->forProduct($filters['product_id']);
        }

        if (!empty($filters['variation_id'])) {
            $query->forVariation($filters['variation_id']);
        }

        if (!empty($filters['type'])) {
            $query->type($filters['type']);
        }

        if (!empty($filters['reason'])) {
            $query->reason($filters['reason']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('created_by', $filters['user_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_products' => Product::where('track_inventory', true)->count(),
            'in_stock' => Product::where('track_inventory', true)
                ->where('stock_quantity', '>', 0)
                ->count(),
            'out_of_stock' => Product::where('track_inventory', true)
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'low_stock' => Product::where('track_inventory', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->where('stock_quantity', '>', 0)
                ->count(),
            'total_stock_value' => $this->calculateTotalStockValue(),
            'recent_movements_count' => StockMovement::whereDate('created_at', '>=', now()->subDays(7))
                ->count(),
            'movements_by_type' => [
                'in' => StockMovement::stockIn()->whereDate('created_at', '>=', now()->subDays(30))->count(),
                'out' => StockMovement::stockOut()->whereDate('created_at', '>=', now()->subDays(30))->count(),
            ]
        ];
    }

    /**
     * Adjust stock using the existing StockMovement::createMovement method
     */
    public function adjustStock(
        ?string $productId,
        ?string $variationId,
        int $quantity,
        string $operation = 'set',
        ?string $reason = null,
        ?string $notes = null
    ): array {
        try {
            // Validate that we have at least one ID
            if (!$productId && !$variationId) {
                return [
                    'success' => false,
                    'message' => 'Either product_id or variation_id is required'
                ];
            }

            // If we only have variation_id, get the product_id
            if (!$productId && $variationId) {
                $variation = ProductVariation::findOrFail($variationId);
                $productId = $variation->product_id;
            }

            // Get current stock
            if ($variationId) {
                $variation = ProductVariation::findOrFail($variationId);
                $currentStock = $variation->stock_quantity;
            } else {
                $product = Product::findOrFail($productId);

                if ($product->is_variable) {
                    return [
                        'success' => false,
                        'message' => 'Cannot adjust stock for variable products directly. Update variations instead.'
                    ];
                }

                $currentStock = $product->stock_quantity;
            }

            // Calculate final quantity based on operation
            $finalQuantity = match ($operation) {
                'set' => $quantity,
                'add' => $currentStock + $quantity,
                'sub' => $currentStock - $quantity,
                default => $currentStock
            };

            // Prevent negative stock
            if ($finalQuantity < 0) {
                return [
                    'success' => false,
                    'message' => 'Stock quantity cannot be negative'
                ];
            }

            // Determine movement type based on operation and reason
            $movementType = $this->determineMovementType($operation, $reason, $currentStock, $finalQuantity);
            $movementReason = $this->mapReasonToStockMovementReason($reason);

            // Use the existing StockMovement::createMovement method
            $movement = StockMovement::createMovement([
                'product_id' => $productId,
                'variation_id' => $variationId,
                'type' => $movementType,
                'reason' => $movementReason,
                'quantity' => $finalQuantity - $currentStock, // The change amount
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Get the updated entity
            if ($variationId) {
                $data = ProductVariation::findOrFail($variationId);
            } else {
                $data = Product::findOrFail($productId);
            }

            return [
                'success' => true,
                'data' => $data,
                'movement' => $movement
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk adjust stock
     */
    public function bulkAdjustStock(array $adjustments): array
    {
        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'errors' => []
        ];

        foreach ($adjustments as $index => $adjustment) {
            try {
                $result = $this->adjustStock(
                    productId: $adjustment['product_id'] ?? null,
                    variationId: $adjustment['variation_id'] ?? null,
                    quantity: $adjustment['quantity'],
                    operation: $adjustment['operation'],
                    reason: $adjustment['reason'] ?? null,
                    notes: $adjustment['notes'] ?? null
                );

                if ($result['success']) {
                    $results['success_count']++;
                } else {
                    $results['failed_count']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'message' => $result['message']
                    ];
                }
            } catch (\Exception $e) {
                $results['failed_count']++;
                $results['errors'][] = [
                    'index' => $index,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Export inventory data
     */
    public function export(array $filters = [], string $format = 'csv'): string
    {
        $query = Product::with(['brand', 'categories'])
            ->where('track_inventory', true);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (!empty($filters['low_stock_only'])) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        $products = $query->get();

        // Generate file
        $fileName = 'inventory_' . now()->format('Y-m-d_His') . '.' . $format;

        // Ensure directory exists
        $exportDir = storage_path('app/public/exports');
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filePath = $exportDir . '/' . $fileName;

        if ($format === 'csv') {
            $this->generateCsv($products, $filePath);
        }

        return 'storage/exports/' . $fileName;
    }

    /**
     * Determine movement type based on operation and reason
     */
    private function determineMovementType(string $operation, ?string $reason, int $oldQty, int $newQty): string
    {
        // If explicit reason is provided
        if ($reason === 'sale' || $reason === 'damage' || $reason === 'theft') {
            return StockMovement::TYPE_OUT;
        }

        if ($reason === 'purchase' || $reason === 'return') {
            return StockMovement::TYPE_IN;
        }

        // Otherwise, determine by operation
        if ($operation === 'set') {
            return StockMovement::TYPE_ADJUSTMENT;
        }

        return match ($operation) {
            'add' => StockMovement::TYPE_IN,
            'sub' => StockMovement::TYPE_OUT,
            default => StockMovement::TYPE_ADJUSTMENT
        };
    }

    /**
     * Map API reason to StockMovement reason constants
     */
    private function mapReasonToStockMovementReason(?string $reason): string
    {
        if (!$reason) {
            return StockMovement::REASON_ADJUSTMENT;
        }

        return match ($reason) {
            'initial_stock' => StockMovement::REASON_INITIAL_STOCK,
            'purchase' => StockMovement::REASON_PURCHASE,
            'sale' => StockMovement::REASON_SALE,
            'return' => StockMovement::REASON_RETURN,
            'damage' => StockMovement::REASON_DAMAGED,
            'theft' => StockMovement::REASON_LOST,
            'adjustment' => StockMovement::REASON_ADJUSTMENT,
            'transfer' => StockMovement::REASON_ADJUSTMENT,
            default => StockMovement::REASON_ADJUSTMENT
        };
    }

    /**
     * Calculate total stock value
     */
    private function calculateTotalStockValue(): float
    {
        $total = Product::where('track_inventory', true)
            ->selectRaw('SUM(stock_quantity * price) as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    /**
     * Generate CSV file
     */
    private function generateCsv($products, string $filePath): void
    {
        $fp = fopen($filePath, 'w');

        // Headers
        fputcsv($fp, [
            'SKU',
            'Name',
            'Brand',
            'Stock Quantity',
            'Low Stock Threshold',
            'Status',
            'Price',
            'Last Updated'
        ]);

        // Data
        foreach ($products as $product) {
            fputcsv($fp, [
                $product->sku,
                $product->name,
                $product->brand?->name ?? 'N/A',
                $product->stock_quantity,
                $product->low_stock_threshold,
                $product->stock_quantity > 0 ? 'In Stock' : 'Out of Stock',
                number_format($product->price / 100, 2), // Assuming price in cents
                $product->updated_at->format('Y-m-d H:i:s')
            ]);
        }

        fclose($fp);
    }
}
