<?php

declare(strict_types=1);

namespace Modules\Promotion\Services;

use Modules\Cart\Models\Cart;
use Modules\Core\Services\BaseService;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Models\PromotionUsage;

class PromotionService extends BaseService
{
    /**
     * Validate coupon code for a cart
     */
    public function validateCoupon(string $code, Cart $cart): Promotion
    {
        $promotion = Promotion::byCode($code)->active()->first();

        if (!$promotion) {
            throw new \Modules\Core\Exceptions\InvalidCouponException('Invalid or expired coupon code.');
        }

        // Check global usage limit
        if ($promotion->hasReachedLimit()) {
            throw new \Modules\Core\Exceptions\InvalidCouponException('This coupon has reached its usage limit.');
        }

        // Check per-customer usage limit if customer is logged in
        if ($cart->customer_id && $promotion->hasCustomerReachedLimit($cart->customer_id)) {
            throw new \Modules\Core\Exceptions\InvalidCouponException('You have already used this coupon the maximum number of times.');
        }

        // Check minimum purchase amount
        if (!$promotion->isApplicableToAmount($cart->subtotal)) {
            throw new \Modules\Core\Exceptions\InvalidCouponException("Minimum purchase amount of {$promotion->min_purchase_amount} required.");
        }

        // Check applicable products/categories
        if (!$this->isApplicableToCartItems($promotion, $cart)) {
            throw new \Modules\Core\Exceptions\InvalidCouponException('This coupon is not applicable to the items in your cart.');
        }

        return $promotion;
    }

    /**
     * Calculate discount amount for a cart
     */
    public function calculateDiscount(Promotion $promotion, Cart $cart): float
    {
        // If promotion applies to specific products only
        if ($promotion->applicable_products || $promotion->applicable_categories) {
            $eligibleAmount = 0;
            
            foreach ($cart->items as $item) {
                if ($this->isItemEligible($promotion, $item)) {
                    $eligibleAmount += $item->subtotal;
                }
            }
            
            return $promotion->calculateDiscount($eligibleAmount);
        }

        // Otherwise apply to full subtotal
        return $promotion->calculateDiscount($cart->subtotal);
    }

    /**
     * Record promotion usage
     */
    public function recordUsage(Promotion $promotion, string $orderId, ?string $customerId, float $discountAmount): void
    {
        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
        ]);

        $promotion->incrementUsage();
    }

    /**
     * Check if promotion is applicable to cart items
     */
    protected function isApplicableToCartItems(Promotion $promotion, Cart $cart): bool
    {
        // If no restrictions, it applies to all
        if (empty($promotion->applicable_products) && empty($promotion->applicable_categories)) {
            return true;
        }

        // Check if at least one item is eligible
        foreach ($cart->items as $item) {
            if ($this->isItemEligible($promotion, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specific item is eligible for promotion
     */
    protected function isItemEligible(Promotion $promotion, $item): bool
    {
        // Check product ID
        if (!empty($promotion->applicable_products)) {
            if (in_array($item->product_id, $promotion->applicable_products)) {
                return true;
            }
        }

        // Check category ID
        if (!empty($promotion->applicable_categories)) {
            // Assuming product has category_id or categories relation loaded
            // This might need adjustment based on how categories are stored on product
            $productCategories = $item->product->categories->pluck('id')->toArray();
            
            if (array_intersect($productCategories, $promotion->applicable_categories)) {
                return true;
            }
        }

        return false;
    }
}
