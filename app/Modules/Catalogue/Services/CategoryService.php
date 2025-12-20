<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Modules\Catalogue\Models\Category;
use Modules\Core\Services\BaseService;
use Illuminate\Http\UploadedFile;

/**
 * Service de gestion des catégories
 *
 * Gère la logique métier pour les opérations CRUD sur les catégories,
 * incluant la gestion de la hiérarchie, des images et des validations.
 */
class CategoryService extends BaseService
{
    /**
     * Crée une nouvelle catégorie
     *
     * @param array $data Données de la catégorie
     * @return Category Catégorie créée
     * @throws \Exception Si erreur lors de la création
     */
    public function createCategory(array $data): Category
    {
        return $this->transaction(function () use ($data) {
            // Génération automatique du slug si non fourni
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            // Validation du parent si fourni
            if (!empty($data['parent_id'])) {
                $parent = Category::findOrFail($data['parent_id']);

                // Vérifier la profondeur maximale
                if ($parent->level >= 3) {
                    throw new \InvalidArgumentException(
                        'La profondeur maximale de catégories (4 niveaux) est atteinte.'
                    );
                }
            }

            // Gestion de l'image
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->uploadImage($data['image']);
            }

            // Création de la catégorie
            $category = Category::create($data);

            $this->logInfo('Catégorie créée', [
                'category_id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);

            return $category->fresh(['parent', 'children']);
        });
    }

    /**
     * Met à jour une catégorie existante
     *
     * @param string $id ID de la catégorie
     * @param array $data Données à mettre à jour
     * @return Category Catégorie mise à jour
     * @throws \Exception Si erreur lors de la mise à jour
     */
    public function updateCategory(string $id, array $data): Category
    {
        return $this->transaction(function () use ($id, $data) {
            $category = Category::findOrFail($id);

            // Régénération du slug si le nom change
            if (isset($data['name']) && $data['name'] !== $category->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }

            // Validation du parent
            if (isset($data['parent_id'])) {
                // Empêcher de se définir comme son propre parent
                if ($data['parent_id'] === $id) {
                    throw new \InvalidArgumentException(
                        'Une catégorie ne peut pas être son propre parent.'
                    );
                }

                // Empêcher de définir un enfant comme parent (éviter les boucles)
                if ($data['parent_id']) {
                    $newParent = Category::findOrFail($data['parent_id']);

                    if ($category->isAncestorOf($newParent)) {
                        throw new \InvalidArgumentException(
                            'Impossible de définir une sous-catégorie comme parent.'
                        );
                    }

                    // Vérifier la profondeur maximale
                    if ($newParent->level >= 3) {
                        throw new \InvalidArgumentException(
                            'La profondeur maximale de catégories est atteinte.'
                        );
                    }
                }
            }

            // Gestion de l'image
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Supprimer l'ancienne image
                if ($category->image) {
                    $this->deleteImage($category->image);
                }

                $data['image'] = $this->uploadImage($data['image']);
            }

            // Suppression de l'image si demandé
            if (!empty($data['remove_image']) && $category->image) {
                $this->deleteImage($category->image);
                $data['image'] = null;
            }

            // Mise à jour
            $category->update($data);

            $this->logInfo('Catégorie mise à jour', [
                'category_id' => $category->id,
                'changes' => array_keys($data),
            ]);

            return $category->fresh(['parent', 'children']);
        });
    }

