<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\Searchable;

/**
 * Modèle Category (Catégorie)
 *
 * Représente une catégorie de produits avec support de hiérarchie parent-enfant.
 * Utilisé pour organiser les produits en arborescence (ex: Homme > Hauts > T-shirts).
 *
 * @property string $id Identifiant unique (UUID)
 * @property string|null $parent_id ID de la catégorie parente
 * @property string $name Nom de la catégorie
 * @property string $slug Slug URL-friendly
 * @property string|null $description Description détaillée
 * @property string|null $image Chemin de l'image de bannière
 * @property string|null $image_url URL complète de l'image
 * @property string|null $icon Icône pour l'interface
 * @property string|null $color Couleur thème (hex)
 * @property int $level Niveau hiérarchique (0 = racine)
 * @property string|null $path Chemin complet (/genre/type/categorie)
 * @property bool $is_active Catégorie active/visible
 * @property bool $is_visible_in_menu Visible dans le menu
 * @property bool $is_featured Mise en avant
 * @property int $sort_order Ordre d'affichage
 * @property array|null $meta_data Métadonnées JSON
 * @property string|null $meta_title Titre SEO
 * @property string|null $meta_description Description SEO
 * @property int $products_count Nombre de produits (calculé)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Category|null $parent Catégorie parente
 * @property-read \Illuminate\Database\Eloquent\Collection|Category[] $children Sous-catégories
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $products Produits de la catégorie
 * @property-read string $path_attribute Chemin complet formaté
 */
class Category extends BaseModel
{
    use Searchable;

    /**
     * Champs indexés pour la recherche
     */
    protected $searchableFields = ['name', 'description'];

    /**
     * Champs assignables en masse
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'color',
        'level',
        'path',
        'is_active',
        'is_visible_in_menu',
        'is_featured',
        'sort_order',
        'meta_data',
        'meta_title',
        'meta_description',
        'products_count',
    ];

    /**
     * Attributs à caster
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible_in_menu' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'level' => 'integer',
            'products_count' => 'integer',
            'meta_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Attributs ajoutés à la sérialisation
     */
    protected $appends = ['image_url'];

    // ============================================================================
    // RELATIONS
    // ============================================================================

    /**
     * Catégorie parente
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Sous-catégories (enfants directs)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Tous les descendants récursifs (enfants, petits-enfants, etc.)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Produits appartenant à cette catégorie (Many-to-Many)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'category_id',
            'product_id'
        )
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    // ============================================================================
    // ACCESSEURS & MUTATEURS
    // ============================================================================

    /**
     * URL complète de l'image
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image
                ? asset('storage/' . $this->image)
                : null
        );
    }

    // ============================================================================
    // MÉTHODES MÉTIER
    // ============================================================================

    /**
     * Récupère tous les ancêtres (parent, grand-parent, etc.)
     *
     * @return \Illuminate\Support\Collection<Category>
     */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $category = $this;

        while ($category->parent) {
            $ancestors->push($category->parent);
            $category = $category->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Récupère le chemin complet (fil d'Ariane)
     *
     * @param string $separator Séparateur entre les noms
     * @return string Ex: "Homme > Hauts > T-shirts"
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $ancestors = $this->ancestors();
        $path = $ancestors->pluck('name')->push($this->name);

        return $path->implode($separator);
    }

    /**
     * Récupère le slug complet (pour URL)
     *
     * @return string Ex: "homme/hauts/tshirts"
     */
    public function getFullSlug(): string
    {
        $ancestors = $this->ancestors();
        $slugs = $ancestors->pluck('slug')->push($this->slug);

        return $slugs->implode('/');
    }

    /**
     * Vérifie si la catégorie est une racine (pas de parent)
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Vérifie si la catégorie est une feuille (pas d'enfants)
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Vérifie si la catégorie peut avoir des produits
     * (généralement uniquement les feuilles)
     */
    public function canHaveProducts(): bool
    {
        // Pour la mode, seules les catégories de niveau 2+ peuvent avoir des produits
        return $this->level >= 2;
    }

    /**
     * Vérifie si une catégorie est descendante de cette catégorie
     */
    public function isAncestorOf(Category $category): bool
    {
        return $category->ancestors()->contains('id', $this->id);
    }

    /**
     * Vérifie si une catégorie est ancêtre de cette catégorie
     */
    public function isDescendantOf(Category $category): bool
    {
        return $this->ancestors()->contains('id', $category->id);
    }

    /**
     * Récupère le genre (catégorie racine)
     *
     * @return Category|null Ex: Homme, Femme, Enfant
     */
    public function getGenre(): ?Category
    {
        if ($this->isRoot()) {
            return $this;
        }

        $ancestors = $this->ancestors();
        return $ancestors->first(); // La première est toujours le genre
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    /**
     * Catégories racines (sans parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Catégories visibles dans le menu
     */
    public function scopeVisibleInMenu($query)
    {
        return $query->where('is_visible_in_menu', true);
    }

    /**
     * Catégories mises en avant
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Catégories avec produits
     */
    public function scopeWithProducts($query)
    {
        return $query->has('products');
    }

    /**
     * Catégories d'un niveau spécifique
     *
     * @param int $level 0 = Genre, 1 = Type, 2 = Catégorie, etc.
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Catégories filles d'un parent
     */
    public function scopeChildrenOf($query, string $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Tri par ordre défini puis par nom
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ============================================================================
    // ÉVÉNEMENTS DU MODÈLE
    // ============================================================================

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        // Calculer le niveau et le chemin avant sauvegarde
        static::saving(function (Category $category) {
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                if ($parent) {
                    $category->level = $parent->level + 1;
                    $category->path = $parent->path . '/' . $category->slug;
                }
            } else {
                $category->level = 0;
                $category->path = '/' . $category->slug;
            }
        });

        // Mettre à jour les compteurs après sauvegarde
        static::saved(function (Category $category) {
            $category->updateProductsCount();
        });

        // Supprimer les enfants en cascade lors de la suppression définitive
        static::deleting(function (Category $category) {
            if ($category->isForceDeleting()) {
                // Supprimer récursivement tous les enfants
                foreach ($category->children as $child) {
                    $child->forceDelete();
                }
            }
        });
    }

    /**
     * Met à jour le compteur de produits
     */
    public function updateProductsCount(): void
    {
        $this->products_count = $this->products()->count();
        $this->saveQuietly(); // Sans déclencher les événements
    }
}
