@props(['approvalRequest'])

@php
    $bgClass = match ($approvalRequest->status) {
        \App\Enums\ApprovalStatus::Approved => 'bg-success-50 border-success-200 dark:bg-success-500/5 dark:border-success-400/20',
        \App\Enums\ApprovalStatus::Rejected => 'bg-error-50 border-error-200 dark:bg-error-500/5 dark:border-error-400/20',
        \App\Enums\ApprovalStatus::Canceled => 'bg-gray-50 border-gray-200 dark:bg-white/[0.02] dark:border-gray-700',
        default => 'bg-white border-gray-200 dark:bg-white/[0.03] dark:border-gray-800',
    };
@endphp
<div class="rounded-2xl border p-5 shadow-theme-sm {{ $bgClass }}">
    <h4 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-4">Workflow Progress</h4>

    @if ($approvalRequest->workflow_snapshot)
        @foreach ($approvalRequest->workflow_snapshot['groups'] as $groupIndex => $group)
            @php
                $groupSteps = $approvalRequest->steps->where('group_name', $group['name']);
                $allApproved = $groupSteps->every(fn($s) => $s->state === \App\Enums\StepState::Approved);
                $anyRejected = $groupSteps->contains('state', \App\Enums\StepState::Rejected);
            @endphp

            <div class="mb-4">
                <div class="flex items-center gap-2 mb-2">
                    @if ($allApproved)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-100 text-success-600 dark:bg-success-500/10 dark:text-success-400">✓</span>
                    @elseif ($anyRejected)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-error-100 text-error-600 dark:bg-error-500/10 dark:text-error-400">✗</span>
                    @else
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">●</span>
                    @endif
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Group {{ $groupIndex + 1 }}: {{ $group['name'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ $group['type'] }}
                    </span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">#</th>
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Approver</th>
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Email</th>
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Job Title</th>
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">At</th>
                                <th class="py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($groupSteps->sortBy('order') as $step)
                                <tr>
                                    <td class="py-2 pr-3 text-gray-500 dark:text-gray-400">{{ $step->order + 1 }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $step->approver?->name ?? '—' }}</span>
                                        @if ($step->delegatedFrom)
                                            <span class="text-xs text-gray-400 italic">(delegated from {{ $step->delegatedFrom->name }})</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-gray-500 dark:text-gray-400 text-xs">{{ $step->approver?->email ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-gray-500 dark:text-gray-400">{{ $step->approver_job_title ?: '—' }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($step->state === \App\Enums\StepState::Approved)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">Approved</span>
                                        @elseif ($step->state === \App\Enums\StepState::Rejected)
                                            @if ($step->action_at)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-error-100 px-2 py-0.5 text-xs font-medium text-error-700 dark:bg-error-500/15 dark:text-error-400">Rejected</span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Skipped</span>
                                            @endif
                                        @elseif ($step->state === \App\Enums\StepState::AwaitingPrevious)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Awaiting</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-gray-500 dark:text-gray-400">
                                        {{ $step->action_at?->format('M d, h:i A') ?: '—' }}
                                    </td>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">
                                        {{ $step->notes ? '"' . \Illuminate\Support\Str::limit($step->notes, 60) . '"' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
