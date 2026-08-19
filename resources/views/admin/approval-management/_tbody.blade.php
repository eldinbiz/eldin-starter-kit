@php
use App\Enums\StepState;
use App\Enums\ApprovalStatus;
@endphp

@foreach($rows as $step)
    @php
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
@endforeach

{{-- Reusable Delegate & Skip Modals --}}
<tr
    class="hidden"
    x-data="{
        delegateOpen: false,
        skipOpen: false,
        activeStepId: null,
        activeTitle: '',
        activeLabel: '',
        currentApprover: '',
        currentApproverId: null,
        selectedUser: '',
        delegateUrl: '',
        skipUrl: ''
    }"
    @open-delegate.window="
        activeStepId = $event.detail.id;
        activeLabel = $event.detail.label;
        activeTitle = $event.detail.title;
        currentApprover = $event.detail.approver;
        currentApproverId = $event.detail.approverId;
        selectedUser = '';
        delegateUrl = '{{ url('admin/approval-management') }}/' + $event.detail.id + '/delegate';
        delegateOpen = true;
    "
    @open-skip.window="
        activeStepId = $event.detail.id;
        activeLabel = $event.detail.label;
        skipUrl = '{{ url('admin/approval-management') }}/' + $event.detail.id + '/skip';
        skipOpen = true;
    "
>
    <td colspan="7">
        {{-- Delegate Modal --}}
        <div x-show="delegateOpen" x-cloak x-trap.noscroll="delegateOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.outside="delegateOpen = false" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900 text-left">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">Reassign Approver</h4>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Reassign <span class="font-medium text-gray-700 dark:text-gray-300" x-text="'&quot;' + activeLabel + '&quot; — ' + activeTitle"></span>
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Current: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="currentApprover"></span>
                </p>

                <form :action="delegateUrl" method="POST" class="mt-4">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Approver</label>
                    <select x-model="selectedUser" name="approver_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <option value="">Select user...</option>
                        @foreach ($companyUsers as $u)
                            <option value="{{ $u->id }}" x-show="currentApproverId !== {{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="delegateOpen = false" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit" :disabled="selectedUser === ''" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                            Delegate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Skip Modal --}}
        <div x-show="skipOpen" x-cloak x-trap.noscroll="skipOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.outside="skipOpen = false" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900 text-left">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">Skip Step</h4>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Skip <span class="font-medium text-gray-700 dark:text-gray-300" x-text="'&quot;' + activeLabel + '&quot;?'"></span>
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    This will bypass the step and advance the workflow.
                </p>

                <form :action="skipUrl" method="POST" class="mt-4">
                    @csrf
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="skipOpen = false" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600">
                            Skip
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </td>
</tr>
