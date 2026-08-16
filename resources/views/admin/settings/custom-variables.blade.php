@extends('admin.settings.layout', [
    'title' => 'Custom Variables',
    'heading' => 'Custom Variables & Key-Value Parameters',
    'subheading' => 'Manage dynamic application variables (text, numeric, json) accessible via Setting::get("key") or Setting::custom("key").',
])

@section('settings-content')
{{-- Ace Editor Assets via jsDelivr CDN --}}
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/ace.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/mode-json.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/theme-tomorrow_night.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/theme-chrome.js"></script>

<div x-data="{
    modalOpen: false,
    editMode: false,
    formAction: '{{ route('admin.settings.custom-variables.store') }}',
    formMethod: 'POST',
    varId: null,
    varKey: '',
    varType: 'text',
    varScopeCompanyId: '{{ $selectedCompanyId ?? '' }}',
    varValue: '',
    varDescription: '',
    jsonError: '',
    isChecking: false,
    confirmOverwriteOpen: false,
    existingVariable: null,
    aceEditor: null,
    defaultJsonTemplate: JSON.stringify({
        enabled: true,
        sample_key: 'sample_value',
        items: [
            'item_1',
            'item_2'
        ]
    }, null, 2),

    initAce() {
        if (typeof ace === 'undefined') {
            setTimeout(() => this.initAce(), 50);
            return;
        }

        const el = this.$refs.aceEditor;
        if (!el) return;

        if (!window._customVarAceEditor) {
            window._customVarAceEditor = ace.edit(el, {
                mode: 'ace/mode/json',
                tabSize: 2,
                useSoftTabs: true,
                showPrintMargin: false,
                fontSize: '12px',
                useWorker: false,
            });

            window._customVarAceEditor.session.on('change', () => {
                this.varValue = window._customVarAceEditor.getValue();
                this.validateJson();
            });
        }

        const editor = window._customVarAceEditor;
        const isDark = document.body.classList.contains('dark') || document.documentElement.classList.contains('dark');
        editor.setTheme(isDark ? 'ace/theme/tomorrow_night' : 'ace/theme/chrome');

        const currentVal = this.varValue || (this.editMode ? '' : this.defaultJsonTemplate);
        this.varValue = currentVal;
        editor.setValue(currentVal, -1);
        editor.clearSelection();

        setTimeout(() => {
            editor.resize(true);
            editor.renderer.updateFull();
        }, 50);

        this.validateJson();
    },

    formatJson() {
        const editor = window._customVarAceEditor;
        if (!editor) return;
        try {
            const raw = editor.getValue();
            const parsed = JSON.parse(raw);
            const pretty = JSON.stringify(parsed, null, 2);
            editor.setValue(pretty, -1);
            editor.clearSelection();
            this.varValue = pretty;
            this.jsonError = '';
        } catch (e) {
            this.jsonError = 'Cannot format invalid JSON: ' + e.message;
        }
    },

    onTypeChange() {
        this.validateJson();
        if (this.varType === 'json') {
            if (!this.editMode && (!this.varValue || this.varValue.trim() === '')) {
                this.varValue = this.defaultJsonTemplate;
            }
            this.$nextTick(() => {
                this.initAce();
            });
        }
    },

    openCreate() {
        this.editMode = false;
        this.formAction = '{{ route('admin.settings.custom-variables.store') }}';
        this.formMethod = 'POST';
        this.varId = null;
        this.varKey = '';
        this.varType = 'text';
        this.varScopeCompanyId = '{{ $selectedCompanyId ?? '' }}';
        this.varValue = '';
        this.varDescription = '';
        this.jsonError = '';
        this.isChecking = false;
        this.confirmOverwriteOpen = false;
        this.existingVariable = null;
        this.modalOpen = true;

        if (this.varType === 'json') {
            this.varValue = this.defaultJsonTemplate;
            this.$nextTick(() => {
                this.initAce();
            });
        }
    },

    openEdit(item) {
        this.editMode = true;
        this.formAction = `/admin/settings/custom-variables/${item.id}`;
        this.formMethod = 'POST';
        this.varId = item.id;
        this.varKey = item.key;
        this.varType = item.type;
        this.varScopeCompanyId = item.company_id || '';
        this.varValue = item.raw_value || '';
        this.varDescription = item.description || '';
        this.jsonError = '';
        this.isChecking = false;
        this.confirmOverwriteOpen = false;
        this.existingVariable = null;
        this.modalOpen = true;

        if (this.varType === 'json') {
            try {
                const parsed = JSON.parse(this.varValue);
                this.varValue = JSON.stringify(parsed, null, 2);
            } catch (e) {}
            this.$nextTick(() => {
                this.initAce();
            });
        }
    },

    validateJson() {
        if (this.varType !== 'json' || !this.varValue) {
            this.jsonError = '';
            return true;
        }
        try {
            JSON.parse(this.varValue);
            this.jsonError = '';
            return true;
        } catch (e) {
            this.jsonError = 'Invalid JSON: ' + e.message;
            return false;
        }
    },

    async handleSubmit(e) {
        if (!this.validateJson()) {
            return;
        }

        {{-- If editing an existing variable directly, submit immediately --}}
        if (this.editMode) {
            this.$refs.customVarForm.submit();
            return;
        }

        {{-- Add mode: perform live DB check for key existence in the selected scope --}}
        this.isChecking = true;
        try {
            const response = await fetch('{{ route('admin.settings.custom-variables.check') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    key: this.varKey,
                    company_id: this.varScopeCompanyId ? this.varScopeCompanyId : null
                })
            });

            if (!response.ok) {
                this.$refs.customVarForm.submit();
                return;
            }

            const data = await response.json();
            if (data.exists) {
                this.existingVariable = data;
                this.confirmOverwriteOpen = true;
                return;
            }

            this.$refs.customVarForm.submit();
        } catch (err) {
            this.$refs.customVarForm.submit();
        } finally {
            this.isChecking = false;
        }
    },

    confirmOverwrite() {
        this.confirmOverwriteOpen = false;
        this.$refs.customVarForm.submit();
    }
}">
    {{-- Filter & Action Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.settings.custom-variables') }}" class="flex items-center gap-3">
            <label for="company_filter" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                Scope Filter:
            </label>
            <select
                id="company_filter"
                name="company_id"
                onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
                <option value="">Global System Defaults</option>
                @foreach ($companies as $comp)
                    <option value="{{ $comp->id }}" {{ $selectedCompanyId === $comp->id ? 'selected' : '' }}>
                        {{ $comp->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <button
            type="button"
            @click="openCreate()"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
        >
            <span>+</span>
            <span>Add Custom Variable</span>
        </button>
    </div>

    {{-- Table of Variables --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                    <th scope="col" class="py-3.5 px-4">Key</th>
                    <th scope="col" class="py-3.5 px-4">Type</th>
                    <th scope="col" class="py-3.5 px-4">Value</th>
                    <th scope="col" class="py-3.5 px-4">Scope</th>
                    <th scope="col" class="py-3.5 px-4">Description</th>
                    <th scope="col" class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($variables as $var)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            {{ $var->key }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($var->type === 'numeric')
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400">
                                    Numeric
                                </span>
                            @elseif ($var->type === 'json')
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400">
                                    JSON
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-700/10 dark:bg-gray-400/10 dark:text-gray-400">
                                    Text
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 max-w-xs truncate font-mono text-xs text-gray-600 dark:text-gray-400">
                            {{ is_array($var->typed_value) ? json_encode($var->typed_value) : (string) $var->typed_value }}
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            @if ($var->company)
                                <span class="text-brand-600 dark:text-brand-400 font-medium">{{ $var->company->name }}</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">Global</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-xs text-gray-500 dark:text-gray-400">
                            {{ $var->description ?? '—' }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="openEdit({
                                        id: {{ $var->id }},
                                        key: '{{ addslashes($var->key) }}',
                                        type: '{{ $var->type }}',
                                        company_id: '{{ $var->company_id }}',
                                        raw_value: `{{ addslashes($var->getRawOriginal('value') ?? '') }}`,
                                        description: '{{ addslashes($var->description ?? '') }}'
                                    })"
                                    class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                                    title="Edit"
                                >
                                    ✏️
                                </button>

                                <form
                                    action="{{ route('admin.settings.custom-variables.destroy', $var->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete variable \'{{ $var->key }}\'?');"
                                    class="inline"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="rounded p-1 text-error-500 hover:bg-error-50 hover:text-error-700 dark:hover:bg-error-500/10"
                                        title="Delete"
                                    >
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No custom variables configured. Click <strong>+ Add Custom Variable</strong> above to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create / Edit Modal (Alpine.js) --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="if (confirmOverwriteOpen) { confirmOverwriteOpen = false; } else { modalOpen = false; }"
    >
        <div
            @click.outside="if (!confirmOverwriteOpen) modalOpen = false"
            class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            {{-- In-Modal Overwrite Confirmation Overlay --}}
            <div
                x-show="confirmOverwriteOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute inset-0 z-20 flex flex-col justify-between bg-white p-6 dark:bg-gray-900 overflow-y-auto"
            >
                <div>
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ __('Variable Key Already Exists') }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('A variable with this key already exists in the database.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-4 space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-xs dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Key') }}:</span>
                            <span class="font-mono font-semibold text-gray-800 dark:text-gray-200" x-text="existingVariable?.key"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Scope') }}:</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200" x-text="existingVariable?.company_name"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-800">
                            <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Current Database Value') }} (<span x-text="existingVariable?.type"></span>):
                            </div>
                            <div class="max-h-20 overflow-y-auto rounded bg-white p-2 font-mono text-[11px] text-gray-700 dark:bg-gray-950 dark:text-gray-300">
                                <span x-text="existingVariable?.value || '({{ __('Empty') }})'"></span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-800">
                            <div class="text-[11px] font-medium text-brand-600 dark:text-brand-400 mb-1">
                                {{ __('New Value to Save') }} (<span x-text="varType"></span>):
                            </div>
                            <div class="max-h-20 overflow-y-auto rounded bg-white p-2 font-mono text-[11px] text-gray-700 dark:bg-gray-950 dark:text-gray-300">
                                <span x-text="varValue || '({{ __('Empty') }})'"></span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        {{ __('Would you like to update the existing key with this new value?') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        @click="confirmOverwriteOpen = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ __('Keep Editing') }}
                    </button>
                    <button
                        type="button"
                        @click="confirmOverwrite()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 focus:outline-hidden"
                    >
                        <span>{{ __('Yes, Update Variable') }}</span>
                    </button>
                </div>
            </div>

            {{-- Main Form Header --}}
            <div class="mb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Edit Custom Variable' : 'Add New Custom Variable'"></h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Define a key-type-value configuration pair accessible system-wide or per-company.
                </p>
            </div>

            {{-- Main Form Body --}}
            <form x-ref="customVarForm" :action="formAction" method="POST" @submit.prevent="handleSubmit($event)" class="space-y-4">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT" />
                </template>

                {{-- Key --}}
                <div>
                    <label for="var_key" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Variable Key <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="var_key"
                        name="key"
                        x-model="varKey"
                        :disabled="editMode"
                        required
                        placeholder="e.g. max_file_upload_mb or support_phone"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 font-mono text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden disabled:bg-gray-100 disabled:opacity-75 dark:border-gray-700 dark:bg-gray-950 dark:text-white/90 dark:disabled:bg-gray-800"
                    />
                    <p class="mt-1 text-[11px] text-gray-400">Alphanumeric, dashes, and underscores only.</p>
                </div>

                {{-- Scope Company (Only on Create) --}}
                <div x-show="!editMode">
                    <label for="var_company_id" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Scope
                    </label>
                    <select
                        id="var_company_id"
                        name="company_id"
                        x-model="varScopeCompanyId"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    >
                        <option value="">Global (All Companies / System Default)</option>
                        @foreach ($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type --}}
                <div>
                    <label for="var_type" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Data Type <span class="text-error-500">*</span>
                    </label>
                    <select
                        id="var_type"
                        name="type"
                        x-model="varType"
                        @change="onTypeChange()"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    >
                        <option value="text">Text (String)</option>
                        <option value="numeric">Numeric (Integer / Decimal)</option>
                        <option value="json">JSON (Object / Array)</option>
                    </select>
                </div>

                {{-- Value --}}
                <div>
                    <label for="var_value" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Value
                    </label>

                    {{-- Text input for text --}}
                    <template x-if="varType === 'text'">
                        <textarea
                            id="var_value"
                            name="value"
                            x-model="varValue"
                            rows="3"
                            placeholder="Enter text value"
                            class="w-full rounded-lg border border-gray-300 bg-transparent p-3 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                        ></textarea>
                    </template>

                    {{-- Number input for numeric --}}
                    <template x-if="varType === 'numeric'">
                        <input
                            type="number"
                            step="any"
                            id="var_value"
                            name="value"
                            x-model="varValue"
                            placeholder="e.g. 25 or 3.14"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 font-mono text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                        />
                    </template>

                    {{-- Ace Code Editor for JSON --}}
                    <div x-show="varType === 'json'" x-cloak class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                JSON Object / Array Editor
                            </span>
                            <button
                                type="button"
                                @click="formatJson()"
                                class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                title="Format JSON with 2-space indentation"
                            >
                                <span>🧹</span>
                                <span>Format JSON</span>
                            </button>
                        </div>
                        <div
                            x-ref="aceEditor"
                            id="custom_var_ace_editor"
                            style="height: 220px; min-height: 220px; width: 100%; position: relative;"
                            class="w-full rounded-lg border border-gray-300 shadow-theme-xs dark:border-gray-700"
                        ></div>
                        <input type="hidden" name="value" :value="varValue" :disabled="varType !== 'json'" />
                        <p x-show="jsonError" x-text="jsonError" class="mt-1 text-xs text-error-500"></p>
                        <p x-show="!jsonError && varValue" class="mt-1 text-[11px] text-success-600 dark:text-success-400 flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Valid JSON
                        </p>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label for="var_description" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Description / Notes
                    </label>
                    <input
                        type="text"
                        id="var_description"
                        name="description"
                        x-model="varDescription"
                        placeholder="Purpose or context of this variable"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        @click="modalOpen = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isChecking"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 focus:outline-hidden disabled:opacity-50"
                    >
                        <svg x-show="isChecking" class="h-3.5 w-3.5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isChecking ? 'Checking...' : 'Save Variable'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
