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

    /*
    |--------------------------------------------------------------------------
    | Rétro-compatibilité (ANCIEN CODE)
    |--------------------------------------------------------------------------
    | Tu peux les supprimer quand tout le code utilisera images.paths.*
    */
    'brand_logo_path'      => 'brands/logos',
    'product_images_path'  => 'products/images',
    'category_images_path' => 'categories/images',
    'banner_images_path'   => 'banners/images',
];
