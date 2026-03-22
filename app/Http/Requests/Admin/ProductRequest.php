<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $productId = $this->route('produit')?->id;

        return [
            'category_id'       => 'required|exists:product_categories,id',
            'name'              => 'required|string|max:200',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'sku'               => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'price'             => 'required|numeric|min:0',
            // ✅ Fix : gt:price causait des erreurs en update si compare_price inchangé
            'compare_price'     => 'nullable|numeric|min:0',
            'cost_price'        => 'nullable|numeric|min:0',
            'stock'             => 'required|integer|min:0',
            'low_stock_alert'   => 'nullable|integer|min:0',
            'weight'            => 'nullable|numeric|min:0',
            'dimensions'        => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width'  => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'track_stock'       => 'boolean',
            'allow_backorder'   => 'boolean',
            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'is_digital'        => 'boolean',
            'thumbnail'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:50',
            'attributes'        => 'nullable|array',
            'sort_order'        => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Veuillez sélectionner une catégorie.',
            'category_id.exists'   => 'Cette catégorie n\'existe pas.',
            'name.required'        => 'Le nom du produit est obligatoire.',
            'price.required'       => 'Le prix est obligatoire.',
            'price.numeric'        => 'Le prix doit être un nombre.',
            'stock.required'       => 'Le stock est obligatoire.',
            'sku.unique'           => 'Ce SKU est déjà utilisé par un autre produit.',
            'thumbnail.image'      => 'La miniature doit être une image.',
            'thumbnail.max'        => 'La miniature ne doit pas dépasser 5 Mo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convertir les booleans envoyés en string depuis le formulaire
        $this->merge([
            'track_stock'    => filter_var($this->track_stock, FILTER_VALIDATE_BOOLEAN),
            'allow_backorder'=> filter_var($this->allow_backorder, FILTER_VALIDATE_BOOLEAN),
            'is_active'      => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            'is_featured'    => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            'is_digital'     => filter_var($this->is_digital, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}