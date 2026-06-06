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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,mobile_money,credit',
            'payment_reference' => 'nullable|string|max:100',
            'cash_received' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'table_id' => 'nullable|exists:restaurant_tables,id',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Le panier ne peut pas être vide.',
            'items.min' => 'Le panier doit contenir au moins un article.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
            'cash_received.required_if' => 'Le montant reçu est obligatoire pour un paiement en espèces.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->payment_method === 'cash' && !$this->cash_received) {
                $validator->errors()->add('cash_received', 'Le montant reçu est obligatoire pour un paiement en espèces.');
            }
            if ($this->payment_method === 'cash' && $this->cash_received) {
                $total = collect($this->items)->sum(fn($i) => $i['quantity'] * $i['price']);
                if ($this->cash_received < $total) {
                    $validator->errors()->add('cash_received', 'Le montant reçu est inférieur au total.');
                }
            }
            if ($this->payment_method === 'mobile_money' && !$this->payment_reference) {
                $validator->errors()->add('payment_reference', 'La référence de transaction est obligatoire pour Mobile Money.');
            }
        });
    }
}
