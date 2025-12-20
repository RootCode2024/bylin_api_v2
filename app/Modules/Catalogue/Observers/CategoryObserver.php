<?php

namespace Modules\Catalogue\Observers;

use Modules\Catalogue\Models\Category;
use Illuminate\Support\Facades\Storage;

/**
 * Observateur du modèle Category
 *
 * Gère automatiquement certaines opérations lors des événements
 * du cycle de vie des catégories, notamment la suppression des images.
 */
class CategoryObserver
{
    /**
     * Gère l'événement "suppression définitive" d'une catégorie
     *
     * Cette méthode est appelée automatiquement avant qu'une catégorie
     * ne soit définitivement supprimée de la base de données.
     * Elle supprime l'image associée du stockage si celle-ci existe.
     *
     * @param Category $category Instance de la catégorie en cours de suppression
     * @return void
     */
    public function forceDeleting(Category $category): void
    {
        // Vérifier si la catégorie possède une image et si le fichier existe
        if ($category->image && Storage::disk('public')->exists($category->image)) {
            // Supprimer le fichier image du disque de stockage public
            Storage::disk('public')->delete($category->image);
        }
    }

    /**
     * Gère l'événement "mise à jour" d'une catégorie
     *
     * Supprime l'ancienne image si une nouvelle est téléchargée
     *
     * @param Category $category Instance de la catégorie en cours de mise à jour
     * @return void
     */
    public function updating(Category $category): void
    {
        // Vérifier si l'image a été modifiée
        if ($category->isDirty('image') && $category->getOriginal('image')) {
            $oldImage = $category->getOriginal('image');

            // Supprimer l'ancienne image si elle existe
            if (Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
        }
    }
}
