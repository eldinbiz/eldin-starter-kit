<div>
    <x-ui::card>
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $this->isEditing ? 'Edit Role: ' . ucfirst($this->name) : 'Create New Role' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $this->isEditing ? 'Update role name and customize assignable permission privileges.' : 'Define a new role and assign its permission privileges.' }}
            </p>
        </div>

        {{-- Flash Errors / Validation --}}
        @if ($errors->any())
            <x-ui::alert type="error" class="mb-6">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui::alert>
        @endif

        <form wire:submit.prevent="save">
            {{-- Role Name --}}
            <div class="mb-6">
                <x-ui::input
                    type="text"
                    id="role_name"
                    wire:model="name"
                    label="Role Name"
                    required
                    maxlength="255"
                    placeholder="e.g. content_moderator"
                    helper='Use lowercase alphanumeric characters with underscores or hyphens, e.g. "senior_auditor".'
                />
            </div>

            {{-- Permissions Section Header & Filter --}}
            <div class="mb-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">
                            Permissions Matrix
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ count($selectedPermissions) }} permission(s) currently selected.
                        </p>
                    </div>

                    {{-- Search Filter in Permissions --}}
                    <div class="w-full sm:w-64">
                        <x-ui::input
                            type="text"
                            wire:model.live.debounce.150ms="permissionSearch"
                            placeholder="Filter permissions..."
                        />
                    </div>
                </div>

                {{-- Grouped Permissions --}}
                <div class="space-y-6">
                    @forelse ($this->groupedPermissions as $group => $permissions)
                        <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                            {{-- Group Header & Group Select All Button --}}
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200/70 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $group }}
                                    </h5>
                                    <span class="rounded-full bg-gray-200/80 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        {{ count($permissions) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="toggleGroup('{{ $group }}')"
                                        class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 cursor-pointer"
                                    >
                                        {{ $this->isGroupFullySelected($group) ? 'Deselect All' : 'Select All' }}
                                    </button>
                                </div>
                            </div>

                            {{-- Permission Items Grid / List --}}
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                @foreach ($permissions as $key => $definition)
                                    @php
                                        $alwaysEnabled = $this->isAlwaysEnabled($key);
                                        $isChecked = $alwaysEnabled || in_array($key, $selectedPermissions, true);
                                    @endphp
                                    <label
                                        wire:click.prevent="togglePermission('{{ $key }}')"
                                        class="flex items-start gap-3 rounded-lg border p-3 transition select-none cursor-pointer {{ $isChecked ? 'border-brand-200 bg-brand-50/20 dark:border-brand-800/40 dark:bg-brand-500/5' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700/60 dark:bg-white/[0.02] dark:hover:bg-white/[0.04]' }} {{ $alwaysEnabled ? 'opacity-85 cursor-not-allowed' : '' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $key }}"
                                            @checked($isChecked)
                                            @disabled($alwaysEnabled)
                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-white/[0.05]"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-xs font-medium text-gray-800 dark:text-white/90">
                                                    {{ $definition['label'] ?? $key }}
                                                </span>
                                                @if ($alwaysEnabled)
                                                    <span class="inline-flex items-center gap-1 rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                                        <svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                                        </svg>
                                                        Always enabled
                                                    </span>
                                                @endif
                                            </div>
                                            @if (! empty($definition['description']))
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ $definition['description'] }}
                                                </p>
                                            @else
                                                <span class="block text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                                    {{ $key }}
                                                </span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 p-8 text-center dark:border-gray-800">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No permissions matched the filter "{{ $permissionSearch }}".
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Form Footer Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
                <a href="{{ route('admin.roles.index') }}" wire:navigate>
                    <x-ui::button variant="outline">
                        Cancel
                    </x-ui::button>
                </a>
                <x-ui::button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ $this->isEditing ? 'Update Role' : 'Create Role' }}</span>
                </x-ui::button>
            </div>
        </form>
    </x-ui::card>
</div>
