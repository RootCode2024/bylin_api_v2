<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Modules\Catalogue\Models\Brand;
use Modules\Core\Services\BaseService;

/**
 * Brand Service
 */
class BrandService extends BaseService
{
    /**
     * Create a new brand
     */
    public function createBrand(array $data): Brand
    {
        return $this->transaction(function () use ($data) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $brand = Brand::create($data);

            if (isset($data['logo'])) {
                $brand->addMedia($data['logo'])->toMediaCollection('logo');
            }

            $this->logInfo('Brand created', ['brand_id' => $brand->id]);

            return $brand;
        });
    }

    /**
     * Update a brand
     */
    public function updateBrand(string $id, array $data): Brand
    {
        return $this->transaction(function () use ($id, $data) {
            $brand = Brand::findOrFail($id);

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $brand->update($data);

            if (isset($data['logo'])) {
                $brand->clearMediaCollection('logo');
                $brand->addMedia($data['logo'])->toMediaCollection('logo');
            }

            $this->logInfo('Brand updated', ['brand_id' => $brand->id]);

            return $brand;
        });
    }

    /**
     * Delete a brand
     */
    public function deleteBrand(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $brand = Brand::findOrFail($id);
            $brand->delete();
            
            $this->logInfo('Brand deleted', ['brand_id' => $id]);
            
            return true;
        });
    }
}
