<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Services\BrandService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Http\Requests\StoreBrandRequest;
use Modules\Catalogue\Http\Requests\UpdateBrandRequest;

/**
 * Contrôleur de gestion des marques
 *
 * Ce contrôleur gère toutes les opérations CRUD (Créer, Lire, Mettre à jour, Supprimer)
 * pour les marques, ainsi que les opérations en masse et les statistiques.
 */
class BrandController extends ApiController
{
    /**
     * Constructeur du contrôleur
     *
     * @param BrandService $brandService Service de gestion des marques
     */
    public function __construct(
        private BrandService $brandService
    ) {}

    /**
     * Liste toutes les marques avec filtres et pagination
     *
     * Paramètres de requête acceptés :
     * - search : Terme de recherche
     * - is_active : Filtrer par statut actif/inactif
     * - only_trashed : Afficher uniquement les marques supprimées
     * - with_trashed : Inclure les marques supprimées
     * - per_page : Nombre d'éléments par page (défaut: 15)
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse Liste paginée des marques
     */
    public function index(Request $request): JsonResponse
    {
        $query = Brand::query();

        // Recherche
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtrage par statut
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Gestion des suppressions
        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        // Tri par ordre de tri puis par nom
        $query->orderBy('sort_order')->orderBy('name');

        $brands = $query->paginate($request->per_page ?? 15);

        return $this->successResponse($brands);
    }

    /**
     * Crée une nouvelle marque
     *
     * @param StoreBrandRequest $request Requête de création validée
     * @return JsonResponse Marque créée avec message de succès
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $brand = $this->brandService->createBrand($validated);

        return $this->createdResponse($brand, 'Marque créée avec succès');
    }

    /**
     * Affiche les détails d'une marque spécifique
     *
     * @param string $id Identifiant de la marque
     * @return JsonResponse Détails de la marque
     */
    public function show(string $id): JsonResponse
    {
        $brand = Brand::withTrashed()->findOrFail($id);
        return $this->successResponse($brand);
    }

    /**
     * Met à jour une marque existante
     *
     * @param UpdateBrandRequest $request Requête de mise à jour validée
     * @param string $id Identifiant de la marque
     * @return JsonResponse Marque mise à jour avec message de succès
     */
    public function update(UpdateBrandRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $brand = $this->brandService->updateBrand($id, $validated);

        return $this->successResponse($brand, 'Marque mise à jour avec succès');
    }

    /**
     * Supprime une marque (soft delete)
     *
     * @param string $id Identifiant de la marque
     * @return JsonResponse Message de confirmation
     */
    public function destroy(string $id): JsonResponse
    {
        $this->brandService->deleteBrand($id);

        return $this->successResponse(null, 'Marque supprimée avec succès');
    }

    /**
     * Restaure une marque supprimée
     *
     * @param string $id Identifiant de la marque
     * @return JsonResponse Marque restaurée avec message de succès
     */
    public function restore(string $id): JsonResponse
    {
        $brand = Brand::onlyTrashed()->findOrFail($id);
        $brand->restore();

        return $this->successResponse($brand, 'Marque restaurée avec succès');
    }

    /**
     * Supprime définitivement une marque de la base de données
     *
     * @param string $id Identifiant de la marque
     * @return JsonResponse Message de confirmation
     */
    public function forceDelete(string $id): JsonResponse
    {
        $brand = Brand::withTrashed()->findOrFail($id);
        $brand->forceDelete();

        return $this->successResponse(null, 'Marque supprimée définitivement');
    }

    /**
     * Supprime plusieurs marques en masse (soft delete)
     *
     * @param Request $request Requête contenant les IDs des marques
     * @return JsonResponse Message de confirmation avec le nombre de marques supprimées
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:brands,id'
        ]);

        Brand::whereIn('id', $validated['ids'])->delete();

        return $this->successResponse(
            null,
            count($validated['ids']) . ' marque(s) supprimée(s) avec succès'
        );
    }

    /**
     * Restaure plusieurs marques en masse
     *
     * @param Request $request Requête contenant les IDs des marques
     * @return JsonResponse Message de confirmation avec le nombre de marques restaurées
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:brands,id'
        ]);

        $count = Brand::onlyTrashed()
            ->whereIn('id', $validated['ids'])
            ->restore();

        return $this->successResponse(
            null,
            $count . ' marque(s) restaurée(s) avec succès'
        );
    }

    /**
     * Supprime définitivement plusieurs marques en masse
     *
     * @param Request $request Requête contenant les IDs des marques
     * @return JsonResponse Message de confirmation avec le nombre de marques supprimées
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:brands,id'
        ]);

        $brands = Brand::withTrashed()->whereIn('id', $validated['ids'])->get();

        foreach ($brands as $brand) {
            $brand->forceDelete();
        }

        return $this->successResponse(null, count($validated['ids']) . ' marque(s) supprimée(s) définitivement');
    }

    /**
     * Retourne les statistiques des marques
     *
     * Statistiques disponibles :
     * - total : Nombre total de marques (incluant supprimées)
     * - active : Nombre de marques actives
     * - inactive : Nombre de marques inactives
     * - with_products : Nombre de marques ayant des produits
     * - trashed : Nombre de marques supprimées
     *
     * @return JsonResponse Statistiques des marques
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Brand::withTrashed()->count(),
            'active' => Brand::where('is_active', true)->count(),
            'inactive' => Brand::where('is_active', false)->count(),
            'with_products' => Brand::has('products')->count(),
            'trashed' => Brand::onlyTrashed()->count(),
        ];

        return $this->successResponse($stats);
    }
}
