<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('prep_time_minutes')->default(15)->after('sort_order');
            $table->string('kitchen_route')->default('kitchen')->after('prep_time_minutes');
            // kitchen_route: 'kitchen' = envoyé au KDS, 'bar' = écran bar, 'counter' = reste au comptoir
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['prep_time_minutes', 'kitchen_route']);
        });
    }
};
