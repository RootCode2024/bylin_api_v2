<?php

declare(strict_types=1);

namespace Modules\Cart\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Cart\Enums\GiftCartStatus;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\GiftCartContributor;
use Modules\Core\Exceptions\BusinessException;
use Modules\Core\Services\BaseService;

/**
 * Gift Cart Service
 * 
 * Handles business logic for gift cart creation and collaborative payments
 */
class GiftCartService extends BaseService
{
    /**
     * Convert a regular cart to a gift cart
     */
    public function convertToGiftCart(
        string $cartId,
        string $ownerId,
        ?string $message = null,
        ?int $expirationDays = 30
    ): Cart {
        return $this->transaction(function () use ($cartId, $ownerId, $message, $expirationDays) {
            $cart = Cart::findOrFail($cartId);

            if ($cart->is_gift_cart) {
                throw new BusinessException('This cart is already a gift cart');
            }

            if ($cart->items->isEmpty()) {
                throw new BusinessException('Cannot create gift cart with empty cart');
            }

            $token = $this->generateUniqueToken();
            $expiresAt = $expirationDays 
                ? now()->addDays($expirationDays) 
                : now()->addDays(config('ecommerce.gift_cart.default_expiration_days', 30));

            $cart->update([
                'is_gift_cart' => true,
                'gift_cart_token' => $token,
                'gift_cart_status' => GiftCartStatus::PENDING,
                'gift_cart_target_amount' => $cart->total,
                'gift_cart_paid_amount' => 0,
                'gift_cart_owner_id' => $ownerId,
                'gift_cart_message' => $message,
                'gift_cart_expires_at' => $expiresAt,
            ]);

            $this->logInfo('Gift cart created', [
                'cart_id' => $cart->id,
                'token' => $token,
                'owner_id' => $ownerId,
            ]);

            // Dispatch event
            event(new \Modules\Cart\Events\GiftCartCreated($cart));

            return $cart->fresh();
        });
    }

    /**
     * Get gift cart by token
     */
    public function getByToken(string $token): Cart
    {
        $cart = Cart::where('gift_cart_token', $token)
            ->where('is_gift_cart', true)
            ->with(['items.product', 'contributors'])
            ->firstOrFail();

        // Check if expired
        if ($cart->gift_cart_expires_at && $cart->gift_cart_expires_at->isPast()) {
            if ($cart->gift_cart_status !== GiftCartStatus::EXPIRED) {
                $cart->update(['gift_cart_status' => GiftCartStatus::EXPIRED]);
            }
        }

        return $cart;
    }

    /**
     * Add contribution to gift cart
     */
    public function addContribution(
        string $token,
        array $contributorData,
        float $amount
    ): GiftCartContributor {
        return $this->transaction(function () use ($token, $contributorData, $amount) {
            $cart = $this->getByToken($token);

            // Validate cart status
            if ($cart->gift_cart_status === GiftCartStatus::COMPLETED) {
                throw new BusinessException('This gift cart is already fully funded');
            }

            if ($cart->gift_cart_status === GiftCartStatus::EXPIRED) {
                throw new BusinessException('This gift cart has expired');
            }

            if ($cart->gift_cart_status === GiftCartStatus::CANCELLED) {
                throw new BusinessException('This gift cart has been cancelled');
            }

            // Validate amount
            $remainingAmount = $cart->gift_cart_target_amount - $cart->gift_cart_paid_amount;
            if ($amount > $remainingAmount) {
                $amount = $remainingAmount; // Cap at remaining amount
            }

            $percentage = ($amount / $cart->gift_cart_target_amount) * 100;

            // Check minimum contribution
            $minPercentage = config('ecommerce.gift_cart.min_contribution_percentage', 5);
            if ($percentage < $minPercentage) {
                throw new BusinessException("Minimum contribution is {$minPercentage}%");
            }

            // Create contributor
            $contributor = GiftCartContributor::create([
                'gift_cart_id' => $cart->id,
                'contributor_name' => $contributorData['name'],
                'contributor_email' => $contributorData['email'],
                'contributor_customer_id' => $contributorData['customer_id'] ?? null,
                'contribution_amount' => $amount,
                'contribution_percentage' => $percentage,
                'payment_status' => 'pending',
                'message' => $contributorData['message'] ?? null,
            ]);

            $this->logInfo('Contribution added', [
                'cart_id' => $cart->id,
                'contributor_id' => $contributor->id,
                'amount' => $amount,
            ]);

            return $contributor;
        });
    }

