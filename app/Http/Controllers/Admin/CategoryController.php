<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{

    public function index(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;

        $categories = Category::where('restaurant_id', $restaurantId)
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['restaurant_id'] = $request->user()->restaurant_id;

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Request $request, Category $category): View
    {
        // Sécurité : vérifier l'appartenance au restaurant
        if ($category->restaurant_id !== $request->user()->restaurant_id) {
            abort(403, 'Accès non autorisé.');
        }

        return view('admin.categories.edit', compact('category'));
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        // Sécurité : vérifier l'appartenance au restaurant
        if ($category->restaurant_id !== $request->user()->restaurant_id) {
            abort(403, 'Accès non autorisé.');
        }

        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        // Sécurité : vérifier l'appartenance au restaurant
        if ($category->restaurant_id !== $request->user()->restaurant_id) {
            abort(403, 'Accès non autorisé.');
        }

        // Bloquer la suppression si des produits sont liés
        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Impossible de supprimer cette catégorie : des produits y sont encore associés. Réassignez ou supprimez d\'abord les produits.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }

    public function toggleStatus(Request $request, Category $category): RedirectResponse
    {
        // Sécurité : vérifier l'appartenance au restaurant
        if ($category->restaurant_id !== $request->user()->restaurant_id) {
            abort(403, 'Accès non autorisé.');
        }

        $category->update(['is_active' => !$category->is_active]);
        $status = $category->is_active ? 'activée' : 'désactivée';

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Catégorie « {$category->name} » {$status}.");
    }
}
