<?php

declare(strict_types=1);

namespace Modules\Reviews\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Reviews\Models\Review;

class ReviewController extends ApiController
{
    /**
     * Get customer's reviews
     */
    public function myReviews(): JsonResponse
    {
        $customerId = auth()->id();

        $reviews = Review::with('product')
            ->forCustomer($customerId)
            ->latest()
            ->get();

        return $this->successResponse($reviews);
    }

    /**
     * Create a new review
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'order_id' => 'nullable|uuid|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:5000',
        ]);

        $customerId = auth()->id();

        // Check if customer already reviewed this product
        $existingReview = Review::where('customer_id', $customerId)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existingReview) {
            return $this->errorResponse('You have already reviewed this product', 409);
        }

        $review = Review::create([
            'product_id' => $validated['product_id'],
            'customer_id' => $customerId,
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'status' => Review::STATUS_PENDING,
        ]);

        $review->load('product');

        return $this->successResponse($review, 'Review submitted successfully. It will be published after approval.', 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:5000',
        ]);

        $customerId = auth()->id();

        $review = Review::where('customer_id', $customerId)
            ->findOrFail($id);

        // Can only update pending reviews
        if ($review->status !== Review::STATUS_PENDING) {
            return $this->errorResponse('You can only update pending reviews', 403);
        }

        $review->update($validated);
        $review->load('product');

        return $this->successResponse($review, 'Review updated successfully');
    }

    /**
     * Delete a review
     */
    public function destroy(string $id): JsonResponse
    {
        $customerId = auth()->id();

        $review = Review::where('customer_id', $customerId)
            ->findOrFail($id);

        $review->delete();

        return $this->successResponse(null, 'Review deleted successfully');
    }
}
