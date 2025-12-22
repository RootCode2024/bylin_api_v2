<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Géré par les policies
    }

    public function rules(): array
    {
        return [
            // Informations de base
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],

            // Prix
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gt:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],

            // Stock
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_inventory' => ['boolean'],

            // Précommande - NOMS UNIFORMISÉS
            'is_preorder_enabled' => ['boolean'],
            'preorder_available_date' => ['nullable', 'date', 'after:today'],
            'preorder_limit' => ['nullable', 'integer', 'min:1'],
            'preorder_message' => ['nullable', 'string', 'max:255'],
            'preorder_terms' => ['nullable', 'string', 'max:1000'],

            // SEO
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'array'],

            // Dimensions
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric', 'min:0'],
            'dimensions.width' => ['nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0'],

            // Relations
            'brand_id' => ['nullable', 'exists:brands,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],

            // Status
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'is_featured' => ['boolean'],
            'is_new' => ['boolean'],
            'is_on_sale' => ['boolean'],

            // Variabilité
            'is_variable' => ['boolean'],
            'variation_attributes' => ['nullable', 'array'],
            'variation_attributes.*' => ['required', 'exists:attributes,id'],

            // Bylin Authenticity
            'requires_authenticity' => ['boolean'],
            'authenticity_codes_count' => ['nullable', 'integer', 'min:1', 'max:10000'],

            // Media
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'price.required' => 'Le prix est obligatoire.',
            'price.min' => 'Le prix doit être positif.',
            'compare_price.gt' => 'Le prix comparatif doit être supérieur au prix de vente.',
            'preorder_available_date.after' => 'La date de disponibilité doit être dans le futur.',
            'images.*.image' => 'Le fichier doit être une image.',
            'images.*.max' => 'L\'image ne doit pas dépasser 5Mo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'track_inventory' => $this->boolean('track_inventory', true),
            'is_preorder_enabled' => $this->boolean('is_preorder_enabled', false),
            'is_featured' => $this->boolean('is_featured', false),
            'is_new' => $this->boolean('is_new', false),
            'is_on_sale' => $this->boolean('is_on_sale', false),
            'is_variable' => $this->boolean('is_variable', false),
            'requires_authenticity' => $this->boolean('requires_authenticity', false),
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        return array_merge([
            'stock_quantity' => 0,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
            'is_preorder_enabled' => false,
            'is_featured' => false,
            'is_new' => false,
            'is_on_sale' => false,
            'is_variable' => false,
            'requires_authenticity' => false,
            'status' => 'draft',
        ], $data);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Précommande manuelle impossible avec du stock
            if ($this->is_preorder_enabled && $this->stock_quantity > 0) {
                $validator->errors()->add(
                    'is_preorder_enabled',
                    'Un produit avec du stock ne peut pas être en précommande manuelle.'
                );
            }

            // Authenticity réservée à Bylin
            if ($this->requires_authenticity && $this->brand_id) {
                $brand = \Modules\Catalogue\Models\Brand::find($this->brand_id);
                if ($brand && $brand->slug !== 'bylin') {
                    $validator->errors()->add(
                        'requires_authenticity',
                        'L\'authentification est réservée aux produits Bylin.'
                    );
                }
            }
        });
    }
}
