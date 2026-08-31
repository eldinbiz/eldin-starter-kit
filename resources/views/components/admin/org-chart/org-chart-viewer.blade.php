<?php

use App\Actions\OrgChart\GetOrgChartTreeAction;
use App\Actions\OrgChart\ReassignManagerAction;
use App\Actions\OrgChart\TraceReportingLineAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    /**
     * Reassign a user's manager, title, or status.
     */
    public function updateManager(
        int $userId,
        ?int $newManagerId,
        ?string $jobTitle,
        ?string $status,
        ReassignManagerAction $action
    ): void {
        $this->authorize('manage-org-chart');

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $activeCompany = $authUser?->getActiveCompany();

        if (! $activeCompany) {
            $this->flashError = 'No active company selected.';

            return;
        }

        try {
            $action->execute($activeCompany, [
                'user_id' => $userId,
                'new_manager_id' => $newManagerId,
                'job_title' => $jobTitle,
                'status' => $status,
            ]);

            $this->flashSuccess = 'Reporting line successfully updated.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }

        $this->dispatch('org-chart-updated');
    }

    /**
     * Trace reporting chain from user up to company root.
     *
     * @return array<int, array<string, mixed>>
     */
    public function traceReportingChain(int $userId, TraceReportingLineAction $action): array
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        $activeCompany = $authUser?->getActiveCompany();

        if (! $activeCompany) {
            return [];
        }

        return $action->execute($activeCompany, $userId);
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function activeCompany(): ?Company
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        return $authUser?->getActiveCompany();
    }

    #[Computed]
    public function canManage(): bool
    {
        return Auth::user()?->can('manage-org-chart') ?? false;
    }

    #[Computed]
    public function chartData(): array
    {
        if (! $this->activeCompany) {
            return [];
        }

        $action = app(GetOrgChartTreeAction::class);

        return $action->execute($this->activeCompany);
    }
};
?>

<div>
    {{-- Flash Messages --}}
    @if ($flashSuccess)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    @if ($flashError)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashError }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-error-600 hover:text-error-800 dark:text-error-400 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Organization Chart</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $this->activeCompany?->name ?? 'Company' }} — Reporting Structure
            </p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <div class="relative">
                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400">
                    <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" />
                    </svg>
                </span>
                <input
                    type="text"
                    id="orgChartSearchInput"
                    placeholder="Search users..."
                    class="w-64 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500"
                />
            </div>
            {{-- Reset View --}}
            <button
                type="button"
                id="resetOrgChartViewBtn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 cursor-pointer"
            >
                <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/>
                </svg>
                Reset View
            </button>
        </div>
    </div>

    {{-- Canvas --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6" wire:ignore>
        <div id="org-chart-container" class="org-chart-container overflow-auto"></div>
    </div>
</div>
