<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Promotion\Models\Promotion;

class PromotionController extends ApiController
{
    /**
     * List promotions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Promotion::query();

        if ($request->has('active')) {
            $query->where('is_active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        $promotions = $query->latest()->paginate($request->per_page ?? 20);

        return $this->successResponse($promotions);
    }

    /**
     * Create promotion
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:promotions,code|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
        ]);

        $promotion = Promotion::create($validated);

        return $this->createdResponse($promotion, 'Promotion created successfully');
    }

    /**
     * Show promotion
     */
    public function show(string $id): JsonResponse
    {
        $promotion = Promotion::with('usages')->findOrFail($id);
        return $this->successResponse($promotion);
    }

    /**
     * Update promotion
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:promotions,code,' . $id,
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:percentage,fixed_amount',
            'value' => 'sometimes|required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
        ]);

        $promotion->update($validated);

        return $this->successResponse($promotion, 'Promotion updated successfully');
    }

    /**
     * Delete promotion
     */
    public function destroy(string $id): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return $this->successResponse(null, 'Promotion deleted successfully');
    }
}
