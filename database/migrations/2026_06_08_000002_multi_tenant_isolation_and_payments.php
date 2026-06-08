<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── S'assurer que restaurant_id est obligatoire pour les rôles staff ──
        // Ajouter une contrainte NOT NULL conditionnelle via un trigger applicatif
        // (on rend le champ NULLABLE à la DB level pour super_admin, mais on force via validation)

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'restaurant_id')) {
                // S'assurer que le FK existe
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            }
        });

        // ── S'assurer que toutes les tables de restaurant sont filtrées par FK ──
        // Ajouter FK manquantes si nécessaire
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'restaurant_id')) {
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'restaurant_id')) {
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'restaurant_id')) {
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            }
        });

        // ── Ajouter order_payments table pour paiements fractionnés ──
        if (!Schema::hasTable('order_payments')) {
            Schema::create('order_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->enum('payment_method', ['cash', 'mobile_money', 'credit', 'card']);
                $table->decimal('amount', 12, 2);
                $table->string('currency', 5)->default('FC');
                $table->string('exchange_rate', 20)->nullable()->default('1');
                $table->string('payment_reference', 100)->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['order_id']);
                $table->index(['restaurant_id', 'created_at']);
            });
        }

        // ── Ajouter has_electronic_drawer si absent ──
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'has_electronic_drawer')) {
                $table->boolean('has_electronic_drawer')->default(true)->after('tax_rate');
            }
            if (!Schema::hasColumn('restaurants', 'sla_warning_minutes')) {
                $table->unsignedInteger('sla_warning_minutes')->default(30)->after('has_electronic_drawer');
            }
            if (!Schema::hasColumn('restaurants', 'default_currency')) {
                $table->string('default_currency', 5)->default('FC')->after('currency');
            }
            if (!Schema::hasColumn('restaurants', 'secondary_currency')) {
                $table->string('secondary_currency', 5)->default('USD')->after('default_currency');
            }
            if (!Schema::hasColumn('restaurants', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 2)->default(2850)->after('secondary_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['has_electronic_drawer', 'sla_warning_minutes', 'default_currency', 'secondary_currency', 'exchange_rate']);
        });
        Schema::dropIfExists('order_payments');
    }
};
