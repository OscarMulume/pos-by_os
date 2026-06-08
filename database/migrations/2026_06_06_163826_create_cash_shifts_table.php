<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_shifts')) return;
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Caissier
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Fond de caisse d'ouverture (théorique)
            $table->decimal('opening_balance_fc', 12, 2)->default(0);
            $table->decimal('opening_balance_usd', 12, 2)->default(0);

            // Comptage de fermeture (déclaré par le caissier - à l'aveugle)
            $table->decimal('closing_count_fc', 12, 2)->nullable();
            $table->decimal('closing_count_usd', 12, 2)->nullable();

            // Montants théoriques calculés par le système
            $table->decimal('expected_fc', 12, 2)->nullable();
            $table->decimal('expected_usd', 12, 2)->nullable();

            // Écarts (calculés automatiquement)
            $table->decimal('gap_fc', 12, 2)->nullable();
            $table->decimal('gap_usd', 12, 2)->nullable();

            // Rapport Z
            $table->decimal('total_sales_fc', 12, 2)->default(0);
            $table->decimal('total_sales_usd', 12, 2)->default(0);
            $table->decimal('total_refunds_fc', 12, 2)->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('total_cancelled')->default(0);

            $table->enum('status', ['open', 'closing', 'closed', 'audited'])->default('open');
            $table->foreignId('audited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
    }
};
