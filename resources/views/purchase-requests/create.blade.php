@extends('layouts.app')

@section('title', 'Create Purchase Request')

@section('content')
<div x-data="{ pageName: 'Create Purchase Request' }">
    @include('partials.breadcrumb')
</div>

<div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">New Purchase Request</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Fill in the purchase details below</p>
        </div>
        <a href="{{ route('purchase-requests.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Back to list
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

    <form action="{{ route('purchase-requests.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Title <span class="text-error-500">*</span>
            </label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                placeholder="e.g., Office Laptops Procurement Q3"
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="department" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Department <span class="text-error-500">*</span>
                </label>
                <select name="department" id="department" required
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Select Department</option>
                    <option value="IT" {{ old('department') === 'IT' ? 'selected' : '' }}>IT & Technology</option>
                    <option value="Finance" {{ old('department') === 'Finance' ? 'selected' : '' }}>Finance & Accounting</option>
                    <option value="Procurement" {{ old('department') === 'Procurement' ? 'selected' : '' }}>Procurement</option>
                    <option value="HR" {{ old('department') === 'HR' ? 'selected' : '' }}>Human Resources</option>
                    <option value="Operations" {{ old('department') === 'Operations' ? 'selected' : '' }}>Operations</option>
                </select>
            </div>

            <div>
                <label for="total_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Total Amount ($) <span class="text-error-500">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" name="total_amount" id="total_amount" value="{{ old('total_amount') }}" required
                    placeholder="0.00"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
            </div>
        </div>

        <div>
            <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Description / Purpose
            </label>
            <textarea name="description" id="description" rows="4"
                placeholder="Provide rationale or specs for this purchase request..."
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('purchase-requests.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                Save as Draft
            </button>
        </div>
    </form>
</div>
@endsection
