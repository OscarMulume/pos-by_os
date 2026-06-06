<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plats Principaux', 'icon' => '🍚', 'color' => '#EF4444'],
            ['name' => 'Boissons', 'icon' => '🥤', 'color' => '#3B82F6'],
            ['name' => 'Entrées', 'icon' => '🥗', 'color' => '#10B981'],
            ['name' => 'Desserts', 'icon' => '🍰', 'color' => '#F59E0B'],
            ['name' => 'Accompagnements', 'icon' => '🍟', 'color' => '#8B5CF6'],
            ['name' => 'Alcools', 'icon' => '🍾', 'color' => '#EC4899'],
            ['name' => 'Café & Thé', 'icon' => '☕', 'color' => '#78350F'],
        ];

        for ($restoId = 1; $restoId <= 4; $restoId++) {
            foreach ($categories as $index => $cat) {
                Category::create([
                    'restaurant_id' => $restoId,
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'display_order' => $index,
                    'is_active' => true,
                ]);
            }
        }
    }
}
