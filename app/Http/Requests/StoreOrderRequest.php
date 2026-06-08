<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1|max:999',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.notes'        => 'nullable|string|max:200',
            'payment_method'       => 'required|in:cash,mobile_money,credit',
            'payment_reference'    => 'nullable|string|max:100',
            'cash_received'        => 'nullable|numeric|min:0',
            'customer_name'        => 'nullable|string|max:100',
            'customer_phone'       => 'nullable|string|max:20',
            'notes'                => 'nullable|string|max:500',
            'discount_amount'      => 'nullable|numeric|min:0',
            'tax_amount'           => 'nullable|numeric|min:0',
            'total'                => 'nullable|numeric|min:0',
            'table_id'             => 'nullable|integer|exists:restaurant_tables,id',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Le panier ne peut pas être vide.',
            'items.min'                   => 'Le panier doit contenir au moins un article.',
            'items.*.product_id.required' => 'Produit manquant.',
            'items.*.product_id.exists'   => 'Produit introuvable.',
            'items.*.quantity.required'   => 'Quantité requise.',
            'items.*.quantity.min'        => 'La quantité minimum est 1.',
            'items.*.unit_price.required' => 'Prix unitaire requis.',
            'payment_method.required'     => 'Le mode de paiement est obligatoire.',
            'payment_method.in'           => 'Mode de paiement invalide.',
            'table_id.exists'             => 'Table introuvable.',
        ];
    }

    /**
     * Préparer les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        // S'assurer que items est toujours un tableau
        if (!$this->has('items') || !is_array($this->items)) {
            $this->merge(['items' => []]);
        }
    }
}
