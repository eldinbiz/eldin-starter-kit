@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <x-ui::card>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit User Details</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Modify information or adjust roles for this user.</p>
            </div>
            <a href="{{ route('admin.users.index') }}">
                <x-ui::button variant="outline" size="sm">
                    Back to List
                </x-ui::button>
            </a>
        </div>

        @if(session('success'))
            <x-ui::alert type="success" class="mb-4">
                {{ session('success') }}
            </x-ui::alert>
        @endif

        @if(session('error'))
            <x-ui::alert type="error" class="mb-4">
                {{ session('error') }}
            </x-ui::alert>
        @endif

        @if ($errors->any())
            <x-ui::alert type="error" class="mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui::alert>
        @endif

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                {{-- Full Name --}}
                <x-ui::input
                    type="text"
                    id="name"
                    name="name"
                    label="Full Name"
                    value="{{ old('name', $user->name) }}"
                    placeholder="Enter full name"
                    required
                />

                {{-- Email Address --}}
                <x-ui::input
                    type="email"
                    id="email"
                    name="email"
                    label="Email Address"
                    value="{{ old('email', $user->email) }}"
                    placeholder="name@example.com"
                    required
                />

                {{-- Password (Optional) --}}
                <x-ui::input
                    type="password"
                    id="password"
                    name="password"
                    label="Password"
                    helper="(Leave blank to keep current password)"
                    placeholder="New password (optional)"
                />

                {{-- Authentication Method --}}
                <x-ui::select
                    id="auth_method"
                    name="auth_method"
                    label="Authentication Method"
                    required
                >
                    @foreach (\App\Models\User::AUTH_METHODS as $key => $label)
                        <option value="{{ $key }}" {{ old('auth_method', $user->auth_method ?? 'local') === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $key }})
                        </option>
                    @endforeach
                </x-ui::select>

                {{-- Assign Roles --}}
                <div>
                    <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Assign Roles
                        @if($activeCompany)
                            <x-ui::badge variant="neutral" class="ml-1.5">
                                {{ $activeCompany->name }}
                            </x-ui::badge>
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
                            @php
                                $hasRole = in_array($role->id, $userRoleIds);
                            @endphp
                            <x-ui::checkbox
                                name="roles[]"
                                value="{{ $role->id }}"
                                id="role_{{ $role->id }}"
                                label="{{ ucfirst($role->name) }}"
                                :checked="$hasRole"
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
                        Update User
                    </x-ui::button>
                </div>
            </div>
        </form>
    </x-ui::card>

    {{-- Company Assignments Section --}}
    <x-ui::card>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">Company Assignments</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage which companies this user belongs to.</p>
            </div>
        </div>

        @if($assignedCompanies->isNotEmpty())
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Company</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Roles</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Job Title</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedCompanies as $company)
                        <tr class="border-b border-gray-50 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium text-gray-800 dark:text-white">
                                {{ $company->name }}
                                @if(!$company->is_active)
                                    <x-ui::badge variant="neutral" class="ml-1.5">Inactive</x-ui::badge>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                @if($company->user_roles->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($company->user_roles as $role)
                                            <x-ui::badge variant="neutral">
                                                {{ ucfirst($role->name) }}
                                            </x-ui::badge>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $company->pivot?->job_title ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <div x-data="{ confirming: false, typedName: '' }">
                                    <button type="button"
                                            @click="confirming = true; typedName = ''"
                                            class="text-xs text-error-500 hover:text-error-700 font-medium cursor-pointer">
                                        Remove
                                    </button>

                                    <div x-show="confirming" x-cloak x-trap.noscroll="confirming"
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs">
                                        <div @click.outside="confirming = false; typedName = ''"
                                             class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                            <p class="text-center text-sm font-medium text-gray-800 dark:text-white/90 mb-1">
                                                Remove from {{ $company->name }}?
                                            </p>
                                            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                                                This will remove <strong>{{ $user->name }}</strong> from <strong>{{ $company->name }}</strong>. Their roles and assignments within this company will be revoked.
                                            </p>
                                            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                                                Type <code>{{ $company->name }}</code> to confirm.
                                            </p>
                                            <form action="{{ route('admin.users.remove-company', [$user->id, $company->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui::input
                                                    type="text"
                                                    x-model="typedName"
                                                    placeholder="Type {{ $company->name }}"
                                                    class="mb-4"
                                                />
                                                <div class="flex justify-end gap-2 mt-4">
                                                    <x-ui::button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        @click="confirming = false; typedName = ''"
                                                    >
                                                        Cancel
                                                    </x-ui::button>
                                                    <x-ui::button
                                                        type="submit"
                                                        variant="destructive"
                                                        size="sm"
                                                        ::disabled="typedName !== '{{ addslashes($company->name) }}'"
                                                    >
                                                        Remove
                                                    </x-ui::button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">This user is not assigned to any companies yet.</p>
        @endif

        {{-- Assign to Company Form --}}
        @if($availableCompanies->isNotEmpty())
            <div class="border-t border-gray-100 dark:border-gray-800 pt-5">
                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assign to a Company</h5>
                <form action="{{ route('admin.users.assign-company', $user->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <x-ui::select name="company_id" required>
                        <option value="">Select Company</option>
                        @foreach($availableCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </x-ui::select>
                    <div class="flex flex-wrap gap-2">
                        @foreach($roles as $role)
                            <x-ui::checkbox
                                name="roles[]"
                                value="{{ $role->id }}"
                                label="{{ ucfirst($role->name) }}"
                            />
                        @endforeach
                    </div>
                    <x-ui::input
                        type="text"
                        name="job_title"
                        placeholder="Job Title"
                    />
                    <x-ui::select name="manager_id">
                        <option value="">No Manager</option>
                        @php
                            $allAssignedUsers = $user->companies()
                                ->with(['users' => fn ($q) => $q->select('users.id', 'users.name')])
                                ->get()
                                ->pluck('users')
                                ->flatten()
                                ->unique('id')
                                ->filter(fn ($u) => $u->id !== $user->id)
                                ->values();
                        @endphp
                        @foreach($allAssignedUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </x-ui::select>
                    <div class="sm:col-span-4 pt-1">
                        <x-ui::button
                            type="submit"
                            variant="primary"
                            size="sm"
                        >
                            Assign to Company
                        </x-ui::button>
                    </div>
                </form>
            </div>
        @else
            <p class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-100 dark:border-gray-800 pt-5 mt-5">This user is already assigned to all active companies.</p>
        @endif
    </x-ui::card>
</div>
@endsection
