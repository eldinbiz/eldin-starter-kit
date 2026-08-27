<div>
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
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
        <div class="mb-4 flex items-center justify-between rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
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

    {{-- Top Controls --}}
    @include('livewire.datatables.header', [
        'placeholder' => 'Search roles by name...',
        'hasFilters' => true,
    ])

    {{-- Collapsible Filter Panel --}}
    @if ($this->filterPanelOpen)
        <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50/75 p-4 dark:border-gray-800 dark:bg-gray-800/40">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Status Filter --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Status
                    </label>
                    <select
                        wire:model.live="filterValues.status"
                        class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="disabled">Disabled Only</option>
                    </select>
                </div>

                {{-- Include Trashed Toggle --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Trash Filter
                    </label>
                    <label class="inline-flex items-center gap-2 pt-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="checkbox"
                            value="1"
                            wire:model.live="filterValues.trashed"
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Show deleted roles only</span>
                    </label>
                </div>
            </div>

            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs font-medium text-error-600 hover:text-error-700 dark:text-error-400 dark:hover:text-error-300 cursor-pointer"
                >
                    Reset all filters
                </button>
            </div>
        </div>
    @endif

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips', [
        'filterLabels' => [
            'status' => 'Status',
            'trashed' => 'Deleted Roles',
        ],
    ])

    {{-- Loading Skeleton --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 5, 'rows' => 5])
    </div>

    {{-- Table Content --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            {{-- Desktop Table View --}}
            <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800/60 dark:border-gray-800">
                        <tr>
                            @include('livewire.datatables.th', ['field' => 'name', 'label' => 'Role', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Permissions', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'users_count', 'label' => 'Users', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'is_active', 'label' => 'Status', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'created_at', 'label' => 'Created', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Actions', 'sortable' => false, 'align' => 'right'])
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-transparent">
                        @foreach ($this->rows as $role)
                            <tr class="hover:bg-gray-50/75 dark:hover:bg-white/[0.02] transition-colors {{ $role->trashed() ? 'opacity-60' : '' }}">
                                {{-- Role Column --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                                            {{ strtoupper(substr($role->name, 0, 2)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">
                                                {{ ucfirst(str_replace(['-', '_'], ' ', $role->name)) }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $role->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Permissions Column --}}
                                <td class="py-3.5 px-4 max-w-xs md:max-w-md">
                                    @php
                                        $perms = $role->permissions ?? [];
                                        $displayPerms = array_slice($perms, 0, 3);
                                        $extraCount = count($perms) - 3;
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @forelse ($displayPerms as $permKey)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                {{ $this->permissionLabels[$permKey] ?? $permKey }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">No permissions</span>
                                        @endforelse

                                        @if ($extraCount > 0)
                                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400" title="{{ implode(', ', array_slice($perms, 3)) }}">
                                                +{{ $extraCount }} more
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Users Count --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 font-medium">
                                        {{ $role->users_count }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($role->trashed())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                                            Deleted
                                        </span>
                                    @elseif (! $role->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                                            Disabled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
                                            Active
                                        </span>
                                    @endif
                                </td>

                                {{-- Created Date --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                        {{ $role->created_at ? $role->created_at->format('M d, Y') : '&mdash;' }}
                                    </p>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (! $role->trashed())
                                            {{-- Edit Role --}}
                                            <a
                                                href="{{ route('admin.roles.edit', $role->id) }}"
                                                wire:navigate
                                                title="Edit role"
                                                class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white transition"
                                            >
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                    <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                                </svg>
                                            </a>

                                            {{-- Toggle Active / Disabled --}}
                                            <button
                                                type="button"
                                                wire:click="toggleStatus({{ $role->id }})"
                                                title="{{ $role->is_active ? 'Disable role' : 'Enable role' }}"
                                                class="cursor-pointer transition {{ $role->is_active ? 'text-gray-500 hover:text-warning-500 dark:text-gray-400 dark:hover:text-warning-400' : 'text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400' }}"
                                            >
                                                @if ($role->is_active)
                                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                                    </svg>
                                                @endif
                                            </button>

                                            {{-- If disabled, allow soft-delete prompt --}}
                                            @if (! $role->is_active)
                                                <button
                                                    type="button"
                                                    wire:click="promptDelete({{ $role->id }})"
                                                    title="Delete role"
                                                    class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                                >
                                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        @else
                                            {{-- Restore Role --}}
                                            <button
                                                type="button"
                                                wire:click="restoreRole({{ $role->id }})"
                                                title="Restore role"
                                                class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer transition"
                                            >
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards Grid View --}}
            <div class="grid grid-cols-1 gap-4 sm:hidden">
                @foreach ($this->rows as $role)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] {{ $role->trashed() ? 'opacity-60' : '' }}">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3">
                                <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                                    {{ strtoupper(substr($role->name, 0, 2)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ ucfirst($role->name) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $role->users_count }} user(s)</p>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            @if ($role->trashed())
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                                    Deleted
                                </span>
                            @elseif (! $role->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                                    Disabled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
                                    Active
                                </span>
                            @endif
                        </div>

                        {{-- Permissions --}}
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            @php
                                $perms = $role->permissions ?? [];
                                $displayPerms = array_slice($perms, 0, 4);
                                $extraCount = count($perms) - 4;
                            @endphp
                            @forelse ($displayPerms as $permKey)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $this->permissionLabels[$permKey] ?? $permKey }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">No permissions</span>
                            @endforelse

                            @if ($extraCount > 0)
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    +{{ $extraCount }} more
                                </span>
                            @endif
                        </div>

                        {{-- Footer: Created + Actions --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Created {{ $role->created_at ? $role->created_at->format('M d, Y') : '&mdash;' }}
                            </span>

                            <div class="flex items-center gap-2">
                                @if (! $role->trashed())
                                    <a
                                        href="{{ route('admin.roles.edit', $role->id) }}"
                                        wire:navigate
                                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white transition"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                        </svg>
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="toggleStatus({{ $role->id }})"
                                        class="cursor-pointer transition {{ $role->is_active ? 'text-gray-500 hover:text-warning-500 dark:text-gray-400' : 'text-gray-500 hover:text-success-500 dark:text-gray-400' }}"
                                    >
                                        @if ($role->is_active)
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                                            </svg>
                                        @else
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                            </svg>
                                        @endif
                                    </button>

                                    @if (! $role->is_active)
                                        <button
                                            type="button"
                                            wire:click="promptDelete({{ $role->id }})"
                                            class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                    @endif
                                @else
                                    <button
                                        type="button"
                                        wire:click="restoreRole({{ $role->id }})"
                                        class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer transition"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @include('livewire.datatables.pagination', ['paginator' => $this->rows])
        @else
            {{-- Empty State --}}
            @include('livewire.datatables.empty', [
                'title' => 'No roles found',
                'message' => 'No role records matched your search query or filter criteria.',
            ])
        @endif
    </div>

    {{-- Type-to-confirm Delete Role Confirmation Modal --}}
    @if ($roleToDeleteId)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.cancelDelete()"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                {{-- Warning Icon --}}
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 mb-4">
                    <svg class="h-7 w-7 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                {{-- Header --}}
                <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">
                    Delete Role?
                </h3>

                {{-- Description --}}
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                    This action cannot be undone. The role <strong class="font-semibold text-error-600 dark:text-error-400">{{ $roleToDeleteName }}</strong> will be soft-deleted.
                </p>

                @if ($roleToDeleteUserCount > 0)
                    <div class="mb-4 rounded-lg bg-warning-50 px-3 py-2 text-center dark:bg-warning-500/10">
                        <p class="text-xs text-warning-700 dark:text-warning-400">
                            ⚠ {{ $roleToDeleteUserCount }} user(s) are currently assigned to this role.
                        </p>
                    </div>
                @endif

                <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Type <code class="rounded bg-gray-100 px-1 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $roleToDeleteName }}</code> to confirm.
                </p>

                {{-- Input --}}
                <input
                    type="text"
                    wire:model.live="deleteConfirmTypedName"
                    placeholder="Type role name"
                    class="mb-4 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:placeholder-gray-500"
                />

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50 cursor-pointer transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="deleteRole"
                        @disabled($deleteConfirmTypedName !== $roleToDeleteName)
                        class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition {{ $deleteConfirmTypedName === $roleToDeleteName ? 'bg-error-600 hover:bg-error-700 shadow-theme-xs cursor-pointer' : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed opacity-60' }}"
                    >
                        Delete Role
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
