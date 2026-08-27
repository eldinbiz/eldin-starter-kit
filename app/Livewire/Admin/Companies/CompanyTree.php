<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Companies;

use App\Actions\Companies\ReparentCompanyAction;
use App\Actions\Companies\ToggleCompanyActiveAction;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CompanyTree extends Component
{
    public string $searchQuery = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    /**
     * Toggle company active / disabled state.
     */
    public function toggleCompanyActive(int $companyId, ToggleCompanyActiveAction $action): void
    {
        $this->authorize('manage-companies');

        $company = Company::find($companyId);

        if (! $company) {
            return;
        }

        try {
            $updated = $action->execute($company);
            $status = $updated->is_active ? 'enabled' : 'disabled';
            $this->flashSuccess = "Company '{$company->name}' was successfully {$status}.";
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }

        $this->dispatch('company-tree-updated');
    }

    /**
     * Reparent a company.
     */
    public function reparentCompany(int $companyId, ?int $newParentId, ReparentCompanyAction $action): void
    {
        $this->authorize('manage-companies');

        try {
            $action->execute($companyId, $newParentId);
            $this->flashSuccess = 'Company parent structure successfully updated.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }

        $this->dispatch('company-tree-updated');
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function canManage(): bool
    {
        return Auth::user()?->can('manage-companies') ?? false;
    }

    #[Computed]
    public function treeData(): array
    {
        $roots = Company::buildTree();

        return $this->flattenTree($roots);
    }

    /**
     * @param  Collection<int, Company>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function flattenTree(Collection $nodes, ?int $parentId = null): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $flat[] = [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
                'type' => $node->type,
                'type_label' => $node->type_label,
                'parent_id' => $parentId,
                'is_active' => (bool) $node->is_active,
                'users_count' => $node->users_count ?? 0,
                'logo_url' => $node->logo ? asset('storage/'.$node->logo) : null,
            ];

            if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                $flat = array_merge($flat, $this->flattenTree($node->children, $node->id));
            }
        }

        return $flat;
    }

    public function render(): View
    {
        return view('livewire.admin.companies.company-tree');
    }
}
