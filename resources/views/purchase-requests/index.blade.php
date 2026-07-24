@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Purchase Requests</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage purchase requests and submit for approval.</p>
        </div>
        <a href="{{ route('purchase-requests.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
            + Create PR
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if ($purchaseRequests->isEmpty())
        <div class="py-10 text-center">
            <p class="text-gray-500 dark:text-gray-400">No purchase requests yet.</p>
            <a href="{{ route('purchase-requests.create') }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
                Create your first purchase request
            </a>
        </div>
    @else
        <div class="w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-gray-100 border-y dark:border-gray-800">
                        <th class="py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Title</p></th>
                        <th class="py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amount</p></th>
                        <th class="py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="py-3 text-left"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Created</p></th>
                        <th class="py-3 text-right"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action</p></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($purchaseRequests as $pr)
                        <tr>
                            <td class="py-3.5">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}" class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90">
                                    {{ $pr->title }}
                                </a>
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">${{ number_format($pr->amount, 2) }}</p>
                            </td>
                            <td class="py-3.5">
                                @if ($pr->status === 'draft')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Draft</span>
                                @elseif ($pr->status === 'pending_approval')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">Pending Approval</span>
                                @elseif ($pr->status === 'approved')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">Approved</span>
                                @elseif ($pr->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
                                @endif
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $pr->created_at->format('M d, Y') }}</p>
                            </td>
                            <td class="py-3.5 text-right">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
