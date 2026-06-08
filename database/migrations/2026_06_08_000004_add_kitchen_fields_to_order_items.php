<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'kitchen_status')) {
                $table->string('kitchen_status', 20)->default('en_attente')->after('notes');
            }
            if (!Schema::hasColumn('order_items', 'kitchen_route')) {
                $table->string('kitchen_route', 20)->default('kitchen')->after('kitchen_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['kitchen_status', 'kitchen_route']);
        });
    }
};
