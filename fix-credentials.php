<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Restaurant;
use App\Models\PosTerminal;
use App\Models\RestaurantTable;
use App\Models\Product;
use App\Models\Category;

echo "═══════════════════════════════════════════\n";
echo "  RECONSTRUCTION DES IDENTIFIANTS\n";
echo "═══════════════════════════════════════════\n\n";

// ── 1. Super-Admin ──────────────────────────
$super = User::where('role', 'super_admin')->first();
if ($super) {
    $super->update([
        'email'    => 'superadmin@msec-pos.com',
        'password' => Hash::make('SuperSecurise2026!'),
        'name'     => 'Super Admin',
    ]);
    echo "[OK] Super-Admin: superadmin@msec-pos.com / SuperSecurise2026!\n";
} else {
    User::create([
        'name'     => 'Super Admin',
        'email'    => 'superadmin@msec-pos.com',
        'password' => Hash::make('SuperSecurise2026!'),
        'role'     => 'super_admin',
        'is_active' => true,
    ]);
    echo "[OK] Super-Admin créé: superadmin@msec-pos.com / SuperSecurise2026!\n";
}

// ── 2. Restaurant de démo ──────────────────
$restaurant = Restaurant::firstOrCreate(
    ['name' => 'MSEC Restaurant Démo'],
    [
        'address'      => 'Avenue Kenda, Commune de Gombe, Kinshasa',
        'phone'        => '+243 81 234 5678',
        'email'        => 'contact@msec-restaurant.com',
        'currency'     => 'FC',
        'tax_rate'     => 0,
        'type'         => 'permanent',
        'status'       => 'active',
        'is_active'    => true,
        'receipt_header' => 'MSEC Restaurant',
        'receipt_footer' => 'Merci de votre visite!',
    ]
);
echo "[OK] Restaurant: {$restaurant->name} (ID: {$restaurant->id})\n";

// ── 3. Terminaux POS ────────────────────────
$t1 = PosTerminal::firstOrCreate(
    ['name' => 'Caisse Principale', 'restaurant_id' => $restaurant->id],
    ['is_active' => true]
);
$t2 = PosTerminal::firstOrCreate(
    ['name' => 'Bar', 'restaurant_id' => $restaurant->id],
    ['is_active' => true]
);
echo "[OK] Terminaux: Caisse Principale, Bar\n";

// ── 4. Tables ──────────────────────────────
$zones = ['Salle' => 6, 'Terrasse' => 4, 'VIP' => 2];
foreach ($zones as $zone => $count) {
    for ($i = 1; $i <= $count; $i++) {
        RestaurantTable::firstOrCreate(
            ['name' => "$zone $i", 'pos_terminal_id' => $t1->id],
            ['status' => 'libre', 'capacity' => 4, 'zone' => $zone, 'is_active' => true]
        );
    }
}
echo "[OK] Tables: " . RestaurantTable::count() . " créées\n";

// ── 5. Catégories ──────────────────────────
$cats = ['Plats', 'Boissons', 'Desserts', 'Entrées'];
foreach ($cats as $catName) {
    Category::firstOrCreate(
        ['name' => $catName, 'restaurant_id' => $restaurant->id],
        ['is_active' => true, 'sort_order' => 0]
    );
}
echo "[OK] Catégories créées\n";

