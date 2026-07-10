@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<div class="max-w-2xl mx-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Create New User</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Add a new user to the system.</p>
        </div>
        <a
            href="{{ route('admin.users.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
        >
            Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="space-y-6">
            <!-- Full Name -->
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Full Name <span class="text-error-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Enter full name"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                />
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Email Address <span class="text-error-500">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="name@example.com"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Password <span class="text-error-500">*</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min. 8 characters"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                />
            </div>

            <!-- Assign Roles -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Assign Roles <span class="text-error-500">*</span>
                </label>
                <div class="flex flex-col gap-3">
                    <!-- Default User Role (Checked & Disabled) -->
                    <label class="flex items-center cursor-not-allowed select-none text-sm text-gray-500 dark:text-gray-400">
                        <div class="relative mr-2.5">
                            <input type="checkbox" checked disabled class="sr-only" />
                            <div class="box flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                                <span class="text-gray-400 text-xs">✔</span>
                            </div>
                            <input type="hidden" name="roles[]" value="{{ $roles->firstWhere('name', 'user')->id ?? '' }}" />
                        </div>
                        <span>User (Default Role)</span>
                    </label>

                    <!-- Additional Roles -->
                    @foreach($roles->where('name', '!=', 'user') as $role)
                        <label class="flex items-center cursor-pointer select-none text-sm text-gray-700 dark:text-gray-300">
                            <div class="relative mr-2.5" x-data="{ checked: false }">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->id }}"
                                    id="role_{{ $role->id }}"
                                    class="sr-only"
                                    @change="checked = $el.checked"
                                />
                                <div
                                    :class="checked ? 'border-brand-500 bg-brand-500' : 'border-gray-300 bg-transparent dark:border-gray-700'"
                                    class="box flex h-5 w-5 items-center justify-center rounded border transition"
                                >
                                    <span x-show="checked" class="text-white text-xs">✔</span>
                                </div>
                            </div>
                            <span>{{ ucfirst($role->name) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button
                    type="submit"
                    class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                >
                    Create User
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
