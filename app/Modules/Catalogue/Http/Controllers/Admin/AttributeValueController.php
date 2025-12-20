<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\AttributeValue;
use Modules\Catalogue\Services\AttributeService;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Catalogue\Http\Requests\StoreAttributeValueRequest;
use Modules\Catalogue\Http\Requests\UpdateAttributeValueRequest;

/**
 * Contrôleur de gestion des valeurs d'attributs
 *
 * Gère les opérations CRUD pour les valeurs d'attributs
 * (ex: "Rouge", "Bleu", "Vert" pour l'attribut "Couleur")
 */
class AttributeValueController extends ApiController
{
    /**
     * Constructeur du contrôleur
     *
     * @param AttributeService $attributeService Service de gestion des attributs
     */
    public function __construct(
        private AttributeService $attributeService
    ) {}

    /**
     * Liste toutes les valeurs d'un attribut
     *
     * @param string $attributeId ID de l'attribut
     * @param Request $request Requête HTTP
     * @return JsonResponse Liste des valeurs
     */
    public function index(string $attributeId, Request $request): JsonResponse
    {
        $query = AttributeValue::where('attribute_id', $attributeId);

        // Recherche
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('value', 'like', '%' . $request->search . '%')
                    ->orWhere('label', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $query->orderBy('sort_order')->orderBy('value');

        $values = $query->paginate($request->per_page ?? 50);

        return $this->successResponse($values);
    }

    /**
     * Crée une nouvelle valeur d'attribut
     *
     * @param StoreAttributeValueRequest $request Requête validée
     * @return JsonResponse Valeur créée
     */
    public function store(StoreAttributeValueRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $value = AttributeValue::create($validated);

        return $this->createdResponse($value, 'Valeur d\'attribut créée avec succès');
    }

    /**
     * Affiche une valeur d'attribut spécifique
     *
     * @param string $id ID de la valeur
     * @return JsonResponse Détails de la valeur
     */
    public function show(string $id): JsonResponse
    {
        $value = AttributeValue::with('attribute')->findOrFail($id);
        return $this->successResponse($value);
    }

    /**
     * Met à jour une valeur d'attribut
     *
     * @param UpdateAttributeValueRequest $request Requête validée
     * @param string $id ID de la valeur
     * @return JsonResponse Valeur mise à jour
     */
    public function update(UpdateAttributeValueRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $value = AttributeValue::findOrFail($id);
        $value->update($validated);

        return $this->successResponse($value, 'Valeur d\'attribut mise à jour avec succès');
    }

    /**
     * Supprime une valeur d'attribut
     *
     * @param string $id ID de la valeur
     * @return JsonResponse Message de confirmation
     */
    public function destroy(string $id): JsonResponse
    {
        $value = AttributeValue::findOrFail($id);

        // Vérifier si la valeur est utilisée par des produits
        $productsCount = $value->attribute->products()
            ->wherePivot('attribute_value_id', $id)
            ->count();

        if ($productsCount > 0) {
            return $this->errorResponse(
                'Impossible de supprimer cette valeur car elle est utilisée par ' . $productsCount . ' produit(s)',
                422
            );
        }

        $value->delete();

        return $this->successResponse(null, 'Valeur d\'attribut supprimée avec succès');
    }

    /**
     * Réorganise les valeurs d'attributs
     *
     * @param Request $request Requête contenant l'ordre des valeurs
     * @return JsonResponse Message de confirmation
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|string|exists:attribute_values,id',
            'orders.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($validated['orders'] as $order) {
            AttributeValue::where('id', $order['id'])
                ->update(['sort_order' => $order['sort_order']]);
        }

        return $this->successResponse(null, 'Ordre mis à jour avec succès');
    }

    /**
     * Suppression en masse de valeurs
     *
     * @param Request $request Requête contenant les IDs
     * @return JsonResponse Message de confirmation
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:attribute_values,id'
        ]);

        // Vérifier si les valeurs sont utilisées
        $usedValues = AttributeValue::whereIn('id', $validated['ids'])
            ->whereHas('attribute.products', function ($q) use ($validated) {
                $q->whereIn('attribute_value_id', $validated['ids']);
            })
            ->count();

        if ($usedValues > 0) {
            return $this->errorResponse(
                'Certaines valeurs sont utilisées par des produits et ne peuvent pas être supprimées',
                422
            );
        }

        AttributeValue::whereIn('id', $validated['ids'])->delete();

        return $this->successResponse(
            null,
            count($validated['ids']) . ' valeur(s) supprimée(s) avec succès'
        );
    }
}
