<?php

use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\KdsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Pos\CashShiftController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Pos\PosController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Réinitialisation mot de passe (Option A)
    Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Redirect based on role after login
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isManager()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('pos.index');
    })->name('dashboard');

    // ═══════════════════════════════════════════════════════════
    // SUPER-ADMIN routes (accès total)
    // ═══════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')->prefix('superadmin')->name('superadmin.')->group(function () {
        // Dashboard global
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        // Gestion des restaurants
        Route::get('/restaurants', [SuperAdminController::class, 'restaurants'])->name('restaurants.index');
        Route::get('/restaurants/create', [SuperAdminController::class, 'createRestaurant'])->name('restaurants.create');
        Route::post('/restaurants', [SuperAdminController::class, 'storeRestaurant'])->name('restaurants.store');
        Route::get('/restaurants/{restaurant}', [SuperAdminController::class, 'showRestaurant'])->name('restaurants.show');
        Route::get('/restaurants/{restaurant}/edit', [SuperAdminController::class, 'editRestaurant'])->name('restaurants.edit');
        Route::put('/restaurants/{restaurant}', [SuperAdminController::class, 'updateRestaurant'])->name('restaurants.update');
        Route::delete('/restaurants/{restaurant}', [SuperAdminController::class, 'destroyRestaurant'])->name('restaurants.destroy');
        Route::patch('/restaurants/{restaurant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('restaurants.toggle-status');

        // Gestion des terminaux POS
        Route::post('/restaurants/{restaurant}/terminals', [SuperAdminController::class, 'storeTerminal'])->name('restaurants.terminals.store');
        Route::patch('/terminals/{terminal}/toggle', [SuperAdminController::class, 'toggleTerminal'])->name('restaurants.terminals.toggle');
        Route::delete('/terminals/{terminal}', [SuperAdminController::class, 'destroyTerminal'])->name('restaurants.terminals.destroy');

        // Gestion des tables
        Route::get('/restaurants/{restaurant}/tables', [SuperAdminController::class, 'tables'])->name('restaurants.tables.index');
        Route::post('/restaurants/{restaurant}/tables', [SuperAdminController::class, 'storeTable'])->name('restaurants.tables.store');
        Route::post('/restaurants/{restaurant}/tables/bulk', [SuperAdminController::class, 'bulkStoreTables'])->name('restaurants.tables.bulk');
        Route::put('/restaurants/{restaurant}/tables/{table}', [SuperAdminController::class, 'updateTable'])->name('restaurants.tables.update');
        Route::delete('/tables/{table}', [SuperAdminController::class, 'destroyTable'])->name('restaurants.tables.destroy');

        // Toggle statut restaurant (action rapide)
        Route::post('/restaurants/{restaurant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('restaurants.toggle-status');
    });

    // ═══════════════════════════════════════════════════════════
    // LICENCES (Super-Admin)
    // ═══════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/licenses', [SuperAdminController::class, 'licenses'])->name('licenses');
    });

    // ═══════════════════════════════════════════════════════════
    // KDS — Kitchen Display System (cuisine)
    // ═══════════════════════════════════════════════════════════
    Route::middleware('auth')->prefix('kds')->name('kds.')->group(function () {
        Route::get('/', [KdsController::class, 'index'])->name('index');
        Route::get('/orders', [KdsController::class, 'orders'])->name('orders');
        Route::post('/order/{order}/start-prep', [KdsController::class, 'startPrep'])->name('start-prep');
        Route::post('/order/{order}/mark-ready', [KdsController::class, 'markReady'])->name('mark-ready');
    });

    // POS (cashier + manager + admin + super_admin)
    Route::middleware('role:cashier,manager,admin,super_admin')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/order', [OrderController::class, 'store'])->name('order.store');
        Route::post('/order/{order}/ready', [OrderController::class, 'markReady'])->name('order.ready');
        Route::post('/order/{order}/deliver', [OrderController::class, 'markDelivered'])->name('order.deliver');
        Route::post('/order/{order}/pay', [OrderController::class, 'pay'])->name('order.pay');
        Route::post('/order/{order}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
        Route::get('/order/{order}/receipt', [OrderController::class, 'receipt'])->name('order.receipt');
        Route::get('/unsettled', [OrderController::class, 'checkUnsettled'])->name('unsettled');

        // Cash shifts
        Route::get('/cash-shift/status', [CashShiftController::class, 'status'])->name('cash-shift.status');
        Route::post('/cash-shift/open', [CashShiftController::class, 'open'])->name('cash-shift.open');
        Route::post('/cash-shift/close', [CashShiftController::class, 'close'])->name('cash-shift.close');
        Route::get('/cash-shift/history', [CashShiftController::class, 'history'])->name('cash-shift.history');
    });

    // ADMIN + MANAGER (gestion des utilisateurs, restaurants, etc.)
    Route::middleware('role:admin,manager,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::patch('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{order}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/transactions/{order}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');

        // Settings, restaurants, reports, inventaire : admin + manager + super_admin
        Route::middleware('role:admin,manager,super_admin')->group(function () {
            Route::resource('restaurants', RestaurantController::class);
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Inventaire / Stocks
        Route::get('/inventory', [App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/adjust', [App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::put('/inventory/{product}/settings', [App\Http\Controllers\Admin\InventoryController::class, 'updateSettings'])->name('inventory.settings');
        Route::get('/inventory/movements', [App\Http\Controllers\Admin\InventoryController::class, 'movements'])->name('inventory.movements');

        // Réinitialisation PIN employé
        Route::post('/users/{user}/reset-pin', [UserController::class, 'resetPin'])->name('users.reset-pin');

        // ═══════════════════════════════════════════════════════════
        // SUPERVISION SALLE — Plan de salle temps réel (SLA)
        // ═══════════════════════════════════════════════════════════
        Route::get('/floor-plan', [App\Http\Controllers\Admin\FloorPlanController::class, 'index'])->name('floor-plan.index');
        Route::get('/floor-plan/data', [App\Http\Controllers\Admin\FloorPlanController::class, 'data'])->name('floor-plan.data');
        Route::get('/floor-plan/table/{table}', [App\Http\Controllers\Admin\FloorPlanController::class, 'tableDetails'])->name('floor-plan.table');
    });
});
});

