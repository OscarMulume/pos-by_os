<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class RestaurantScope implements Scope
{
    /**
     * Appliquer le scope de restaurant à toutes les requêtes.
     * Le super_admin voit tout (pas de filtre).
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = auth()->user();

        // Pas d'utilisateur authentifié → pas de résultats
        if (!$user) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // Super-admin voit tout
        if ($user->isSuperAdmin()) {
            return;
        }

        // Staff → filtrer par restaurant_id
        if ($user->restaurant_id) {
            $builder->where($model->getTable() . '.restaurant_id', $user->restaurant_id);
        } else {
            // Staff sans restaurant → pas de résultats
            $builder->whereRaw('1 = 0');
        }
    }
}
