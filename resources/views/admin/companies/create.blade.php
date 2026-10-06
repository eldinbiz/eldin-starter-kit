@extends('layouts.app')

@section('title', 'Create Company')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Create Company</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a new company to the hierarchy.</p>
    </div>

    @if($errors->any())
    <div class="rounded-lg bg-error-50 dark:bg-error-900/20 border border-error-200 dark:border-error-800 px-4 py-3 text-sm text-error-700 dark:text-error-300">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500" />
        </div>

        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Slug *</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500"
                placeholder="e.g. acme-corp" />
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company Type *</label>
            <select name="type" id="type" required
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500">
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ old('type', 'operating') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parent Company</label>
            <select name="parent_id" id="parent_id"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500">
                <option value="">None (Top-Level)</option>
                @foreach($parentCompanies as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', request('parent_id')) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name }} — {{ $parent->type_label }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Optional. Leave blank to create a top-level (root) company.</p>
        </div>

        <div x-data="{ logoPreview: null }">
            <label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo</label>
            <input type="file" name="logo" id="logo" accept=".ico,.png,.jpg,.jpeg"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-900/20 dark:file:text-brand-400"
                @change="logoPreview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null" />
            <p class="text-xs text-gray-400 mt-1">Accepted: .ico, .png, .jpg, .jpeg (max 2MB).</p>
            <div x-show="logoPreview" x-cloak class="mt-2">
                <img :src="logoPreview" alt="Logo preview" class="h-16 w-16 rounded-lg object-contain bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" data-testid="create-company-submit-button" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                Create Company
            </button>
            <a href="{{ route('admin.companies.index') }}" data-testid="create-company-cancel-button" class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
