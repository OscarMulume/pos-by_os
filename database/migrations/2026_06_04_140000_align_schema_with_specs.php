<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Ajouter pos_terminal_id dans orders ──
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'pos_terminal_id')) {
                $table->foreignId('pos_terminal_id')->nullable()->after('restaurant_id')
                    ->constrained()->nullOnDelete();
            }
        });

        // ── 2. Corriger restaurant_tables : remplacer restaurant_id par pos_terminal_id ──
        // D'abord vérifier si on doit recréer la table
        if (Schema::hasColumn('restaurant_tables', 'restaurant_id')) {
            // SQLite ne supporte pas DROP COLUMN facilement, on recrée
            Schema::create('restaurant_tables_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_terminal_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 50);
                $table->string('status', 20)->default('libre');
                $table->unsignedInteger('capacity')->nullable();
                $table->string('zone', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Copier les données existantes
            $tables = DB::table('restaurant_tables')->get();
            foreach ($tables as $t) {
                DB::table('restaurant_tables_new')->insert([
                    'id' => $t->id,
                    'pos_terminal_id' => $t->pos_terminal_id ?? null,
                    'name' => $t->name,
                    'status' => $t->status ?? 'libre',
                    'capacity' => $t->capacity ?? null,
                    'zone' => $t->zone ?? null,
                    'is_active' => $t->is_active ?? true,
                    'created_at' => $t->created_at ?? now(),
                    'updated_at' => $t->updated_at ?? now(),
                ]);
            }

            Schema::drop('restaurant_tables');
            Schema::rename('restaurant_tables_new', 'restaurant_tables');
        }

        // ── 3. Ajouter champs manquants dans restaurants ──
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'receipt_header')) {
                $table->string('receipt_header', 255)->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'receipt_footer')) {
                $table->string('receipt_footer', 255)->nullable();
            }
        });

        // ── 4. Ajouter stock_quantity dans products ──
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('price');
            }
            if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'track_inventory')) {
                $table->boolean('track_inventory')->default(false)->after('low_stock_threshold');
            }
        });

        // ── 5. Table stock_movements pour traçabilité ──
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 20); // 'sale', 'adjustment', 'return', 'initial'
                $table->integer('quantity'); // négatif pour sortie, positif pour entrée
                $table->integer('stock_before');
                $table->integer('stock_after');
                $table->string('reason', 255)->nullable();
                $table->timestamps();
            });
        }

        // ── 6. Ajouter last_sync_at pour période de grâce licence ──
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pos_terminal_id']);
            $table->dropColumn('pos_terminal_id');
        });
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['receipt_header', 'receipt_footer', 'last_sync_at']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'low_stock_threshold', 'track_inventory']);
        });
        Schema::dropIfExists('stock_movements');
    }
};
