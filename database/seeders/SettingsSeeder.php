<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'POS System', 'group' => 'general', 'type' => 'text'],
            ['key' => 'currency', 'value' => 'FC', 'group' => 'general', 'type' => 'text'],
            ['key' => 'tax_rate', 'value' => '0', 'group' => 'general', 'type' => 'number'],
            ['key' => 'receipt_header', 'value' => 'Bienvenue', 'group' => 'receipt', 'type' => 'text'],
            ['key' => 'receipt_footer', 'value' => 'Merci de votre visite!', 'group' => 'receipt', 'type' => 'text'],
            ['key' => 'primary_color', 'value' => '#3B82F6', 'group' => 'appearance', 'type' => 'color'],
            ['key' => 'dark_mode', 'value' => '0', 'group' => 'appearance', 'type' => 'boolean'],
            ['key' => 'language', 'value' => 'fr', 'group' => 'general', 'type' => 'text'],
            ['key' => 'timezone', 'value' => 'Africa/Kinshasa', 'group' => 'general', 'type' => 'text'],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'group' => 'general', 'type' => 'text'],
            ['key' => 'items_per_page', 'value' => '25', 'group' => 'general', 'type' => 'number'],
            ['key' => 'enable_audit_log', 'value' => '1', 'group' => 'security', 'type' => 'boolean'],
            ['key' => 'auto_backup', 'value' => '0', 'group' => 'backup', 'type' => 'boolean'],
            ['key' => 'backup_time', 'value' => '23:59', 'group' => 'backup', 'type' => 'text'],
            ['key' => 'company_name', 'value' => 'Ma Société', 'group' => 'company', 'type' => 'text'],
            ['key' => 'company_address', 'value' => '', 'group' => 'company', 'type' => 'text'],
            ['key' => 'company_phone', 'value' => '', 'group' => 'company', 'type' => 'text'],
            ['key' => 'company_email', 'value' => '', 'group' => 'company', 'type' => 'email'],
            ['key' => 'company_tax_id', 'value' => '', 'group' => 'company', 'type' => 'text'],
            ['key' => 'receipt_logo_enabled', 'value' => '0', 'group' => 'receipt', 'type' => 'boolean'],
            ['key' => 'show_cost_prices', 'value' => '0', 'group' => 'security', 'type' => 'boolean'],
            ['key' => 'allow_discount', 'value' => '1', 'group' => 'pos', 'type' => 'boolean'],
            ['key' => 'max_discount_percent', 'value' => '10', 'group' => 'pos', 'type' => 'number'],
            ['key' => 'pos_theme', 'value' => 'default', 'group' => 'appearance', 'type' => 'text'],
            ['key' => 'low_stock_alert', 'value' => '1', 'group' => 'pos', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }
    }
}
