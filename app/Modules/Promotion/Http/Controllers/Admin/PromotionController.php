<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Http\Requests\StorePromotionRequest;
use Modules\Promotion\Http\Requests\UpdatePromotionRequest;

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
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $promotion = Promotion::create($validated);

        return $this->createdResponse($promotion, 'Promotion créée avec succès');
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
    public function update(string $id, UpdatePromotionRequest $request): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validated();

        $promotion->update($validated);

        return $this->successResponse($promotion, 'Promotion mise à jour avec succès');
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
