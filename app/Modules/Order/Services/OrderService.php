<?php

declare(strict_types=1);

namespace Modules\Order\Services;

use Modules\Core\Services\BaseService;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStatusHistory;

class OrderService extends BaseService
{
    /**
     * Get order by ID
     */
    public function getOrder(string $id): Order
    {
        return Order::with(['items.product', 'items.variation', 'statusHistories'])
            ->findOrFail($id);
    }

    /**
     * Update order status
     */
    public function updateStatus(Order $order, string $status, ?string $note = null, ?string $userId = null): Order
    {
        if ($order->status === $status) {
            return $order;
        }

        $order->update(['status' => $status]);

        // Record history
        OrderStatusHistory::createHistory($order->id, $status, $note, $userId);

        // Trigger notifications based on status
        // event(new OrderStatusChanged($order));

        return $order;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Order $order, string $status): Order
    {
        $order->update(['payment_status' => $status]);
        
        return $order;
    }

    /**
     * Cancel order
     */
    public function cancelOrder(Order $order, ?string $reason = null, ?string $userId = null): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in its current state.');
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);
        
        OrderStatusHistory::createHistory(
            $order->id, 
            Order::STATUS_CANCELLED, 
            $reason ?? 'Order cancelled by user', 
            $userId
        );

        // Release stock logic should be called here or via event listener
        // app(InventoryService::class)->releaseOrderStock($order);

        return $order;
    }
}
