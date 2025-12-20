<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Category;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Seeder complet du catalogue
 *
 * Crée une structure complète de données pour un e-commerce de vêtements :
 * - Marques (incluant Bylin)
 * - Hiérarchie de catégories (4 niveaux)
 * - Attributs (tailles, couleurs, etc.)
 * - Produits avec variations
 */
class CatalogueSeeder extends Seeder
{
    private \Faker\Generator $faker;

    /**
     * Exécute le seeder
     */
    public function run(): void
    {
        $this->faker = \Faker\Factory::create('fr_FR');

        DB::transaction(function () {
            $this->command->info('🚀 Début du seeding du catalogue...');

            // 1. Créer les marques
            $this->command->info('📦 Création des marques...');
            $brands = $this->createBrands();
            $this->command->info('✓ ' . $brands->count() . ' marques créées');

            // 2. Créer la hiérarchie de catégories
            $this->command->info('📁 Création de la hiérarchie de catégories...');
            $categories = $this->createCategories();
            $this->command->info('✓ ' . $categories->count() . ' catégories créées');

            // 3. Créer les attributs
            $this->command->info('🎨 Création des attributs...');
            $attributes = $this->createAttributes();
            $this->command->info('✓ ' . $attributes->count() . ' attributs créés');

            // 4. Créer les produits
            $this->command->info('👕 Création des produits...');
            $this->createProducts($brands, $categories, $attributes);
            $this->command->info('✓ Produits créés avec succès');

            $this->command->info('✨ Seeding du catalogue terminé avec succès !');
        });
    }

