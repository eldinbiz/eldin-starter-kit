@extends('layouts.app')

@section('title', 'User Roles')

@section('content')
<div x-data="{ pageName: 'User Roles' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">All Roles</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage roles and their permissions.</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($showTrashed)
                <a
                    href="{{ route('admin.roles.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]"
                >
                    Show Active Roles
                </a>
            @else
                <a
                    href="{{ route('admin.roles.index', ['trashed' => 1]) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]"
                >
                    Show Deleted Roles
                </a>
            @endif

            <a
                href="{{ route('admin.roles.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 3.33331C10.4602 3.33331 10.8333 3.70641 10.8333 4.16665V9.16665H15.8333C16.2936 9.16665 16.6667 9.53974 16.6667 9.99998C16.6667 10.4602 16.2936 10.8333 15.8333 10.8333H10.8333V15.8333C10.8333 16.2936 10.4602 16.6666 10 16.6666C9.53976 16.6666 9.16667 16.2936 9.16667 15.8333V10.8333H4.16667C3.70643 10.8333 3.33333 10.4602 3.33333 9.99998C3.33333 9.53974 3.70643 9.16665 4.16667 9.16665H9.16667V4.16665C9.16667 3.70641 9.53976 3.33331 10 3.33331Z" fill="currentColor"/>
                </svg>
                Create Role
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="w-full overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Role</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Permissions</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Users</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Created</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($roles as $role)
                    <tr class="{{ $role->trashed() ? 'opacity-60' : '' }}">
                        <td class="py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ strtoupper(substr($role->name, 0, 2)) }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ ucfirst($role->name) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $role->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5">
                            <div class="flex flex-wrap gap-1.5">
                                @php $rolePermissions = $role->permissions ?? []; @endphp
                                @forelse($rolePermissions as $permKey)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        {{ $permissionLabels[$permKey] ?? $permKey }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">No permissions</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-3.5">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $role->users_count }}</p>
                        </td>
                        <td class="py-3.5">
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
                        <td class="py-3.5">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $role->created_at->format('M d, Y') }}</p>
                        </td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if (! $role->trashed())
                                    <a
                                        href="{{ route('admin.roles.edit', $role->id) }}"
                                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                        </svg>
                                    </a>
                                @endif

                                @if ($role->trashed())
                                    {{-- Soft-deleted: Show restore button --}}
                                    <form action="{{ route('admin.roles.restore', $role->id) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer"
                                            title="Restore role"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @elseif (! $role->is_active)
                                    {{-- Disabled: Show enable and delete buttons --}}
                                    <form action="{{ route('admin.roles.restore', $role->id) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer"
                                            title="Enable role"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <div x-data="{ confirming: false, typedName: '' }">
                                        {{-- Trash icon button toggles the confirmation modal --}}
                                        <button
                                            type="button"
                                            @click="confirming = true; typedName = ''"
                                            class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                                            title="Delete role"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>

                                        {{-- Modal overlay --}}
                                        <div
                                            x-show="confirming"
                                            x-cloak
                                            x-trap.noscroll="confirming"
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                                        >
                                            {{-- Modal card --}}
                                            <div
                                                @click.outside="confirming = false; typedName = ''"
                                                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]"
                                            >
                                                <p class="text-center text-sm font-medium text-gray-800 dark:text-white/90 mb-1">
                                                    Delete role?
                                                </p>
                                                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                                                    This action cannot be undone. The role <strong class="font-semibold text-error-600 dark:text-error-400">{{ $role->name }}</strong> will be deleted.
                                                </p>

                                                @if ($role->users_count > 0)
                                                    <div class="mb-4 rounded-lg bg-warning-50 px-3 py-2 text-center dark:bg-warning-500/10">
                                                        <p class="text-xs text-warning-700 dark:text-warning-400">
                                                            ⚠ {{ $role->users_count }} user(s) are currently assigned to this role.
                                                        </p>
                                                    </div>
                                                @endif

                                                <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                                                    Type <code class="rounded bg-gray-100 px-1 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $role->name }}</code> to confirm.
                                                </p>

                                                <form action="{{ route('admin.roles.soft-delete', $role->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input
                                                        type="text"
                                                        x-model="typedName"
                                                        placeholder="Type role name"
                                                        class="mb-4 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500"
                                                    >
                                                    <div class="flex justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            @click="confirming = false; typedName = ''"
                                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]"
                                                        >
                                                            Cancel
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            :disabled="typedName !== '{{ $role->name }}'"
                                                            :class="typedName === '{{ $role->name }}' ? 'bg-error-500 hover:bg-error-600 cursor-pointer' : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                                                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Active: Show disable button --}}
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to disable this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-gray-500 hover:text-warning-500 dark:text-gray-400 dark:hover:text-warning-400 cursor-pointer"
                                            title="Disable role"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center">
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ $showTrashed ? 'No deleted roles found.' : 'No roles found.' }}
                            </p>
                            @if (! $showTrashed)
                                <a href="{{ route('admin.roles.create') }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
                                    Create your first role
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
