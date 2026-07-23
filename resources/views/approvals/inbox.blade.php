@extends('layouts.app')

@section('title', 'Approval Inbox')

@section('content')
<div x-data="{ pageName: 'Approvals' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Approval Inbox</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Pending steps requiring your approval.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
            {{ session('success') }}
        </div>
    @endif

    @if ($steps->isEmpty())
        <div class="py-10 text-center">
            <p class="text-gray-500 dark:text-gray-400">No pending approvals.</p>
        </div>
    @else
        <div class="w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-gray-100 border-y dark:border-gray-800">
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Request</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Requestor</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Step</p>
                        </th>
                        <th class="py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Submitted</p>
                        </th>
                        <th class="py-3 text-right">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($steps as $step)
                        <tr>
                            <td class="py-3.5">
                                <a href="{{ route('approvals.show', $step->request->id) }}" class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                    @if ($step->request->approvable instanceof \App\Contracts\Approvable)
                                        {{ $step->request->approvable->getApprovalTitle() }}
                                    @else
                                        Approval Request #{{ $step->request->id }}
                                    @endif
                                </a>
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->request->requestor->name }}</p>
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->display_label }} ({{ $step->group_name }})</p>
                            </td>
                            <td class="py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->request->submitted_at?->format('M d, Y') }}</p>
                            </td>
                            <td class="py-3.5 text-right">
                                <a href="{{ route('approvals.show', $step->request->id) }}"
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $steps->links() }}
        </div>
    @endif
</div>
@endsection
