<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalogue\Services\CategoryService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Models\Category;

class CategoryController extends ApiController
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->with('parent')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = $this->categoryService->createCategory($request->all());

        return $this->createdResponse($category, 'Category created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::with('children')->findOrFail($id);
        return $this->successResponse($category);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $category = $this->categoryService->updateCategory($id, $request->all());
        return $this->successResponse($category, 'Category updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->categoryService->deleteCategory($id);
        return $this->successResponse(null, 'Category deleted successfully');
    }
}
