@extends('admin.settings.layout', [
    'title' => 'Custom Variables',
    'heading' => 'Custom Variables & Key-Value Parameters',
    'subheading' => 'Manage dynamic application variables (text, numeric, json) accessible via Setting::get("key") or Setting::custom("key").',
])

@section('settings-content')
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
        this.modalOpen = true;
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
        this.modalOpen = true;
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
    }
}">
    <!-- Filter & Action Header -->
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

    <!-- Table of Variables -->
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

    <!-- Create / Edit Modal (Alpine.js) -->
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="modalOpen = false"
    >
        <div
            @click.outside="modalOpen = false"
            class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="mb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white" x-text="editMode ? 'Edit Custom Variable' : 'Add New Custom Variable'"></h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Define a key-type-value configuration pair accessible system-wide or per-company.
                </p>
            </div>

            <form :action="formAction" method="POST" @submit="validateJson()" class="space-y-4">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT" />
                </template>

                <!-- Key -->
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

                <!-- Scope Company (Only on Create) -->
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

                <!-- Type -->
                <div>
                    <label for="var_type" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Data Type <span class="text-error-500">*</span>
                    </label>
                    <select
                        id="var_type"
                        name="type"
                        x-model="varType"
                        @change="validateJson()"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    >
                        <option value="text">Text (String)</option>
                        <option value="numeric">Numeric (Integer / Decimal)</option>
                        <option value="json">JSON (Object / Array)</option>
                    </select>
                </div>

                <!-- Value -->
                <div>
                    <label for="var_value" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Value
                    </label>

                    <!-- Text input for text -->
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

                    <!-- Number input for numeric -->
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

                    <!-- Monospace textarea for JSON -->
                    <template x-if="varType === 'json'">
                        <div>
                            <textarea
                                id="var_value"
                                name="value"
                                x-model="varValue"
                                @input="validateJson()"
                                rows="4"
                                placeholder='{"key": "value", "enabled": true}'
                                class="w-full rounded-lg border border-gray-300 bg-transparent p-3 font-mono text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                            ></textarea>
                            <p x-show="jsonError" x-text="jsonError" class="mt-1 text-xs text-error-500"></p>
                        </div>
                    </template>
                </div>

                <!-- Description -->
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
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 focus:outline-hidden"
                    >
                        Save Variable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
