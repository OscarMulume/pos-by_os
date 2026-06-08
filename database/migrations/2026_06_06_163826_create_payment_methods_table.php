<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) return;
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Ex: Cash USD, Cash FC, M-Pesa, Orange Money
            $table->string('slug')->unique(); // cash_usd, cash_fc, m_pesa, orange_money, card
            $table->string('type'); // cash, mobile_money, card, credit
            $table->string('currency')->default('FC'); // FC, USD, MULTI
            $table->string('icon')->nullable(); // emoji ou classe icon
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot restaurant <-> payment_methods (pour activer/désactiver par restaurant)
        Schema::create('restaurant_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_payment_method');
        Schema::dropIfExists('payment_methods');
    }
};
