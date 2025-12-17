<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HomeContentController extends Controller
{
    /**
     * Get all content for the home page (navigation + page content).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $data = [
            'success' => true,
            'data' => [
                'navigation' => $this->getNavigationData(),
                'home_content' => $this->getHomeContentData(),
            ],
        ];

        return response()->json($data);
    }

    /**
     * Get navigation structure (MainHeader).
     */
    private function getNavigationData(): array
    {
        // 1. Fetch dynamic data for "Nouveautés"
        $newArrivals = \Modules\Catalogue\Models\Product::active()
            ->latest()
            ->take(2)
            ->get()
            ->map(function ($product) {
                return [
                    'label' => $product->name,
                    'url' => '/product/' . $product->slug,
                ];
            });

        // 2. Fetch dynamic data for "Catégories" (Enfants/Sub-categories)
        // We take sub-categories (where parent_id is not null) or just active categories if no hierarchy yet.
        $childCategories = \Modules\Catalogue\Models\Category::active()
            ->whereNotNull('parent_id')
            ->take(5)
            ->get()
            ->map(function ($category) {
                return [
                    'label' => $category->name,
                    'url' => '/category/' . $category->slug,
                ];
            });
        
        // Fallback if no children, take roots
        if ($childCategories->isEmpty()) {
            $childCategories = \Modules\Catalogue\Models\Category::active()
                ->take(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'label' => $category->name,
                        'url' => '/category/' . $category->slug,
                    ];
                });
        }

        // 3. Fetch Featured Product for Mega Menu
        $featured = \Modules\Catalogue\Models\Product::active()->featured()->first();
        $featuredBlock = [
            'title' => 'Mise en avant',
            'type' => 'image',
            'image_url' => $featured ? $featured->getFirstMediaUrl('images') : 'https://api.bylin-style.com/storage/mega-menu-featured.jpg',
            'image_label' => $featured ? $featured->name : 'Notre Best Seller',
            'url' => $featured ? '/product/' . $featured->slug : '/shop',
        ];

        return [
            [
                'label' => 'Vêtements',
                'url' => '/shop',
                'mega_menu' => [
                    [
                        'title' => 'Nouveautés',
                        'type' => 'links',
                        'links' => $newArrivals->toArray(),
                        'bottom_link' => ['label' => 'Voir tous', 'url' => '/shop?sort=newest'],
                    ],
                    [
                        'title' => 'Catégories',
                        'type' => 'links',
                        'links' => $childCategories->toArray(),
                        'bottom_link' => ['label' => 'Voir tout', 'url' => '/categories'],
                    ],
                    $featuredBlock
                ],
            ],
            ['label' => 'Les Packs', 'url' => '/packs', 'mega_menu' => null],
            ['label' => 'Tutoriels', 'url' => '/tutorials', 'mega_menu' => null],
            ['label' => 'Maison bylin', 'url' => '/bylin', 'mega_menu' => null],
        ];
    }

    /**
     * Get home page specific content.
     */
    private function getHomeContentData(): array
    {
        // Categories Explorer (Roots)
        $rootCategories = \Modules\Catalogue\Models\Category::root()
            ->active()
            ->take(3)
            ->get()
            ->map(function ($cat) {
                return [
                    'name' => strtoupper($cat->name),
                    'image' => $cat->image ?? 'https://api.bylin-style.com/storage/cat-placeholder.jpg',
                    'url' => '/category/' . $cat->slug,
                ];
            });

        // New Collection (Latest Products)
        $newCollection = \Modules\Catalogue\Models\Product::active()
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($product) {
                return $this->formatProductForHome($product);
            });

        // Shop Selection (Random or Featured)
        $selection = \Modules\Catalogue\Models\Product::active()
            ->inStock()
            ->inRandomOrder()
            ->take(8)
            ->get()
            ->map(function ($product) {
               return $this->formatProductForHome($product);
            });

        return [
            'hero' => [
                'title_line_1' => 'BYLIN',
                'title_line_2' => 'STYLE',
                'collection_tag' => 'Collection CONFIDENCE — 2025',
                'location' => 'Cotonou / Bénin',
                'background_image' => 'https://api.bylin-style.com/storage/hero.jpg',
            ],
            'categories_explorer' => [
                'display_title' => 'Explorer',
                'subtitle' => 'Sélection par catégorie',
                'items' => $rootCategories->toArray(),
            ],
            'new_collection_scroll' => [
                'sidebar_text' => 'Nouvelle Collection',
                'intro' => [
                    'title_line_1' => 'BYLIN',
                    'title_line_2' => 'NEW GEN',
                    'description' => 'Une esthétique brute forgée dans les rues de Cotonou...',
                ],
                'looks' => $newCollection->toArray(),
                'see_more_link' => ['label' => 'Tout Voir ->', 'url' => '/shop'],
            ],
            'shop_selection' => [
                'title' => 'Sélection Boutique',
                'items' => $selection->toArray(),
            ],
            'faq' => [
                [
                    'q' => 'Livraison & Délais ?',
                    'a' => 'Expédition rapide depuis Cotonou...',
                ],
                [
                    'q' => 'Politique de Retour ?',
                    'a' => 'Retours acceptés sous 14 jours...',
                ],
                [
                    'q' => 'Sizing & Coupes ?',
                    'a' => 'Nos pièces suivent une esthétique Oversize...',
                ],
            ],
        ];
    }

    private function formatProductForHome($product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price_formatted' => number_format((float)$product->price, 0, ',', ' ') . ' FCFA',
            'material' => $product->meta_data['material'] ?? 'Coton Bio', // Fallback or meta field
            'image' => $product->getFirstMediaUrl('images') ?: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=1000&auto=format&fit=crop',
            'tag' => $product->is_featured ? 'BESTSELLER' : 'NEW',
            'url' => '/product/' . $product->slug,
        ];
    }
}
