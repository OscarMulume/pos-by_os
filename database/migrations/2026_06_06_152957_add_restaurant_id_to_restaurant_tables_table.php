<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });

        // Migrer les données existantes : retrouver le restaurant via le terminal
        $tables = DB::table('restaurant_tables')->get();
        foreach ($tables as $t) {
            if ($t->pos_terminal_id) {
                $terminal = DB::table('pos_terminals')->where('id', $t->pos_terminal_id)->first();
                if ($terminal) {
                    DB::table('restaurant_tables')
                        ->where('id', $t->id)
                        ->update(['restaurant_id' => $terminal->restaurant_id]);
                }
            }
        }

        // Pour les restaurants sans tables, créer des tables par défaut
        $restaurants = DB::table('restaurants')->get();
        foreach ($restaurants as $resto) {
            $hasTables = DB::table('restaurant_tables')
                ->where('restaurant_id', $resto->id)
                ->exists();

            if (!$hasTables) {
                // Créer 12 tables par défaut pour chaque restaurant
                $zones = [
                    ['Salle', 6, 4],
                    ['Terrasse', 4, 2],
                    ['VIP', 2, 6],
                ];
                $order = 0;
                foreach ($zones as [$zoneName, $count, $capacity]) {
                    for ($i = 1; $i <= $count; $i++) {
                        DB::table('restaurant_tables')->insert([
                            'restaurant_id' => $resto->id,
                            'name' => $zoneName . ' ' . $i,
                            'zone' => $zoneName,
                            'capacity' => $capacity,
                            'status' => 'libre',
                            'is_active' => true,
                            'pos_terminal_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });
    }
};
