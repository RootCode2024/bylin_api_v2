<?php

namespace Modules\Catalogue\Observers;

use Modules\Catalogue\Models\Brand;
use Illuminate\Support\Facades\Storage;

/**
 * Observateur du modèle Brand
 *
 * Cet observateur écoute les événements du modèle Brand et exécute
 * des actions automatiques lors de certaines opérations.
 */
class BrandObserver
{
    /**
     * Gère l'événement "suppression définitive" d'une marque
     *
     * Cette méthode est appelée automatiquement avant qu'une marque
     * ne soit définitivement supprimée de la base de données.
     * Elle supprime le fichier logo associé du stockage si celui-ci existe.
     *
     * @param Brand $brand Instance de la marque en cours de suppression
     * @return void
     */
    public function forceDeleting(Brand $brand): void
    {
        // Vérifier si la marque possède un logo et si le fichier existe dans le stockage
        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            // Supprimer le fichier logo du disque de stockage public
            Storage::disk('public')->delete($brand->logo);
        }
    }
}