    /**
     * Crée les marques
     */
    private function createBrands(): \Illuminate\Support\Collection
    {
        $brandsData = [
            [
                'name' => 'Bylin',
                'slug' => 'bylin',
                'description' => 'Notre marque exclusive de vêtements premium pour toute la famille. Qualité, style et confort réunis.',
                'website' => 'https://bylin.com',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Leader mondial des équipements sportifs. Just Do It.',
                'website' => 'https://nike.com',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Marque sportswear allemande reconnue mondialement. Impossible is Nothing.',
                'website' => 'https://adidas.com',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Fast fashion espagnole, tendances actuelles à prix accessibles.',
                'website' => 'https://zara.com',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'description' => 'Mode et qualité au meilleur prix, pour toute la famille.',
                'website' => 'https://hm.com',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Uniqlo',
                'slug' => 'uniqlo',
                'description' => 'LifeWear japonais : simplicité, qualité et fonctionnalité.',
                'website' => 'https://uniqlo.com',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Gucci',
                'slug' => 'gucci',
                'description' => 'Luxe italien, créativité et savoir-faire depuis 1921.',
                'website' => 'https://gucci.com',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Levi\'s',
                'slug' => 'levis',
                'description' => 'L\'inventeur du jean. Qualité et authenticité américaine.',
                'website' => 'https://levis.com',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        return collect($brandsData)->map(function ($data) {
            return Brand::create($data);
        });
    }

    /**
     * Crée la hiérarchie de catégories
     */
    private function createCategories(): \Illuminate\Support\Collection
    {
        $categories = collect();

        // ========================================
        // NIVEAU 0 : GENRES
        // ========================================

        $homme = $this->createCategory([
            'name' => 'Homme',
            'slug' => 'homme',
            'description' => 'Mode masculine : vêtements, chaussures et accessoires',
            'icon' => 'mars',
            'is_visible_in_menu' => true,
            'sort_order' => 1,
        ]);
        $categories->push($homme);

        $femme = $this->createCategory([
            'name' => 'Femme',
            'slug' => 'femme',
            'description' => 'Mode féminine : vêtements, chaussures et accessoires',
            'icon' => 'venus',
            'is_visible_in_menu' => true,
            'sort_order' => 2,
        ]);
        $categories->push($femme);

        $enfant = $this->createCategory([
            'name' => 'Enfant',
            'slug' => 'enfant',
            'description' => 'Mode enfantine pour garçons et filles',
            'icon' => 'baby',
            'is_visible_in_menu' => true,
            'sort_order' => 3,
        ]);
        $categories->push($enfant);

        $mixte = $this->createCategory([
            'name' => 'Mixte',
            'slug' => 'mixte',
            'description' => 'Vêtements unisexe et accessoires pour tous',
            'icon' => 'users',
            'is_visible_in_menu' => true,
            'sort_order' => 4,
        ]);
        $categories->push($mixte);

        // ========================================
        // NIVEAU 1 : TYPES (HOMME)
        // ========================================

        $hommeHauts = $this->createCategory([
            'parent_id' => $homme->id,
            'name' => 'Hauts',
            'slug' => 'homme-hauts',
            'description' => 'T-shirts, chemises, pulls et sweats pour homme',
            'sort_order' => 1,
        ]);
        $categories->push($hommeHauts);

        $hommeBas = $this->createCategory([
            'parent_id' => $homme->id,
            'name' => 'Bas',
            'slug' => 'homme-bas',
            'description' => 'Pantalons, jeans et shorts pour homme',
            'sort_order' => 2,
        ]);
        $categories->push($hommeBas);

        $hommeExterieur = $this->createCategory([
            'parent_id' => $homme->id,
            'name' => 'Vêtements d\'extérieur',
            'slug' => 'homme-exterieur',
            'description' => 'Vestes, manteaux et blousons pour homme',
            'sort_order' => 3,
        ]);
        $categories->push($hommeExterieur);

        $hommeChaussures = $this->createCategory([
            'parent_id' => $homme->id,
            'name' => 'Chaussures',
            'slug' => 'homme-chaussures',
            'description' => 'Chaussures de ville, sport et casual pour homme',
            'sort_order' => 4,
        ]);
        $categories->push($hommeChaussures);

        $hommeAccessoires = $this->createCategory([
            'parent_id' => $homme->id,
            'name' => 'Accessoires',
            'slug' => 'homme-accessoires',
            'description' => 'Ceintures, casquettes, écharpes et plus',
            'sort_order' => 5,
        ]);
        $categories->push($hommeAccessoires);

        // ========================================
        // NIVEAU 2 : CATÉGORIES (HOMME > HAUTS)
        // ========================================

        $hommeHautsCategories = [
            ['name' => 'T-shirts', 'desc' => 'T-shirts manches courtes et longues'],
            ['name' => 'Chemises', 'desc' => 'Chemises habillées et casual'],
            ['name' => 'Polos', 'desc' => 'Polos sport et élégants'],
            ['name' => 'Sweats & Hoodies', 'desc' => 'Sweats à capuche et pulls molletonnés'],
            ['name' => 'Pulls', 'desc' => 'Pulls en laine, cachemire et coton'],
        ];

        foreach ($hommeHautsCategories as $index => $cat) {
            $categories->push($this->createCategory([
                'parent_id' => $hommeHauts->id,
                'name' => $cat['name'],
                'slug' => 'homme-hauts-' . Str::slug($cat['name']),
                'description' => $cat['desc'],
                'sort_order' => $index + 1,
            ]));
        }

        // ========================================
        // NIVEAU 2 : CATÉGORIES (HOMME > BAS)
        // ========================================

        $hommeBasCategories = [
            ['name' => 'Jeans', 'desc' => 'Jeans slim, regular et relaxed'],
            ['name' => 'Pantalons', 'desc' => 'Pantalons chino et habillés'],
            ['name' => 'Shorts', 'desc' => 'Shorts sport et casual'],
            ['name' => 'Joggings', 'desc' => 'Pantalons de sport confortables'],
        ];

        foreach ($hommeBasCategories as $index => $cat) {
            $categories->push($this->createCategory([
                'parent_id' => $hommeBas->id,
                'name' => $cat['name'],
                'slug' => 'homme-bas-' . Str::slug($cat['name']),
                'description' => $cat['desc'],
                'sort_order' => $index + 1,
            ]));
        }

        // ========================================
        // NIVEAU 1 : TYPES (FEMME)
        // ========================================

        $femmeHauts = $this->createCategory([
            'parent_id' => $femme->id,
            'name' => 'Hauts',
            'slug' => 'femme-hauts',
            'description' => 'Tops, chemisiers et pulls pour femme',
            'sort_order' => 1,
        ]);
        $categories->push($femmeHauts);

        $femmeBas = $this->createCategory([
            'parent_id' => $femme->id,
            'name' => 'Bas',
            'slug' => 'femme-bas',
            'description' => 'Pantalons, jeans et jupes pour femme',
            'sort_order' => 2,
        ]);
        $categories->push($femmeBas);

        $femmeRobes = $this->createCategory([
            'parent_id' => $femme->id,
            'name' => 'Robes',
            'slug' => 'femme-robes',
            'description' => 'Robes courtes, longues et de soirée',
            'sort_order' => 3,
        ]);
        $categories->push($femmeRobes);

        $femmeChaussures = $this->createCategory([
            'parent_id' => $femme->id,
            'name' => 'Chaussures',
            'slug' => 'femme-chaussures',
            'description' => 'Chaussures, bottines et sandales pour femme',
            'sort_order' => 4,
        ]);
        $categories->push($femmeChaussures);

        $femmeAccessoires = $this->createCategory([
            'parent_id' => $femme->id,
            'name' => 'Accessoires & Bijoux',
            'slug' => 'femme-accessoires-bijoux',
            'description' => 'Sacs, bijoux et accessoires mode',
            'sort_order' => 5,
        ]);
        $categories->push($femmeAccessoires);

        // ========================================
        // NIVEAU 2 : CATÉGORIES (FEMME > HAUTS)
        // ========================================

        $femmeHautsCategories = [
            ['name' => 'T-shirts', 'desc' => 'T-shirts basiques et imprimés'],
            ['name' => 'Chemisiers', 'desc' => 'Chemisiers élégants'],
            ['name' => 'Tops', 'desc' => 'Tops et débardeurs'],
            ['name' => 'Pulls', 'desc' => 'Pulls doux et chauds'],
            ['name' => 'Blouses', 'desc' => 'Blouses fluides et légères'],
        ];

        foreach ($femmeHautsCategories as $index => $cat) {
            $categories->push($this->createCategory([
                'parent_id' => $femmeHauts->id,
                'name' => $cat['name'],
                'slug' => 'femme-hauts-' . Str::slug($cat['name']),
                'description' => $cat['desc'],
                'sort_order' => $index + 1,
            ]));
        }

        // ========================================
        // NIVEAU 2 : CATÉGORIES (FEMME > ROBES)
        // ========================================

        $femmeRobesCategories = [
            ['name' => 'Robes courtes', 'desc' => 'Robes au-dessus du genou'],
            ['name' => 'Robes longues', 'desc' => 'Robes maxi et midi'],
            ['name' => 'Robes de soirée', 'desc' => 'Robes élégantes pour occasions'],
        ];

        foreach ($femmeRobesCategories as $index => $cat) {
            $categories->push($this->createCategory([
                'parent_id' => $femmeRobes->id,
                'name' => $cat['name'],
                'slug' => 'femme-robes-' . Str::slug($cat['name']),
                'description' => $cat['desc'],
                'sort_order' => $index + 1,
            ]));
        }

        // ========================================
        // NIVEAU 2 : CATÉGORIES (FEMME > ACCESSOIRES)
        // ========================================

        $femmeAccessoiresCategories = [
            ['name' => 'Sacs à main', 'desc' => 'Sacs, pochettes et cabas'],
            ['name' => 'Bijoux', 'desc' => 'Colliers, bracelets et boucles d\'oreilles'],
            ['name' => 'Foulards & Écharpes', 'desc' => 'Accessoires de cou'],
            ['name' => 'Montres', 'desc' => 'Montres mode et classiques'],
        ];

        foreach ($femmeAccessoiresCategories as $index => $cat) {
            $categories->push($this->createCategory([
                'parent_id' => $femmeAccessoires->id,
                'name' => $cat['name'],
                'slug' => $femmeAccessoires->slug . '-' . Str::slug($cat['name']),
                'description' => $cat['desc'],
                'sort_order' => $index + 1,
            ]));
        }

        // ========================================
        // CATÉGORIES SPÉCIALES (TRANSVERSALES)
        // ========================================

        $categories->push($this->createCategory([
            'name' => 'Nouveautés',
            'slug' => 'nouveautes',
            'description' => 'Les dernières arrivées de toutes nos collections',
            'icon' => 'sparkles',
            'is_featured' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 100,
        ]));

        $categories->push($this->createCategory([
            'name' => 'Promotions',
            'slug' => 'promotions',
            'description' => 'Nos meilleures offres et réductions',
            'icon' => 'tag',
            'color' => '#FF0000',
            'is_featured' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 101,
        ]));

        $categories->push($this->createCategory([
            'name' => 'Collection Bylin',
            'slug' => 'collection-bylin',
            'description' => 'Notre marque exclusive : qualité premium et style unique',
            'icon' => 'star',
            'color' => '#FFD700',
            'is_featured' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 102,
        ]));

        $categories->push($this->createCategory([
            'name' => 'Meilleures ventes',
            'slug' => 'meilleures-ventes',
            'description' => 'Les produits préférés de nos clients',
            'icon' => 'trending-up',
            'is_featured' => true,
            'sort_order' => 103,
        ]));

        return $categories;
    }

    /**
     * Helper pour créer une catégorie
     */
    private function createCategory(array $data): Category
    {
        return Category::create(array_merge([
            'is_active' => true,
            'is_visible_in_menu' => false,
            'is_featured' => false,
        ], $data));
    }

    /**
     * Crée les attributs
     */
    private function createAttributes(): \Illuminate\Support\Collection
    {
        $attributes = collect();

        // Tailles vêtements
        $sizeClothing = Attribute::create([
            'name' => 'Taille',
            'code' => 'size_clothing',
            'type' => 'select',
            'is_filterable' => true,
            'sort_order' => 1,
        ]);
        $sizeClothing->values()->createMany([
            ['value' => 'xs', 'label' => 'XS', 'sort_order' => 1],
            ['value' => 's', 'label' => 'S', 'sort_order' => 2],
            ['value' => 'm', 'label' => 'M', 'sort_order' => 3],
            ['value' => 'l', 'label' => 'L', 'sort_order' => 4],
            ['value' => 'xl', 'label' => 'XL', 'sort_order' => 5],
            ['value' => 'xxl', 'label' => 'XXL', 'sort_order' => 6],
            ['value' => '3xl', 'label' => '3XL', 'sort_order' => 7],
        ]);
        $attributes->push($sizeClothing);

        // Pointures
        $sizeShoes = Attribute::create([
            'name' => 'Pointure',
            'code' => 'size_shoes',
            'type' => 'select',
            'is_filterable' => true,
            'sort_order' => 2,
        ]);
        $shoeSizes = [];
        for ($i = 35; $i <= 46; $i++) {
            $shoeSizes[] = [
                'value' => (string) $i,
                'label' => (string) $i,
                'sort_order' => $i - 34,
            ];
        }
        $sizeShoes->values()->createMany($shoeSizes);
        $attributes->push($sizeShoes);

        // Couleurs
        $color = Attribute::create([
            'name' => 'Couleur',
            'code' => 'color',
            'type' => 'color',
            'is_filterable' => true,
            'sort_order' => 3,
        ]);
        $color->values()->createMany([
            ['value' => 'noir', 'label' => 'Noir', 'color_code' => '#000000', 'sort_order' => 1],
            ['value' => 'blanc', 'label' => 'Blanc', 'color_code' => '#FFFFFF', 'sort_order' => 2],
            ['value' => 'gris', 'label' => 'Gris', 'color_code' => '#808080', 'sort_order' => 3],
            ['value' => 'bleu_marine', 'label' => 'Bleu marine', 'color_code' => '#000080', 'sort_order' => 4],
            ['value' => 'bleu_ciel', 'label' => 'Bleu ciel', 'color_code' => '#87CEEB', 'sort_order' => 5],
            ['value' => 'rouge', 'label' => 'Rouge', 'color_code' => '#FF0000', 'sort_order' => 6],
            ['value' => 'vert', 'label' => 'Vert', 'color_code' => '#008000', 'sort_order' => 7],
            ['value' => 'jaune', 'label' => 'Jaune', 'color_code' => '#FFFF00', 'sort_order' => 8],
            ['value' => 'rose', 'label' => 'Rose', 'color_code' => '#FFC0CB', 'sort_order' => 9],
            ['value' => 'beige', 'label' => 'Beige', 'color_code' => '#F5F5DC', 'sort_order' => 10],
            ['value' => 'marron', 'label' => 'Marron', 'color_code' => '#A52A2A', 'sort_order' => 11],
            ['value' => 'orange', 'label' => 'Orange', 'color_code' => '#FFA500', 'sort_order' => 12],
        ]);
        $attributes->push($color);

        return $attributes;
    }

    /**
     * Crée les produits avec variations
     */
    private function createProducts($brands, $categories, $attributes): void
    {
        $bylinBrand = $brands->firstWhere('slug', 'bylin');

        if (!$bylinBrand) {
            $this->command->warn('Brand "bylin" introuvable, utilisation d\'une marque aléatoire à la place.');
            $bylinBrand = $brands->first(); // fallback
        }

        // Catégories pour produits (niveau 2 uniquement)
        $productCategories = $categories->filter(fn($cat) => $cat->level === 2);

        if ($productCategories->isEmpty()) {
            $this->command->warn('Aucune catégorie de niveau 2 disponible, seeding des produits annulé.');
            return;
        }

        // Catégories spéciales
        $nouveautes = $categories->firstWhere('slug', 'nouveautes');
        $promotions = $categories->firstWhere('slug', 'promotions');
        $collectionBylin = $categories->firstWhere('slug', 'collection-bylin');

        $productCount = 0;
        $totalProducts = 80;

        for ($i = 0; $i < $totalProducts; $i++) {
            // 25% de produits Bylin
            if ($i < ($totalProducts * 0.25)) {
                $brand = $bylinBrand;
            } else {
                $brand = $brands->count() ? $brands->random() : $bylinBrand;
            }

            // Catégorie aléatoire (niveau 2)
            $category = $productCategories->count() ? $productCategories->random() : null;
            if (!$category) continue;

            // Récupérer le genre (level 0)
            $genre = $category->getGenre();

            // Nom du produit
            $productType = $category->name;
            $adjective = $this->faker->randomElement([
                'Premium',
                'Élégant',
                'Confortable',
                'Moderne',
                'Classique',
                'Sport',
                'Casual',
                'Chic'
            ]);
            $name = "{$adjective} {$productType} {$brand->name}";

            // Prix selon la marque
            $priceRanges = [
                'bylin' => [4990, 19990],
                'gucci' => [49990, 199990],
                'nike' => [3990, 15990],
                'adidas' => [3990, 14990],
                'zara' => [1990, 8990],
                'hm' => [990, 5990],
                'uniqlo' => [1990, 7990],
                'levis' => [4990, 12990],
            ];
            $range = $priceRanges[$brand->slug] ?? [2990, 9990];
            $price = $this->faker->numberBetween($range[0], $range[1]);

            // Création du produit
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(6),
                'sku' => strtoupper(substr($brand->slug, 0, 3)) . '-' . Str::upper(Str::random(8)),
                'description' => $this->faker->paragraphs(3, true),
                'short_description' => $this->faker->sentence(15),
                'price' => $price,
                'compare_price' => $this->faker->boolean(30) ? (int)($price * 1.25) : null,
                'stock_quantity' => $this->faker->numberBetween(5, 150),
                'is_active' => true,
                'is_featured' => $this->faker->boolean(15),
                'meta_title' => $name . ' | Bylin',
                'meta_description' => substr($this->faker->sentence(20), 0, 160),
                'meta_data' => [
                    'material' => $this->faker->randomElement([
                        'Coton 100%',
                        'Lin',
                        'Soie',
                        'Polyester',
                        'Laine mérinos',
                        'Coton bio',
                        'Viscose',
                        'Denim'
                    ]),
                    'care' => $this->faker->randomElement([
                        'Lavage en machine à 30°C',
                        'Lavage à la main',
                        'Nettoyage à sec uniquement',
                        'Lavage en machine à 40°C'
                    ]),
                    'fit' => $this->faker->randomElement([
                        'Coupe slim',
                        'Coupe regular',
                        'Coupe oversize',
                        'Coupe ajustée'
                    ]),
                ],
            ]);

            // Attacher les catégories
            $categoriesToAttach = [$category->id];
            if ($genre) $categoriesToAttach[] = $genre->id;
            if ($category->parent) $categoriesToAttach[] = $category->parent->id;
            if ($i < 10 && $nouveautes) $categoriesToAttach[] = $nouveautes->id;
            if ($this->faker->boolean(20) && $promotions) $categoriesToAttach[] = $promotions->id;
            if ($brand->slug === 'bylin' && $collectionBylin) $categoriesToAttach[] = $collectionBylin->id;

            $product->categories()->attach(array_unique($categoriesToAttach));

            // Créer des variations (vêtements seulement)
            $isClothing = !Str::contains(strtolower($category->name), ['chaussure', 'sac', 'bijou', 'montre']);

            if ($isClothing) {
                $sizes = ['XS', 'S', 'M', 'L', 'XL'];
                $colors = ['Noir', 'Blanc', 'Bleu marine'];

                foreach ($sizes as $size) {
                    foreach ($colors as $colorName) {
                        $product->variations()->create([
                            'sku' => $product->sku . '-' . $size . '-' . substr($colorName, 0, 3),
                            'variation_name' => "{$size} / {$colorName}",
                            'price' => $product->price,
                            'stock_quantity' => $this->faker->numberBetween(0, 30),
                            'is_active' => true,
                            'attributes' => [
                                'size' => $size,
                                'color' => $colorName,
                            ],
                        ]);
                    }
                }
            }

            $productCount++;
            if ($productCount % 10 === 0) {
                $this->command->info("  → {$productCount}/{$totalProducts} produits créés");
            }
        }
    }
}
