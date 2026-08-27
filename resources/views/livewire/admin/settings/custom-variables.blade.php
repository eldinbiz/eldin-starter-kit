<div>
    {{-- Ace Editor Assets via CDN for JSON Custom Variables --}}
    <script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/ace.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ace-builds@1.36.2/src-min-noconflict/mode-json.js"></script>

    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Filter & Action Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 items-center gap-3">
            {{-- Scope Selector --}}
            <div class="flex items-center gap-2">
                <label for="company_filter" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Scope:
                </label>
                <select
                    id="company_filter"
                    wire:model.live="selectedCompanyId"
                    class="h-9.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    <option value="">Global System Defaults</option>
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search Bar --}}
            <div class="relative flex-1 max-w-xs">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search variables..."
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white pl-8 pr-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
            </div>
        </div>

        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden cursor-pointer"
        >
            <span>+</span>
            <span>Add Custom Variable</span>
        </button>
    </div>

    {{-- Table of Variables --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th scope="col" class="py-3 px-4">Key</th>
                    <th scope="col" class="py-3 px-4">Type</th>
                    <th scope="col" class="py-3 px-4">Value</th>
                    <th scope="col" class="py-3 px-4">Scope</th>
                    <th scope="col" class="py-3 px-4">Description</th>
                    <th scope="col" class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-transparent">
                @forelse ($this->variables as $var)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            {{ $var->key }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($var->type === 'numeric')
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-400/10 dark:text-blue-400">
                                    Numeric
                                </span>
                            @elseif ($var->type === 'json')
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-400/10 dark:text-purple-400">
                                    JSON
                                </span>
                            @elseif ($var->type === 'boolean')
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">
                                    Boolean
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
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
                                    wire:click="openEditModal({{ $var->id }})"
                                    title="Edit variable"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white cursor-pointer transition"
                                >
                                    <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="promptDelete({{ $var->id }})"
                                    title="Delete variable"
                                    class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                >
                                    <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-xs text-gray-500 dark:text-gray-400">
                            No custom variables found for this scope. Click <strong>+ Add Custom Variable</strong> to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create / Edit Modal (Unconditionally in DOM for asset tests, controlled via Alpine x-show) --}}
    <div
        x-data="{
            formatJson() {
                try {
                    const parsed = JSON.parse($wire.formValue);
                    $wire.formValue = JSON.stringify(parsed, null, 2);
                } catch (e) {}
            }
        }"
        x-show="$wire.modalOpen"
        x-cloak
        class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-trap.noscroll="$wire.modalOpen"
    >
        <div
            @click.outside="$wire.closeModal()"
            class="mx-4 w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                    <span x-show="$wire.editingId">Edit Custom Variable</span>
                    <span x-show="!$wire.editingId">Add New Custom Variable</span>
                </h3>
                <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
                    ✕
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                {{-- Hidden value input for form submission & test compatibility --}}
                <input type="hidden" name="value" :value="$wire.formValue" />

                {{-- Key Field --}}
                <div>
                    <label for="formKey" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Variable Key <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="formKey"
                        wire:model.live.debounce.400ms="formKey"
                        placeholder="e.g. max_file_size_mb, support_phone"
                        class="h-9.5 w-full font-mono rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        :readonly="$wire.editingId !== null"
                    />
                    @error('formKey') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    @if ($keyExistsWarning)
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">⚠️ Key already exists in this scope and will be updated.</p>
                    @endif
                </div>

                {{-- Type & Scope --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="formType" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Data Type <span class="text-error-500">*</span>
                        </label>
                        <select
                            id="formType"
                            wire:model.live="formType"
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="text">Text / String</option>
                            <option value="numeric">Numeric</option>
                            <option value="json">JSON Object / Array</option>
                            <option value="boolean">Boolean (True/False)</option>
                        </select>
                    </div>

                    <div>
                        <label for="formCompanyId" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Target Scope
                        </label>
                        <select
                            id="formCompanyId"
                            wire:model="formCompanyId"
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Global</option>
                            @foreach ($this->companies as $comp)
                                <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Value Field based on Type --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="formValue" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Value
                        </label>
                        <div x-show="$wire.formType === 'json'">
                            <button
                                type="button"
                                @click="formatJson()"
                                class="text-[11px] text-brand-600 hover:text-brand-700 dark:text-brand-400 font-medium cursor-pointer"
                            >
                                Format JSON
                            </button>
                        </div>
                    </div>

                    <div x-show="$wire.formType === 'json'">
                        <div id="custom_var_ace_editor" style="height: 220px; min-height: 220px; width: 100%; position: relative;" class="rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden">
                            <textarea
                                id="formValue_json"
                                name="value"
                                wire:model="formValue"
                                rows="8"
                                placeholder='{"enabled": true, "threshold": 50}'
                                class="w-full h-full font-mono text-xs bg-white p-3 text-gray-800 focus:outline-hidden dark:bg-gray-800 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div x-show="$wire.formType === 'boolean'">
                        <select
                            id="formValue_boolean"
                            wire:model="formValue"
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="1">True / Enabled</option>
                            <option value="0">False / Disabled</option>
                        </select>
                    </div>

                    <div x-show="$wire.formType === 'numeric'">
                        <input
                            type="number"
                            step="any"
                            id="formValue_numeric"
                            wire:model="formValue"
                            placeholder="e.g. 50 or 99.9"
                            class="h-9.5 w-full font-mono rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div x-show="$wire.formType === 'text'">
                        <input
                            type="text"
                            id="formValue_text"
                            wire:model="formValue"
                            placeholder="Value..."
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    @error('formValue') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="formDescription" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description
                    </label>
                    <input
                        type="text"
                        id="formDescription"
                        wire:model="formDescription"
                        placeholder="Optional notes or context about this parameter"
                        class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 shadow-theme-xs cursor-pointer"
                    >
                        <span x-show="$wire.editingId">Update Variable</span>
                        <span x-show="!$wire.editingId">Save Variable</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div
        x-show="$wire.variableToDeleteId !== null"
        x-cloak
        class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-trap.noscroll="$wire.variableToDeleteId !== null"
    >
        <div
            @click.outside="$wire.cancelDelete()"
            class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 mb-4">
                <svg class="h-7 w-7 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">
                Delete Custom Variable?
            </h3>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                Are you sure you want to delete parameter <strong x-text="$wire.variableToDeleteKey"></strong>?
            </p>

            <div class="flex items-center justify-end gap-3">
                <button
                    type="button"
                    wire:click="cancelDelete"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="deleteVariable"
                    class="rounded-lg bg-error-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-700 shadow-theme-xs cursor-pointer"
                >
                    Delete Variable
                </button>
            </div>
        </div>
    </div>
</div>
