<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── pos_terminals : multi-caisses par restaurant ──
        if (!Schema::hasTable('pos_terminals')) {
            Schema::create('pos_terminals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── cash_shifts : sessions de caisse ──
        if (!Schema::hasTable('cash_shifts')) {
            Schema::create('cash_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_terminal_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('start_amount', 12, 2)->default(0);
                $table->decimal('end_amount_expected', 12, 2)->default(0);
                $table->decimal('end_amount_counted', 12, 2)->nullable();
                $table->decimal('difference', 12, 2)->default(0);
                $table->string('status', 20)->default('open');
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // ── Ajouts restaurants ──
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'status')) {
                $table->string('status', 30)->default('active');
            }
            if (!Schema::hasColumn('restaurants', 'type')) {
                $table->string('type', 20)->default('permanent');
            }
            if (!Schema::hasColumn('restaurants', 'photo_path')) {
                $table->string('photo_path', 255)->nullable();
            }
            if (!Schema::hasColumn('restaurants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable();
            }
        });

        // ── Ajouts users ──
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pin_code')) {
                $table->string('pin_code', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'webauthn_id')) {
                $table->string('webauthn_id', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'pos_terminal_id')) {
                $table->unsignedInteger('pos_terminal_id')->nullable();
            }
        });

        // ── Ajout orders : lien vers pos_terminal ──
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'pos_terminal_id')) {
                $table->unsignedInteger('pos_terminal_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('pos_terminals');

        // SQLite ne supporte pas dropColumn en batch — on recrée les tables
        // Pour simplifier, on laisse les colonnes orphelines (pas de down destructif)
    }
};
