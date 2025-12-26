<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\Category;
use Modules\Catalogue\Models\Attribute;

class CatalogueSeeder extends Seeder
{
    private \Faker\Generator $faker;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('fr_FR');

        DB::transaction(function () {
            $this->command->info('🚀 Début du seeding du catalogue...');

            $brands = $this->createBrands();
            $this->command->info('✓ ' . $brands->count() . ' marques créées');

            $categories = $this->createCategories();
            $this->command->info('✓ ' . $categories->count() . ' catégories créées');

            $attributes = $this->createAttributes();
            $this->command->info('✓ ' . $attributes->count() . ' attributs créés');

            $this->createProducts($brands, $categories, $attributes);
            $this->command->info('✓ Produits créés avec succès');

            $this->command->info('✨ Seeding terminé !');
        });
    }

    private function createBrands(): Collection
    {
        $brandsData = [
            ['name' => 'Bylin', 'slug' => 'bylin', 'sort_order' => 1],
            ['name' => 'Nike', 'slug' => 'nike', 'sort_order' => 2],
            ['name' => 'Adidas', 'slug' => 'adidas', 'sort_order' => 3],
            ['name' => 'Zara', 'slug' => 'zara', 'sort_order' => 4],
            ['name' => 'H&M', 'slug' => 'hm', 'sort_order' => 5],
            ['name' => 'Gucci', 'slug' => 'gucci', 'sort_order' => 6],
            ['name' => 'Levi\'s', 'slug' => 'levis', 'sort_order' => 7],
        ];

        return collect($brandsData)->map(function ($data) {
            return Brand::create(array_merge($data, [
                'description' => $this->faker->sentence(10),
                'is_active' => true,
            ]));
        });
    }

    private function createCategories(): Collection
    {
        $categories = collect();

        $genres = [
            ['name' => 'Homme', 'icon' => 'mars'],
            ['name' => 'Femme', 'icon' => 'venus'],
            ['name' => 'Enfant', 'icon' => 'baby'],
            ['name' => 'Mixte', 'icon' => 'users'],
        ];

        foreach ($genres as $i => $g) {
            $cat = $this->createCategory(array_merge($g, ['level' => 0, 'sort_order' => $i + 1]));
            $categories->push($cat);

            $types = ['Hauts', 'Bas', 'Chaussures', 'Accessoires'];
            foreach ($types as $j => $typeName) {
                $subCat = $this->createCategory([
                    'name' => $typeName,
                    'parent_id' => $cat->id,
                    'level' => 1,
                    'sort_order' => $j + 1,
                    'slug' => Str::slug($cat->name . '-' . $typeName)
                ]);
                $categories->push($subCat);

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
                        'level' => 2,
                        'sort_order' => $k + 1,
                        'slug' => Str::slug($cat->name . '-' . $typeName . '-' . $subTypeName)
                    ]);
                    $categories->push($finalCat);
                }
            }
        }

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
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Category::create(array_merge([
            'is_active' => true,
            'is_visible_in_menu' => true,
            'description' => $this->faker->sentence(),
        ], $data));
    }

    private function createAttributes(): Collection
    {
        $attributes = collect();

        $sizeAttr = Attribute::create(['name' => 'Taille', 'code' => 'size', 'type' => 'select']);
        $sizeAttr->values()->createMany(
            collect(['XS', 'S', 'M', 'L', 'XL'])->map(fn($v, $k) => ['value' => $v, 'label' => $v, 'sort_order' => $k])->toArray()
        );
        $attributes->push($sizeAttr);

        $shoeAttr = Attribute::create(['name' => 'Pointure', 'code' => 'shoe_size', 'type' => 'taille']);
        $shoeAttr->values()->createMany(
            collect(range(36, 45))->map(fn($v, $k) => ['value' => (string)$v, 'label' => (string)$v, 'sort_order' => $k])->toArray()
        );
        $attributes->push($shoeAttr);

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

        return $attributes;
    }

    private function createProducts($brands, $categories, $attributes): void
    {
        $sizeAttribute = $attributes->firstWhere('code', 'size');
        $shoeAttribute = $attributes->firstWhere('code', 'shoe_size');
        $colorAttribute = $attributes->firstWhere('code', 'color');

        $sizeAttribute?->load('values');
        $shoeAttribute?->load('values');
        $colorAttribute?->load('values');

        $leafCategories = $categories->filter(fn($c) => $c->level === 2);
        $bylinBrand = $brands->firstWhere('slug', 'bylin');

        $totalProducts = 80;

        for ($i = 0; $i < $totalProducts; $i++) {
            $brand = ($i < 20) ? $bylinBrand : $brands->random();

            $category = $leafCategories->random();

            $catsToSync = [$category->id];
            if ($category->parent_id) {
                $catsToSync[] = $category->parent_id;
                $parent = $categories->firstWhere('id', $category->parent_id);
                if ($parent && $parent->parent_id) {
                    $catsToSync[] = $parent->parent_id;
                }
            }

            $isShoe = Str::contains($category->slug, 'chaussure');
            $isAccessory = Str::contains($category->slug, 'accessoire');
            $isClothing = !$isShoe && !$isAccessory;

            $name = $this->faker->word . ' ' . $brand->name . ' ' . $category->name;

            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => ucfirst($name),
                'slug' => Str::slug($name) . '-' . Str::random(6),
                'sku' => strtoupper(substr($brand->name, 0, 3)) . '-' . Str::random(8),
                'description' => $this->faker->paragraph,
                'short_description' => $this->faker->sentence,
                'price' => $this->faker->numberBetween(20, 300) * 100,
                'stock_quantity' => 0,
                'status' => 'active',
                'is_featured' => $this->faker->boolean(20),
                'is_variable' => ($isClothing || $isShoe),
                'requires_authenticity' => ($brand->slug === 'bylin'),
            ]);

            $product->categories()->sync($catsToSync);

            if ($isClothing || $isShoe) {
                $targetSizeAttr = $isShoe ? $shoeAttribute : $sizeAttribute;

                $selectedSizes = $targetSizeAttr->values->random(min(3, $targetSizeAttr->values->count()));
                $selectedColors = $colorAttribute->values->random(min(2, $colorAttribute->values->count()));

                foreach ($selectedColors as $colorVal) {
                    foreach ($selectedSizes as $sizeVal) {

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

                        $product->attributes()->syncWithoutDetaching([
                            $targetSizeAttr->id => ['attribute_value_id' => $sizeVal->id],
                            $colorAttribute->id => ['attribute_value_id' => $colorVal->id]
                        ]);
                    }
                }

                $product->update([
                    'is_variable' => true
                ]);
            } else {
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
