<?php

declare(strict_types=1);

namespace Modules\Cart\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Core\Services\BaseService;
use Modules\Promotion\Services\PromotionService;

class CartService extends BaseService
{
    protected ?PromotionService $promotionService;

    public function __construct()
    {
        // Lazy load PromotionService to avoid circular dependency if any
        // In a real app, use dependency injection
    }

    /**
     * Get or create a cart for the current session/user
     */
    public function getCart(?string $customerId = null, ?string $sessionId = null): Cart
    {
        if ($customerId) {
            // Find customer's active cart
            $cart = Cart::forCustomer($customerId)->active()->first();

            if (!$cart) {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                ]);
            }
        } else {
            // Find guest cart by session
            $cart = Cart::forSession($sessionId)->active()->first();

            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $sessionId,
                ]);
            }
        }

        return $cart->load('items.product', 'items.variation');
    }

    /**
     * Add item to cart
     */
    public function addItem(Cart $cart, array $data): Cart
    {
        return DB::transaction(function () use ($cart, $data) {
            $productId = $data['product_id'];
            $variationId = $data['variation_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;
            $options = $data['options'] ?? null;

            // Validate product/variation existence and stock
            $product = Product::findOrFail($productId);
            $price = $product->price;

            if ($variationId) {
                $variation = ProductVariation::where('product_id', $productId)
                    ->findOrFail($variationId);
                $price = $variation->price;
                
                if ($variation->stock_quantity < $quantity) {
                    throw new \Modules\Core\Exceptions\OutOfStockException("Insufficient stock for variation: {$variation->variation_name}");
                }
            } else {
                if ($product->stock_quantity < $quantity) {
                    throw new \Modules\Core\Exceptions\OutOfStockException("Insufficient stock for product: {$product->name}");
                }
            }

            // Check if item already exists in cart
            $existingItem = $cart->items()
                ->where('product_id', $productId)
                ->when($variationId, function ($q) use ($variationId) {
                    return $q->where('variation_id', $variationId);
                })
                ->first();

            if ($existingItem) {
                $existingItem->incrementQuantity($quantity);
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'options' => $options,
                ]);
            }

            return $this->recalculateCart($cart);
        });
    }

    /**
     * Update item quantity
     */
    public function updateItem(Cart $cart, string $itemId, int $quantity): Cart
    {
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            // Check stock
            if ($item->variation_id) {
                $stock = $item->variation->stock_quantity;
            } else {
                $stock = $item->product->stock_quantity;
            }

            if ($stock < $quantity) {
                throw new \Modules\Core\Exceptions\OutOfStockException("Insufficient stock. Available: {$stock}");
            }

            $item->updateQuantity($quantity);
        }

        return $this->recalculateCart($cart);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Cart $cart, string $itemId): Cart
    {
        $cart->items()->where('id', $itemId)->delete();
        return $this->recalculateCart($cart);
    }

    /**
     * Clear cart
     */
    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'coupon_code' => null,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 0,
            'total' => 0,
        ]);
    }

    /**
     * Merge guest cart into customer cart
     */
    public function mergeCarts(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            foreach ($guestCart->items as $item) {
                $this->addItem($customerCart, [
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'quantity' => $item->quantity,
                    'options' => $item->options,
                ]);
            }

            $guestCart->delete();
            
            return $this->recalculateCart($customerCart);
        });
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(Cart $cart, string $code): Cart
    {
        // This would use PromotionService to validate and calculate discount
        // For now, simple placeholder logic
        
        // $promotion = $this->promotionService->validateCoupon($code, $cart);
        // $discount = $promotion->calculateDiscount($cart->subtotal);
        
        // Placeholder
        $cart->coupon_code = strtoupper($code);
        // $cart->discount_amount = ...
        
        return $this->recalculateCart($cart);
    }

    /**
     * Recalculate cart totals
     */
    public function recalculateCart(Cart $cart): Cart
    {
        $cart->refresh();
        
        $subtotal = $cart->items->sum('subtotal');
        $cart->subtotal = $subtotal;

        // Calculate Tax (e.g., 18% VAT if applicable, or 0)
        $taxRate = config('cart.tax_rate', 0);
        $cart->tax_amount = $subtotal * $taxRate;

        // Calculate Shipping (placeholder, should use ShippingService)
        // $cart->shipping_amount = ...

        // Re-calculate discount if coupon exists
        if ($cart->coupon_code) {
            // $discount = ...
            // $cart->discount_amount = $discount;
        }

        $cart->total = $cart->subtotal + $cart->tax_amount + $cart->shipping_amount - $cart->discount_amount;
        $cart->save();

        return $cart->load('items.product', 'items.variation');
    }
}
