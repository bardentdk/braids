<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (Patricia)
        $admin = User::updateOrCreate(
            ['email' => 'patricia@patricia-braids.com'],
            [
                'name'              => 'Patricia Admin',
                'password'          => Hash::make('patricia2025!'),
                'role'              => UserRole::Admin,
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        // Client de démonstration
        $clientUser = User::updateOrCreate(
            ['email' => 'demo@client.com'],
            [
                'name'              => 'Amina Diallo',
                'password'          => Hash::make('demo2025!'),
                'role'              => UserRole::Client,
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        Client::updateOrCreate(
            ['email' => 'demo@client.com'],
            [
                'user_id'    => $clientUser->id,
                'first_name' => 'Amina',
                'last_name'  => 'Diallo',
                'phone'      => '06 12 34 56 78',
                'hair_type'  => '4c',
                'newsletter' => true,
            ]
        );
    }
}