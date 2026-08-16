@extends('admin.settings.layout', [
    'title' => 'Company Preferences',
    'heading' => 'Company Branding & Preferences',
    'subheading' => 'Customize company logo, localization overrides, and curated theme palette per tenant.',
])

@section('settings-content')
<div>
    {{-- Company Selector Bar --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
        <form method="GET" action="{{ route('admin.settings.company') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <label for="company_id_select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Selected Company:
            </label>
            <select
                id="company_id_select"
                name="company_id"
                onchange="this.form.submit()"
                class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
                @foreach ($companies as $comp)
                    <option value="{{ $comp->id }}" {{ ($selectedCompany?->id === $comp->id) ? 'selected' : '' }}>
                        {{ $comp->name }} ({{ $comp->type ?? 'Company' }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($selectedCompany)
        <form
            action="{{ route('admin.settings.company.update') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="{
                selectedTheme: '{{ old('brand_theme', $settings['brand_theme']) }}',
                themes: {{ Js::from($brandThemeOptions) }},
                get currentTheme() {
                    return this.themes[this.selectedTheme] || this.themes['brand'] || {};
                }
            }"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $selectedCompany->id }}" />
            <input type="hidden" name="brand_theme" :value="selectedTheme" />

            {{-- 1. Company Identity & Logo Section --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                        1. Company Logo & Identity
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Upload a tenant-specific logo displayed in the company switcher, headers, and subsidiary hierarchy.
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-20 w-36 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                        @if (!empty($settings['company_logo']) || !empty($selectedCompany->logo))
                            <img src="{{ $settings['company_logo'] ?: $selectedCompany->logo }}" alt="Company Logo" class="max-h-16 max-w-full object-contain" />
                        @else
                            <span class="text-xs text-gray-400">No Custom Logo</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="company_logo"
                            name="company_logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                        />
                        <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB). Falls back to global application logo if not set.</p>
                    </div>
                </div>
            </div>

            {{-- 2. Localization Overrides Section --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                        2. Localization Overrides
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure company-specific timezone and date formatting to override system defaults for users in this tenant.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Company Timezone Override --}}
                    <div>
                        <label for="default_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Timezone Override
                        </label>
                        <select
                            id="default_timezone"
                            name="default_timezone"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($timezones as $tzKey => $tzLabel)
                                <option value="{{ $tzKey }}" {{ old('default_timezone', $settings['default_timezone']) === $tzKey ? 'selected' : '' }}>
                                    {{ $tzLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select "Use System Default" to inherit the global platform timezone.</p>
                    </div>

                    {{-- Company Date Format Override --}}
                    <div>
                        <label for="date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Date Format Override
                        </label>
                        <select
                            id="date_format"
                            name="date_format"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($dateFormats as $formatKey => $formatLabel)
                                <option value="{{ $formatKey }}" {{ old('date_format', $settings['date_format']) === $formatKey ? 'selected' : '' }}>
                                    {{ $formatLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select "Use System Default" to inherit the global platform date format.</p>
                    </div>
                </div>
            </div>

            {{-- 3. Brand Accent Theme Section (20 Curated Presets) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                            3. Primary / Accent Brand Theme
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Select one of 20 designer-balanced Tailwind v4 color palettes to brand buttons, active tabs, and badges.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Selected:</span>
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-800 dark:text-white">
                            <span class="h-2.5 w-2.5 rounded-full" :style="`background-color: ${currentTheme.dot}`"></span>
                            <span x-text="currentTheme.label"></span>
                        </span>
                    </div>
                </div>

                {{-- Theme Swatch Grid --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($brandThemeOptions as $themeKey => $themeInfo)
                        <button
                            type="button"
                            @click="selectedTheme = '{{ $themeKey }}'"
                            class="group relative flex items-center gap-2.5 rounded-lg border p-2.5 text-left text-xs font-medium transition-all focus:outline-hidden"
                            :class="selectedTheme === '{{ $themeKey }}'
                                ? 'border-gray-900 bg-gray-50/80 shadow-xs ring-2 ring-gray-900/10 dark:border-white dark:bg-gray-800/80 dark:ring-white/20'
                                : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:border-gray-600'"
                        >
                            {{-- Color Dot --}}
                            <span
                                class="h-4 w-4 shrink-0 rounded-full shadow-xs ring-1 ring-black/10 dark:ring-white/10"
                                style="background-color: {{ $themeInfo['dot'] }};"
                            ></span>

                            {{-- Label --}}
                            <span class="truncate text-gray-700 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white">
                                {{ $themeInfo['label'] }}
                            </span>

                            {{-- Selected Checkmark Indicator --}}
                            <span
                                x-show="selectedTheme === '{{ $themeKey }}'"
                                x-cloak
                                class="ml-auto flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] text-white"
                                style="background-color: {{ $themeInfo['dot'] }};"
                            >
                                ✓
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- Live Interactive Component Preview --}}
                <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Live Component Preview
                        </span>
                        <span class="text-xs text-gray-400" x-text="`Previewing ${currentTheme.label}`"></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Sample Soft Badge --}}
                        <div
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium shadow-xs transition-colors"
                            :style="`background-color: ${currentTheme.variables ? currentTheme.variables['--color-brand-50'] : '#ecf3ff'}; color: ${currentTheme.dot}`"
                        >
                            <span class="h-2 w-2 rounded-full" :style="`background-color: ${currentTheme.dot}`"></span>
                            Active Tenant Status
                        </div>

                        {{-- Sample Primary Button --}}
                        <div
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-xs font-semibold text-white shadow-theme-xs transition-colors"
                            :style="`background-color: ${currentTheme.dot}`"
                        >
                            Primary Action Button
                        </div>

                        {{-- Sample Outline / Tab Indicator --}}
                        <div
                            class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium transition-colors"
                            :style="`border-color: ${currentTheme.dot}; color: ${currentTheme.dot}`"
                        >
                            Active Tab / Filter
                        </div>

                        {{-- Sample Focus Input Ring --}}
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="Sample form input"
                                class="h-8 rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 shadow-xs focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                :style="`border-color: ${currentTheme.dot}`"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/50"
                >
                    Save Company Preferences
                </button>
            </div>
        </form>
    @else
        <div class="py-8 text-center text-sm text-gray-500">
            Please create or select a company to configure tenant preferences.
        </div>
    @endif
</div>
@endsection
