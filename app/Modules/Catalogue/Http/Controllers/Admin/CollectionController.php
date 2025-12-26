<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Services\CollectionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Catalogue\Http\Requests\StoreCollectionRequest;
use Modules\Catalogue\Http\Requests\UpdateCollectionRequest;

class CollectionController extends ApiController
{
    public function __construct(
        protected CollectionService $collectionService
    ) {}

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
                'with' => $request->input('with', ['media']),
                'with_trashed' => $request->boolean('with_trashed', false),
                'order_by' => $request->input('order_by', 'created_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            if ($request->boolean('with_products', false)) $filters['with'][] = 'products';

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

    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $collection = $this->collectionService->getWithProducts($id);

            return $this->successResponse(
                $collection,
                'Collection récupérée avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            Log::error('Error loading collection', [
                'collection_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération de la collection',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function update(UpdateCollectionRequest $request, string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->update($id, $request->validated());

            return $this->successResponse(
                $collection,
                'Collection mise à jour avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la mise à jour de la collection',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->collectionService->delete($id);

            return $this->successResponse(
                null,
                'Collection supprimée avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

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
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function toggleActive(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->toggleActive($id);

            return $this->successResponse(
                $collection,
                $collection->is_active
                    ? 'Collection activée'
                    : 'Collection désactivée'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors du changement de statut: ' . $e->getMessage(),
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function statistics(string $id): JsonResponse
    {
        try {
            $stats = $this->collectionService->getStatistics($id);

            return $this->successResponse(
                $stats,
                'Statistiques récupérées avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des statistiques',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function refreshCounts(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->refreshCounts($id);

            return $this->successResponse(
                $collection,
                'Compteurs actualisés avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'actualisation',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function archive(string $id): JsonResponse
    {
        try {
            $collection = $this->collectionService->archive($id);

            return $this->successResponse(
                $collection,
                'Collection archivée avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'archivage',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

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

    public function addProducts(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'string', 'exists:products,id'],
        ]);

        try {
            $collection = $this->collectionService->addProducts(
                $id,
                $request->input('product_ids')
            );

            return $this->successResponse(
                $collection,
                sprintf('%d produit(s) ajouté(s) à la collection', count($request->input('product_ids')))
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            Log::error('Error adding products to collection', [
                'collection_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    public function removeProducts(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'string', 'exists:products,id'],
        ]);

        try {
            $collection = $this->collectionService->removeProducts(
                $id,
                $request->input('product_ids')
            );

            return $this->successResponse(
                $collection,
                sprintf('%d produit(s) retiré(s) de la collection', count($request->input('product_ids')))
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    public function syncProducts(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['required', 'string', 'exists:products,id'],
        ]);

        try {
            $collection = $this->collectionService->syncProducts(
                $id,
                $request->input('product_ids')
            );

            return $this->successResponse(
                $collection,
                'Produits de la collection synchronisés avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    public function availableProducts(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'brand_id' => $request->input('brand_id'),
                'category_id' => $request->input('category_id'),
            ];

            $products = $this->collectionService->getAvailableProducts($filters);

            return $this->successResponse(
                $products,
                'Produits disponibles récupérés avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des produits disponibles',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function bulkMoveProducts(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'string', 'exists:products,id'],
            'collection_id' => ['nullable', 'string', 'exists:collections,id'],
        ]);

        try {
            $result = $this->collectionService->bulkMoveProducts(
                $request->input('product_ids'),
                $request->input('collection_id')
            );

            return $this->successResponse(
                $result,
                sprintf('%d produit(s) déplacé(s) avec succès', $result['updated'])
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }

    public function productsStatistics(string $id): JsonResponse
    {
        try {
            $stats = $this->collectionService->getProductsStatistics($id);

            return $this->successResponse(
                $stats,
                'Statistiques des produits récupérées avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Collection introuvable', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des statistiques',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }
}
