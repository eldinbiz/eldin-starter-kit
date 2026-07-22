@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div x-data="{ pageName: 'User Management' }">
    @include('partials.breadcrumb')
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit User Details</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Modify information or adjust roles for this user.</p>
        </div>
        <a
            href="{{ route('admin.users.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
        >
            Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-800 px-4 py-3 text-sm text-error-700 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
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
                    value="{{ old('name', $user->name) }}"
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
                    value="{{ old('email', $user->email) }}"
                    placeholder="name@example.com"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                />
            </div>

            <!-- Password (Optional) -->
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Password <span class="text-xs text-gray-400 font-normal">(Leave blank to keep current password)</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="New password (optional)"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                />
            </div>

            <!-- Assign Roles -->
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
                        @php
                            $hasRole = in_array($role->id, $userRoleIds);
                        @endphp
                        <label class="flex items-center cursor-pointer select-none text-sm text-gray-700 dark:text-gray-300">
                            <div class="relative mr-2.5" x-data="{ checked: {{ $hasRole ? 'true' : 'false' }} }">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->id }}"
                                    id="role_{{ $role->id }}"
                                    class="sr-only"
                                    {{ $hasRole ? 'checked' : '' }}
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
                    Update User
                </button>
            </div>
        </div>
    </form>
</div>

    <!-- Company Assignments Section -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-8">
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
                                    <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                @if($company->user_roles->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($company->user_roles as $role)
                                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                                {{ ucfirst($role->name) }}
                                            </span>
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
                                            class="text-xs text-error-500 hover:text-error-700 font-medium">
                                        Remove
                                    </button>

                                    <div x-show="confirming" x-cloak x-trap.noscroll="confirming"
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                                        <div @click.outside="confirming = false; typedName = ''"
                                             class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">
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
                                                <input type="text" x-model="typedName" placeholder="Type {{ $company->name }}"
                                                       class="mb-4 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button"
                                                            @click="confirming = false; typedName = ''"
                                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                                        Cancel
                                                    </button>
                                                    <button type="submit"
                                                            :disabled="typedName !== '{{ addslashes($company->name) }}'"
                                                            :class="typedName === '{{ addslashes($company->name) }}'
                                                                ? 'bg-error-500 hover:bg-error-600 cursor-pointer'
                                                                : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                                                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors">
                                                        Remove
                                                    </button>
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

        <!-- Assign to Company Form -->
        @if($availableCompanies->isNotEmpty())
            <div class="border-t border-gray-100 dark:border-gray-800 pt-5">
                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assign to a Company</h5>
                <form action="{{ route('admin.users.assign-company', $user->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <select name="company_id" required
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-hidden focus:border-brand-500 dark:border-gray-800 dark:bg-gray-950 dark:text-white">
                        <option value="">Select Company</option>
                        @foreach($availableCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex flex-wrap gap-2">
                        @foreach($roles as $role)
                        <label class="flex items-center gap-1.5 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                            <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($role->name) }}</span>
                        </label>
                        @endforeach
                    </div>
                    <input type="text" name="job_title" placeholder="Job Title"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-hidden focus:border-brand-500 dark:border-gray-800 dark:bg-gray-950 dark:text-white" />
                    <select name="manager_id"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-hidden focus:border-brand-500 dark:border-gray-800 dark:bg-gray-950 dark:text-white">
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
                    </select>
                    <div class="sm:col-span-4 pt-1">
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                            Assign to Company
                        </button>
                    </div>
                </form>
            </div>
        @else
            <p class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-100 dark:border-gray-800 pt-5 mt-5">This user is already assigned to all active companies.</p>
        @endif
    </div>
</div>
@endsection
