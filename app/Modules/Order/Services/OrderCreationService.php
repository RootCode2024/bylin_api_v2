<?php

declare(strict_types=1);

namespace Modules\Order\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\CartService;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Models\OrderStatusHistory;
use Modules\Promotion\Services\PromotionService;

class OrderCreationService extends BaseService
{
    protected CartService $cartService;
    protected InventoryService $inventoryService;
    protected PromotionService $promotionService;

    public function __construct(
        CartService $cartService,
        InventoryService $inventoryService,
        PromotionService $promotionService
    ) {
        $this->cartService = $cartService;
        $this->inventoryService = $inventoryService;
        $this->promotionService = $promotionService;
    }

    /**
     * Create order from cart
     */
    public function createOrderFromCart(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            // 1. Validate cart
            if ($cart->items->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // 2. Validate stock for all items
            foreach ($cart->items as $item) {
                if (!$this->inventoryService->checkStock($item->product_id, $item->quantity, $item->variation_id)) {
                    throw new \Modules\Core\Exceptions\OutOfStockException("Insufficient stock for item: {$item->product->name}");
                }
            }

            // 3. Create Order
            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'payment_method' => $data['payment_method'] ?? null,
                'customer_email' => $data['customer_email'], // Snapshot email
                'customer_phone' => $data['customer_phone'], // Snapshot phone
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'subtotal' => $cart->subtotal,
                'discount_amount' => $cart->discount_amount,
                'tax_amount' => $cart->tax_amount,
                'shipping_amount' => $cart->shipping_amount,
                'total' => $cart->total,
                'coupon_code' => $cart->coupon_code,
                'customer_note' => $data['customer_note'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // 4. Create Order Items and Reserve Stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->variation ? $item->variation->sku : $item->product->sku,
                    'variation_name' => $item->variation ? $item->variation->variation_name : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'discount_amount' => 0, // Item level discount logic if needed
                    'total' => $item->subtotal,
                    'options' => $item->options,
                ]);

                // Reserve stock
                $this->inventoryService->reserveStock(
                    $item->product_id,
                    $item->quantity,
                    $item->variation_id,
                    $order->id
                );
            }

            // 5. Record Promotion Usage if applicable
            if ($cart->coupon_code) {
                $promotion = $this->promotionService->validateCoupon($cart->coupon_code, $cart);
                $this->promotionService->recordUsage(
                    $promotion,
                    $order->id,
                    $cart->customer_id,
                    $cart->discount_amount
                );
            }

            // 6. Record Initial Status History
            OrderStatusHistory::createHistory($order->id, Order::STATUS_PENDING, 'Order created');

            // 7. Clear Cart
            $this->cartService->clearCart($cart);

            return $order;
        });
    }
}
