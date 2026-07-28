@extends('layouts.app')
@section('title', 'Create Purchase Request')
@section('content')
<div x-data="{ pageName: 'Create Purchase Request' }">@include('partials.breadcrumb')</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-6">Create Purchase Request</h3>

    <form action="{{ route('purchase-requests.store') }}" method="POST">
        @csrf

        <div class="space-y-4 max-w-2xl">
            {{-- Title --}}
            <div>
                <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Title
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500"
                    placeholder="e.g. Office Supplies Q3" required maxlength="255">
                @error('title')
                    <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Amount --}}
            <div>
                <label for="amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Amount
                </label>
                <input type="number" id="amount" name="amount" value="{{ old('amount') }}" step="0.01" min="0"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500"
                    placeholder="0.00" required>
                @error('amount')
                    <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Goods to Purchase (Description) --}}
            <div>
                <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Goods to Purchase
                </label>
                <textarea id="description" name="description" rows="5"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500"
                    placeholder="List the goods and quantities to be purchased...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 mt-6 dark:border-gray-700">
            <a href="{{ route('purchase-requests.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                Create Request
            </button>
        </div>
    </form>
</div>
@endsection