// ── 6. Produits ────────────────────────────
$products = [
    ['name' => 'Poulet Choma', 'price' => 15000, 'cat' => 'Plats', 'stock' => 50],
    ['name' => 'Poulet Braisé', 'price' => 12000, 'cat' => 'Plats', 'stock' => 40],
    ['name' => 'Poisson Grillé', 'price' => 18000, 'cat' => 'Plats', 'stock' => 30],
    ['name' => 'Riz Sauce Arachide', 'price' => 8000, 'cat' => 'Plats', 'stock' => 60],
    ['name' => 'Frites', 'price' => 5000, 'cat' => 'Entrées', 'stock' => 100],
    ['name' => 'Salade de Choux', 'price' => 4000, 'cat' => 'Entrées', 'stock' => 40],
    ['name' => 'Buc Coca-Cola', 'price' => 3000, 'cat' => 'Boissons', 'stock' => 200],
    ['name' => 'Buc Fanta', 'price' => 3000, 'cat' => 'Boissons', 'stock' => 200],
    ['name' => 'Eau Minérale', 'price' => 1500, 'cat' => 'Boissons', 'stock' => 300],
    ['name' => 'Jus de Gingembre', 'price' => 4000, 'cat' => 'Boissons', 'stock' => 50],
    ['name' => 'Crème Glacée', 'price' => 6000, 'cat' => 'Desserts', 'stock' => 30],
    ['name' => 'Tiramisu', 'price' => 7000, 'cat' => 'Desserts', 'stock' => 25],
];
foreach ($products as $p) {
    $cat = Category::where('name', $p['cat'])->where('restaurant_id', $restaurant->id)->first();
    Product::firstOrCreate(
        ['name' => $p['name'], 'restaurant_id' => $restaurant->id],
        [
            'category_id' => $cat?->id,
            'price' => $p['price'],
            'is_available' => true,
            'stock_quantity' => $p['stock'],
            'low_stock_threshold' => 10,
            'track_inventory' => true,
            'sort_order' => 0,
        ]
    );
}
echo "[OK] Produits: " . Product::where('restaurant_id', $restaurant->id)->count() . " créés\n";

// ── 7. Manager ─────────────────────────────
$manager = User::firstOrCreate(
    ['email' => 'manager.demo@msec-pos.com'],
    [
        'name'     => 'Manager Démo',
        'password' => Hash::make('Manager2026!'),
        'pin_code' => Hash::make('1111'),
        'role'     => 'manager',
        'restaurant_id' => $restaurant->id,
        'is_active' => true,
        'started_at' => now(),
    ]
);
echo "[OK] Manager: manager.demo@msec-pos.com / Manager2026! / PIN: 1111\n";

// ── 8. Caissier ────────────────────────────
$caissier = User::firstOrCreate(
    ['email' => 'caisse1.demo@msec-pos.com'],
    [
        'name'     => 'Caissier Démo',
        'password' => Hash::make('Caisse2026!'),
        'pin_code' => Hash::make('2222'),
        'role'     => 'cashier',
        'restaurant_id' => $restaurant->id,
        'pos_terminal_id' => $t1->id,
        'is_active' => true,
        'started_at' => now(),
    ]
);
echo "[OK] Caissier: caisse1.demo@msec-pos.com / Caisse2026! / PIN: 2222\n";

// ── 9. Cuisinier ───────────────────────────
$cuisinier = User::firstOrCreate(
    ['email' => 'cuisine1.demo@msec-pos.com'],
    [
        'name'     => 'Cuisinier Démo',
        'password' => Hash::make('Cuisine2026!'),
        'pin_code' => Hash::make('3333'),
        'role'     => 'cook',
        'restaurant_id' => $restaurant->id,
        'is_active' => true,
        'started_at' => now(),
    ]
);
echo "[OK] Cuisinier: cuisine1.demo@msec-pos.com / Cuisine2026! / PIN: 3333\n";

// ═══════════════════════════════════════════
// RÉSUMÉ FINAL
// ═══════════════════════════════════════════
echo "\n";
echo "═══════════════════════════════════════════\n";
echo "  IDENTIFIANTS — RÉSUMÉ FINAL\n";
echo "═══════════════════════════════════════════\n\n";

$allUsers = User::orderBy('role')->get();
foreach ($allUsers as $u) {
    $pin = match($u->email) {
        'manager.demo@msec-pos.com' => '1111',
        'caisse1.demo@msec-pos.com' => '2222',
        'cuisine1.demo@msec-pos.com' => '3333',
        default => 'N/A',
    };
    $pwd = match($u->email) {
        'superadmin@msec-pos.com' => 'SuperSecurise2026!',
        'manager.demo@msec-pos.com' => 'Manager2026!',
        'caisse1.demo@msec-pos.com' => 'Caisse2026!',
        'cuisine1.demo@msec-pos.com' => 'Cuisine2026!',
        default => '???',
    };
    echo strtoupper($u->role) . ":\n";
    echo "  Email:    {$u->email}\n";
    echo "  Password: {$pwd}\n";
    echo "  PIN:      {$pin}\n";
    echo "  Username: {$u->username}\n\n";
}

echo "═══════════════════════════════════════════\n";
echo "Serveur: http://127.0.0.1:8000\n";
echo "         http://172.30.112.168:8000\n";
echo "═══════════════════════════════════════════\n";
