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
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage procurement purchase requests and approvals</p>
        </div>
        <a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition-colors">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Create Purchase Request
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if ($purchaseRequests->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">No purchase requests created yet for this company.</p>
            <a href="{{ route('purchase-requests.create') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:underline">
                Create your first Purchase Request &rarr;
            </a>
        </div>
    @else
        <div class="w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                        <th class="px-4 py-3">PR Number</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    @foreach ($purchaseRequests as $pr)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                            <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}" class="hover:text-brand-500">
                                    {{ $pr->pr_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3.5 text-gray-800 dark:text-gray-200">
                                {{ $pr->title }}
                            </td>
                            <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400">
                                {{ $pr->department }}
                            </td>
                            <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400">
                                {{ $pr->requester?->name }}
                            </td>
                            <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                ${{ number_format((float) $pr->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($pr->status === 'draft')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Draft</span>
                                @elseif ($pr->status === 'pending_approval')
                                    <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Pending Approval</span>
                                @elseif ($pr->status === 'approved')
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Approved</span>
                                @elseif ($pr->status === 'rejected')
                                    <span class="inline-flex rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-400">Rejected</span>
                                @elseif ($pr->status === 'canceled')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Canceled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400">
                                @formatDate($pr->created_at)
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}" class="font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $purchaseRequests->links() }}
        </div>
    @endif
</div>
@endsection
