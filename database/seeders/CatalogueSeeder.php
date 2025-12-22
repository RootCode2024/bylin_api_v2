<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Category;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\Attribute;
use Modules\Catalogue\Enums\ProductStatus; // Assure-toi d'avoir cet Enum
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Seeder complet du catalogue
 */
class CatalogueSeeder extends Seeder
{
    private \Faker\Generator $faker;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('fr_FR');

        DB::transaction(function () {
            $this->command->info('🚀 Début du seeding du catalogue...');

            // 1. Marques
            $brands = $this->createBrands();
            $this->command->info('✓ ' . $brands->count() . ' marques créées');

            // 2. Catégories (Hiérarchie corrigée)
            $categories = $this->createCategories();
            $this->command->info('✓ ' . $categories->count() . ' catégories créées');

            // 3. Attributs (Récupération des objets pour usage ultérieur)
            $attributes = $this->createAttributes();
            $this->command->info('✓ ' . $attributes->count() . ' attributs créés');

            // 4. Produits (Logique corrigée)
            $this->createProducts($brands, $categories, $attributes);
            $this->command->info('✓ Produits créés avec succès');

            $this->command->info('✨ Seeding terminé !');
        });
    }

    private function createBrands(): \Illuminate\Support\Collection
    {
        $brandsData = [
            ['name' => 'Bylin', 'slug' => 'bylin', 'sort_order' => 1],
            ['name' => 'Nike', 'slug' => 'nike', 'sort_order' => 2],
            ['name' => 'Adidas', 'slug' => 'adidas', 'sort_order' => 3],
            ['name' => 'Zara', 'slug' => 'zara', 'sort_order' => 4],
            ['name' => 'H&M', 'slug' => 'hm', 'sort_order' => 5],
            ['name' => 'Uniqlo', 'slug' => 'uniqlo', 'sort_order' => 6],
            ['name' => 'Gucci', 'slug' => 'gucci', 'sort_order' => 7],
            ['name' => 'Levi\'s', 'slug' => 'levis', 'sort_order' => 8],
        ];

        return collect($brandsData)->map(function ($data) {
            return Brand::create(array_merge($data, [
                'description' => $this->faker->sentence(10),
                'is_active' => true,
            ]));
        });
    }

    private function createCategories(): \Illuminate\Support\Collection
    {
        $categories = collect();

        // --- NIVEAU 0 ---
        $genres = [
            ['name' => 'Homme', 'icon' => 'mars'],
            ['name' => 'Femme', 'icon' => 'venus'],
            ['name' => 'Enfant', 'icon' => 'baby'],
            ['name' => 'Mixte', 'icon' => 'users'],
        ];

        foreach ($genres as $i => $g) {
            $cat = $this->createCategory(array_merge($g, ['level' => 0, 'sort_order' => $i + 1]));
            $categories->push($cat);

            // --- NIVEAU 1 (Types) ---
            $types = ['Hauts', 'Bas', 'Chaussures', 'Accessoires'];
            foreach ($types as $j => $typeName) {
                $subCat = $this->createCategory([
                    'name' => $typeName,
                    'parent_id' => $cat->id,
                    'level' => 1, // Important !
                    'sort_order' => $j + 1,
                    'slug' => Str::slug($cat->name . '-' . $typeName)
                ]);
                $categories->push($subCat);

                // --- NIVEAU 2 (Produits spécifiques) ---
                $subTypes = match ($typeName) {
                    'Hauts' => ['T-shirts', 'Pulls', 'Chemises', 'Vestes'],
                    'Bas' => ['Jeans', 'Pantalons', 'Shorts'],
                    'Chaussures' => ['Baskets', 'Bottes', 'Sandales'],
                    'Accessoires' => ['Sacs', 'Ceintures', 'Chapeaux'],
                    default => []
                };

                foreach ($subTypes as $k => $subTypeName) {
                    $finalCat = $this->createCategory([
                        'name' => $subTypeName,
                        'parent_id' => $subCat->id,
                        'level' => 2, // Important !
                        'sort_order' => $k + 1,
                        'slug' => Str::slug($cat->name . '-' . $typeName . '-' . $subTypeName)
                    ]);
                    $categories->push($finalCat);
                }
            }
        }

        // Catégories spéciales
        $specials = ['Nouveautés', 'Promotions', 'Collection Bylin'];
        foreach ($specials as $i => $name) {
            $categories->push($this->createCategory([
                'name' => $name,
                'is_featured' => true,
                'level' => 0,
                'sort_order' => 100 + $i
            ]));
        }

        return $categories;
    }

    private function createCategory(array $data): Category
    {
        // Génération automatique du slug si absent
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Category::create(array_merge([
            'is_active' => true,
            'is_visible_in_menu' => true,
            'description' => $this->faker->sentence(),
        ], $data));
    }

    private function createAttributes(): \Illuminate\Support\Collection
    {
        $attributes = collect();

        // Taille (Vêtements)
        $sizeAttr = Attribute::create(['name' => 'Taille', 'code' => 'size', 'type' => 'select']);
        $sizeAttr->values()->createMany(
            collect(['XS', 'S', 'M', 'L', 'XL'])->map(fn($v, $k) => ['value' => $v, 'label' => $v, 'sort_order' => $k])->toArray()
        );
        $attributes->push($sizeAttr);

        // Pointure (Chaussures)
        $shoeAttr = Attribute::create(['name' => 'Pointure', 'code' => 'shoe_size', 'type' => 'taille']);
        $shoeAttr->values()->createMany(
            collect(range(36, 45))->map(fn($v, $k) => ['value' => (string)$v, 'label' => (string)$v, 'sort_order' => $k])->toArray()
        );
        $attributes->push($shoeAttr);

        // Couleur
        $colorAttr = Attribute::create(['name' => 'Couleur', 'code' => 'color', 'type' => 'color']);
        $colors = [
            ['value' => 'noir', 'label' => 'Noir', 'code' => '#000000'],
            ['value' => 'blanc', 'label' => 'Blanc', 'code' => '#FFFFFF'],
            ['value' => 'bleu', 'label' => 'Bleu', 'code' => '#0000FF'],
            ['value' => 'rouge', 'label' => 'Rouge', 'code' => '#FF0000'],
        ];
        foreach ($colors as $k => $c) {
            $colorAttr->values()->create(['value' => $c['value'], 'label' => $c['label'], 'color_code' => $c['code'], 'sort_order' => $k]);
        }
        $attributes->push($colorAttr);

        return $attributes; // Retourne la collection des modèles Attribute
    }

    private function createProducts($brands, $categories, $attributes): void
    {
        // Récupérer les attributs spécifiques
        $sizeAttribute = $attributes->firstWhere('code', 'size');
        $shoeAttribute = $attributes->firstWhere('code', 'shoe_size');
        $colorAttribute = $attributes->firstWhere('code', 'color');

        // Récupérer les valeurs des attributs (Eager loading pour éviter N+1 dans la boucle)
        $sizeAttribute?->load('values');
        $shoeAttribute?->load('values');
        $colorAttribute?->load('values');

        // Filtrer uniquement les catégories feuilles (niveau 2)
        $leafCategories = $categories->filter(fn($c) => $c->level === 2);
        $bylinBrand = $brands->firstWhere('slug', 'bylin');

        $totalProducts = 80;

        for ($i = 0; $i < $totalProducts; $i++) {
            // Choix de la marque (Boost Bylin)
            $brand = ($i < 20) ? $bylinBrand : $brands->random();

            // Choix catégorie
            $category = $leafCategories->random();

            // Remontée des parents pour les liaisons
            $catsToSync = [$category->id];
            if ($category->parent_id) {
                $catsToSync[] = $category->parent_id;
                // Si le parent a un parent (Genre)
                $parent = $categories->firstWhere('id', $category->parent_id);
                if ($parent && $parent->parent_id) {
                    $catsToSync[] = $parent->parent_id;
                }
            }

            // Détection du type de produit pour les variations
            $isShoe = Str::contains($category->slug, 'chaussure');
            $isAccessory = Str::contains($category->slug, 'accessoire');
            $isClothing = !$isShoe && !$isAccessory;

            $name = $this->faker->word . ' ' . $brand->name . ' ' . $category->name;

            // Création Produit
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => ucfirst($name),
                'slug' => Str::slug($name) . '-' . Str::random(6),
                'sku' => strtoupper(substr($brand->name, 0, 3)) . '-' . Str::random(8),
                'description' => $this->faker->paragraph,
                'short_description' => $this->faker->sentence,
                'price' => $this->faker->numberBetween(20, 300) * 100, // Prix en centimes ou XOF
                'stock_quantity' => 0, // Sera calculé via les variations
                'status' => 'active', // Important
                'is_featured' => $this->faker->boolean(20),
                'is_variable' => ($isClothing || $isShoe), // Flag ajouté
                'requires_authenticity' => ($brand->slug === 'bylin'), // Bylin feature
            ]);

            $product->categories()->sync($catsToSync);

            // GESTION DES VARIATIONS
            if ($isClothing || $isShoe) {
                $targetSizeAttr = $isShoe ? $shoeAttribute : $sizeAttribute;

                // On prend 3 tailles et 2 couleurs au hasard
                $selectedSizes = $targetSizeAttr->values->random(min(3, $targetSizeAttr->values->count()));
                $selectedColors = $colorAttribute->values->random(min(2, $colorAttribute->values->count()));

                foreach ($selectedColors as $colorVal) {
                    foreach ($selectedSizes as $sizeVal) {

                        // 1. Création de la Variation
                        $product->variations()->create([
                            'sku' => $product->sku . '-' . $sizeVal->value . '-' . substr($colorVal->value, 0, 3),
                            'variation_name' => "{$sizeVal->label} / {$colorVal->label}",
                            'price' => $product->price,
                            'stock_quantity' => $this->faker->numberBetween(0, 20),
                            'is_active' => true,
                            'attributes' => [
                                $targetSizeAttr->code => $sizeVal->value,
                                $colorAttribute->code => $colorVal->value
                            ]
                        ]);

                        // 2. Remplissage de la table pivot product_attributes (CRUCIAL POUR LES FILTRES)
                        // On attache la valeur de taille
                        $product->attributes()->syncWithoutDetaching([
                            $targetSizeAttr->id => ['attribute_value_id' => $sizeVal->id],
                            $colorAttribute->id => ['attribute_value_id' => $colorVal->id]
                        ]);
                    }
                }

                // Mise à jour du flag is_variable et recalcul stock
                $product->update([
                    'is_variable' => true,
                    // Note: Le stock se mettra à jour via l'Observer des variations qu'on a codé avant
                ]);
            } else {
                // Produit simple (Accessoire)
                $product->update([
                    'stock_quantity' => $this->faker->numberBetween(10, 50),
                    'is_variable' => false
                ]);
            }

            if (($i + 1) % 10 === 0) {
                $this->command->info("  → " . ($i + 1) . " produits traités");
            }
        }
    }
}
