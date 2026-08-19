@php
use App\Enums\StepState;
use App\Enums\ApprovalStatus;

$isActionable = ($step->state === StepState::Pending || $step->state === StepState::AwaitingPrevious)
    && $step->request->isPending();
$canDelegate = $isActionable && $step->state === StepState::Pending;
$canSkip = $isActionable;
$approvalTitle = $step->request->approvable instanceof \App\Contracts\Approvable
    ? $step->request->approvable->getApprovalTitle()
    : 'Approval Request #'.$step->request->id;
@endphp

<tr>
    <td class="py-3.5">
        <a href="{{ route('approvals.show', $step->request->id) }}" class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400">
            {{ $approvalTitle }}
        </a>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->request->requestor->name }}</p>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->display_label }}</p>
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $step->approver?->name ?? '—' }}</p>
    </td>
    <td class="py-3.5">
        @if ($step->state === StepState::Pending)
            @if ($step->request->isPending())
                <span class="inline-flex items-center rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-500">Pending</span>
            @else
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Pending</span>
            @endif
        @elseif ($step->state === StepState::AwaitingPrevious)
            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/10 dark:text-blue-500">Awaiting</span>
        @elseif ($step->state === StepState::Approved)
            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">Approved</span>
        @elseif ($step->state === StepState::Rejected)
            <span class="inline-flex items-center rounded-full bg-error-50 px-2 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
        @elseif ($step->state === StepState::Skipped)
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Skipped</span>
        @endif
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">@formatDate($step->created_at)</p>
    </td>
    <td class="py-3.5 text-right">
        @if ($canDelegate || $canSkip)
            <div class="inline-flex items-center gap-2">
                @if ($canDelegate)
                    <button
                        type="button"
                        @click="$dispatch('open-delegate', { id: {{ $step->id }}, label: @js($step->display_label), title: @js($approvalTitle), approver: @js($step->approver?->name ?? 'None'), approverId: {{ $step->approver_id ?? 'null' }} })"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20"
                    >
                        Delegate
                    </button>
                @endif

                @if ($canSkip)
                    <button
                        type="button"
                        @click="$dispatch('open-skip', { id: {{ $step->id }}, label: @js($step->display_label) })"
                        class="inline-flex items-center justify-center rounded-lg bg-error-50 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20"
                    >
                        Skip
                    </button>
                @endif
            </div>
        @endif
    </td>
</tr>
