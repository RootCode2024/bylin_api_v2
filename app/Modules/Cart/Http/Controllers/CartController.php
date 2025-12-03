<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Services\CartService;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Cart Controller
 * 
 * Handles shopping cart operations
 */
class CartController extends ApiController
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get current user's or session's cart
     */
    public function show(Request $request): JsonResponse
    {
        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID'); // Or from cookie

        if (!$customerId && !$sessionId) {
            // Generate a session ID if none provided for guest
            $sessionId = (string) \Illuminate\Support\Str::uuid();
        }

        $cart = $this->cartService->getCart($customerId, $sessionId);

        // Return session ID in header if it was generated
        $response = $this->successResponse($cart);
        if (!$customerId && $sessionId) {
            $response->headers->set('X-Session-ID', $sessionId);
        }

        return $response;
    }

    /**
     * Add item to cart
     */
    public function addItem(\Modules\Cart\Http\Requests\AddToCartRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);

        try {
            $cart = $this->cartService->addItem($cart, $validated);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($cart, 'Item added to cart');
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(string $itemId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);

        // Verify item belongs to cart
        if (!$cart->items()->where('id', $itemId)->exists()) {
            return $this->errorResponse('Item not found in cart', 404);
        }

        try {
            $cart = $this->cartService->updateItem($cart, $itemId, $validated['quantity']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($cart, 'Cart updated');
    }

    /**
     * Remove item from cart
     */
    public function removeItem(string $itemId, Request $request): JsonResponse
    {
        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);

        // Verify item belongs to cart
        if (!$cart->items()->where('id', $itemId)->exists()) {
            return $this->errorResponse('Item not found in cart', 404);
        }

        $cart = $this->cartService->removeItem($cart, $itemId);

        return $this->successResponse($cart, 'Item removed from cart');
    }

    /**
     * Clear cart
     */
    public function clear(Request $request): JsonResponse
    {
        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);
        
        $this->cartService->clearCart($cart);

        return $this->successResponse(null, 'Cart cleared');
    }

    /**
     * Apply coupon
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);

        try {
            $cart = $this->cartService->applyCoupon($cart, $validated['coupon_code']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($cart, 'Coupon applied');
    }

    /**
     * Remove coupon
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $customerId = $request->user()?->id;
        $sessionId = $request->header('X-Session-ID');

        $cart = $this->cartService->getCart($customerId, $sessionId);

        // First recalculate the cart to get correct totals
        $this->cartService->recalculateCart($cart);
        
        // Then update coupon fields
        $cart->update([
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        return $this->successResponse($cart, 'Coupon removed');
    }
}
