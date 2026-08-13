@extends('admin.settings.layout', [
    'title' => 'General & Branding Settings',
    'heading' => 'General & Localization Settings',
    'subheading' => 'Configure platform branding, default system timezone, date formats, and theme preferences.',
])

@section('settings-content')
<form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Application Name --}}
        <div class="md:col-span-2">
            <label for="app_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Application Name <span class="text-error-500">*</span>
            </label>
            <input
                type="text"
                id="app_name"
                name="app_name"
                value="{{ old('app_name', $settings['app_name']) }}"
                required
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                placeholder="e.g. Scaffolding App"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Displayed in browser title bars, header navbar, and system notification emails.
            </p>
        </div>

        {{-- App Logo Upload --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Application Logo
            </label>
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-28 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                    @if (!empty($settings['app_logo']))
                        <img src="{{ $settings['app_logo'] }}" alt="App Logo" class="max-h-12 max-w-full object-contain" />
                    @else
                        <span class="text-xs text-gray-400">Default Logo</span>
                    @endif
                </div>
                <div class="flex-1">
                    <input
                        type="file"
                        id="app_logo"
                        name="app_logo"
                        accept="image/png,image/jpeg,image/svg+xml,image/webp"
                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                    />
                    <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB)</p>
                </div>
            </div>
        </div>

        {{-- App Favicon Upload --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Application Favicon
            </label>
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                    @if (!empty($settings['app_favicon']))
                        <img src="{{ $settings['app_favicon'] }}" alt="Favicon" class="h-8 w-8 object-contain" />
                    @else
                        <span class="text-xs text-gray-400">32x32</span>
                    @endif
                </div>
                <div class="flex-1">
                    <input
                        type="file"
                        id="app_favicon"
                        name="app_favicon"
                        accept="image/x-icon,image/png,image/svg+xml"
                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                    />
                    <p class="mt-1 text-xs text-gray-500">ICO, PNG or SVG (Max 1MB)</p>
                </div>
            </div>
        </div>

        {{-- Default Timezone --}}
        <div>
            <label for="default_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Default System Timezone <span class="text-error-500">*</span>
            </label>
            <select
                id="default_timezone"
                name="default_timezone"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
                @foreach ($timezones as $tzValue => $tzLabel)
                    <option value="{{ $tzValue }}" {{ old('default_timezone', $settings['default_timezone']) === $tzValue ? 'selected' : '' }}>
                        {{ $tzLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Default Date Format --}}
        <div>
            <label for="date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                System Date Format <span class="text-error-500">*</span>
            </label>
            <select
                id="date_format"
                name="date_format"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
                @foreach ($dateFormats as $formatKey => $formatLabel)
                    <option value="{{ $formatKey }}" {{ old('date_format', $settings['date_format']) === $formatKey ? 'selected' : '' }}>
                        {{ $formatLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Time Format (12h/24h) --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Time Display Format
            </label>
            <div class="flex items-center gap-6 pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="radio"
                        name="time_format"
                        value="24h"
                        {{ old('time_format', $settings['time_format']) === '24h' ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <span>24 Hours (e.g. 14:30)</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="radio"
                        name="time_format"
                        value="12h"
                        {{ old('time_format', $settings['time_format']) === '12h' ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <span>12 Hours AM/PM (e.g. 02:30 PM)</span>
                </label>
            </div>
        </div>

        {{-- Default Theme Mode --}}
        <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Default Theme Appearance
            </label>
            <div class="flex items-center gap-6 pt-1">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="radio"
                        name="default_theme"
                        value="system"
                        {{ old('default_theme', $settings['default_theme']) === 'system' ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <span>🖥️ System Adaptive (Auto)</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="radio"
                        name="default_theme"
                        value="light"
                        {{ old('default_theme', $settings['default_theme']) === 'light' ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <span>☀️ Light Mode</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="radio"
                        name="default_theme"
                        value="dark"
                        {{ old('default_theme', $settings['default_theme']) === 'dark' ? 'checked' : '' }}
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <span>🌙 Dark Mode</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Submit Button --}}
    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/50"
        >
            Save General Settings
        </button>
    </div>
</form>
@endsection
