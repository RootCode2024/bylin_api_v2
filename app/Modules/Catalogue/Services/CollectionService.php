<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Models\Brand;
use Illuminate\Support\Facades\Auth;
use Modules\Catalogue\Models\Product;
use Modules\Core\Services\BaseService;
use Modules\Catalogue\Models\Collection;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionService extends BaseService
{
    public function findOrFail(string $id): Collection
    {
        return Collection::findOrFail($id);
    }

    public function getAll(array $filters = []): EloquentCollection
    {
        $query = Collection::query()->with(['media']);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15)
    {
        $query = Collection::query()->with(['media']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['is_active']) && $filters['is_active'] !== null) $query->where('is_active', $filters['is_active']);

        if (isset($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->active(),
                'featured' => $query->featured(),
                'upcoming' => $query->upcoming(),
                'current' => $query->current(),
                'archived' => $query->archived(),
                default => null,
            };
        }

        if (!empty($filters['with_trashed'])) $query->withTrashed();
        if (!empty($filters['with'])) $query->with($filters['with']);
        if (!empty($filters['search'])) $query->search($filters['search']);
        if (!empty($filters['season'])) $query->bySeason($filters['season']);
        if ($filters['with_counts'] ?? false) $query->withProductsCount()->withActiveProductsCount();

        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);
    }

    public function create(array $data): Collection
    {
        return $this->transaction(function () use ($data) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            $coverImage = $data['cover_image'] ?? null;
            $bannerImage = $data['banner_image'] ?? null;

            unset($data['cover_image'], $data['banner_image']);

            $collection = Collection::create($data);

            if ($coverImage instanceof UploadedFile) {
                $collection->addMedia($coverImage)
                    ->toMediaCollection('cover');
            }

            if ($bannerImage instanceof UploadedFile) {
                $collection->addMedia($bannerImage)
                    ->toMediaCollection('banner');
            }

            $this->logActivity($collection, 'Collection créée');

            return $collection->fresh(['media', 'products']);
        });
    }

    public function update(string $id, array $data): Collection
    {
        return $this->transaction(function () use ($id, $data) {
            $collection = $this->findOrFail($id);

            if (isset($data['name']) && $data['name'] !== $collection->name) $data['slug'] = $this->generateUniqueSlug($data['name'], $id);

            $coverImage = $data['cover_image'] ?? null;
            $bannerImage = $data['banner_image'] ?? null;
            $deleteCover = $data['cover_image_to_delete'] ?? false;
            $deleteBanner = $data['banner_image_to_delete'] ?? false;

            unset(
                $data['cover_image'],
                $data['banner_image'],
                $data['cover_image_to_delete'],
                $data['banner_image_to_delete']
            );

            $collection->update($data);

            if ($deleteCover) {
                $collection->clearMediaCollection('cover');
            } elseif ($coverImage instanceof UploadedFile) {
                $collection->clearMediaCollection('cover');
                $collection->addMedia($coverImage)->toMediaCollection('cover');
            }

            if ($deleteBanner) {
                $collection->clearMediaCollection('banner');
            } elseif ($bannerImage instanceof UploadedFile) {
                $collection->clearMediaCollection('banner');
                $collection->addMedia($bannerImage)->toMediaCollection('banner');
            }

            $this->logActivity($collection, 'Collection mise à jour');

            return $collection->fresh(['media', 'products']);
        });
    }

    public function delete(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $collection = $this->findOrFail($id);

            $productsCount = $collection->products()->count();
            if ($productsCount > 0) {
                throw new \Exception(
                    "Impossible de supprimer cette collection. Elle contient {$productsCount} produit(s). " .
                        "Veuillez d'abord réassigner ou supprimer ces produits."
                );
            }

            $collection->clearMediaCollection('cover');
            $collection->clearMediaCollection('banner');
            $collection->clearMediaCollection('gallery');

            $this->logActivity($collection, 'Collection supprimée');

            return $collection->delete();
        });
    }

    public function toggleFeatured(string $id): Collection
    {
        $collection = $this->findOrFail($id);
        $collection->update(['is_featured' => !$collection->is_featured]);

        $this->logActivity(
            $collection,
            $collection->is_featured ? 'Collection mise en avant' : 'Collection retirée de la mise en avant'
        );

        return $collection->fresh(['media', 'products']);
    }

    public function toggleActive(string $id): Collection
    {
        try {
            $collection = $this->findOrFail($id);

            $newStatus = !$collection->is_active;
            $collection->update(['is_active' => $newStatus]);

            $this->logActivity(
                $collection,
                $collection->is_active ? 'Collection activée' : 'Collection désactivée'
            );

            return $collection->fresh(['media', 'products']);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getWithProducts(string $id): Collection
    {
        return Collection::with(['products', 'activeProducts', 'media'])
            ->withProductsCount()->findOrFail($id);
    }

    public function getStatistics(string $id): array
    {
        $collection = $this->findOrFail($id);

        return [
            'total_products' => $collection->products()->count(),
            'active_products' => $collection->activeProducts()->count(),
            'total_stock' => $collection->products()->sum('stock_quantity'),
            'total_value' => $collection->products()->sum(DB::raw('stock_quantity * price')),
            'average_price' => $collection->products()->avg('price'),
            'total_authenticity_codes' => $collection->authenticityCodes()->count(),
            'activated_codes' => $collection->authenticityCodes()->activated()->count(),
            'available_codes' => $collection->authenticityCodes()->unactivated()->count(),
        ];
    }

    public function refreshCounts(string $id): Collection
    {
        $collection = $this->findOrFail($id);
        $collection->updateProductsCount();
        $collection->updateTotalStock();
        return $collection->fresh(['media', 'products']);
    }

    protected function getBylinBrandId(): ?string
    {
        $bylinBrand = Brand::where('is_bylin_brand', true)->first();

        if ($bylinBrand) return $bylinBrand->id;

        $brandId = config('catalogue.bylin_brand_id');

        if ($brandId) return $brandId;

        return null;
    }

    public function addProducts(
        string $collectionId,
        array $productIds,
        bool $assignBylinBrand = true
    ): Collection {
        return $this->transaction(function () use ($collectionId, $productIds, $assignBylinBrand) {
            $collection = $this->findOrFail($collectionId);

            $validProducts = Product::whereIn('id', $productIds)->pluck('id')->toArray();

            if (empty($validProducts)) throw new \Exception("Aucun produit valide trouvé");

            $updateData = ['collection_id' => $collectionId];

            if ($assignBylinBrand) {
                $bylinBrandId = $this->getBylinBrandId();
                if ($bylinBrandId) $updateData['brand_id'] = $bylinBrandId;
            }

            Product::whereIn('id', $validProducts)->update($updateData);

            $collection->updateProductsCount();
            $collection->updateTotalStock();

            $logMessage = sprintf('Ajout de %d produit(s) à la collection', count($validProducts));
            if ($assignBylinBrand && isset($updateData['brand_id'])) {
                $logMessage .= ' (brand Bylin assigné)';
            }

            $this->logActivity($collection, $logMessage);

            return $collection->fresh(['media', 'products']);
        });
    }

    public function removeProducts(string $collectionId, array $productIds): Collection
    {
        return $this->transaction(function () use ($collectionId, $productIds) {
            $collection = $this->findOrFail($collectionId);

            Product::whereIn('id', $productIds)->where('collection_id', $collectionId)->update(['collection_id' => null]);

            $collection->updateProductsCount();
            $collection->updateTotalStock();

            $this->logActivity(
                $collection,
                sprintf('Retrait de %d produit(s) de la collection', count($productIds))
            );

            return $collection->fresh(['media', 'products']);
        });
    }

    public function syncProducts(string $collectionId, array $productIds): Collection
    {
        return $this->transaction(function () use ($collectionId, $productIds) {
            $collection = $this->findOrFail($collectionId);

            Product::where('collection_id', $collectionId)->update(['collection_id' => null]);

            if (!empty($productIds)) {
                $validProducts = Product::whereIn('id', $productIds)->pluck('id')->toArray();
                if (!empty($validProducts)) Product::whereIn('id', $validProducts)->update(['collection_id' => $collectionId]);
            }

            $collection->updateProductsCount();
            $collection->updateTotalStock();

            $this->logActivity(
                $collection,
                sprintf('Synchronisation des produits: %d produit(s)', count($productIds))
            );

            return $collection->fresh(['media', 'products']);
        });
    }

    public function getAvailableProducts(array $filters = []): EloquentCollection
    {
        $query = Product::query()->whereNull('collection_id')->where('status', 'active');

        if (!empty($filters['search'])) $query->search($filters['search']);
        if (!empty($filters['brand_id'])) $query->where('brand_id', $filters['brand_id']);
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        $query->orderBy('name', 'asc');

        return $query->get();
    }

    public function moveProduct(string $productId, ?string $newCollectionId): Product
    {
        return $this->transaction(function () use ($productId, $newCollectionId) {
            $product = Product::findOrFail($productId);
            $oldCollectionId = $product->collection_id;

            $product->update(['collection_id' => $newCollectionId]);

            if ($oldCollectionId) {
                $oldCollection = Collection::find($oldCollectionId);
                if ($oldCollection) {
                    $oldCollection->updateProductsCount();
                    $oldCollection->updateTotalStock();
                }
            }

            if ($newCollectionId) {
                $newCollection = Collection::find($newCollectionId);
                if ($newCollection) {
                    $newCollection->updateProductsCount();
                    $newCollection->updateTotalStock();
                }
            }

            return $product->fresh(['collection']);
        });
    }

    public function bulkMoveProducts(array $productIds, ?string $newCollectionId): array
    {
        return $this->transaction(function () use ($productIds, $newCollectionId) {
            $affectedCollections = Product::whereIn('id', $productIds)->whereNotNull('collection_id')->pluck('collection_id')->unique()->toArray();
            $updated = Product::whereIn('id', $productIds)->update(['collection_id' => $newCollectionId]);

            if ($newCollectionId) ($affectedCollections[] = $newCollectionId);

            $affectedCollections = array_unique($affectedCollections);

            foreach ($affectedCollections as $collectionId) {
                $collection = Collection::find($collectionId);
                if ($collection) {
                    $collection->updateProductsCount();
                    $collection->updateTotalStock();
                }
            }

            return [
                'updated' => $updated,
                'collections_affected' => $affectedCollections,
            ];
        });
    }

    public function getProductsStatistics(string $collectionId): array
    {
        $collection = $this->findOrFail($collectionId);

        return [
            'total_products' => $collection->products()->count(),
            'active_products' => $collection->products()->where('status', 'active')->count(),
            'draft_products' => $collection->products()->where('status', 'draft')->count(),
            'out_of_stock' => $collection->products()->where('stock_quantity', '<=', 0)->count(),
            'low_stock' => $collection->products()->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'total_stock' => $collection->products()->sum('stock_quantity'),
            'total_value' => $collection->products()->sum(DB::raw('stock_quantity * price')),
            'average_price' => round($collection->products()->avg('price'), 2),
            'by_brand' => $collection->products()->selectRaw('brand_id, COUNT(*) as count')->groupBy('brand_id')->with('brand:id,name')->get()
                ->map(function ($item) {
                    return [
                        'brand_id' => $item->brand_id,
                        'brand_name' => $item->brand?->name ?? 'Sans marque',
                        'count' => $item->count,
                    ];
                }),
        ];
    }

    // ============================================================================
    // HELPERS
    // ============================================================================

    protected function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?string $ignoreId = null): bool
    {
        $query = Collection::where('slug', $slug);
        if ($ignoreId) $query->where('id', '!=', $ignoreId);

        return $query->exists();
    }

    protected function logActivity(Collection $collection, string $message): void
    {
        if (class_exists(Activity::class) && Auth::check()) {
            activity()
                ->performedOn($collection)
                ->causedBy(Auth::user())
                ->log($message);
        }
    }

    public function getAllSeasons(): array
    {
        return Collection::query()->whereNotNull('season')->distinct()->pluck('season')->toArray();
    }

    public function getFeatured(int $limit = 6): EloquentCollection
    {
        return Collection::featured()->active()->current()->with(['media'])->withProductsCount()->orderBy('sort_order')->limit($limit)->get();
    }

    public function archive(string $id): Collection
    {
        $collection = $this->findOrFail($id);

        $collection->update([
            'end_date' => now(),
            'is_active' => false,
        ]);

        $this->logActivity($collection, 'Collection archivée');

        return $collection->fresh(['media', 'products']);
    }
}
