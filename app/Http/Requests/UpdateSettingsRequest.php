<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'app_name' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'restaurant_name' => 'nullable|string|max:100',
            'restaurant_address' => 'nullable|string|max:255',
            'restaurant_phone' => 'nullable|string|max:20',
            'restaurant_email' => 'nullable|email|max:100',
            'primary_color' => 'nullable|string|max:7',
            'dark_mode' => 'boolean',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max|50',
            'date_format' => 'nullable|string|max:20',
            'items_per_page' => 'nullable|integer|min:5|max:100',
            'enable_audit_log' => 'boolean',
            'auto_backup' => 'boolean',
            'backup_time' => 'nullable|string|max:5',
        ];
    }
}
