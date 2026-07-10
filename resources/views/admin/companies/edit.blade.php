@extends('layouts.app')

@section('title', 'Edit Company — ' . $company->name)

@section('content')
<div class="space-y-6 max-w-3xl">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Company</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $company->name }}</p>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-lg bg-error-50 dark:bg-error-900/20 border border-error-200 dark:border-error-800 px-4 py-3 text-sm text-error-700 dark:text-error-300">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Company Details Form -->
    <form action="{{ route('admin.companies.update', $company) }}" method="POST" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Slug *</label>
                <input type="text" name="slug" id="slug" value="{{ old('slug', $company->slug) }}" required
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
            </div>
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parent Company</label>
            <select name="parent_id" id="parent_id"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500">
                <option value="">None (Top-Level)</option>
                @foreach($parentCompanies as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $company->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo URL</label>
            <input type="text" name="logo" id="logo" value="{{ old('logo', $company->logo) }}"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
        </div>

        <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $company->is_active) ? 'checked' : '' }}
                class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                Save Changes
            </button>
            <a href="{{ route('admin.companies.index') }}" class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                Cancel
            </a>
        </div>
    </form>

    <!-- Assigned Users Section -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-5">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Company Users ({{ $companyUsers->count() }})</h3>

        @if($companyUsers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">User</th>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Role</th>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Job Title</th>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companyUsers as $user)
                    <tr class="border-b border-gray-50 dark:border-gray-800">
                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-white">{{ $user->name }}</td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400 capitalize">
                            {{ $user->companyRoles->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $user->pivot->job_title ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <form action="{{ route('admin.companies.remove-user', [$company, $user->id]) }}" method="POST" onsubmit="return confirm('Remove this user from the company?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-error-500 hover:text-error-700 font-medium">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-400 dark:text-gray-500">No users assigned to this company yet.</p>
        @endif

        <!-- Assign User Form -->
        @if($availableUsers->count() > 0)
        <div class="border-t border-gray-100 dark:border-gray-800 pt-5 mt-5">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assign a User</h4>
            <form action="{{ route('admin.companies.assign-user', $company) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <select name="user_id" required
                    class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-hidden focus:border-brand-500 dark:border-gray-800 dark:bg-gray-950 dark:text-white">
                    <option value="">Select User</option>
                    @foreach($availableUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
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
                    @foreach($companyUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <div class="sm:col-span-4 pt-1">
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                        Assign User
                    </button>
                </div>
            </form>
        </div>
        @else
        <p class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-100 dark:border-gray-800 pt-5 mt-5">All users are already assigned to this company.</p>
        @endif
    </div>
</div>
@endsection
