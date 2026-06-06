<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => 'required|string|max:50|unique:categories,name,' . $categoryId . ',id,restaurant_id,' . $this->user()->restaurant_id,
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'name.unique' => 'Une catégorie avec ce nom existe déjà pour ce restaurant.',
            'color.regex' => 'La couleur doit être un code hexadécimal valide (ex: #FF5500).',
            'display_order.integer' => 'L\'ordre d\'affichage doit être un nombre entier.',
            'display_order.min' => 'L\'ordre d\'affichage ne peut pas être négatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
            'display_order' => $this->input('display_order', 0),
        ]);
    }
}
