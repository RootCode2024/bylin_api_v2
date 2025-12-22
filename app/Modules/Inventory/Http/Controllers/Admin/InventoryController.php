<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Http\Requests\AdjustStockRequest;
use Modules\Inventory\Http\Requests\BulkAdjustStockRequest;

class InventoryController extends ApiController
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->input('threshold');
        $perPage = $request->input('per_page', 15);

        $items = $this->inventoryService->getLowStockItems($threshold, $perPage);

        return $this->successResponse($items, 'Low stock items retrieved successfully');
    }

    /**
     * Get out of stock items
     */
    public function outOfStock(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $items = $this->inventoryService->getOutOfStockItems($perPage);

        return $this->successResponse($items, 'Out of stock items retrieved successfully');
    }

    /**
     * Get inventory movements/history
     */
    public function movements(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_id',
            'variation_id',
            'type',
            'date_from',
            'date_to',
            'user_id'
        ]);
        $perPage = $request->input('per_page', 15);

        $movements = $this->inventoryService->getMovements($filters, $perPage);

        return $this->successResponse($movements, 'Inventory movements retrieved successfully');
    }

    /**
     * Get inventory statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->inventoryService->getStatistics();

        return $this->successResponse($stats, 'Inventory statistics retrieved successfully');
    }

    /**
     * Adjust stock for a product or variation
     */
    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $result = $this->inventoryService->adjustStock(
            productId: $request->input('product_id'),
            variationId: $request->input('variation_id'),
            quantity: $request->input('quantity'),
            operation: $request->input('operation', 'set'),
            reason: $request->input('reason'),
            notes: $request->input('notes')
        );

        if ($result['success']) {
            return $this->successResponse(
                $result['data'],
                'Stock adjusted successfully'
            );
        }

        return $this->errorResponse($result['message'], 400);
    }

    /**
     * Bulk adjust stock for multiple items
     */
    public function bulkAdjust(BulkAdjustStockRequest $request): JsonResponse
    {
        $result = $this->inventoryService->bulkAdjustStock(
            $request->input('adjustments')
        );

        return $this->successResponse(
            $result,
            "Successfully adjusted {$result['success_count']} items"
        );
    }

    /**
     * Export inventory data
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'brand_id',
            'category_id',
            'low_stock_only'
        ]);
        $format = $request->input('format', 'csv');

        $filePath = $this->inventoryService->export($filters, $format);

        return $this->successResponse([
            'file_url' => url($filePath),
            'expires_at' => now()->addHours(24)->toIso8601String()
        ], 'Export generated successfully');
    }
}
