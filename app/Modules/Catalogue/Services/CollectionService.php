<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Services\BaseService;
use Illuminate\Support\Facades\Storage;
use Modules\Catalogue\Models\Collection;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;


/**
 * Collection Service
 */
class CollectionService extends BaseService
{

    public function findOrFail(string $id): Collection
    {
        return Collection::findOrFail($id);
    }


    public function getAll(array $filters = []): EloquentCollection
    {
        $query = Collection::query();

        // ✅ Filtre par is_active (ajout)
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

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

        if (!empty($filters['season'])) {
            $query->bySeason($filters['season']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if ($filters['with_counts'] ?? false) {
            $query->withProductsCount()
                ->withActiveProductsCount();
        }

        if (!empty($filters['with'])) {
            $query->with($filters['with']);
        }

        if (!empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15)
    {
        $query = Collection::query();

        // ✅ Filtre par is_active (ajout)
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filtre par status (vos scopes existants)
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

        if (!empty($filters['season'])) {
            $query->bySeason($filters['season']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if ($filters['with_counts'] ?? false) {
            $query->withProductsCount()
                ->withActiveProductsCount();
        }

        if (!empty($filters['with'])) {
            $query->with($filters['with']);
        }

        if (!empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data) {

            $data['slug'] = $this->generateUniqueSlug($data['name']);

            if (isset($data['cover_image']) && $data['cover_image'] instanceof \Illuminate\Http\UploadedFile) {
                $data['cover_image'] = $this->uploadImage($data['cover_image'], 'collections/covers');
            }

            if (isset($data['banner_image']) && $data['banner_image'] instanceof \Illuminate\Http\UploadedFile) {
                $data['banner_image'] = $this->uploadImage($data['banner_image'], 'collections/banners');
            }

            $collection = Collection::create($data);

            if (class_exists(Activity::class) && Auth::check()) {
                activity()
                    ->performedOn($collection)
                    ->causedBy(Auth::user())
                    ->log('Collection créée');
            }

            return $collection->fresh();
        });
    }

    public function update(string $id, array $data): Collection
    {
        return DB::transaction(function () use ($id, $data) {
            $collection = $this->findOrFail($id);

            if (isset($data['name']) && $data['name'] !== $collection->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }

            if (isset($data['cover_image']) && $data['cover_image'] instanceof \Illuminate\Http\UploadedFile) {

                if ($collection->cover_image) {
                    Storage::disk('public')->delete($collection->cover_image);
                }
                $data['cover_image'] = $this->uploadImage($data['cover_image'], 'collections/covers');
            }

            if (isset($data['banner_image']) && $data['banner_image'] instanceof \Illuminate\Http\UploadedFile) {

                if ($collection->banner_image) {
                    Storage::disk('public')->delete($collection->banner_image);
                }
                $data['banner_image'] = $this->uploadImage($data['banner_image'], 'collections/banners');
            }

            $collection->update($data);


            if (class_exists(Activity::class) && Auth::check()) {
                activity()
                    ->performedOn($collection)
                    ->causedBy(Auth::user())
                    ->log('Collection mise à jour');
            }

            return $collection->fresh();
        });
    }

    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $collection = $this->findOrFail($id);

            $productsCount = $collection->products()->count();
            if ($productsCount > 0) {
                throw new \Exception(
                    "Impossible de supprimer cette collection. Elle contient {$productsCount} produit(s). " .
                        "Veuillez d'abord réassigner ou supprimer ces produits."
                );
            }

            if ($collection->cover_image) {
                Storage::disk('public')->delete($collection->cover_image);
            }
            if ($collection->banner_image) {
                Storage::disk('public')->delete($collection->banner_image);
            }

            if (class_exists(Activity::class) && Auth::check()) {
                activity()
                    ->performedOn($collection)
                    ->causedBy(Auth::user())
                    ->log('Collection supprimée');
            }

            return $collection->delete();
        });
    }

    public function toggleFeatured(string $id): Collection
    {
        $collection = $this->findOrFail($id);
        $collection->update(['is_featured' => !$collection->is_featured]);

        if (class_exists(Activity::class) && Auth::check()) {
            activity()
                ->performedOn($collection)
                ->causedBy(Auth::user())
                ->log($collection->is_featured ? 'Collection mise en avant' : 'Collection retirée de la mise en avant');
        }

        return $collection->fresh();
    }

    public function toggleActive(string $id): Collection
    {
        try {
            // ✅ Log pour debug
            Log::info('Service toggleActive start', ['id' => $id]);

            $collection = $this->findOrFail($id);

            // ✅ Log état actuel
            Log::info('Current collection state', [
                'id' => $collection->id,
                'name' => $collection->name,
                'is_active' => $collection->is_active
            ]);

            // Toggle le statut
            $newStatus = !$collection->is_active;
            $collection->update(['is_active' => $newStatus]);

            // ✅ Log après update
            Log::info('After update', [
                'id' => $collection->id,
                'new_is_active' => $collection->is_active
            ]);

            // Activity log (optionnel)
            if (class_exists(\Spatie\Activitylog\Facades\Activity::class) && \Illuminate\Support\Facades\Auth::check()) {
                \Spatie\Activitylog\Facades\Activity::log(
                    $collection->is_active ? 'Collection activée' : 'Collection désactivée'
                );
            }

            return $collection->fresh();
        } catch (\Exception $e) {
            Log::error('Service toggleActive error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getWithProducts(string $id): Collection
    {
        return Collection::with(['products', 'activeProducts'])
            ->withProductsCount()
            ->findOrFail($id);
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

        return $collection->fresh();
    }

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

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    protected function uploadImage(\Illuminate\Http\UploadedFile $file, string $path): string
    {
        return $file->store($path, 'public');
    }

    public function getAllSeasons(): array
    {
        return Collection::query()
            ->whereNotNull('season')
            ->distinct()
            ->pluck('season')
            ->toArray();
    }

    public function getFeatured(int $limit = 6): EloquentCollection
    {
        return Collection::featured()
            ->active()
            ->current()
            ->withProductsCount()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function archive(string $id): Collection
    {
        $collection = $this->findOrFail($id);

        $collection->update([
            'end_date' => now(),
            'is_active' => false,
        ]);

        if (class_exists(Activity::class) && Auth::check()) {
            activity()
                ->performedOn($collection)
                ->causedBy(Auth::user())
                ->log('Collection archivée');
        }

        return $collection->fresh();
    }
}
