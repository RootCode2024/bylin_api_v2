<?php

namespace Modules\Catalogue\Console\Commands;

use Illuminate\Console\Command;
use Modules\Catalogue\Models\Brand;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DebugBrandMediaCommand extends Command
{
    protected $signature = 'brands:debug-media {brand_id? : ID de la marque (optionnel)}';
    protected $description = 'Débogue les des marques pour identifier les problèmes';

    public function handle(): int
    {
        $brandId = $this->argument('brand_id');

        if ($brandId) {
            $this->debugSingleBrand($brandId);
        } else {
            $this->debugAllBrands();
        }

        return self::SUCCESS;
    }

    private function debugSingleBrand(string $brandId): void
    {
        $this->info("Débogage de la marque : {$brandId}");
        $this->newLine();

        try {
            $brand = Brand::findOrFail($brandId);

            $this->info("Marque trouvée : {$brand->name}");
            $this->newLine();

            $this->info("Vérifications du modèle :");
            $this->line("   - Implémente HasMedia : " . ($brand instanceof \Spatie\MediaLibrary\HasMedia ? '✅' : '❌'));
            $this->line("   - Trait InteractsWithMedia : " . (method_exists($brand, 'registerMediaCollections') ? '✅' : '❌'));
            $this->newLine();

            $allMedia = Media::where('model_type', get_class($brand))
                ->where('model_id', $brand->id)->get();

            $this->info("Médias dans la base de données :");
            $this->line("   - Nombre total : " . $allMedia->count());

            if ($allMedia->isNotEmpty()) {
                $this->table(
                    ['ID', 'Collection', 'Nom', 'Taille', 'Créé le'],
                    $allMedia->map(fn($m) => [
                        $m->id,
                        $m->collection_name,
                        $m->file_name,
                        $this->formatBytes($m->size),
                        $m->created_at->format('d/m/Y H:i:s')
                    ])
                );
            } else {
                $this->warn("Aucun média trouvé dans la table 'media'");
            }
            $this->newLine();

            $this->info("Médias via relation Spatie :");
            $mediaViaRelation = $brand->getMedia('logo');
            $this->line("   - Nombre via getMedia('logo') : " . $mediaViaRelation->count());

            if ($mediaViaRelation->isNotEmpty()) {
                foreach ($mediaViaRelation as $media) {
                    $this->line("   - URL : " . $media->getUrl());
                    $this->line("   - Chemin : " . $media->getPath());
                    $this->line("   - Existe sur disque : " . (file_exists($media->getPath()) ? '✅' : '❌'));
                }
            }
            $this->newLine();

            $this->info("Accessor logo_url :");
            $this->line("   - Valeur : " . ($brand->logo_url ?? 'null'));
            $this->newLine();

            $this->info("Collections enregistrées :");
            try {
                $brand->registerMediaCollections();
                $this->line("   Méthode registerMediaCollections() exécutée sans erreur");
            } catch (\Exception $e) {
                $this->error("Erreur : " . $e->getMessage());
            }
            $this->newLine();

            $this->info("⚡ Test eager loading :");
            $brandWithMedia = Brand::with('media')->find($brandId);
            $this->line("   - Médias chargés : " . $brandWithMedia->media->count());
            $this->line("   - logo_url : " . ($brandWithMedia->logo_url ?? 'null'));
        } catch (\Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
            $this->error("Trace : " . $e->getTraceAsString());
        }
    }

    private function debugAllBrands(): void
    {
        $this->info("Débogage de toutes les marques");
        $this->newLine();

        $brands = Brand::all();
        $this->info("Nombre total de marques : " . $brands->count());
        $this->newLine();

        $brandsWithMedia = [];
        $brandsWithoutMedia = [];

        foreach ($brands as $brand) {
            $mediaCount = $brand->getMedia('logo')->count();

            if ($mediaCount > 0) {
                $brandsWithMedia[] = [
                    $brand->id,
                    $brand->name,
                    $mediaCount,
                    $brand->logo_url ?? 'null'
                ];
            } else {
                $brandsWithoutMedia[] = [
                    $brand->id,
                    $brand->name,
                    '0',
                    'null'
                ];
            }
        }

        if (!empty($brandsWithMedia)) {
            $this->info("Marques AVEC logos :");
            $this->table(
                ['ID', 'Nom', 'Nb médias', 'logo_url'],
                $brandsWithMedia
            );
            $this->newLine();
        }

        if (!empty($brandsWithoutMedia)) {
            $this->warn("Marques SANS logos :");
            $this->table(
                ['ID', 'Nom', 'Nb médias', 'logo_url'],
                $brandsWithoutMedia
            );
        }

        $this->newLine();
        $this->info("Statistiques :");
        $this->line("   - Marques avec logo : " . count($brandsWithMedia));
        $this->line("   - Marques sans logo : " . count($brandsWithoutMedia));

        $totalMediaInDb = Media::where('model_type', Brand::class)->count();
        $this->line("   - Total médias en DB : " . $totalMediaInDb);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';

        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
