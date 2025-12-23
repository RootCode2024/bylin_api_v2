<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Services\CollectionService;
use Modules\Catalogue\Http\Requests\StoreCollectionRequest;
use Modules\Catalogue\Http\Requests\UpdateCollectionRequest;

/**
 * Collection Controller (Admin)
 *
 * Gestion des collections Bylin par les administrateurs
 */
class CollectionController extends ApiController
{
    public function __construct(
        protected CollectionService $collectionService
    ) {}

    /**
     * Get all collections (with filters)
     *
     * @group Collections Admin
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'season' => $request->input('season'),
                'search' => $request->input('search'),
                'is_active' => $request->has('is_active')
                    ? (bool) $request->input('is_active')
                    : null,
                'with_counts' => $request->boolean('with_counts', true),
                'with' => $request->input('with', []),
                'with_trashed' => $request->boolean('with_trashed', false),
                'order_by' => $request->input('order_by', 'created_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            // Paginated or all
            if ($request->boolean('paginate', true)) {
                $perPage = (int) $request->input('per_page', 15);
                $collections = $this->collectionService->getPaginated($filters, $perPage);

                return $this->paginatedResponse($collections, 'Collections récupérées avec succès');
            }

            $collections = $this->collectionService->getAll($filters);

            return $this->successResponse(
                $collections,
                'Collections récupérées avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des collections',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Store a new collection
     *
     * @group Collections Admin
     * @authenticated
     */
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        try {
            $collection = $this->collectionService->create($request->validated());

            return $this->successResponse(
                $collection,
                'Collection créée avec succès',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la création de la collection',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get a specific collection
     *
     * @group Collections Admin
     * @authenticated
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try {
            if ($request->boolean('with_products')) {
                $collection = $this->collectionService->getWithProducts($id);
            } else {
                $collection = $this->collectionService->findOrFail($id);
            }

            return $this->successResponse(
                $collection,
                'Collection récupérée avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération de la collection',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update a collection
     *
     * @group Collections Admin
     * @authenticated
     */
    public function update(UpdateCollectionRequest $request, string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->update($id, $request->validated());

            return $this->successResponse(
                $collection,
                'Collection mise à jour avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la mise à jour de la collection',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Delete a collection
     *
     * @group Collections Admin
     * @authenticated
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->collectionService->delete($id);

            return $this->successResponse(
                null,
                'Collection supprimée avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    /**
     * Toggle featured status
     *
     * @group Collections Admin
     * @authenticated
     */
    public function toggleFeatured(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->toggleFeatured($id);

            return $this->successResponse(
                $collection,
                $collection->is_featured
                    ? 'Collection mise en avant'
                    : 'Collection retirée de la mise en avant'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Toggle active status
     *
     * @group Collections Admin
     * @authenticated
     */
    public function toggleActive(string $id): JsonResponse
    {
        try {
            // ✅ Log pour debug
            Log::info('Toggle active called', ['collection_id' => $id]);

            $collection = $this->collectionService->toggleActive($id);

            // ✅ Log succès
            Log::info('Toggle active success', [
                'collection_id' => $id,
                'new_status' => $collection->is_active
            ]);

            return $this->successResponse(
                $collection,
                $collection->is_active
                    ? 'Collection activée'
                    : 'Collection désactivée'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Collection not found', ['id' => $id]);
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            // ✅ Log détaillé de l'erreur
            Log::error('Toggle active error', [
                'collection_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Erreur lors du changement de statut: ' . $e->getMessage(),
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get collection statistics
     *
     * @group Collections Admin
     * @authenticated
     */
    public function statistics(string $id): JsonResponse
    {
        try {
            $stats = $this->collectionService->getStatistics($id);

            return $this->successResponse(
                $stats,
                'Statistiques récupérées avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des statistiques',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Refresh cached counts
     *
     * @group Collections Admin
     * @authenticated
     */
    public function refreshCounts(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->refreshCounts($id);

            return $this->successResponse(
                $collection,
                'Compteurs actualisés avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'actualisation',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Archive a collection
     *
     * @group Collections Admin
     * @authenticated
     */
    public function archive(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->archive($id);

            return $this->successResponse(
                $collection,
                'Collection archivée avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'archivage',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get all seasons
     *
     * @group Collections Admin
     * @authenticated
     */
    public function seasons(): JsonResponse
    {
        try {
            $seasons = $this->collectionService->getAllSeasons();

            return $this->successResponse(
                $seasons,
                'Saisons récupérées avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des saisons',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get featured collections
     *
     * @group Collections Admin
     * @authenticated
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 6);
            $collections = $this->collectionService->getFeatured($limit);

            return $this->successResponse(
                $collections,
                'Collections en vedette récupérées avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des collections en vedette',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }
}
