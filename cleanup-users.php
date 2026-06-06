<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "═══════════════════════════════════════════\n";
echo "  NETTOYAGE DES ANCIENS COMPTES\n";
echo "═══════════════════════════════════════════\n\n";

// Supprimer les anciens comptes qui ne correspondent pas aux specs
$oldEmails = ['admin@pos.local', 'caissier1@pos.local', 'caissier2@pos.local', 'superadmin@pos.local'];
foreach ($oldEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->delete();
        echo "[SUPPRIMÉ] {$email}\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════\n";
echo "  COMPTES ACTUELS (FINAL)\n";
echo "═══════════════════════════════════════════\n\n";

$allUsers = User::orderBy('role')->get();
foreach ($allUsers as $u) {
    echo str_pad(strtoupper($u->role), 15) . " | {$u->email} | PIN: ";
    echo match($u->email) {
        'manager.demo@msec-pos.com' => '1111',
        'caisse1.demo@msec-pos.com' => '2222',
        'cuisine1.demo@msec-pos.com' => '3333',
        default => 'N/A',
    };
    echo "\n";
}

echo "\nTotal: " . User::count() . " utilisateurs\n";
