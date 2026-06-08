<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_alert_threshold')->default(5)->after('low_stock_threshold');
            $table->string('stock_status')->default('normal')->after('stock_alert_threshold');
            // stock_status: 'normal', 'low', 'critique', 'rupture'
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_status', 'stock_alert_threshold']);
        });
    }
};
