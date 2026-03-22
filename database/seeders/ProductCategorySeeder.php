<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Extensions Cheveux',  'color' => '#c4956a', 'sort_order' => 1],
            ['name' => 'Soins Capillaires',   'color' => '#d4af37', 'sort_order' => 2],
            ['name' => 'Accessoires Tresses', 'color' => '#e14d6e', 'sort_order' => 3],
            ['name' => 'Produits Naturels',   'color' => '#4a7c59', 'sort_order' => 4],
            ['name' => 'Bijoux de Tresses',   'color' => '#9b59b6', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, ['slug' => Str::slug($cat['name']), 'is_active' => true])
            );
        }
    }
}