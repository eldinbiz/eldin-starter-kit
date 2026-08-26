<div
    x-data="{
        themes: {{ Js::from($themeOptions) }},
        applyThemeVariables(themeKey) {
            const theme = this.themes[themeKey];
            if (!theme || !theme.variables) return;
            for (const [prop, val] of Object.entries(theme.variables)) {
                document.documentElement.style.setProperty(prop, val);
            }
        }
    }"
>
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-6 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    @if ($flashError)
        <div class="mb-6 flex items-center justify-between rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashError }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-error-600 hover:text-error-800 dark:text-error-400 dark:hover:text-error-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Company Selector Bar --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <label for="company_id_select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Selected Company:
                </label>
                <select
                    id="company_id_select"
                    wire:model.live="selectedCompanyId"
                    class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->name }} ({{ $comp->type ?? 'Company' }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($this->selectedCompany)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Editing preferences for <strong>{{ $this->selectedCompany->name }}</strong>
                </span>
            @endif
        </div>
    </div>

    @if ($this->selectedCompany)
        <form wire:submit="save" class="space-y-6">
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
                    <div class="flex h-20 w-36 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50 overflow-hidden">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="New Logo Preview" class="max-h-16 max-w-full object-contain" />
                        @elseif (!empty($existing_logo))
                            <img src="{{ $existing_logo }}" alt="Company Logo" class="max-h-16 max-w-full object-contain" />
                        @else
                            <span class="text-xs text-gray-400">No Custom Logo</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="company_logo"
                            wire:model="logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                        />
                        <div wire:loading wire:target="logo" class="text-xs text-brand-500 mt-1">Uploading logo preview...</div>
                        @error('logo') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB). Falls back to global application logo if not set.</p>
                    </div>
                </div>
            </div>

            {{-- 2. Brand Theme Palette (Instant Live Preview) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                2. Brand Theme Palette
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Select a curated primary color palette for this company. Clicking any theme previews it live in your browser!
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                            Active: {{ $themeOptions[$brand_theme]['label'] ?? ucfirst($brand_theme) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($themeOptions as $key => $opt)
                        @php
                            $isSelected = $brand_theme === $key;
                        @endphp
                        <button
                            type="button"
                            wire:click="setTheme('{{ $key }}')"
                            @click="applyThemeVariables('{{ $key }}')"
                            class="relative flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition-all cursor-pointer {{ $isSelected ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-400 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700' }}"
                        >
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-4 w-4 rounded-full border border-black/10 shadow-xs shrink-0" style="background-color: {{ $opt['dot'] }}"></span>
                                    <span class="text-xs font-medium text-gray-800 dark:text-white/90 truncate">{{ $opt['label'] }}</span>
                                </div>
                                @if ($isSelected)
                                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- 3. Localization Overrides Section --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                        3. Localization Overrides
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure company-specific timezone and date formatting to override system defaults for users in this tenant.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Timezone Override --}}
                    <div>
                        <label for="company_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Timezone Override
                        </label>
                        <select
                            id="company_timezone"
                            wire:model="default_timezone"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($timezones as $tzKey => $tzLabel)
                                <option value="{{ $tzKey }}">{{ $tzLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Format Override --}}
                    <div>
                        <label for="company_date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Date Format Override
                        </label>
                        <select
                            id="company_date_format"
                            wire:model="date_format"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($dateFormats as $dfKey => $dfLabel)
                                <option value="{{ $dfKey }}">{{ $dfLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Save Action --}}
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800 gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="save">Save Company Preferences</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
            No companies found. Please create a company first.
        </div>
    @endif
</div>
