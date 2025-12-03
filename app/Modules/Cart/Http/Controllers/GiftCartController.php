<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Services\GiftCartService;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Gift Cart Controller
 */
class GiftCartController extends ApiController
{
    public function __construct(
        private GiftCartService $giftCartService
    ) {}

    /**
     * Convert regular cart to gift cart
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|uuid|exists:carts,id',
            'message' => 'nullable|string|max:500',
            'expiration_days' => 'nullable|integer|min:1|max:90',
        ]);

        $giftCart = $this->giftCartService->convertToGiftCart(
            $validated['cart_id'],
            $request->user()->id,
            $validated['message'] ?? null,
            $validated['expiration_days'] ?? null
        );

        $link = $this->giftCartService->getGiftCartLink($giftCart->gift_cart_token);

        return $this->successResponse([
            'gift_cart' => $giftCart,
            'share_link' => $link,
        ], 'Gift cart created successfully');
    }

    /**
     * Get gift cart by token (public)
     */
    public function show(string $token): JsonResponse
    {
        $giftCart = $this->giftCartService->getByToken($token);
        return $this->successResponse($giftCart);
    }

    /**
     * Add contribution to gift cart (public)
     */
    public function contribute(string $token, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:500',
        ]);

        $validated['customer_id'] = auth('sanctum')->id();

        $contributor = $this->giftCartService->addContribution(
            $token,
            $validated,
            $validated['amount']
        );

        return $this->createdResponse(
            $contributor,
            'Contribution added. Please proceed to payment.'
        );
    }

    /**
     * Get contributions for a gift cart
     */
    public function contributions(string $token): JsonResponse
    {
        $giftCart = $this->giftCartService->getByToken($token);
        $contributors = $giftCart->contributors()->with('customer')->get();

        return $this->successResponse($contributors);
    }

    /**
     * Get customer's gift carts
     */
    public function myGiftCarts(Request $request): JsonResponse
    {
        $giftCarts = $request->user()
            ->carts()
            ->where('is_gift_cart', true)
            ->with('contributors')
            ->latest()
            ->paginate(10);

        return $this->paginatedResponse($giftCarts);
    }

    /**
     * Cancel gift cart
     */
    public function cancel(string $token, Request $request): JsonResponse
    {
        $giftCart = $this->giftCartService->getByToken($token);

        // Verify ownership
        if ($giftCart->gift_cart_owner_id !== $request->user()->id) {
            return $this->forbiddenResponse('You are not the owner of this gift cart');
        }

        $this->giftCartService->cancelGiftCart($giftCart->id);

        return $this->successResponse(null, 'Gift cart cancelled');
    }
}
