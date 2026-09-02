@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<x-ui::card class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Create New User</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Add a new user to the system.</p>
        </div>
        <a href="{{ route('admin.users.index') }}">
            <x-ui::button variant="outline" size="sm">
                Back to List
            </x-ui::button>
        </a>
    </div>

    @if ($errors->any())
        <x-ui::alert type="error" class="mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui::alert>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="space-y-6">
            {{-- Full Name --}}
            <x-ui::input
                type="text"
                id="name"
                name="name"
                label="Full Name"
                value="{{ old('name') }}"
                placeholder="Enter full name"
                required
            />

            {{-- Email Address --}}
            <x-ui::input
                type="email"
                id="email"
                name="email"
                label="Email Address"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                required
            />

            {{-- Password --}}
            <x-ui::input
                type="password"
                id="password"
                name="password"
                label="Password"
                placeholder="Min. 8 characters"
                required
            />

            {{-- Authentication Method --}}
            <x-ui::select
                id="auth_method"
                name="auth_method"
                label="Authentication Method"
                required
            >
                @foreach (\App\Models\User::AUTH_METHODS as $key => $label)
                    <option value="{{ $key }}" {{ old('auth_method', 'local') === $key ? 'selected' : '' }}>
                        {{ $label }} ({{ $key }})
                    </option>
                @endforeach
            </x-ui::select>

            {{-- Assign Roles --}}
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Assign Roles
                    @if($activeCompany)
                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                            {{ $activeCompany->name }}
                        </span>
                    @endif
                    <span class="text-error-500">*</span>
                </label>
                <div class="flex flex-col gap-3">
                    {{-- Default User Role (Checked & Disabled) --}}
                    <div class="flex items-center">
                        <x-ui::checkbox
                            label="User (Default Role)"
                            checked
                            disabled
                        />
                        <input type="hidden" name="roles[]" value="{{ $roles->firstWhere('name', 'user')->id ?? '' }}" />
                    </div>

                    {{-- Additional Roles --}}
                    @foreach($roles->where('name', '!=', 'user') as $role)
                        <x-ui::checkbox
                            name="roles[]"
                            value="{{ $role->id }}"
                            id="role_{{ $role->id }}"
                            label="{{ ucfirst($role->name) }}"
                        />
                    @endforeach
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="pt-4">
                <x-ui::button
                    type="submit"
                    variant="primary"
                    class="w-full py-3"
                >
                    Create User
                </x-ui::button>
            </div>
        </div>
    </form>
</x-ui::card>
@endsection
