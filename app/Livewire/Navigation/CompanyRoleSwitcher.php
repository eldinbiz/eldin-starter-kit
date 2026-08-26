<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Actions\Companies\SwitchActiveCompanyAction;
use App\Actions\Roles\SwitchActiveRoleAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CompanyRoleSwitcher extends Component
{
    public bool $companyDropdownOpen = false;

    public bool $roleDropdownOpen = false;

    #[Computed]
    public function user(): ?User
    {
        /** @var User|null $user */
        return Auth::user();
    }

    #[Computed]
    public function userCompanies(): Collection
    {
        return $this->user ? $this->user->companies()->get() : new Collection;
    }

    #[Computed]
    public function activeCompany(): ?Company
    {
        return $this->user?->getActiveCompany();
    }

    #[Computed]
    public function activeRole(): ?Role
    {
        return $this->user?->getActiveRole($this->activeCompany);
    }

    #[Computed]
    public function availableRoles(): Collection
    {
        if (! $this->user || ! $this->activeCompany) {
            return new Collection;
        }

        return $this->user->getCompanyRoles($this->activeCompany);
    }

    public function toggleCompanyDropdown(): void
    {
        $this->companyDropdownOpen = ! $this->companyDropdownOpen;
        $this->roleDropdownOpen = false;
    }

    public function toggleRoleDropdown(): void
    {
        $this->roleDropdownOpen = ! $this->roleDropdownOpen;
        $this->companyDropdownOpen = false;
    }

    public function closeDropdowns(): void
    {
        $this->companyDropdownOpen = false;
        $this->roleDropdownOpen = false;
    }

    public function switchCompany(int $companyId, SwitchActiveCompanyAction $action): void
    {
        if (! $this->user) {
            return;
        }

        $action->execute($this->user, $companyId);
        $this->closeDropdowns();

        // Redirect via Livewire SPA navigation to refresh company theme and context
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function switchRole(string $roleName, SwitchActiveRoleAction $action): void
    {
        if (! $this->user) {
            return;
        }

        $action->execute($this->user, $roleName);
        $this->closeDropdowns();

        // Redirect via Livewire SPA navigation to re-evaluate role permissions
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.navigation.company-role-switcher');
    }
}
