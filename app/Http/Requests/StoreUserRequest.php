<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $userId,
            'email' => 'nullable|email|max:100|unique:users,email,' . $userId,
            'password' => $userId ? 'nullable|string|min:6' : 'required|string|min:6',
            'role' => 'required|in:admin,cashier',
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'is_active' => 'boolean',
        ];
    }
}
