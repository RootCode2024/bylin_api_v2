<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Services\BrandService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Models\Brand;

class BrandController extends ApiController
{
    public function __construct(
        private BrandService $brandService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $brands = Brand::query()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($brands);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $brand = $this->brandService->createBrand($request->all());

        return $this->createdResponse($brand, 'Brand created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        return $this->successResponse($brand);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $brand = $this->brandService->updateBrand($id, $request->all());
        return $this->successResponse($brand, 'Brand updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->brandService->deleteBrand($id);
        return $this->successResponse(null, 'Brand deleted successfully');
    }
}
