<?php

declare(strict_types=1);

namespace Modules\Catalogue\Services;

use Illuminate\Support\Str;
use Modules\Catalogue\Models\Brand;
use Modules\Core\Services\BaseService;
use Illuminate\Support\Facades\Storage;

/**
 * Service de gestion des marques (Brand)
 *
 * Fournit les fonctionnalités suivantes :
 * - Création, mise à jour et suppression de marques
 * - Gestion des logos sur le disque
 * - Génération de slugs uniques pour les marques
 *
 * @package Modules\Catalogue\Services
 */
class BrandService extends BaseService
{
    /**
     * Crée une nouvelle marque.
     *
     * @param array $data Données de la marque (name, logo, etc.)
     * @return Brand La marque créée
     *
     * @example
     * $brandService->createBrand([
     *     'name' => 'Ma Marque',
     *     'logo' => UploadedFile $logo
     * ]);
     */
    public function createBrand(array $data): Brand
    {
        return $this->transaction(function () use ($data) {

            $data['slug'] = $this->generateUniqueSlug($data['name']);

            // Gestion de l'upload du logo
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                $logoPath = $data['logo']->store('brands/logos', 'public');
                $data['logo'] = $logoPath;
            }

            $brand = Brand::create($data);

            $this->logInfo('Marque créée', ['brand_id' => $brand->id]);

            return $brand;
        });
    }

    /**
     * Met à jour une marque existante.
     *
     * @param string $id ID de la marque
     * @param array $data Données à mettre à jour (name, logo, etc.)
     * @return Brand La marque mise à jour
     *
     * @example
     * $brandService->updateBrand($id, [
     *     'name' => 'Nouveau nom',
     *     'logo' => UploadedFile $logo
     * ]);
     */
    public function updateBrand(string $id, array $data): Brand
    {
        return $this->transaction(function () use ($id, $data) {
            $brand = Brand::findOrFail($id);

            // Gestion du slug si le nom change
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $brand->id);
            }

            // Gestion de l'upload du logo
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                // Supprimetion de l'ancien logo
                $this->deleteBrandLogo($brand);

                // Upload du nouveau logo
                $data['logo'] = $data['logo']->store(
                    config('catalogue.brand_logo_path', 'brands/logos'),
                    'public'
                );
            }

            $brand->update($data);

            $this->logInfo('Brand updated', ['brand_id' => $brand->id]);

            return $brand;
        });
    }

    /**
     * Supprime une marque.
     *
     * @param string $id ID de la marque
     * @return bool Succès de l'opération
     */
    public function deleteBrand(string $id): bool
    {
        return $this->transaction(function () use ($id) {
            $brand = Brand::findOrFail($id);

            $brand->delete();

            $this->logInfo('Brand deleted', ['brand_id' => $id]);

            return true;
        });
    }

    /**
     * Génère un slug unique pour la table brands.
     *
     * @param string $name Nom de la marque
     * @param int|null $ignoreId ID à ignorer (utile pour update)
     * @return string Slug unique
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Brand::where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Supprime le logo d'une marque du disque.
     *
     * @param Brand $brand La marque dont le logo sera supprimé
     */
    public function deleteBrandLogo(Brand $brand): void
    {
        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
            $this->logInfo('Logo deleted from disk', [
                'brand_id' => $brand->id,
                'logo' => $brand->logo
            ]);
        }
    }
}
