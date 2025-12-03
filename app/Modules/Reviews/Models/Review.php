<?php

declare(strict_types=1);

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Catalogue\Models\Product;
use Modules\Core\Models\BaseModel;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;

class Review extends BaseModel
{
    use HasUuids, SoftDeletes;

    protected $table = 'reviews';

    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'status',
        'is_verified_purchase',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Get the product being reviewed
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the customer who wrote the review
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order this review is linked to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the media for this review
     */
    public function media(): HasMany
    {
        return $this->hasMany(ReviewMedia::class);
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for verified purchases
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope for product reviews
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for customer reviews
     */
    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for rating filter
     */
    public function scopeRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope ordered by most helpful
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    /**
     * Check if review is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if review is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Approve the review
     */
    public function approve(): self
    {
        $this->status = self::STATUS_APPROVED;
        $this->save();

        // Update product average rating
        $this->product->updateAverageRating();

        return $this;
    }

    /**
     * Reject the review
     */
    public function reject(): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->save();

        return $this;
    }

    /**
     * Increment helpful count
     */
    public function markAsHelpful(): self
    {
        $this->increment('helpful_count');
        return $this;
    }

    /**
     * Validate rating value
     */
    public static function validateRating(int $rating): bool
    {
        return $rating >= 1 && $rating <= 5;
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($review) {
            // Check if this is a verified purchase
            if ($review->order_id) {
                $review->is_verified_purchase = true;
            }
        });

        static::created(function ($review) {
            // Update product rating if approved
            if ($review->isApproved()) {
                $review->product->updateAverageRating();
            }
        });

        static::updated(function ($review) {
            // Update product rating if status changed to/from approved
            if ($review->isDirty('status')) {
                $review->product->updateAverageRating();
            }
        });

        static::deleted(function ($review) {
            // Update product rating after deletion
            $review->product->updateAverageRating();
        });
    }
}
