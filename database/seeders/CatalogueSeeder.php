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
        // 1. Create Brands
        $bylin = Brand::create([
            'name' => 'Bylin',
            'slug' => 'bylin',
            'description' => 'Marque premium de vêtements et accessoires.',
            'website' => 'https://bylin.com',
            'is_active' => true,
        ]);

        $nike = Brand::create([
            'name' => 'Nike',
            'slug' => 'nike',
            'is_active' => true,
        ]);

        $zara = Brand::create([
            'name' => 'Zara',
            'slug' => 'zara',
            'is_active' => true,
        ]);

        // 2. Create Categories Hierarchy
        // Homme
        $homme = Category::create(['name' => 'Homme', 'slug' => 'homme', 'is_active' => true]);
        $hommeVetements = Category::create(['name' => 'Vêtements', 'slug' => 'homme-vetements', 'parent_id' => $homme->id, 'is_active' => true]);
        $hommeChaussures = Category::create(['name' => 'Chaussures', 'slug' => 'homme-chaussures', 'parent_id' => $homme->id, 'is_active' => true]);
        
        // Sous-catégories Homme Vêtements
        $tshirts = Category::create(['name' => 'T-shirts & Polos', 'slug' => 't-shirts-polos', 'parent_id' => $hommeVetements->id, 'is_active' => true]);
        $chemises = Category::create(['name' => 'Chemises', 'slug' => 'chemises', 'parent_id' => $hommeVetements->id, 'is_active' => true]);
        $vestes = Category::create(['name' => 'Vestes & Blazers', 'slug' => 'vestes-blazers', 'parent_id' => $hommeVetements->id, 'is_active' => true]);
        $pantalons = Category::create(['name' => 'Pantalons', 'slug' => 'pantalons', 'parent_id' => $hommeVetements->id, 'is_active' => true]);

        // Femme
        $femme = Category::create(['name' => 'Femme', 'slug' => 'femme', 'is_active' => true]);
        $femmeVetements = Category::create(['name' => 'Vêtements', 'slug' => 'femme-vetements', 'parent_id' => $femme->id, 'is_active' => true]);
        $femmeChaussures = Category::create(['name' => 'Chaussures', 'slug' => 'femme-chaussures', 'parent_id' => $femme->id, 'is_active' => true]);
        $accessoires = Category::create(['name' => 'Accessoires & Bijoux', 'slug' => 'accessoires-bijoux', 'parent_id' => $femme->id, 'is_active' => true]);

        // Sous-catégories Femme Accessoires
        $colliers = Category::create(['name' => 'Colliers', 'slug' => 'colliers', 'parent_id' => $accessoires->id, 'is_active' => true]);
        $sacs = Category::create(['name' => 'Sacs', 'slug' => 'sacs', 'parent_id' => $accessoires->id, 'is_active' => true]);

        // 3. Create Attributes
        // Taille Vêtements
        $sizeClothing = Attribute::create(['name' => 'Taille', 'code' => 'size_clothing', 'type' => 'select']);
        $sizeClothing->values()->createMany([
            ['value' => 'XS', 'code' => 'XS'],
            ['value' => 'S', 'code' => 'S'],
            ['value' => 'M', 'code' => 'M'],
            ['value' => 'L', 'code' => 'L'],
            ['value' => 'XL', 'code' => 'XL'],
            ['value' => 'XXL', 'code' => 'XXL'],
        ]);

        // Taille Chaussures
        $sizeShoes = Attribute::create(['name' => 'Pointure', 'code' => 'size_shoes', 'type' => 'select']);
        $sizeShoes->values()->createMany([
            ['value' => '39', 'code' => '39'],
            ['value' => '40', 'code' => '40'],
            ['value' => '41', 'code' => '41'],
            ['value' => '42', 'code' => '42'],
            ['value' => '43', 'code' => '43'],
            ['value' => '44', 'code' => '44'],
            ['value' => '45', 'code' => '45'],
        ]);

        // Couleurs
        $color = Attribute::create(['name' => 'Couleur', 'code' => 'color', 'type' => 'color']);
        $color->values()->createMany([
            ['value' => 'Noir', 'code' => '#000000'],
            ['value' => 'Blanc', 'code' => '#FFFFFF'],
            ['value' => 'Bleu Marine', 'code' => '#000080'],
            ['value' => 'Beige', 'code' => '#F5F5DC'],
            ['value' => 'Rouge', 'code' => '#FF0000'],
            ['value' => 'Gris', 'code' => '#808080'],
        ]);

        // Matière
        $material = Attribute::create(['name' => 'Matière', 'code' => 'material', 'type' => 'select']);
        $material->values()->createMany([
            ['value' => 'Coton'],
            ['value' => 'Lin'],
            ['value' => 'Cuir'],
            ['value' => 'Soie'],
            ['value' => 'Denim'],
            ['value' => 'Or 18k'],
            ['value' => 'Argent'],
        ]);

        // 4. Create Products

        // --- HOMME ---

        // Blazer Bylin Signature
        $blazer = Product::create([
            'brand_id' => $bylin->id,
            'name' => 'Blazer Croisé Signature',
            'slug' => 'blazer-croise-signature',
            'sku' => 'BY-BLZ-001',
            'description' => 'Un blazer croisé élégant en laine vierge, coupe ajustée. Idéal pour les occasions formelles ou un look business casual.',
            'price' => 185000,
            'stock_quantity' => 20,
            'is_active' => true,
            'is_featured' => true,
            'requires_authenticity' => true,
        ]);
        $blazer->categories()->attach([$homme->id, $hommeVetements->id, $vestes->id]);

        // Chemise Lin
        $chemise = Product::create([
            'brand_id' => $bylin->id,
            'name' => 'Chemise en Lin Premium',
            'slug' => 'chemise-lin-premium',
            'sku' => 'BY-CHM-LIN-02',
            'description' => 'Chemise légère en 100% lin, parfaite pour l\'été. Col mao et boutons en nacre.',
            'price' => 45000,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
        $chemise->categories()->attach([$homme->id, $hommeVetements->id, $chemises->id]);

        // T-shirt Basique
        $tshirt = Product::create([
            'brand_id' => $bylin->id,
            'name' => 'T-shirt Coton Pima',
            'slug' => 't-shirt-coton-pima',
            'sku' => 'BY-TSH-003',
            'description' => 'Le t-shirt parfait. Coton Pima ultra-doux, coupe moderne, ne bouloche pas.',
            'price' => 25000,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);
        $tshirt->categories()->attach([$homme->id, $hommeVetements->id, $tshirts->id]);

        // Sneakers
        $sneakers = Product::create([
            'brand_id' => $nike->id,
            'name' => 'Air Force 1 Low',
            'slug' => 'air-force-1-low',
            'sku' => 'NK-AF1-001',
            'description' => 'La légende continue de vivre avec la Nike Air Force 1 \'07.',
            'price' => 85000,
            'stock_quantity' => 30,
            'is_active' => true,
        ]);
        $sneakers->categories()->attach([$homme->id, $hommeChaussures->id]);

        // --- FEMME ---

        // Collier Or
        $collier = Product::create([
            'brand_id' => $bylin->id,
            'name' => 'Collier Chaîne Or Fin',
            'slug' => 'collier-chaine-or-fin',
            'sku' => 'BY-JW-001',
            'description' => 'Collier délicat en plaqué or 18 carats. Un intemporel à porter seul ou en accumulation.',
            'price' => 35000,
            'stock_quantity' => 15,
            'is_active' => true,
            'is_featured' => true,
        ]);
        $collier->categories()->attach([$femme->id, $accessoires->id, $colliers->id]);

        // Robe Soie (Pre-order)
        $robe = Product::create([
            'brand_id' => $bylin->id,
            'name' => 'Robe Soie Soirée (Précommande)',
            'slug' => 'robe-soie-soiree',
            'sku' => 'BY-DRS-PRE-01',
            'description' => 'Robe longue en soie véritable. Disponible en précommande pour la collection d\'hiver.',
            'price' => 120000,
            'stock_quantity' => 0,
            'is_active' => true,
            'is_preorder_enabled' => true,
            'preorder_available_date' => now()->addMonths(1),
        ]);
        $robe->categories()->attach([$femme->id, $femmeVetements->id]);
    }
}