    /**
     * Process contribution payment
     */
    public function processContributionPayment(string $contributorId, string $paymentId): void
    {
        $this->transaction(function () use ($contributorId, $paymentId) {
            $contributor = GiftCartContributor::findOrFail($contributorId);
            $contributor->markAsPaid($paymentId);

            $cart = $contributor->giftCart;
            $cart->increment('gift_cart_paid_amount', $contributor->contribution_amount);

            // Check if fully funded
            if ($cart->gift_cart_paid_amount >= $cart->gift_cart_target_amount) {
                $this->completeGiftCart($cart->id);
            } else {
                // Update to partial status
                $cart->update(['gift_cart_status' => GiftCartStatus::PARTIAL]);
            }

            // Dispatch event
            event(new \Modules\Cart\Events\GiftCartContributionReceived($cart, $contributor));

            $this->logInfo('Contribution payment processed', [
                'cart_id' => $cart->id,
                'contributor_id' => $contributorId,
            ]);
        });
    }

    /**
     * Complete gift cart and create order
     */
    protected function completeGiftCart(string $cartId): void
    {
        $cart = Cart::findOrFail($cartId);
        
        $cart->update(['gift_cart_status' => GiftCartStatus::COMPLETED]);

        // Dispatch event to create order
        event(new \Modules\Cart\Events\GiftCartCompleted($cart));

        $this->logInfo('Gift cart completed', ['cart_id' => $cartId]);
    }

    /**
     * Cancel gift cart
     */
    public function cancelGiftCart(string $cartId, ?string $reason = null): bool
    {
        return $this->transaction(function () use ($cartId, $reason) {
            $cart = Cart::findOrFail($cartId);

            if ($cart->gift_cart_status === GiftCartStatus::COMPLETED) {
                throw new BusinessException('Cannot cancel a completed gift cart');
            }

            // Check if there are paid contributions (need refunds)
            $paidContributions = $cart->contributors()->paid()->exists();
            if ($paidContributions) {
                throw new BusinessException(
                    'Gift cart has paid contributions. Process refunds before cancelling.'
                );
            }

            $cart->update(['gift_cart_status' => GiftCartStatus::CANCELLED]);

            $this->logInfo('Gift cart cancelled', [
                'cart_id' => $cartId,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Get gift cart link
     */
    public function getGiftCartLink(string $token): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/gift-cart/{$token}";
    }

    /**
     * Generate unique token for gift cart
     */
    protected function generateUniqueToken(): string
    {
        do {
            $token = 'gc_' . Str::random(16);
        } while (Cart::where('gift_cart_token', $token)->exists());

        return $token;
    }

    /**
     * Check for expired gift carts
     */
    public function checkExpiredGiftCarts(): int
    {
        $expiredCount = 0;

        $expiredCarts = Cart::where('is_gift_cart', true)
            ->where('gift_cart_status', '!=', GiftCartStatus::COMPLETED)
            ->where('gift_cart_status', '!=', GiftCartStatus::EXPIRED)
            ->where('gift_cart_expires_at', '<', now())
            ->get();

        foreach ($expiredCarts as $cart) {
            $cart->update(['gift_cart_status' => GiftCartStatus::EXPIRED]);

            // Handle refunds if configured
            if (config('ecommerce.gift_cart.refund_on_expiration', true)) {
                event(new \Modules\Cart\Events\GiftCartExpired($cart));
            }

            $expiredCount++;
        }

        return $expiredCount;
    }
}
