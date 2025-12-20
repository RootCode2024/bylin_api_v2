<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Modules\Catalogue\Models\Attribute;
use Modules\Catalogue\Models\AttributeValue;
use Modules\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Attribute Service
 *
 * Gère la logique métier pour les attributs produits et leurs valeurs
 * (Couleur, Taille, Matière, etc.)
 */
class AttributeService extends BaseService
{
    /**
     * Crée un nouvel attribut avec ses valeurs
     *
     * @param array $data Données de l'attribut
     * @return Attribute
     */
    public function createAttribute(array $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            // Extraire les valeurs avant de créer l'attribut
            $values = $data['values'] ?? [];
            unset($data['values']);

            // Créer l'attribut
            $attribute = Attribute::create($data);

            // Créer les valeurs associées
            if (!empty($values)) {
                foreach ($values as $index => $valueData) {
                    // Définir l'ordre si non spécifié
                    if (!isset($valueData['sort_order'])) {
                        $valueData['sort_order'] = $index;
                    }

                    // Définir le label si non spécifié
                    if (!isset($valueData['label']) && isset($valueData['value'])) {
                        $valueData['label'] = $valueData['value'];
                    }

                    $attribute->values()->create($valueData);
                }
            }

            $this->logInfo('Attribute created', [
                'attribute_id' => $attribute->id,
                'name' => $attribute->name,
                'values_count' => count($values)
            ]);

            return $attribute->load('values');
        });
    }

    /**
     * Met à jour un attribut existant
     *
     * @param string $id ID de l'attribut
     * @param array $data Données à mettre à jour
     * @return Attribute
     */
    public function updateAttribute(string $id, array $data): Attribute
    {
        return DB::transaction(function () use ($id, $data) {
            $attribute = Attribute::findOrFail($id);

            // Extraire les valeurs avant de mettre à jour l'attribut
            $values = $data['values'] ?? null;
            unset($data['values']);

            // Mettre à jour l'attribut
            $attribute->update($data);

            // Gérer les valeurs si fournies
            if ($values !== null) {
                $this->syncAttributeValues($attribute, $values);
            }

            $this->logInfo('Attribute updated', [
                'attribute_id' => $attribute->id,
                'name' => $attribute->name
            ]);

            return $attribute->load('values');
        });
    }

    /**
     * Synchronise les valeurs d'un attribut
     *
     * @param Attribute $attribute
     * @param array $values
     * @return void
     */
    protected function syncAttributeValues(Attribute $attribute, array $values): void
    {
        foreach ($values as $index => $valueData) {
            if (isset($valueData['id'])) {
                // Mettre à jour une valeur existante
                $value = AttributeValue::find($valueData['id']);
                if ($value && $value->attribute_id === $attribute->id) {
                    $value->update($valueData);
                }
            } else {
                // Créer une nouvelle valeur
                if (!isset($valueData['sort_order'])) {
                    $valueData['sort_order'] = $index;
                }

                if (!isset($valueData['label']) && isset($valueData['value'])) {
                    $valueData['label'] = $valueData['value'];
                }

                $attribute->values()->create($valueData);
            }
        }
    }

    /**
     * Supprime un attribut (soft delete)
     *
     * @param string $id ID de l'attribut
     * @return bool
     */
    public function deleteAttribute(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $attribute = Attribute::findOrFail($id);

            // Vérifier si l'attribut est utilisé par des produits
            if ($attribute->products()->exists()) {
                throw new \Exception(
                    'Impossible de supprimer cet attribut car il est utilisé par des produits'
                );
            }

            $deleted = $attribute->delete();

            $this->logInfo('Attribute deleted', [
                'attribute_id' => $id,
                'name' => $attribute->name
            ]);

            return $deleted;
        });
    }

    /**
     * Crée une valeur d'attribut
     *
     * @param array $data Données de la valeur
     * @return AttributeValue
     */
    public function createAttributeValue(array $data): AttributeValue
    {
        return DB::transaction(function () use ($data) {
            // Définir le label si non spécifié
            if (!isset($data['label']) && isset($data['value'])) {
                $data['label'] = $data['value'];
            }

            $value = AttributeValue::create($data);

            $this->logInfo('Attribute value created', [
                'value_id' => $value->id,
                'attribute_id' => $value->attribute_id,
                'value' => $value->value
            ]);

            return $value;
        });
    }

    /**
     * Met à jour une valeur d'attribut
     *
     * @param string $id ID de la valeur
     * @param array $data Données à mettre à jour
     * @return AttributeValue
     */
    public function updateAttributeValue(string $id, array $data): AttributeValue
    {
        return DB::transaction(function () use ($id, $data) {
            $value = AttributeValue::findOrFail($id);
            $value->update($data);

            $this->logInfo('Attribute value updated', [
                'value_id' => $value->id,
                'value' => $value->value
            ]);

            return $value;
        });
    }

    /**
     * Supprime une valeur d'attribut
     *
     * @param string $id ID de la valeur
     * @return bool
     */
    public function deleteAttributeValue(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $value = AttributeValue::findOrFail($id);

            // Vérifier si la valeur est utilisée par des produits
            $productsCount = $value->attribute->products()
                ->wherePivot('attribute_value_id', $id)
                ->count();

            if ($productsCount > 0) {
                throw new \Exception(
                    "Impossible de supprimer cette valeur car elle est utilisée par {$productsCount} produit(s)"
                );
            }

            $deleted = $value->delete();

            $this->logInfo('Attribute value deleted', [
                'value_id' => $id,
                'value' => $value->value
            ]);

            return $deleted;
        });
    }
}
