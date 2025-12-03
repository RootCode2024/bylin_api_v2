<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Services\AttributeService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Models\Attribute;

class AttributeController extends ApiController
{
    public function __construct(
        private AttributeService $attributeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $attributes = Attribute::query()
            ->with('values')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($attributes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:attributes,code',
            'type' => 'required|string',
        ]);

        $attribute = $this->attributeService->createAttribute($request->all());

        return $this->createdResponse($attribute, 'Attribute created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $attribute = Attribute::with('values')->findOrFail($id);
        return $this->successResponse($attribute);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $attribute = $this->attributeService->updateAttribute($id, $request->all());
        return $this->successResponse($attribute, 'Attribute updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->attributeService->deleteAttribute($id);
        return $this->successResponse(null, 'Attribute deleted successfully');
    }
}
