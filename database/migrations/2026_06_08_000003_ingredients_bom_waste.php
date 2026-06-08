<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Table des matières premières (ingredients) ──
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('unit_of_measure', 20); // kg, litre, piece, gramme, cl, ml
            $table->decimal('cost_per_unit', 10, 4); // Coût par unité de mesure
            $table->decimal('stock_quantity', 12, 3)->default(0); // Stock actuel
            $table->decimal('alert_threshold', 12, 3)->default(0); // Seuil d'alerte
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'is_active']);
        });

        // ── 2. Table pivot Fiches Techniques (BOM) ──
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_required', 10, 3); // Quantité nécessaire pour 1 unité du produit
            $table->string('unit_of_measure', 20); // Doit correspondre à l'unité de l'ingrédient
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
            $table->index('product_id');
        });

        // ── 3. Table des pertes/démarques (waste_logs) ──
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['product', 'ingredient']); // Type d'item perdu
            $table->unsignedBigInteger('item_id'); // ID polymorphique
            $table->string('item_name', 100); // Nom au moment de la perte (snapshot)
            $table->decimal('quantity', 12, 3);
            $table->string('unit_of_measure', 20);
            $table->decimal('cost_at_loss', 10, 2)->default(0); // Coût au moment de la perte
            $table->string('reason', 50); // casse, avariation, expiration, erreur_preparation, autre
            $table->text('notes')->nullable();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['restaurant_id', 'item_type', 'created_at']);
            $table->index(['item_type', 'item_id']);
        });

        // ── 4. Ajouter cost_price calculé dans products ──
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'cost_price_calculated')) {
                $table->decimal('cost_price_calculated', 10, 2)->default(0)->after('cost_price');
            }
            if (!Schema::hasColumn('products', 'food_cost_percentage')) {
                $table->decimal('food_cost_percentage', 5, 2)->default(0)->after('cost_price_calculated');
            }
            if (!Schema::hasColumn('products', 'margin_percentage')) {
                $table->decimal('margin_percentage', 5, 2)->default(0)->after('food_cost_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost_price_calculated', 'food_cost_percentage', 'margin_percentage']);
        });
        Schema::dropIfExists('waste_logs');
        Schema::dropIfExists('product_ingredients');
        Schema::dropIfExists('ingredients');
    }
};