// ═══════════════════════════════════════════════════════════
// API Routes (licence, sync, webauthn, whatsapp)
// ═══════════════════════════════════════════════════════════
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/license/verify', [LicenseController::class, 'verify'])->name('license.verify');
    Route::post('/license/generate/{restaurant}', [LicenseController::class, 'generate'])->name('license.generate');
    Route::post('/pin/verify', [App\Http\Controllers\Api\PinController::class, 'verify'])->name('pin.verify');

    // WebAuthn / Biométrie
    Route::post('/webauthn/register', [App\Http\Controllers\Api\WebAuthnController::class, 'register'])->name('webauthn.register');
    Route::post('/webauthn/verify', [App\Http\Controllers\Api\WebAuthnController::class, 'verify'])->name('webauthn.verify');
    Route::delete('/webauthn', [App\Http\Controllers\Api\WebAuthnController::class, 'destroy'])->name('webauthn.destroy');

    // WhatsApp
    Route::post('/whatsapp/send-receipt', [App\Http\Controllers\Api\WhatsAppController::class, 'sendReceipt'])->name('whatsapp.send-receipt');
});

// Reçus
Route::middleware('auth')->prefix('pos')->name('pos.')->group(function () {
    Route::get('/order/{order}/receipt', [App\Http\Controllers\Pos\ReceiptController::class, 'show'])->name('order.receipt');
    Route::get('/order/{order}/receipt/proforma', [App\Http\Controllers\Pos\ReceiptController::class, 'proforma'])->name('order.receipt.proforma');
    Route::get('/order/{order}/receipt/thermal', [App\Http\Controllers\Pos\ReceiptController::class, 'thermal'])->name('order.receipt.thermal');
});

// Page hors-ligne
Route::get('/offline', fn() => view('offline'))->name('offline');

// Manifest PWA
Route::get('/manifest.json', fn() => response()->file(public_path('manifest.json')));
