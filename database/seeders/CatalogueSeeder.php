<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Category;
use Modules\Catalogue\Models\Product;
use Modules\Catalogue\Models\Attribute;
use Illuminate\Support\Str;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // 1. Create Brands
        $brandsData = [
            ['name' => 'Bylin', 'slug' => 'bylin', 'description' => 'Marque premium de vêtements et accessoires.', 'website' => 'https://bylin.com', 'is_active' => true],
            ['name' => 'Nike', 'slug' => 'nike', 'description' => 'Just Do It.', 'is_active' => true],
            ['name' => 'Zara', 'slug' => 'zara', 'description' => 'Fast fashion leader.', 'is_active' => true],
            ['name' => 'H&M', 'slug' => 'hm', 'description' => 'Fashion and quality at the best price.', 'is_active' => true],
            ['name' => 'Adidas', 'slug' => 'adidas', 'description' => 'Impossible is nothing.', 'is_active' => true],
            ['name' => 'Uniqlo', 'slug' => 'uniqlo', 'description' => 'LifeWear.', 'is_active' => true],
            ['name' => 'Gucci', 'slug' => 'gucci', 'description' => 'Luxury fashion.', 'is_active' => true],
        ];

        $brands = collect();
        foreach ($brandsData as $data) {
            $brands->push(Brand::create($data));
        }

        // 2. Create Categories Hierarchy
        // Homme
        $homme = Category::create(['name' => 'Homme', 'slug' => 'homme', 'is_active' => true]);
        $hommeVetements = Category::create(['name' => 'Vêtements', 'slug' => 'homme-vetements', 'parent_id' => $homme->id, 'is_active' => true]);
        $hommeChaussures = Category::create(['name' => 'Chaussures', 'slug' => 'homme-chaussures', 'parent_id' => $homme->id, 'is_active' => true]);
        
        $h_subcats = collect([
            Category::create(['name' => 'T-shirts & Polos', 'slug' => 't-shirts-polos', 'parent_id' => $hommeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Chemises', 'slug' => 'chemises', 'parent_id' => $hommeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Vestes & Blazers', 'slug' => 'vestes-blazers', 'parent_id' => $hommeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Pantalons', 'slug' => 'pantalons', 'parent_id' => $hommeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Jeans', 'slug' => 'jeans', 'parent_id' => $hommeVetements->id, 'is_active' => true]),
        ]);

        // Femme
        $femme = Category::create(['name' => 'Femme', 'slug' => 'femme', 'is_active' => true]);
        $femmeVetements = Category::create(['name' => 'Vêtements', 'slug' => 'femme-vetements', 'parent_id' => $femme->id, 'is_active' => true]);
        $femmeChaussures = Category::create(['name' => 'Chaussures', 'slug' => 'femme-chaussures', 'parent_id' => $femme->id, 'is_active' => true]);
        $accessoires = Category::create(['name' => 'Accessoires & Bijoux', 'slug' => 'accessoires-bijoux', 'parent_id' => $femme->id, 'is_active' => true]);

        $f_subcats = collect([
            Category::create(['name' => 'Robes', 'slug' => 'robes', 'parent_id' => $femmeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Tops & T-shirts', 'slug' => 'tops-tshirts', 'parent_id' => $femmeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Jupes', 'slug' => 'jupes', 'parent_id' => $femmeVetements->id, 'is_active' => true]),
            Category::create(['name' => 'Manteaux', 'slug' => 'manteaux', 'parent_id' => $femmeVetements->id, 'is_active' => true]),
        ]);

        $acc_subcats = collect([
            Category::create(['name' => 'Colliers', 'slug' => 'colliers', 'parent_id' => $accessoires->id, 'is_active' => true]),
            Category::create(['name' => 'Sacs', 'slug' => 'sacs', 'parent_id' => $accessoires->id, 'is_active' => true]),
            Category::create(['name' => 'Montres', 'slug' => 'montres', 'parent_id' => $accessoires->id, 'is_active' => true]),
        ]);

        // 3. Create Attributes
        $sizeClothing = Attribute::create(['name' => 'Taille', 'code' => 'size_clothing', 'type' => 'select']);
        $sizeClothing->values()->createMany([
            ['value' => 'XS', 'code' => 'XS'],
            ['value' => 'S', 'code' => 'S'],
            ['value' => 'M', 'code' => 'M'],
            ['value' => 'L', 'code' => 'L'],
            ['value' => 'XL', 'code' => 'XL'],
            ['value' => 'XXL', 'code' => 'XXL'],
        ]);

        $sizeShoes = Attribute::create(['name' => 'Pointure', 'code' => 'size_shoes', 'type' => 'select']);
        $sizeShoes->values()->createMany([
            ['value' => '36', 'code' => '36'],
            ['value' => '37', 'code' => '37'],
            ['value' => '38', 'code' => '38'],
            ['value' => '39', 'code' => '39'],
            ['value' => '40', 'code' => '40'],
            ['value' => '41', 'code' => '41'],
            ['value' => '42', 'code' => '42'],
            ['value' => '43', 'code' => '43'],
            ['value' => '44', 'code' => '44'],
            ['value' => '45', 'code' => '45'],
        ]);

        $color = Attribute::create(['name' => 'Couleur', 'code' => 'color', 'type' => 'color']);
        $color->values()->createMany([
            ['value' => 'Noir', 'code' => '#000000'],
            ['value' => 'Blanc', 'code' => '#FFFFFF'],
            ['value' => 'Bleu', 'code' => '#0000FF'],
            ['value' => 'Rouge', 'code' => '#FF0000'],
            ['value' => 'Vert', 'code' => '#008000'],
            ['value' => 'Jaune', 'code' => '#FFFF00'],
            ['value' => 'Rose', 'code' => '#FFC0CB'],
            ['value' => 'Beige', 'code' => '#F5F5DC'],
        ]);

        // 4. Create 60 Products
        for ($i = 0; $i < 60; $i++) {
            // Determine Category and Brand
            $isMan = $faker->boolean(50);
            $rootCat = $isMan ? $homme : $femme;
            
            // Subcategory Selection
            if ($isMan) {
                $subCat = $faker->boolean(80) ? $h_subcats->random() : $hommeChaussures;
            } else {
                $rand = rand(1, 100);
                if ($rand < 60) $subCat = $f_subcats->random();
                elseif ($rand < 90) $subCat = $acc_subcats->random();
                else $subCat = $femmeChaussures;
            }

            // Brand Logic: Bylin is primary (20%), others random
            $brand = ($i < 12) ? $brands->firstWhere('slug', 'bylin') : $brands->random();

            $name = $brand->name . ' ' . $faker->words(3, true);
            $price = $faker->numberBetween(15000, 250000);
            
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => ucfirst($name),
                'slug' => Str::slug($name) . '-' . Str::random(5),
                'sku' => strtoupper(substr($brand->name, 0, 2)) . '-' . Str::upper(Str::random(6)),
                'description' => $faker->paragraph(3),
                'short_description' => $faker->sentence(10),
                'price' => $price,
                'compare_price' => $faker->boolean(30) ? $price * 1.2 : null,
                'stock_quantity' => $faker->numberBetween(0, 100),
                'is_active' => true,
                'is_featured' => $faker->boolean(20),
                'meta_data' => [
                    'material' => $faker->randomElement(['Coton', 'Lin', 'Soie', 'Polyester', 'Laine']),
                    'care' => 'Lavage en machine à 30°C',
                ],
            ]);

            // Categories
            $product->categories()->attach([$rootCat->id, $subCat->id]);

            // Image Placeholder (Unsplash)
            // Note: In real setup, we would attach media via Spatie Medialibrary
            // simulating via attributes if needed or just trusting the mock controller for now
            // But since we want "real data" for the future:
            // $product->addMediaFromUrl(...)->toMediaCollection('images'); (Skipped to avoid HTTP calls during seed)

            // Variations
            // Only for clothing usually
            if ($subCat->parent_id === $hommeVetements->id || $subCat->parent_id === $femmeVetements->id) {
                // S, M, L
                foreach (['S', 'M', 'L'] as $size) {
                    $product->variations()->create([
                        'sku' => $product->sku . '-' . $size,
                        'variation_name' => $size,
                        'price' => $product->price,
                        'stock_quantity' => $faker->numberBetween(0, 20),
                        'is_active' => true,
                        'attributes' => ['size' => $size],
                    ]);
                }
            }
        }
    }
}
