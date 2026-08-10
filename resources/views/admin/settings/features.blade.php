@extends('admin.settings.layout', [
    'title' => 'Feature Toggles',
    'heading' => 'Feature Toggles & Module Management',
    'subheading' => 'Enable or disable platform modules globally, then override per company. Modules default to enabled.',
])

@section('settings-content')
<div class="space-y-6">
    <!-- GLOBAL DEFAULTS -->
    <form action="{{ route('admin.settings.features.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Global Defaults</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Platform-wide default state. Companies inherit these unless overridden below.
                </p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($features as $key => $feature)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ $feature['label'] }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="features[{{ $key }}]" value="0" />
                            <input
                                type="checkbox"
                                name="features[{{ $key }}]"
                                value="1"
                                class="peer sr-only"
                                {{ ! empty($globalValues[$key]) ? 'checked' : '' }}
                            >
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-500 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"></div>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-800">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                >
                    Save Global Defaults
                </button>
            </div>
        </div>
    </form>

    <!-- PER-COMPANY OVERRIDES -->
    <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
        <div class="mb-4 flex flex-col gap-3">
            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Per-Company Overrides</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    "Use Global" inherits the platform default, including future changes to it.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.settings.features') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
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
                        <option value="{{ $comp->id }}" {{ $selectedCompanyId === $comp->id ? 'selected' : '' }}>
                            {{ $comp->name }} ({{ $comp->type ?? 'Company' }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($selectedCompanyId)
            <form action="{{ route('admin.settings.features.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}" />

                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($features as $key => $feature)
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $feature['label'] }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</p>
                            </div>
                            <select
                                name="features[{{ $key }}]"
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden sm:w-44 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            >
                                <option value="" {{ $companyValues[$key] === null ? 'selected' : '' }}>Use Global</option>
                                <option value="1" {{ $companyValues[$key] === true ? 'selected' : '' }}>On</option>
                                <option value="0" {{ $companyValues[$key] === false ? 'selected' : '' }}>Off</option>
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                    >
                        Save Overrides
                    </button>
                </div>
            </form>
        @else
            <div class="rounded-lg bg-gray-50 p-4 text-center text-xs text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                Select a company above to manage its feature overrides.
            </div>
        @endif
    </div>
</div>
@endsection
