<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Collection;
use Modules\Catalogue\Models\Brand;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. D'abord, s'assurer que la marque Bylin existe
        $bylinBrand = Brand::firstOrCreate(
            ['slug' => 'bylin'],
            [
                'name' => 'Bylin',
                'description' => 'Marque de vêtements authentiques avec système de vérification QR',
                'is_bylin_brand' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $this->command->info('✓ Marque Bylin créée/vérifiée');

        // 2. Créer des collections de test
        $collections = [
            [
                'name' => 'Urban Street Collection',
                'slug' => 'urban-street-collection',
                'description' => 'Collection streetwear urbaine avec des pièces audacieuses et modernes. Inspirée de la culture urbaine contemporaine.',
                'season' => 'Automne-Hiver 2024',
                'theme' => 'Streetwear Urbain',
                'release_date' => now()->subMonths(2),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'meta_title' => 'Urban Street Collection - Bylin',
                'meta_description' => 'Découvrez notre collection streetwear urbaine avec authentification QR',
                'meta_keywords' => ['streetwear', 'urban', 'bylin', 'authentique'],
            ],
            [
                'name' => 'Classic Essentials',
                'slug' => 'classic-essentials',
                'description' => 'Les essentiels intemporels pour un style classique et élégant. Des pièces basiques mais sophistiquées.',
                'season' => 'Printemps-Été 2024',
                'theme' => 'Classic',
                'release_date' => now()->subMonths(6),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'meta_title' => 'Classic Essentials - Bylin',
                'meta_description' => 'Collection d\'essentiels classiques avec garantie d\'authenticité',
                'meta_keywords' => ['classic', 'essentials', 'bylin', 'basiques'],
            ],
            [
                'name' => 'Summer Vibes 2025',
                'slug' => 'summer-vibes-2025',
                'description' => 'Collection estivale colorée et légère pour les journées ensoleillées. Confort et style garantis.',
                'season' => 'Été 2025',
                'theme' => 'Summer',
                'release_date' => now()->addMonths(3),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'meta_title' => 'Summer Vibes 2025 - Bylin',
                'meta_description' => 'Collection été 2025 avec codes QR d\'authenticité',
                'meta_keywords' => ['été', 'summer', 'bylin', '2025'],
            ],
            [
                'name' => 'Winter Premium',
                'slug' => 'winter-premium',
                'description' => 'Collection premium pour l\'hiver avec des matériaux de haute qualité. Chaleur et élégance.',
                'season' => 'Hiver 2024-2025',
                'theme' => 'Premium',
                'release_date' => now()->subMonths(1),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'meta_title' => 'Winter Premium Collection - Bylin',
                'meta_description' => 'Collection hiver premium avec authentification garantie',
                'meta_keywords' => ['hiver', 'winter', 'premium', 'bylin'],
            ],
            [
                'name' => 'Limited Edition Drop',
                'slug' => 'limited-edition-drop',
                'description' => 'Édition limitée exclusive avec seulement 100 pièces par article. Rareté et authenticité garanties.',
                'season' => 'Automne 2024',
                'theme' => 'Limited Edition',
                'release_date' => now()->subWeeks(2),
                'end_date' => now()->addMonth(),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 0,
                'meta_title' => 'Limited Edition Drop - Bylin',
                'meta_description' => 'Édition limitée exclusive avec codes d\'authenticité uniques',
                'meta_keywords' => ['limited edition', 'exclusif', 'bylin', 'rare'],
            ],
            [
                'name' => 'Archive Collection 2023',
                'slug' => 'archive-collection-2023',
                'description' => 'Collection archivée de 2023. Pièces vintage et collector.',
                'season' => 'Archive 2023',
                'theme' => 'Vintage',
                'release_date' => now()->subYear(),
                'end_date' => now()->subMonths(3),
                'is_active' => false,
                'is_featured' => false,
                'sort_order' => 99,
                'meta_title' => 'Archive Collection 2023 - Bylin',
                'meta_description' => 'Collection archivée 2023',
                'meta_keywords' => ['archive', 'vintage', '2023', 'bylin'],
            ],
        ];

        foreach ($collections as $collectionData) {
            $collection = Collection::create($collectionData);

            $this->command->info("✓ Collection créée : {$collection->name}");
        }

        // 3. Afficher le résumé
        $totalCollections = Collection::count();
        $activeCollections = Collection::active()->count();
        $featuredCollections = Collection::featured()->count();

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  RÉSUMÉ DU SEEDING - COLLECTIONS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("Total collections : {$totalCollections}");
        $this->command->info("Collections actives : {$activeCollections}");
        $this->command->info("Collections en vedette : {$featuredCollections}");
        $this->command->newLine();
        $this->command->info('✓ Seeding terminé avec succès !');
    }
}
