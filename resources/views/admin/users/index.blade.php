@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                All Users
                @if(!empty($activeCompany))
                    <span class="font-normal text-gray-500"> &mdash; {{ $activeCompany->name }}</span>
                @endif
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage administrators, managers, and regular users.</p>
        </div>
        <div>
            <a
                href="{{ route('admin.users.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 3.33331C10.4602 3.33331 10.8333 3.70641 10.8333 4.16665V9.16665H15.8333C16.2936 9.16665 16.6667 9.53974 16.6667 9.99998C16.6667 10.4602 16.2936 10.8333 15.8333 10.8333H10.8333V15.8333C10.8333 16.2936 10.4602 16.6666 10 16.6666C9.53976 16.6666 9.16667 16.2936 9.16667 15.8333V10.8333H4.16667C3.70643 10.8333 3.33333 10.4602 3.33333 9.99998C3.33333 9.53974 3.70643 9.16665 4.16667 9.16665H9.16667V4.16665C9.16667 3.70641 9.53976 3.33331 10 3.33331Z" fill="currentColor"/>
                </svg>
                Create User
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
                        <a
                            href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}"
                            class="inline-flex items-center gap-1 font-medium text-gray-500 text-theme-xs dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            User
                            @if($sort === 'name')
                                @if($direction === 'asc')
                                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 8l-6 6h12z"/></svg>
                                @else
                                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
                                @endif
                            @endif
                        </a>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Company & Role</p>
                    </th>
                    <th class="py-3 text-left">
                        <a
                            href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc', 'page' => null]) }}"
                            class="inline-flex items-center gap-1 font-medium text-gray-500 text-theme-xs dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            Joined Date
                            @if($sort === 'created_at')
                                @if($direction === 'asc')
                                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 8l-6 6h12z"/></svg>
                                @else
                                    <svg class="fill-current" width="12" height="12" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
                                @endif
                            @endif
                        </a>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($users as $user)
                    <tr>
                        <td class="py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5">
                            @php
                                $rolesByCompany = $user->companyRoles->groupBy('id');
                            @endphp
                            @forelse($rolesByCompany as $roleId => $roleGroup)
                                @php $role = $roleGroup->first(); @endphp
                                <div class="{{ !$loop->first ? 'mt-2' : '' }}">
                                    @if($role->name === 'admin')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#f2f7ff] px-2 py-0.5 text-xs font-medium text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]">
                                            Admin
                                        </span>
                                    @elseif($role->name === 'manager')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500">
                                            Manager
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            User
                                        </span>
                                    @endif
                                    <div class="flex flex-wrap gap-1 mt-1 ml-1">
                                        @foreach($roleGroup as $rolePivot)
                                            @php $company = $user->companies->firstWhere('id', $rolePivot->pivot->company_id); @endphp
                                            @if($company)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                                    {{ $company->name }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <span class="text-gray-400 text-theme-sm dark:text-gray-500">&mdash;</span>
                            @endforelse
                        </td>
                        <td class="py-3.5">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $user->created_at->format('M d, Y') }}</p>
                        </td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('admin.users.edit', $user->id) }}"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                                >
                                    <!-- Edit Icon -->
                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </a>

                                @if($user->id !== Auth::id())
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                                        >
                                            <!-- Trash Icon -->
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No users found.</p>
                            <a href="{{ route('admin.users.create') }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
                                Create your first user
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Show</span>
            <select
                onchange="window.location.href = this.value"
                class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                @foreach([5, 10, 20, 50, 100] as $option)
                    <option
                        value="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => null]) }}"
                        {{ (int) request('per_page', 5) === $option ? 'selected' : '' }}
                    >{{ $option }}</option>
                @endforeach
            </select>
            <span>entries</span>
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
