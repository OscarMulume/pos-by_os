<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Refonte restaurant_tables.status ──
        // Ajouter les champs manquants pour la state machine complete
        Schema::table('restaurant_tables', function (Blueprint $table) {
            // Ajouter current_order_id si absent
            if (!Schema::hasColumn('restaurant_tables', 'current_order_id')) {
                $table->foreignId('current_order_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('orders')
                    ->nullOnDelete();
            }
        });

        // Mettre à jour le type du champ status pour supporter tous les états
        // SQLite ne supporte pas ALTER ENUM, on utilise une approche pragmatique
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: pas de ENUM strict, le champ est déjà VARCHAR
            // On met à jour les valeurs existantes pour correspondre au nouveau format
            DB::table('restaurant_tables')
                ->where('status', 'libre')
                ->update(['status' => 'available']);
            DB::table('restaurant_tables')
                ->where('status', 'occupee')
                ->update(['status' => 'occupied']);
        } else {
            // MySQL/PostgreSQL: ALTER ENUM
            DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status ENUM('available','occupied','kitchen_processing','served_unpaid') DEFAULT 'available'");
        }

        // ── 2. Refonte orders.status ──
        // Ajouter les champs manquants pour le cycle de vie complet
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('ready_at');
            }
        });

        // Mettre à jour le status ENUM de orders
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: conversion des anciennes valeurs
            DB::table('orders')->where('status', 'paid')->update(['status' => 'delivered']);
            DB::table('orders')->where('status', 'cancelled')->update(['status' => 'annulee']);
            DB::table('orders')->where('status', 'pending')->update(['status' => 'sent_to_kitchen']);
        } else {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','sent_to_kitchen','ready','delivered','paid','annulee') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('restaurant_tables')
                ->where('status', 'available')
                ->update(['status' => 'libre']);
            DB::table('restaurant_tables')
                ->whereIn('status', ['occupied', 'kitchen_processing', 'served_unpaid'])
                ->update(['status' => 'occupee']);
            DB::table('orders')->where('status', 'delivered')->update(['status' => 'paid']);
            DB::table('orders')->where('status', 'annulee')->update(['status' => 'cancelled']);
            DB::table('orders')->where('status', 'sent_to_kitchen')->update(['status' => 'pending']);
        } else {
            DB::statement("ALTER TABLE restaurant_tables MODIFY COLUMN status ENUM('libre','occupee') DEFAULT 'libre'");
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('paid','cancelled','pending') DEFAULT 'pending'");
        }

        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_tables', 'current_order_id')) {
                $table->dropForeign(['current_order_id']);
                $table->dropColumn('current_order_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }
};
