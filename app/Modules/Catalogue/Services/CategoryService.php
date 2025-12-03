<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Modules\Catalogue\Models\Category;
use Modules\Core\Services\BaseService;

/**
 * Category Service
 * 
 * Handles business logic for category management
 */
class CategoryService extends BaseService
{
    /**
     * Create a new category
     */
    public function createCategory(array $data): Category
    {
        return $this->transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle parent category
            if (!empty($data['parent_id'])) {
                $parent = Category::findOrFail($data['parent_id']);
                // Logic to prevent cycles could be added here
            }

            $category = Category::create($data);

            // Handle image upload if present
            if (isset($data['image'])) {
                $category->addMedia($data['image'])->toMediaCollection('cover');
            }

            $this->logInfo('Category created', ['category_id' => $category->id]);

            return $category;
        });
    }

    /**
     * Update a category
     */
    public function updateCategory(string $id, array $data): Category
    {
        return $this->transaction(function () use ($id, $data) {
            $category = Category::findOrFail($id);

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Prevent setting self as parent
            if (isset($data['parent_id']) && $data['parent_id'] === $id) {
                unset($data['parent_id']);
            }

            $category->update($data);

            if (isset($data['image'])) {
                $category->clearMediaCollection('cover');
                $category->addMedia($data['image'])->toMediaCollection('cover');
            }

            $this->logInfo('Category updated', ['category_id' => $category->id]);

            return $category;
        });
    }

    /**
     * Delete a category
     */
    public function deleteCategory(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $category = Category::findOrFail($id);
            
            // Check for children or products if necessary
            // For now, we rely on soft deletes or DB constraints
            
            $category->delete();

            $this->logInfo('Category deleted', ['category_id' => $id]);

            return true;
        });
    }
}
