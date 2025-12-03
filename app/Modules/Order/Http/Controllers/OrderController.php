<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Services\CartService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderCreationService;
use Modules\Order\Services\OrderService;

class OrderController extends ApiController
{
    protected OrderService $orderService;
    protected OrderCreationService $orderCreationService;
    protected CartService $cartService;

    public function __construct(
        OrderService $orderService,
        OrderCreationService $orderCreationService,
        CartService $cartService
    ) {
        $this->orderService = $orderService;
        $this->orderCreationService = $orderCreationService;
        $this->cartService = $cartService;
    }

    /**
     * Get customer orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items.product', 'statusHistories'])
            ->where('customer_id', auth()->id())
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($orders);
    }

    /**
     * Get order details
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['items.product', 'items.variation', 'statusHistories', 'payment', 'shipment'])
            ->where('customer_id', auth()->id())
            ->findOrFail($id);
            
        return $this->successResponse($order);
    }

    /**
     * Create new order from cart
     */
    public function create(\Modules\Order\Http\Requests\StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customerId = auth()->id();
        $cart = $this->cartService->getCart($customerId);

        if ($cart->isEmpty()) {
            return $this->errorResponse('Cart is empty', 400);
        }

        try {
            $order = $this->orderCreationService->createOrderFromCart($cart, $validated);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($order, 'Order created successfully', 201);
    }

    /**
     * Cancel order
     */
    public function cancel(string $id, \Modules\Order\Http\Requests\CancelOrderRequest $request): JsonResponse
    {
        $order = Order::where('customer_id', auth()->id())
            ->findOrFail($id);

        try {
            $order = $this->orderService->cancelOrder($order, $request->reason, auth()->id());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($order, 'Order cancelled successfully');
    }
}