    /**
     * Supprime une catégorie (soft delete)
     *
     * @param string $id ID de la catégorie
     * @return bool Succès de la suppression
     * @throws \Exception Si la catégorie a des enfants ou des produits
     */
    public function deleteCategory(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $category = Category::findOrFail($id);

            // Vérifier si la catégorie a des enfants
            if ($category->children()->count() > 0) {
                throw new \InvalidArgumentException(
                    'Impossible de supprimer une catégorie qui contient des sous-catégories. ' .
                        'Veuillez d\'abord supprimer ou déplacer les sous-catégories.'
                );
            }

            // Vérifier si la catégorie a des produits
            if ($category->products()->count() > 0) {
                throw new \InvalidArgumentException(
                    'Impossible de supprimer une catégorie qui contient des produits. ' .
                        'Veuillez d\'abord déplacer ou supprimer les produits.'
                );
            }

            $category->delete();

            $this->logInfo('Catégorie supprimée (soft delete)', [
                'category_id' => $id,
                'name' => $category->name,
            ]);

            return true;
        });
    }

    /**
     * Restaure une catégorie supprimée
     *
     * @param string $id ID de la catégorie
     * @return Category Catégorie restaurée
     */
    public function restoreCategory(string $id): Category
    {
        return $this->transaction(function () use ($id) {
            $category = Category::onlyTrashed()->findOrFail($id);
            $category->restore();

            $this->logInfo('Catégorie restaurée', [
                'category_id' => $id,
                'name' => $category->name,
            ]);

            return $category->fresh();
        });
    }

    /**
     * Supprime définitivement une catégorie
     *
     * @param string $id ID de la catégorie
     * @return bool Succès de la suppression
     */
    public function forceDeleteCategory(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $category = Category::withTrashed()->findOrFail($id);

            // Supprimer l'image si elle existe
            if ($category->image) {
                $this->deleteImage($category->image);
            }

            $category->forceDelete();

            $this->logInfo('Catégorie supprimée définitivement', [
                'category_id' => $id,
                'name' => $category->name,
            ]);

            return true;
        });
    }

    /**
     * Déplace une catégorie vers un nouveau parent
     *
     * @param string $id ID de la catégorie à déplacer
     * @param string|null $newParentId ID du nouveau parent (null pour racine)
     * @return Category Catégorie déplacée
     */
    public function moveCategory(string $id, ?string $newParentId): Category
    {
        return $this->transaction(function () use ($id, $newParentId) {
            $category = Category::findOrFail($id);

            // Ne peut pas se déplacer vers soi-même
            if ($newParentId === $id) {
                throw new \InvalidArgumentException(
                    'Une catégorie ne peut pas être déplacée vers elle-même.'
                );
            }

            // Validation du nouveau parent
            if ($newParentId) {
                $newParent = Category::findOrFail($newParentId);

                // Ne peut pas déplacer vers un de ses descendants
                if ($category->isAncestorOf($newParent)) {
                    throw new \InvalidArgumentException(
                        'Impossible de déplacer une catégorie vers une de ses sous-catégories.'
                    );
                }

                // Vérifier la profondeur maximale
                $newDepth = $newParent->level + 1 + $this->getMaxDepth($category);
                if ($newDepth > 3) {
                    throw new \InvalidArgumentException(
                        'Ce déplacement dépasserait la profondeur maximale autorisée.'
                    );
                }
            }

            $category->update(['parent_id' => $newParentId]);

            $this->logInfo('Catégorie déplacée', [
                'category_id' => $id,
                'old_parent_id' => $category->getOriginal('parent_id'),
                'new_parent_id' => $newParentId,
            ]);

            return $category->fresh(['parent', 'children']);
        });
    }

    /**
     * Réordonne les catégories
     *
     * @param array $order Tableau associatif [id => sort_order]
     * @return bool Succès du réordonnancement
     */
    public function reorderCategories(array $order): bool
    {
        return $this->transaction(function () use ($order) {
            foreach ($order as $id => $sortOrder) {
                Category::where('id', $id)->update(['sort_order' => $sortOrder]);
            }

            $this->logInfo('Catégories réordonnées', [
                'count' => count($order),
            ]);

            return true;
        });
    }

    // ============================================================================
    // MÉTHODES PRIVÉES
    // ============================================================================

    /**
     * Génère un slug unique
     *
     * @param string $name Nom de la catégorie
     * @param string|null $excludeId ID à exclure (pour update)
     * @return string Slug unique
     */
    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Category::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Upload une image
     *
     * @param UploadedFile $file Fichier à uploader
     * @return string Chemin du fichier uploadé
     */
    private function uploadImage(UploadedFile $file): string
    {
        return $file->store('categories', 'public');
    }

    /**
     * Supprime une image
     *
     * @param string $path Chemin de l'image
     * @return bool Succès de la suppression
     */
    private function deleteImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Calcule la profondeur maximale d'une catégorie et ses descendants
     *
     * @param Category $category Catégorie
     * @return int Profondeur maximale
     */
    private function getMaxDepth(Category $category): int
    {
        if ($category->children()->count() === 0) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($category->children as $child) {
            $depth = $this->getMaxDepth($child);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth + 1;
    }
}
