<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_payments')) return;
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 3); // FC, USD
            $table->decimal('exchange_rate_applied', 10, 4)->default(1);
            $table->decimal('amount_in_base_currency', 12, 2); // Converti en FC pour uniformiser
            $table->decimal('change_given', 12, 2)->default(0);
            $table->string('change_currency', 3)->nullable();
            $table->string('reference')->nullable(); // Référence transaction mobile money
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
