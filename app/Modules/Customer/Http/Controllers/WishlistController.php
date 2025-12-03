<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Customer\Models\Wishlist;

class WishlistController extends ApiController
{
    /**
     * Get customer's wishlist
     */
    public function index(): JsonResponse
    {
        $customerId = auth()->id();

        $wishlist = Wishlist::with(['product.brand', 'product.category'])
            ->forCustomer($customerId)
            ->latest()
            ->get();

        return $this->successResponse($wishlist);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $customerId = auth()->id();

        // Check if already in wishlist
        if (Wishlist::hasProduct($customerId, $validated['product_id'])) {
            return $this->errorResponse('Product already in wishlist', 409);
        }

        $wishlistItem = Wishlist::addProduct(
            $customerId,
            $validated['product_id'],
            $validated['notes'] ?? null
        );

        $wishlistItem->load('product');

        return $this->successResponse($wishlistItem, 'Product added to wishlist', 201);
    }

    /**
     * Remove product from wishlist
     */
    public function remove(string $productId): JsonResponse
    {
        $customerId = auth()->id();

        $deleted = Wishlist::removeProduct($customerId, $productId);

        if (!$deleted) {
            return $this->errorResponse('Product not found in wishlist', 404);
        }

        return $this->successResponse(null, 'Product removed from wishlist');
    }

    /**
     * Check if product is in wishlist
     */
    public function check(string $productId): JsonResponse
    {
        $customerId = auth()->id();

        $inWishlist = Wishlist::hasProduct($customerId, $productId);

        return $this->successResponse(['in_wishlist' => $inWishlist]);
    }

    /**
     * Clear entire wishlist
     */
    public function clear(): JsonResponse
    {
        $customerId = auth()->id();

        $count = Wishlist::forCustomer($customerId)->delete();

        return $this->successResponse(
            ['count' => $count],
            'Wishlist cleared'
        );
    }
}
