@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Paramètres</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Configurez les paramètres de votre système de point de vente.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900 dark:border-green-600 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg dark:bg-red-900 dark:border-red-600 dark:text-red-200">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settings-form">
        @csrf

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="flex flex-wrap -mb-px space-x-6" role="tablist">
                @php $tabGroups = ['general' => 'Général', 'appearance' => 'Apparence', 'company' => 'Entreprise', 'receipt' => 'Reçu', 'pos' => 'Caisse', 'security' => 'Sécurité', 'backup' => 'Sauvegarde']; @endphp
                @foreach($tabGroups as $group => $label)
                    <button type="button"
                        class="tab-btn py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                            {{ $loop->first
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                        data-tab="{{ $group }}"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        onclick="switchTab('{{ $group }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Contents --}}
        @foreach($tabGroups as $group => $label)
            <div id="tab-{{ $group }}" class="tab-content {{ $loop->first ? '' : 'hidden' }}" role="tabpanel">

                {{-- ==================== GENERAL ==================== --}}
                @if($group === 'general')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Paramètres généraux</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de l'application</label>
                            <input type="text" name="settings[general][app_name]" id="app_name"
                                value="{{ old('settings.general.app_name', $settings['general']['app_name'] ?? '') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Devise</label>
                            <input type="text" name="settings[general][currency]" id="currency"
                                value="{{ old('settings.general.currency', $settings['general']['currency'] ?? 'EUR') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux de TVA (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="settings[general][tax_rate]" id="tax_rate"
                                value="{{ old('settings.general.tax_rate', $settings['general']['tax_rate'] ?? '20.00') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Langue</label>
                            <select name="settings[general][language]" id="language"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'de' => 'Deutsch'] as $code => $lang)
                                    <option value="{{ $code }}" {{ (old('settings.general.language', $settings['general']['language'] ?? 'fr') == $code) ? 'selected' : '' }}>{{ $lang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fuseau horaire</label>
                            <select name="settings[general][timezone]" id="timezone"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['Europe/Paris' => 'Europe/Paris', 'Europe/London' => 'Europe/London', 'America/New_York' => 'America/New_York', 'Asia/Tokyo' => 'Asia/Tokyo', 'UTC' => 'UTC'] as $tz => $label)
                                    <option value="{{ $tz }}" {{ (old('settings.general.timezone', $settings['general']['timezone'] ?? 'Europe/Paris') == $tz) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Format de date</label>
                            <select name="settings[general][date_format]" id="date_format"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'd.m.Y' => 'DD.MM.YYYY'] as $fmt => $label)
                                    <option value="{{ $fmt }}" {{ (old('settings.general.date_format', $settings['general']['date_format'] ?? 'd/m/Y') == $fmt) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    <div>
                        <label for="items_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Éléments par page</label>
                        <input type="number" min="5" max="100" name="settings[general][items_per_page]" id="items_per_page"
                            value="{{ old('settings.general.items_per_page', $settings['general']['items_per_page'] ?? 25) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Taux de change multi-devise -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Taux de Change Multi-Devise</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="exchange_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Devise secondaire</label>
                            <select name="settings[general][exchange_currency]" id="exchange_currency"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="USD" {{ (old('settings.general.exchange_currency', $settings['general']['exchange_currency'] ?? 'USD') == 'USD') ? 'selected' : '' }}>USD ($)</option>
                                <option value="EUR" {{ (old('settings.general.exchange_currency', $settings['general']['exchange_currency'] ?? '') == 'EUR') ? 'selected' : '' }}>EUR (€)</option>
                                <option value="GBP" {{ (old('settings.general.exchange_currency', $settings['general']['exchange_currency'] ?? '') == 'GBP') ? 'selected' : '' }}>GBP (£)</option>
                            </select>
                        </div>
                        <div>
                            <label for="exchange_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux (1 {{ $settings['general']['exchange_currency'] ?? 'USD' }} = ? FC)</label>
                            <input type="number" step="0.01" min="0" name="settings[general][exchange_rate]" id="exchange_rate"
                                value="{{ old('settings.general.exchange_rate', $settings['general']['exchange_rate'] ?? 2850) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Ex: 2850">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex: 1$ = 2850 FC</p>
                        </div>
                        <div class="flex items-end">
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-700 dark:text-amber-300">
                                <p class="font-semibold">Conversion auto au POS</p>
                                <p>Le caissier pourra afficher le montant en devise secondaire à l'encaissement.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ==================== APPEARANCE ==================== --}}
                @if($group === 'appearance')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Apparence</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Couleur principale</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="settings[appearance][primary_color]" id="primary_color"
                                    value="{{ old('settings.appearance.primary_color', $settings['appearance']['primary_color'] ?? '#4f46e5') }}"
                                    class="h-10 w-14 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-mono" id="primary_color_hex">{{ old('settings.appearance.primary_color', $settings['appearance']['primary_color'] ?? '#4f46e5') }}</span>
                            </div>
                        </div>
                        <div>
                            <label for="pos_theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Thème du POS</label>
                            <select name="settings[appearance][pos_theme]" id="pos_theme"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['default' => 'Par défaut', 'dark' => 'Sombre', 'colorful' => 'Coloré'] as $val => $label)
                                    <option value="{{ $val }}" {{ (old('settings.appearance.pos_theme', $settings['appearance']['pos_theme'] ?? 'default') == $val) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mode sombre</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Activer le thème sombre pour l'interface</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[appearance][dark_mode]" value="0">
                            <input type="checkbox" name="settings[appearance][dark_mode]" value="1"
                                {{ old('settings.appearance.dark_mode', $settings['appearance']['dark_mode'] ?? false) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo</label>
                        <div class="flex items-center space-x-6">
                            <div id="logo-preview-container" class="flex-shrink-0">
                                @if(!empty($settings['appearance']['logo_path']))
                                    <img id="logo-preview" src="{{ asset('storage/' . $settings['appearance']['logo_path']) }}" alt="Logo" class="h-20 w-20 object-contain rounded-lg border border-gray-300 dark:border-gray-600 bg-white">
                                @else
                                    <div id="logo-preview" class="h-20 w-20 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="logo" id="logo" accept="image/*"
                                    class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300"
                                    onchange="previewLogo(this)">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou SVG. Max 2 Mo.</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ==================== COMPANY ==================== --}}
                @if($group === 'company')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Informations de l'entreprise</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de l'entreprise</label>
                            <input type="text" name="settings[company][company_name]" id="company_name"
                                value="{{ old('settings.company.company_name', $settings['company']['company_name'] ?? '') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label for="company_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse</label>
                            <textarea name="settings[company][company_address]" id="company_address" rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('settings.company.company_address', $settings['company']['company_address'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label for="company_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                            <input type="text" name="settings[company][company_phone]" id="company_phone"
                                value="{{ old('settings.company.company_phone', $settings['company']['company_phone'] ?? '') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="company_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="settings[company][company_email]" id="company_email"
                                value="{{ old('settings.company.company_email', $settings['company']['company_email'] ?? '') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="company_tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numéro de TVA / SIRET</label>
                            <input type="text" name="settings[company][company_tax_id]" id="company_tax_id"
                                value="{{ old('settings.company.company_tax_id', $settings['company']['company_tax_id'] ?? '') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                @endif

                {{-- ==================== RECEIPT ==================== --}}
                @if($group === 'receipt')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Configuration des reçus</h2>

                    <div class="space-y-6">
                        <div>
                            <label for="receipt_header" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">En-tête du reçu</label>
                            <textarea name="settings[receipt][receipt_header]" id="receipt_header" rows="3"
                                placeholder="Texte affiché en haut du reçu"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('settings.receipt.receipt_header', $settings['receipt']['receipt_header'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label for="receipt_footer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pied de page du reçu</label>
                            <textarea name="settings[receipt][receipt_footer]" id="receipt_footer" rows="3"
                                placeholder="Texte affiché en bas du reçu (ex: Merci de votre visite)"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('settings.receipt.receipt_footer', $settings['receipt']['receipt_footer'] ?? '') }}</textarea>
                        </div>
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Afficher le logo sur le reçu</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Afficher le logo de l'entreprise sur chaque reçu</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="settings[receipt][receipt_logo_enabled]" value="0">
                                <input type="checkbox" name="settings[receipt][receipt_logo_enabled]" value="1"
                                    {{ old('settings.receipt.receipt_logo_enabled', $settings['receipt']['receipt_logo_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ==================== POS ==================== --}}
                @if($group === 'pos')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Paramètres de caisse</h2>

                    <div class="flex items-center justify-between py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Autoriser les remises</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Permettre aux caissiers d'appliquer des remises</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[pos][allow_discount]" value="0">
                            <input type="checkbox" name="settings[pos][allow_discount]" value="1"
                                {{ old('settings.pos.allow_discount', $settings['pos']['allow_discount'] ?? true) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div>
                            <label for="max_discount_percent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remise maximale (%)</label>
                            <input type="number" step="0.5" min="0" max="100" name="settings[pos][max_discount_percent]" id="max_discount_percent"
                                value="{{ old('settings.pos.max_discount_percent', $settings['pos']['max_discount_percent'] ?? 20) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Alerte stock faible</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Afficher une alerte quand le stock est bas</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[pos][low_stock_alert]" value="0">
                            <input type="checkbox" name="settings[pos][low_stock_alert]" value="1"
                                {{ old('settings.pos.low_stock_alert', $settings['pos']['low_stock_alert'] ?? true) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
                @endif

                {{-- ==================== SECURITY ==================== --}}
                @if($group === 'security')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Sécurité</h2>

                    <div class="flex items-center justify-between py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activer le journal d'audit</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Enregistrer toutes les actions des utilisateurs</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[security][enable_audit_log]" value="0">
                            <input type="checkbox" name="settings[security][enable_audit_log]" value="1"
                                {{ old('settings.security.enable_audit_log', $settings['security']['enable_audit_log'] ?? true) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Afficher les prix d'achat</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Montrer les prix d'achat aux utilisateurs autorisés</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[security][show_cost_prices]" value="0">
                            <input type="checkbox" name="settings[security][show_cost_prices]" value="1"
                                {{ old('settings.security.show_cost_prices', $settings['security']['show_cost_prices'] ?? false) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
                @endif

                {{-- ==================== BACKUP ==================== --}}
                @if($group === 'backup')
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-3">Sauvegarde</h2>

                    <div class="flex items-center justify-between py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sauvegarde automatique</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Activer les sauvegardes automatiques quotidiennes</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[backup][auto_backup]" value="0">
                            <input type="checkbox" name="settings[backup][auto_backup]" value="1"
                                {{ old('settings.backup.auto_backup', $settings['backup']['auto_backup'] ?? false) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div>
                            <label for="backup_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heure de sauvegarde</label>
                            <input type="time" name="settings[backup][backup_time]" id="backup_time"
                                value="{{ old('settings.backup.backup_time', $settings['backup']['backup_time'] ?? '02:00') }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Heure à laquelle la sauvegarde sera exécutée</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Save button per tab --}}
                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        @endforeach
    </form>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.classList.add('hidden');
    });
    // Show selected tab content
    document.getElementById('tab-' + tabName).classList.remove('hidden');

    // Reset all tab buttons
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('border-indigo-600', 'text-indigo-600', 'dark:border-indigo-400', 'dark:text-indigo-400');
        btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
        btn.setAttribute('aria-selected', 'false');
    });

    // Activate clicked tab button
    var activeBtn = document.querySelector('.tab-btn[data-tab="' + tabName + '"]');
    activeBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
    activeBtn.classList.add('border-indigo-600', 'text-indigo-600', 'dark:border-indigo-400', 'dark:text-indigo-400');
    activeBtn.setAttribute('aria-selected', 'true');
}

// Color input hex display
document.getElementById('primary_color')?.addEventListener('input', function() {
    document.getElementById('primary_color_hex').textContent = this.value;
});

// Logo preview
function previewLogo(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('logo-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                var img = document.createElement('img');
                img.id = 'logo-preview';
                img.src = e.target.result;
                img.alt = 'Logo preview';
                img.className = 'h-20 w-20 object-contain rounded-lg border border-gray-300 dark:border-gray-600 bg-white';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
