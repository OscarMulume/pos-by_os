<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des variantes de produits (options)
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100); // Ex: "Accompagnement", "Cuisson", "Taille"
                $table->boolean('is_required')->default(false);
                $table->boolean('allow_multiple')->default(false); // Choix multiple ou unique
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Table des options de variantes
        if (!Schema::hasTable('product_variant_options')) {
            Schema::create('product_variant_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100); // Ex: "Frites", "Alloco", "Pimenté"
                $table->decimal('price_adjustment', 10, 2)->default(0); // Supplément prix
                $table->boolean('is_default')->default(false);
                $table->boolean('is_available')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Ajouter started_at dans users (date d'embauche)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('address');
            }
        });

        // Ajouter champs settings dans restaurants
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 2)->default(1)->after('tax_rate');
            }
            if (!Schema::hasColumn('restaurants', 'exchange_currency')) {
                $table->string('exchange_currency', 10)->default('USD')->after('exchange_rate');
            }
            if (!Schema::hasColumn('restaurants', 'enable_kds')) {
                $table->boolean('enable_kds')->default(true)->after('exchange_currency');
            }
            if (!Schema::hasColumn('restaurants', 'enable_thermal_print')) {
                $table->boolean('enable_thermal_print')->default(true)->after('enable_kds');
            }
            if (!Schema::hasColumn('restaurants', 'enable_whatsapp')) {
                $table->boolean('enable_whatsapp')->default(false)->after('enable_thermal_print');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_options');
        Schema::dropIfExists('product_variants');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'address', 'phone']);
        });
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'exchange_currency', 'enable_kds', 'enable_thermal_print', 'enable_whatsapp']);
        });
    }
};
