<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Order;
use App\Models\PosTerminal;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SuperAdminController extends \App\Http\Controllers\Controller
{
    /**
     * Dashboard Super-Admin : vue globale de tous les restaurants
     */
    public function dashboard(): View
    {
        $restaurants = Restaurant::withCount(['users', 'orders', 'posTerminals'])
            ->withSum('orders as total_revenue', 'total_amount')
            ->orderBy('name')
            ->get();

        // Statistiques globales
        $stats = [
            'total_restaurants'    => Restaurant::count(),
            'active_restaurants'   => Restaurant::where('status', Restaurant::STATUS_ACTIVE)->count(),
            'suspended_restaurants'=> Restaurant::where('status', Restaurant::STATUS_SUSPENDED)->count(),
            'total_users'          => User::count(),
            'total_orders'         => Order::where('status', Order::STATUS_PAID)->count(),
            'total_revenue'        => Order::where('status', Order::STATUS_PAID)->sum('total_amount'),
            'today_orders'         => Order::where('status', Order::STATUS_PAID)->whereDate('created_at', today())->count(),
            'today_revenue'        => Order::where('status', Order::STATUS_PAID)->whereDate('created_at', today())->sum('total_amount'),
            'open_shifts'          => CashShift::where('status', 'open')->count(),
        ];

        // Données pour graphique : CA des 7 derniers jours
        $revenueChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenueChart[] = [
                'date'    => $date->format('d/m'),
                'revenue' => Order::where('status', Order::STATUS_PAID)
                    ->whereDate('created_at', $date)
                    ->sum('total_amount'),
            ];
        }

        // Top restaurants par CA
        $topRestaurants = Restaurant::withCount(['orders'])
            ->withSum('orders as revenue', 'total_amount')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'restaurants', 'stats', 'revenueChart', 'topRestaurants'
        ));
    }

    /**
     * Liste des restaurants avec filtres
     */
    public function restaurants(Request $request): View
    {
        $query = Restaurant::withCount(['users', 'orders', 'posTerminals']);

        // Filtre par statut
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $restaurants = $query->orderBy('name')->paginate(20);

        return view('superadmin.restaurants.index', compact('restaurants'));
    }

    /**
     * Formulaire de création restaurant
     */
    public function createRestaurant(): View
    {
        return view('superadmin.restaurants.create');
    }

    /**
     * Enregistrer un nouveau restaurant
     */
    public function storeRestaurant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:100',
            'address'            => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:100',
            'currency'           => 'nullable|string|max:10|default:FC',
            'tax_rate'           => 'nullable|numeric|min:0|max:100',
            'type'               => 'required|in:permanent,ephemere',
            'status'             => 'required|in:active,inactive,suspended,ferme_temporairement',
            'subscription_ends_at' => 'nullable|date',
            'logo'               => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'photo'              => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'receipt_header'     => 'nullable|string|max:255',
            'receipt_footer'     => 'nullable|string|max:255',
        ]);

        // Upload logo
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')
                ->store('restaurants/logos', 'public');
        }

        // Upload photo
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')
                ->store('restaurants/photos', 'public');
        }

        $validated['is_active'] = in_array($validated['status'], [Restaurant::STATUS_ACTIVE]);

        $restaurant = Restaurant::create($validated);

        // Audit log
        \App\Models\AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'restaurant_created',
            'entity_type' => 'restaurant',
            'entity_id'   => $restaurant->id,
            'new_values'  => ['name' => $restaurant->name, 'status' => $restaurant->status],
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('superadmin.restaurants.index')
            ->with('success', "Restaurant « {$restaurant->name} » créé avec succès.");
    }

    /**
     * Formulaire d'édition restaurant
     */
    public function edit(Restaurant $restaurant): View
    {
        return view('superadmin.restaurants.edit', compact('restaurant'));
    }

    /**
     * Mettre à jour un restaurant
     */
    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:100',
            'address'            => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:100',
            'currency'           => 'nullable|string|max:10',
            'tax_rate'           => 'nullable|numeric|min:0|max:100',
            'type'               => 'required|in:permanent,ephemere',
            'status'             => 'required|in:active,inactive,suspended,ferme_temporairement',
            'subscription_ends_at' => 'nullable|date',
            'logo'               => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'photo'              => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'receipt_header'     => 'nullable|string|max:255',
            'receipt_footer'     => 'nullable|string|max:255',
            'remove_logo'        => 'nullable|boolean',
            'remove_photo'       => 'nullable|boolean',
        ]);

        // Gestion logo
        if ($request->boolean('remove_logo') && $restaurant->logo_path) {
            Storage::disk('public')->delete($restaurant->logo_path);
            $validated['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($restaurant->logo_path) {
                Storage::disk('public')->delete($restaurant->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        // Gestion photo
        if ($request->boolean('remove_photo') && $restaurant->photo_path) {
            Storage::disk('public')->delete($restaurant->photo_path);
            $validated['photo_path'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($restaurant->photo_path) {
                Storage::disk('public')->delete($restaurant->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('restaurants/photos', 'public');
        }

        $validated['is_active'] = in_array($validated['status'], [Restaurant::STATUS_ACTIVE]);

        $oldValues = $restaurant->only(['name', 'status', 'type']);
        $restaurant->update($validated);

        // Audit log
        \App\Models\AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'restaurant_updated',
            'entity_type' => 'restaurant',
            'entity_id'   => $restaurant->id,
            'old_values'  => $oldValues,
            'new_values'  => $restaurant->only(['name', 'status', 'type']),
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('superadmin.restaurants.index')
            ->with('success', "Restaurant « {$restaurant->name} » mis à jour.");
    }

    /**
     * Changer le statut d'un restaurant (action rapide)
     */
    public function toggleStatus(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,ferme_temporairement',
        ]);

        $oldStatus = $restaurant->status;
        $restaurant->update([
            'status'    => $validated['status'],
            'is_active' => $validated['status'] === Restaurant::STATUS_ACTIVE,
        ]);

        \App\Models\AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'restaurant_status_changed',
            'entity_type' => 'restaurant',
            'entity_id'   => $restaurant->id,
            'old_values'  => ['status' => $oldStatus],
            'new_values'  => ['status' => $validated['status']],
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Statut de « {$restaurant->name} » changé en : " . $restaurant->getStatusLabel());
    }

    /**
     * Supprimer un restaurant
     */
    public function destroy(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $name = $restaurant->name;

        // Supprimer fichiers
        if ($restaurant->logo_path) {
            Storage::disk('public')->delete($restaurant->logo_path);
        }
        if ($restaurant->photo_path) {
            Storage::disk('public')->delete($restaurant->photo_path);
        }

        \App\Models\AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'restaurant_deleted',
            'entity_type' => 'restaurant',
            'entity_id'   => $restaurant->id,
            'old_values'  => ['name' => $name],
            'ip_address'  => $request->ip(),
        ]);

        $restaurant->delete();

        return redirect()->route('superadmin.restaurants.index')
            ->with('success', "Restaurant « {$name} » supprimé.");
    }

    /**
     * Détail d'un restaurant (vue Super-Admin)
     */
    public function show(Restaurant $restaurant): View
    {
        $restaurant->load([
            'posTerminals',
            'users' => fn($q) => $q->orderBy('role')->orderBy('name'),
        ]);

        $stats = [
            'total_orders'   => $restaurant->orders()->where('status', Order::STATUS_PAID)->count(),
            'total_revenue'  => $restaurant->totalRevenue(),
            'today_orders'   => $restaurant->todayOrderCount(),
            'today_revenue'  => $restaurant->todayRevenue(),
            'total_products' => $restaurant->products()->count(),
            'total_categories' => $restaurant->categories()->count(),
            'total_terminals'  => $restaurant->posTerminals()->count(),
            'open_shifts'      => $restaurant->cashShifts()->where('status', 'open')->count(),
        ];

        return view('superadmin.restaurants.show', compact('restaurant', 'stats'));
    }

    /**
     * Gestion des terminaux POS d'un restaurant
     */
    public function storeTerminal(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $restaurant->posTerminals()->create($validated);

        return back()->with('success', "Terminal « {$validated['name']} » ajouté.");
    }

    /**
     * Activer/Désactiver un terminal
     */
    public function toggleTerminal(Request $request, PosTerminal $terminal): RedirectResponse
    {
        $terminal->update(['is_active' => !$terminal->is_active]);

        $status = $terminal->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "Terminal « {$terminal->name} » {$status}.");
    }

    /**
     * Supprimer un terminal
     */
    public function destroyTerminal(Request $request, PosTerminal $terminal): RedirectResponse
    {
        $name = $terminal->name;
        $terminal->delete();

        return back()->with('success', "Terminal « {$name} » supprimé.");
    }

    // ── Gestion des tables ──

    public function tables(Restaurant $restaurant): View
    {
        $restaurant->load(['tables' => fn($q) => $q->orderBy('zone')->orderBy('name')]);
        return view('superadmin.restaurants.tables', compact('restaurant'));
    }

    public function storeTable(Request $request, Restaurant $restaurant): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:50',
            'zone'            => 'nullable|string|max:30',
            'capacity'        => 'nullable|integer|min:1|max:50',
            'pos_terminal_id' => 'nullable|exists:pos_terminals,id',
        ]);

        $restaurant->tables()->create($validated);

        return back()->with('success', "Table « {$validated['name']} » ajoutée.");
    }

    public function destroyTable(\App\Models\RestaurantTable $table): \Illuminate\Http\RedirectResponse
    {
        $name = $table->name;
        $table->delete();
        return back()->with('success', "Table « {$name} » supprimée.");
    }

    public function updateTable(Request $request, Restaurant $restaurant, \App\Models\RestaurantTable $table): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:50',
            'zone'     => 'nullable|string|max:30',
            'capacity' => 'nullable|integer|min:1|max:50',
            'status'   => 'nullable|in:libre,occupee,reservee',
        ]);

        $table->update($validated);
        return back()->with('success', "Table « {$table->name} » mise à jour.");
    }

    public function bulkStoreTables(Request $request, Restaurant $restaurant): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'zone'     => 'required|string|max:30',
            'from'     => 'required|integer|min:1',
            'to'       => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1|max:50',
        ]);

        $from = min($validated['from'], $validated['to']);
        $to = max($validated['from'], $validated['to']);
        $created = 0;

        for ($i = $from; $i <= $to; $i++) {
            $name = "{$validated['zone']} {$i}";
            // Éviter les doublons
            if (!$restaurant->tables()->where('name', $name)->exists()) {
                $restaurant->tables()->create([
                    'name'     => $name,
                    'zone'     => $validated['zone'],
                    'capacity' => $validated['capacity'] ?? 4,
                    'status'   => 'libre',
                ]);
                $created++;
            }
        }

        return back()->with("success", "{$created} table(s) créée(s) dans la zone « {$validated['zone']} ».");
    }

    // ── Gestion des licences ──

    public function licenses(): View
    {
        return view('superadmin.licenses');
    }
}
