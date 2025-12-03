<?php

declare(strict_types=1);

namespace Modules\Reviews\Services;

use Modules\Catalogue\Models\Product;
use Modules\Core\Services\BaseService;
use Modules\Reviews\Models\Review;

class ReviewService extends BaseService
{
    /**
     * Approve a review
     */
    public function approveReview(Review $review): Review
    {
        $review->update(['status' => Review::STATUS_APPROVED]);
        
        // Update product average rating
        $this->updateProductRating($review->product_id);

        return $review;
    }

    /**
     * Reject a review
     */
    public function rejectReview(Review $review): Review
    {
        $review->update(['status' => Review::STATUS_REJECTED]);
        
        // Update product average rating (in case it was previously approved)
        $this->updateProductRating($review->product_id);

        return $review;
    }

    /**
     * Recalculate and update product rating
     */
    public function updateProductRating(string $productId): void
    {
        $product = Product::findOrFail($productId);
        
        $stats = Review::where('product_id', $productId)
            ->where('status', Review::STATUS_APPROVED)
            ->selectRaw('AVG(rating) as average, COUNT(*) as count')
            ->first();

        $product->update([
            'rating_average' => $stats->average ?? 0,
            'rating_count' => $stats->count ?? 0,
        ]);
    }
}
