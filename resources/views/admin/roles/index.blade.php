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
                wire:navigate
                data-testid="create-role-button"
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
        <x-ui::alert type="success" class="mb-4">
            {{ session('success') }}
        </x-ui::alert>
    @endif

    @if (session('error'))
        <x-ui::alert type="error" class="mb-4">
            {{ session('error') }}
        </x-ui::alert>
    @endif

    @livewire('admin.roles.role-table')
</div>
@endsection
