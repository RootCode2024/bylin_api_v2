<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Catalogue\Models\ProductVariation;
use Modules\Catalogue\Models\Product;
use Modules\Core\Http\Controllers\ApiController;

/**
 * Contrôleur de gestion des variations de produits
 *
 * Gère les variations de produits (ex: chemise avec différentes couleurs)
 */
class ProductVariationController extends ApiController
{
    /**
     * Liste toutes les variations d'un produit
     *
     * @param string $productId ID du produit
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $productId, Request $request): JsonResponse
    {
        $query = ProductVariation::where('product_id', $productId);

        // Filtrer par statut actif
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Inclure les suppressions si demandé
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $variations = $query->orderBy('variation_name')->get();

        return $this->successResponse($variations);
    }

    /**
     * Crée une nouvelle variation de produit
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'sku' => 'required|string|unique:product_variations,sku',
            'variation_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'stock_quantity' => 'required|integer|min:0',
            'barcode' => 'nullable|string',
            'is_active' => 'boolean',
            'attributes' => 'required|array', // Ex: {"color": ["rouge", "blanc", "noir"]}
            'attributes.*' => 'array'
        ]);

        $variation = ProductVariation::create($validated);

        // Mettre à jour le stock total du produit parent
        $this->updateParentProductStock($validated['product_id']);

        return $this->createdResponse(
            $variation->load('product'),
            'Variation créée avec succès'
        );
    }

    /**
     * Affiche une variation spécifique
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $variation = ProductVariation::with('product')->findOrFail($id);
        return $this->successResponse($variation);
    }

    /**
     * Met à jour une variation
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $variation = ProductVariation::findOrFail($id);

        $validated = $request->validate([
            'sku' => 'sometimes|string|unique:product_variations,sku,' . $id,
            'variation_name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'barcode' => 'nullable|string',
            'is_active' => 'boolean',
            'attributes' => 'sometimes|array',
            'attributes.*' => 'array'
        ]);

        $variation->update($validated);

        // Mettre à jour le stock total du produit parent
        $this->updateParentProductStock($variation->product_id);

        return $this->successResponse(
            $variation->load('product'),
            'Variation mise à jour avec succès'
        );
    }

    /**
     * Supprime une variation (soft delete)
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $variation = ProductVariation::findOrFail($id);
        $productId = $variation->product_id;

        $variation->delete();

        // Mettre à jour le stock total du produit parent
        $this->updateParentProductStock($productId);

        return $this->successResponse(null, 'Variation supprimée avec succès');
    }

    /**
     * Restaure une variation supprimée
     *
     * @param string $id
     * @return JsonResponse
     */
    public function restore(string $id): JsonResponse
    {
        $variation = ProductVariation::onlyTrashed()->findOrFail($id);
        $variation->restore();

        // Mettre à jour le stock total du produit parent
        $this->updateParentProductStock($variation->product_id);

        return $this->successResponse($variation, 'Variation restaurée avec succès');
    }

    /**
     * Met à jour le stock d'une variation
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateStock(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'operation' => 'nullable|in:set,add,subtract' // set (définir), add (ajouter), subtract (retirer)
        ]);

        $variation = ProductVariation::findOrFail($id);
        $operation = $validated['operation'] ?? 'set';

        switch ($operation) {
            case 'add':
                $variation->stock_quantity += $validated['stock_quantity'];
                break;
            case 'subtract':
                $variation->stock_quantity = max(0, $variation->stock_quantity - $validated['stock_quantity']);
                break;
            default: // 'set'
                $variation->stock_quantity = $validated['stock_quantity'];
        }

        $variation->save();

        // Mettre à jour le stock total du produit parent
        $this->updateParentProductStock($variation->product_id);

        return $this->successResponse(
            $variation,
            'Stock mis à jour avec succès'
        );
    }

    /**
     * Récupère les variations par attribut (ex: toutes les couleurs disponibles)
     *
     * @param string $productId
     * @param Request $request
     * @return JsonResponse
     */
    public function getByAttribute(string $productId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attribute_code' => 'required|string' // Ex: 'color'
        ]);

        $variations = ProductVariation::where('product_id', $productId)
            ->where('is_active', true)
            ->get();

        // Grouper par valeur d'attribut
        $grouped = $variations->map(function ($variation) use ($validated) {
            $attributeCode = $validated['attribute_code'];
            $colors = $variation->attributes[$attributeCode] ?? [];

            return [
                'id' => $variation->id,
                'name' => $variation->variation_name,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'stock' => $variation->stock_quantity,
                'colors' => $colors,
                'is_multicolor' => count($colors) > 1
            ];
        });

        return $this->successResponse([
            'product_id' => $productId,
            'attribute' => $validated['attribute_code'],
            'variations' => $grouped
        ]);
    }

    /**
     * Met à jour le stock total du produit parent
     *
     * @param string $productId
     * @return void
     */
    protected function updateParentProductStock(string $productId): void
    {
        $totalStock = ProductVariation::where('product_id', $productId)
            ->where('is_active', true)
            ->sum('stock_quantity');

        Product::where('id', $productId)->update([
            'stock_quantity' => $totalStock
        ]);
    }
}
