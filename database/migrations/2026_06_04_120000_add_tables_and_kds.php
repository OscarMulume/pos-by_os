<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Table des tables de restaurant ──
        if (!Schema::hasTable('restaurant_tables')) {
            Schema::create('restaurant_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_terminal_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 50); // "Table 5", "Terrasse 2", "Bar 1"
                $table->string('status', 20)->default('libre'); // libre, occupee
                $table->unsignedInteger('capacity')->nullable(); // nombre de places
                $table->string('zone', 30)->nullable(); // "Salle", "Terrasse", "Bar", "VIP"
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── Ajouts table orders : liaison table + KDS ──
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'table_id')) {
                $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'kitchen_status')) {
                $table->string('kitchen_status', 20)->default('en_attente'); // en_attente, en_preparation, pret
            }
            if (!Schema::hasColumn('orders', 'sent_to_kitchen_at')) {
                $table->timestamp('sent_to_kitchen_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn(['table_id', 'kitchen_status', 'sent_to_kitchen_at', 'ready_at']);
        });
        Schema::dropIfExists('restaurant_tables');
    }
};
