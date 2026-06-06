<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs du restaurant
     */
    public function index(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;

        $users = User::where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Formulaire de création d'employé
     */
    public function create(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;
        $terminals = PosTerminal::where('restaurant_id', $restaurantId)->where('is_active', true)->get();
        $roles = [
            'manager' => 'Manager',
            'cashier' => 'Caissier / Serveur',
            'cook'    => 'Cuisinier',
        ];

        return view('admin.users.create', compact('roles', 'terminals'));
    }

    /**
     * Créer un nouvel employé
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'username'       => 'required|string|max:50|unique:users,username',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'password'       => 'required|string|min:6',
            'pin_code'       => 'required|string|min:4|max:8',
            'role'           => 'required|in:manager,cashier,cook',
            'pos_terminal_id' => 'nullable|exists:pos_terminals,id',
            'is_active'      => 'boolean',
        ]);

        User::create([
            'name'           => $validated['name'],
            'username'       => $validated['username'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'] ?? null,
            'address'        => $validated['address'] ?? null,
            'password'       => Hash::make($validated['password']),
            'pin_code'       => Hash::make($validated['pin_code']),
            'role'           => $validated['role'],
            'restaurant_id'  => $request->user()->restaurant_id,
            'pos_terminal_id' => $validated['pos_terminal_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
            'started_at'     => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Employé « {$validated['name']} » créé avec succès.");
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Request $request, User $user): View
    {
        $restaurantId = $request->user()->restaurant_id;

        // Sécurité: ne pas pouvoir éditer un user d'un autre restaurant
        if ($user->restaurant_id !== $restaurantId) {
            abort(403, 'Accès non autorisé.');
        }

        $terminals = PosTerminal::where('restaurant_id', $restaurantId)->where('is_active', true)->get();
        $roles = [
            'manager' => 'Manager',
            'cashier' => 'Caissier / Serveur',
            'cook'    => 'Cuisinier',
        ];

        return view('admin.users.edit', compact('user', 'roles', 'terminals'));
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        if ($user->restaurant_id !== $restaurantId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'username'       => 'required|string|max:50|unique:users,username,' . $user->id,
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'password'       => 'nullable|string|min:6',
            'pin_code'       => 'nullable|string|min:4|max:8',
            'role'           => 'required|in:manager,cashier,cook',
            'pos_terminal_id' => 'nullable|exists:pos_terminals,id',
            'is_active'      => 'boolean',
        ]);

        $data = [
            'name'           => $validated['name'],
            'username'       => $validated['username'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'] ?? null,
            'address'        => $validated['address'] ?? null,
            'role'           => $validated['role'],
            'pos_terminal_id' => $validated['pos_terminal_id'] ?? null,
            'is_active'      => $validated['is_active'] ?? false,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($request->filled('pin_code')) {
            $data['pin_code'] = Hash::make($validated['pin_code']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', "Employé « {$user->name} » mis à jour.");
    }

    /**
     * Réinitialiser le PIN d'un employé (par le manager)
     */
    public function resetPin(Request $request, User $user): RedirectResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        if ($user->restaurant_id !== $restaurantId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'new_pin' => 'required|string|min:4|max:8',
        ]);

        $user->update([
            'pin_code' => Hash::make($validated['new_pin']),
        ]);

        return back()->with('success', "PIN de « {$user->name} » réinitialisé.");
    }

    /**
     * Supprimer un employé
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Employé « {$name} » supprimé.");
    }
}
