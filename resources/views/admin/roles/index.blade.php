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
        <div>
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
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Created</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($roles as $role)
                    <tr>
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
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $role->created_at->format('M d, Y') }}</p>
                        </td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('admin.roles.edit', $role->id) }}"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                                >
                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </a>

                                <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No roles found.</p>
                            <a href="{{ route('admin.roles.create') }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
                                Create your first role
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
