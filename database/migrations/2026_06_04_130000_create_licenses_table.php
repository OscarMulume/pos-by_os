<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licenses')) {
            Schema::create('licenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->text('token');
                $table->string('plan', 30)->default('basic'); // basic, pro, enterprise
                $table->timestamp('expires_at');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('max_tables')->default(20);
                $table->unsignedInteger('max_terminals')->default(5);
                $table->json('features')->nullable();
                $table->timestamps();
            });
        }

        // Ajouter last_license_check au restaurant
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'last_license_check')) {
                $table->timestamp('last_license_check')->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'offline_activated_at')) {
                $table->timestamp('offline_activated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['last_license_check', 'offline_activated_at']);
        });
    }
};
