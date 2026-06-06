<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{

    public function index()
    {
        $group = request('group', 'general');
        $groups = [
            'general' => 'Général',
            'appearance' => 'Apparence',
            'company' => 'Entreprise',
            'receipt' => 'Reçus',
            'pos' => 'Point de Vente',
            'security' => 'Sécurité',
            'backup' => 'Sauvegarde',
        ];

        $settings = SiteSetting::orderBy('group')->orderBy('id')->get();
        $settingsByGroup = $settings->groupBy('group');

        return view('admin.settings.index', compact('groups', 'settingsByGroup', 'group'));
    }

    public function update(UpdateSettingsRequest $request)
    {
        $fields = [
            'app_name' => ['group' => 'general', 'type' => 'text'],
            'currency' => ['group' => 'general', 'type' => 'text'],
            'tax_rate' => ['group' => 'general', 'type' => 'number'],
            'language' => ['group' => 'general', 'type' => 'text'],
            'timezone' => ['group' => 'general', 'type' => 'text'],
            'date_format' => ['group' => 'general', 'type' => 'text'],
            'items_per_page' => ['group' => 'general', 'type' => 'number'],
            'primary_color' => ['group' => 'appearance', 'type' => 'color'],
            'dark_mode' => ['group' => 'appearance', 'type' => 'boolean'],
            'pos_theme' => ['group' => 'appearance', 'type' => 'text'],
            'company_name' => ['group' => 'company', 'type' => 'text'],
            'company_address' => ['group' => 'company', 'type' => 'text'],
            'company_phone' => ['group' => 'company', 'type' => 'text'],
            'company_email' => ['group' => 'company', 'type' => 'email'],
            'company_tax_id' => ['group' => 'company', 'type' => 'text'],
            'receipt_header' => ['group' => 'receipt', 'type' => 'text'],
            'receipt_footer' => ['group' => 'receipt', 'type' => 'text'],
            'receipt_logo_enabled' => ['group' => 'receipt', 'type' => 'boolean'],
            'allow_discount' => ['group' => 'pos', 'type' => 'boolean'],
            'max_discount_percent' => ['group' => 'pos', 'type' => 'number'],
            'low_stock_alert' => ['group' => 'pos', 'type' => 'boolean'],
            'enable_audit_log' => ['group' => 'security', 'type' => 'boolean'],
            'show_cost_prices' => ['group' => 'security', 'type' => 'boolean'],
            'auto_backup' => ['group' => 'backup', 'type' => 'boolean'],
            'backup_time' => ['group' => 'backup', 'type' => 'text'],
            // Taux de change multi-devise
            'exchange_rate' => ['group' => 'pos', 'type' => 'number'],
            'exchange_currency' => ['group' => 'pos', 'type' => 'text'],
            'exchange_auto_update' => ['group' => 'pos', 'type' => 'boolean'],
            'default_currency' => ['group' => 'general', 'type' => 'text'],
            'secondary_currency' => ['group' => 'general', 'type' => 'text'],
        ];

        foreach ($fields as $field => $config) {
            if ($request->has($field)) {
                $value = $config['type'] === 'boolean'
                    ? ($request->boolean($field) ? '1' : '0')
                    : $request->input($field);
                SiteSetting::setValue($field, $value, $config['group'], $config['type']);
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $oldLogo = SiteSetting::getValue('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            SiteSetting::setValue('app_logo', $path, 'appearance', 'image');
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Paramètres mis à jour avec succès.');
    }
}
