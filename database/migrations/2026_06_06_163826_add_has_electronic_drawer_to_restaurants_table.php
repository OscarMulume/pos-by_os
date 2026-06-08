<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('has_electronic_drawer')->default(true)->after('tax_rate');
            $table->unsignedSmallInteger('default_cash_register_count')->default(1)->after('has_electronic_drawer');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['has_electronic_drawer', 'default_cash_register_count']);
        });
    }
};
