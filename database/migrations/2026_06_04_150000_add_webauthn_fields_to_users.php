<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'webauthn_public_key')) {
                $table->text('webauthn_public_key')->nullable()->after('webauthn_id');
            }
            if (!Schema::hasColumn('users', 'webauthn_name')) {
                $table->string('webauthn_name', 100)->nullable()->after('webauthn_public_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['webauthn_public_key', 'webauthn_name']);
        });
    }
};
