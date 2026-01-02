<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogue – Activation
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Brand Bylin - Configuration
    |--------------------------------------------------------------------------
    | ID de la marque Bylin (votre marque principale)
    | Les produits ajoutés à une collection prennent automatiquement ce brand_id
    */
    'bylin_brand_id' => env('BYLIN_BRAND_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Images – stockage
    |--------------------------------------------------------------------------
    */
    'images' => [

        // Disk Laravel (config/filesystems.php)
        'disk' => env('CATALOGUE_IMAGE_DISK', 'public'),

        // Chemins relatifs sur le disk
        'paths' => [
            'brands'     => 'brands/logos',
            'products'   => 'products/images',
            'categories' => 'categories/images',
            'banners'    => 'banners/images',
            'collections' => 'collections/images',
        ],

        // Taille max (KB)
        'max_size' => 2048,

        // Types MIME autorisés
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Marques
    |--------------------------------------------------------------------------
    */
    'brands' => [
        'default_active' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Collections Bylin
    |--------------------------------------------------------------------------
    */
    'collections' => [
        'auto_assign_bylin_brand' => env('COLLECTIONS_AUTO_ASSIGN_BYLIN', true),
        'remove_brand_on_removal' => env('COLLECTIONS_REMOVE_BRAND_ON_REMOVAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Catégories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'max_depth' => 3,
        'visible_in_menu' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Produits
    |--------------------------------------------------------------------------
    */
    'products' => [
        'default_status' => 'draft', // draft | published | archived

        'sku' => [
            'auto_generate' => true,
            'prefix' => 'PRD-',
        ],

        'stock' => [
            'track' => true,
            'default_quantity' => 0,
        ],
    ],
];
