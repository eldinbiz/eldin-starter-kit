@extends('layouts.app')
@section('title', 'Purchase Requests')
@section('content')
<div x-data="{ pageName: 'Purchase Requests' }">@include('partials.breadcrumb')</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Purchase Requests</h3>
        </div>
        <a href="{{ route('purchase-requests.create') }}"
            class="inline-flex items-center justify-center gap-1 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
            + Create Request
        </a>
    </div>

    @if ($purchaseRequests->isEmpty())
        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No purchase requests yet.</p>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($purchaseRequests as $pr)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}" class="font-medium text-brand-500 hover:text-brand-600">
                                    {{ $pr->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ number_format((float) $pr->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($pr->status === 'draft')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Draft</span>
                                @elseif ($pr->status === 'pending_approval')
                                    <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Pending Approval</span>
                                @elseif ($pr->status === 'approved')
                                    <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">Approved</span>
                                @elseif ($pr->status === 'rejected')
                                    <span class="inline-flex rounded-full bg-error-100 px-2 py-0.5 text-xs font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
                                @elseif ($pr->status === 'canceled')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Canceled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $pr->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('purchase-requests.show', $pr->id) }}"
                                    class="text-brand-500 hover:text-brand-600 font-medium">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-3">
            @foreach ($purchaseRequests as $pr)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <a href="{{ route('purchase-requests.show', $pr->id) }}" class="font-medium text-brand-500 text-sm">
                            {{ $pr->title }}
                        </a>
                        @if ($pr->status === 'draft')
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Draft</span>
                        @elseif ($pr->status === 'pending_approval')
                            <span class="inline-flex rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Pending</span>
                        @elseif ($pr->status === 'approved')
                            <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">Approved</span>
                        @elseif ($pr->status === 'rejected')
                            <span class="inline-flex rounded-full bg-error-100 px-2 py-0.5 text-xs font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
                        @elseif ($pr->status === 'canceled')
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Canceled</span>
                        @endif
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ number_format((float) $pr->amount, 2) }}</span>
                        <span>{{ $pr->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
