<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name'              => 'Box Braids Classiques',
                'category'          => 'braids',
                'duration'          => 240,
                'price'             => 150.00,
                'deposit_amount'    => 30.00,
                'deposit_required'  => true,
                'short_description' => 'Tresses box braids classiques, élégantes et durables.',
                'includes'          => ['Shampoing inclus', 'Soin avant tresses', 'Coiffure de départ'],
                'requirements'      => ['Cheveux propres et secs', 'Pas de tresses existantes'],
                'is_featured'       => true,
            ],
            [
                'name'              => 'Knotless Braids',
                'category'          => 'braids',
                'duration'          => 300,
                'price'             => 200.00,
                'deposit_amount'    => 40.00,
                'deposit_required'  => true,
                'short_description' => 'Tresses sans nœuds pour un résultat ultra naturel et confortable.',
                'includes'          => ['Shampoing inclus', 'Soin kératine', 'Styling final'],
                'requirements'      => ['Cheveux propres', 'Longueur minimum 5cm'],
                'is_featured'       => true,
            ],
            [
                'name'              => 'Twists Sénégalais',
                'category'          => 'twists',
                'duration'          => 180,
                'price'             => 130.00,
                'deposit_amount'    => 25.00,
                'deposit_required'  => true,
                'short_description' => 'Twists sénégalais longs et brillants, tendance et chic.',
                'includes'          => ['Shampoing', 'Hydratation'],
                'is_featured'       => true,
            ],
            [
                'name'              => 'Micro Braids',
                'category'          => 'braids',
                'duration'          => 420,
                'price'             => 280.00,
                'deposit_amount'    => 60.00,
                'deposit_required'  => true,
                'short_description' => 'Micro tresses ultra fines pour un style unique et raffiné.',
                'includes'          => ['Shampoing premium', 'Soin intensif', 'Séchage'],
                'is_featured'       => false,
            ],
            [
                'name'              => 'Goddess Braids',
                'category'          => 'braids',
                'duration'          => 150,
                'price'             => 100.00,
                'deposit_amount'    => 0,
                'deposit_required'  => false,
                'short_description' => 'Tresses déesse larges et sculptées, statement look garanti.',
                'is_featured'       => true,
            ],
            [
                'name'              => 'Locks Starter',
                'category'          => 'locs',
                'duration'          => 120,
                'price'             => 90.00,
                'deposit_amount'    => 0,
                'deposit_required'  => false,
                'short_description' => 'Démarrage de locks, pour commencer votre journey naturel.',
                'is_featured'       => false,
            ],
            [
                'name'              => 'Coiffure Enfant',
                'category'          => 'kids',
                'duration'          => 90,
                'price'             => 60.00,
                'deposit_amount'    => 0,
                'deposit_required'  => false,
                'short_description' => 'Coiffures adaptées aux petites têtes, avec douceur et patience.',
                'is_featured'       => false,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => Str::slug($service['name'])],
                array_merge($service, [
                    'slug'       => Str::slug($service['name']),
                    'is_active'  => true,
                    'buffer_time'=> 15,
                ])
            );
        }
    }
}