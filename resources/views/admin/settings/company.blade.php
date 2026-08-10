@extends('admin.settings.layout', [
    'title' => 'Company Preferences',
    'heading' => 'Company Branding & Preferences',
    'subheading' => 'Customize company logo, primary theme accent color, and localization overrides per tenant.',
])

@section('settings-content')
<div>
    <!-- Company Selector Bar -->
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
        <form action="{{ route('admin.settings.company.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $selectedCompany->id }}" />

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Company Logo -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Company Logo
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-28 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                            @if (!empty($settings['company_logo']) || !empty($selectedCompany->logo))
                                <img src="{{ $settings['company_logo'] ?: $selectedCompany->logo }}" alt="Company Logo" class="max-h-12 max-w-full object-contain" />
                            @else
                                <span class="text-xs text-gray-400">No Logo</span>
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
                            <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Primary Accent Color -->
                <div x-data="{ color: '{{ old('accent_color', $settings['accent_color']) }}' }">
                    <label for="accent_color" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Primary / Accent Brand Color <span class="text-error-500">*</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            id="accent_color_picker"
                            x-model="color"
                            class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-transparent p-1 dark:border-gray-700"
                        />
                        <input
                            type="text"
                            id="accent_color"
                            name="accent_color"
                            x-model="color"
                            required
                            placeholder="#465FFF"
                            class="h-11 w-40 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                        <div class="h-8 w-8 rounded-full border border-gray-300 shadow-xs" :style="`background-color: ${color}`"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Used for custom tenant badges, action highlights, and email accents.
                    </p>
                </div>

                <!-- Company Timezone Override -->
                <div>
                    <label for="company_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Company Timezone Override
                    </label>
                    <select
                        id="company_timezone"
                        name="company_timezone"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    >
                        @foreach ($timezones as $tzKey => $tzLabel)
                            <option value="{{ $tzKey }}" {{ old('company_timezone', $settings['company_timezone']) === $tzKey ? 'selected' : '' }}>
                                {{ $tzLabel }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Leave as default to inherit the system-wide timezone.</p>
                </div>

                <!-- Company Date Format Override -->
                <div>
                    <label for="company_date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Company Date Format Override
                    </label>
                    <select
                        id="company_date_format"
                        name="company_date_format"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    >
                        @foreach ($dateFormats as $formatKey => $formatLabel)
                            <option value="{{ $formatKey }}" {{ old('company_date_format', $settings['company_date_format']) === $formatKey ? 'selected' : '' }}>
                                {{ $formatLabel }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Leave as default to inherit the system-wide date format.</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/50"
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
