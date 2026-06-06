<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = [
            [
                'name' => 'Restaurant Kin Plazza',
                'address' => '12 Av. Kasa-Vubu, Gombe, Kinshasa',
                'phone' => '+243 81 000 0001',
                'email' => 'kinplazza@pos.local',
            ],
            [
                'name' => 'Restaurant Ngaliema',
                'address' => '45 Blvd. Ngaliema, Ngaliema, Kinshasa',
                'phone' => '+243 81 000 0002',
                'email' => 'ngaliema@pos.local',
            ],
            [
                'name' => 'Restaurant Gombé',
                'address' => '8 Av. de la Gombé, Gombé, Kinshasa',
                'phone' => '+243 81 000 0003',
                'email' => 'gombe@pos.local',
            ],
            [
                'name' => 'Restaurant Bandal',
                'address' => '22 Av. Maman Mobutu, Bandalungwa, Kinshasa',
                'phone' => '+243 81 000 0004',
                'email' => 'bandal@pos.local',
            ],
        ];

        foreach ($restaurants as $resto) {
            Restaurant::create(array_merge($resto, [
                'currency' => 'FC',
                'tax_rate' => '0',
                'receipt_header' => $resto['name'],
                'receipt_footer' => 'Merci de votre visite!',
                'is_active' => true,
            ]));
        }
    }
}
