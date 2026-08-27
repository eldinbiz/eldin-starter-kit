<?php

declare(strict_types=1);

namespace App\Livewire\Admin\OrgChart;

use App\Actions\OrgChart\GetOrgChartTreeAction;
use App\Actions\OrgChart\ReassignManagerAction;
use App\Actions\OrgChart\TraceReportingLineAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class OrgChartViewer extends Component
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

    public function render(): View
    {
        return view('livewire.admin.org-chart.org-chart-viewer');
    }
}
