@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div x-data="{ pageName: 'Edit Role' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Edit Role: {{ ucfirst($role->name) }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Update role name and permissions.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name <span class="text-error-500">*</span></label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $role->name) }}"
                placeholder="e.g. moderator"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder-gray-500 dark:focus:border-brand-400"
                required
                maxlength="255"
            />
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Use lowercase with underscores, e.g. "content_editor".</p>
        </div>

        <!-- Permissions Section -->
        <div class="mb-6">
            <h4 class="mb-4 text-base font-medium text-gray-800 dark:text-white/90">Permissions</h4>

            @foreach($permissionGroups as $group)
                @php
                    $groupPermissions = array_filter($permissions, fn($def) => ($def['group'] ?? '') === $group);
                @endphp

                @if(count($groupPermissions) > 0)
                    <div class="mb-5">
                        <h5 class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $group }}</h5>

                        <div class="flex flex-col gap-2">
                            @foreach($groupPermissions as $key => $definition)
                                <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $key }}"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-white/[0.05]"
                                        {{ in_array($key, old('permissions', $assignedPermissions)) ? 'checked' : '' }}
                                    />
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $definition['label'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $key }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
            <a
                href="{{ route('admin.roles.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7043 4.15338C17.0335 4.46358 17.0478 4.99114 16.736 5.32033L8.376 14.3203C8.22332 14.4843 8.00693 14.5759 7.78144 14.5759C7.55596 14.5759 7.33956 14.4843 7.18687 14.3203L3.26405 10.1003C2.95218 9.77116 2.96648 9.2436 3.29567 8.93173C3.62486 8.61986 4.15241 8.63416 4.46428 8.96335L7.78144 12.5003L15.5357 4.20237C15.8475 3.87318 16.3751 3.84318 16.7043 4.15338Z" fill="currentColor"/>
                </svg>
                Update Role
            </button>
        </div>
    </form>
</div>
@endsection
