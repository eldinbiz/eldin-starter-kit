<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasCompanyContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasCompanyContext, HasFactory, Notifiable;

    /**
     * The roles that belong to the user within specific companies.
     * Returns a BelongsToMany scoped to the company_user_role pivot.
     *
     * @return BelongsToMany<Role>
     */
    public function companyRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_user_role')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    /**
     * Get all roles for this user within a specific company.
     *
     * @return Collection<int, Role>
     */
    public function getCompanyRoles(Company $company): Collection
    {
        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->get();
    }

    /**
     * The companies that the user belongs to, with pivot data.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['manager_id', 'job_title', 'status'])
            ->withTimestamps();
    }

    /**
     * Check if the user has platform-level access (platform-admin role
     * in any company). Platform admins bypass company scoping entirely.
     */
    public function hasPlatformAccess(): bool
    {
        return $this->companyRoles()->where('name', 'platform-admin')->exists();
    }

    /**
     * Check if user has a specific role, optionally scoped to a company.
     * If no company is provided, uses the active company from session.
     */
    public function hasRole(string $role, ?Company $company = null): bool
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return false;
        }

        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->where('name', $role)
            ->exists();
    }

    /**
     * Check if user has any of the given roles, optionally scoped to a company.
     * If no company is provided, uses the active company from session.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles, ?Company $company = null): bool
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return false;
        }

        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->whereIn('name', $roles)
            ->exists();
    }

    /**
     * Get the currently active role within a specific company,
     * falling back to the user's first assigned role in that company.
     * If no company is provided, uses the active company from session.
     */
    public function getActiveRole(?Company $company = null): ?Role
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return null;
        }

        $activeRoleName = session("active_role.{$company->id}");

        if ($activeRoleName) {
            return $this->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->where('name', $activeRoleName)
                ->first();
        }

        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
