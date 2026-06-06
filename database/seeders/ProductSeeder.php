<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            0 => [
                ['name' => 'Poulet Choma', 'price' => 15000, 'cost' => 8000, 'desc' => 'Poulet grille aux epices'],
                ['name' => 'Poisson Braise', 'price' => 20000, 'cost' => 12000, 'desc' => 'Poisson entier braise'],
                ['name' => 'Porc Roti', 'price' => 18000, 'cost' => 10000, 'desc' => 'Roti de porc caramelise'],
                ['name' => 'Ndole Arachides', 'price' => 12000, 'cost' => 6000, 'desc' => 'Ndole aux arachides fraiches'],
                ['name' => 'Saka-Saka', 'price' => 10000, 'cost' => 5000, 'desc' => 'Feuilles de manioc pilees'],
                ['name' => 'Liboke Poisson', 'price' => 22000, 'cost' => 13000, 'desc' => 'Poisson cuit dans la feuille'],
            ],
            4 => [
                ['name' => 'Riz Gras', 'price' => 5000, 'cost' => 2000, 'desc' => 'Riz cuit a l\'huile rouge'],
                ['name' => 'Frites', 'price' => 4000, 'cost' => 1500, 'desc' => 'Pommes de terre frites'],
                ['name' => 'Bananes Plantain', 'price' => 3500, 'cost' => 1000, 'desc' => 'Plantain grille'],
                ['name' => 'Pondu', 'price' => 3000, 'cost' => 1000, 'desc' => 'Feuilles de manioc cuites'],
                ['name' => 'Chikwangue', 'price' => 2500, 'cost' => 800, 'desc' => 'Pate de manioc fermentee'],
                ['name' => 'Baton de Manioc', 'price' => 2500, 'cost' => 800, 'desc' => 'Fufu traditionnel'],
            ],
            1 => [
                ['name' => 'Eau 50cl', 'price' => 2000, 'cost' => 500, 'desc' => 'Eau minerale naturelle'],
                ['name' => 'Jus de Fruit', 'price' => 3500, 'cost' => 1200, 'desc' => 'Jus naturel (mangue, ananas)'],
                ['name' => 'Biere Primus', 'price' => 3000, 'cost' => 1500, 'desc' => 'Biere locale 33cl'],
                ['name' => 'Biere Turbo King', 'price' => 3500, 'cost' => 1800, 'desc' => 'Biere locale premium'],
                ['name' => 'Soda', 'price' => 2500, 'cost' => 800, 'desc' => 'Coca, Fanta, Sprite'],
                ['name' => 'Malaga', 'price' => 2000, 'cost' => 1000, 'desc' => 'Vin de palme traditionnel'],
            ],
            2 => [
                ['name' => 'Salade Composee', 'price' => 6000, 'cost' => 3000, 'desc' => 'Salade fraiche maison'],
                ['name' => 'Sambolay', 'price' => 5000, 'cost' => 2000, 'desc' => 'Beignets de farine epices'],
                ['name' => 'Croquettes Crevettes', 'price' => 7000, 'cost' => 3500, 'desc' => 'Croquettes dorees aux crevettes'],
                ['name' => 'Samoussa Viande', 'price' => 4000, 'cost' => 2000, 'desc' => 'Triangle croustillant a la viande'],
            ],
            3 => [
                ['name' => 'Boule de Neige', 'price' => 3000, 'cost' => 1000, 'desc' => 'Beignet sucre'],
                ['name' => 'Tarte Maison', 'price' => 5000, 'cost' => 2000, 'desc' => 'Tarte du jour'],
                ['name' => 'Creme Caramel', 'price' => 4000, 'cost' => 1500, 'desc' => 'Dessert lacte sucre'],
            ],
            5 => [
                ['name' => 'Vin Rouge', 'price' => 15000, 'cost' => 8000, 'desc' => 'Vin rouge importe'],
                ['name' => 'Whisky', 'price' => 25000, 'cost' => 15000, 'desc' => 'Whisky premium'],
                ['name' => 'Amarula', 'price' => 8000, 'cost' => 4000, 'desc' => 'Creme de marula'],
            ],
            6 => [
                ['name' => 'Cafe Noir', 'price' => 2000, 'cost' => 800, 'desc' => 'Cafe fort sans sucre'],
                ['name' => 'Cafe au Lait', 'price' => 2500, 'cost' => 1000, 'desc' => 'Cafe creme'],
                ['name' => 'The Nature', 'price' => 2000, 'cost' => 500, 'desc' => 'The vert ou noir'],
                ['name' => 'Chocolat Chaud', 'price' => 3000, 'cost' => 1200, 'desc' => 'Chocolat chaud cremeux'],
            ],
        ];

        for ($restoId = 1; $restoId <= 4; $restoId++) {
            $restoCategories = Category::where('restaurant_id', $restoId)->get();

            foreach ($menus as $catIndex => $items) {
                $category = $restoCategories->get($catIndex);
                $categoryId = $category ? $category->id : null;

                foreach ($items as $index => $item) {
                    Product::create([
                        'restaurant_id' => $restoId,
                        'category_id' => $categoryId,
                        'name' => $item['name'],
                        'description' => $item['desc'],
                        'price' => $item['price'],
                        'cost_price' => $item['cost'],
                        'is_available' => true,
                        'sort_order' => $index,
                        'prep_time_minutes' => $this->getPrepTime($catIndex),
                        'kitchen_route' => $this->getKitchenRoute($catIndex),
                    ]);
                }
            }
        }
    }

    private function getPrepTime(int $catIndex): int
    {
        return match ($catIndex) {
            0 => 25, // Plats Principaux
            4 => 15, // Accompagnements
            1 => 2,  // Boissons
            2 => 10, // Entrées
            3 => 8,  // Desserts
            5 => 3,  // Alcools
            6 => 5,  // Café & Thé
            default => 15,
        };
    }

    private function getKitchenRoute(int $catIndex): string
    {
        return match ($catIndex) {
            0, 2, 4 => 'kitchen', // Plats, Entrées, Accompagnements → KDS
            1, 5, 6 => 'bar',     // Boissons, Alcools, Café → Bar
            3 => 'counter',        // Desserts → Comptoir
            default => 'kitchen',
        };
    }
}
