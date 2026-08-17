<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasCompanyContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'auth_method', 'external_id', 'is_system'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const array AUTH_METHODS = [
        'local' => 'Local Password',
        'ldap' => 'LDAP Directory',
        'google' => 'Google SSO',
        'microsoft' => 'Microsoft SSO',
        'auth0' => 'Auth0 SSO',
        'saml2' => 'SAML 2.0',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'auth_method' => 'local',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasCompanyContext, HasFactory, Impersonate, Notifiable;

    /**
     * Check if the user can impersonate other users.
     * Strictly requires explicit user-impersonation permission on active role.
     */
    public function canImpersonate(): bool
    {
        return $this->getActiveRole()?->hasPermission('user-impersonation') ?? false;
    }

    /**
     * Check if the user can be impersonated by another user.
     */
    public function canBeImpersonated(): bool
    {
        return true;
    }

    public function scopeHuman(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Cached active role instance for the current request.
     */
    private ?Role $cachedActiveRole = null;

    /**
     * Company ID used for the cached active role, to detect context switches.
     */
    private ?int $cachedActiveRoleCompanyId = null;

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
        return $this->companyRoles()
            ->whereIn('name', ['sysadmin', 'platform-admin'])
            ->exists();
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
     *
     * Memoized per request to prevent duplicate database queries.
     */
    public function getActiveRole(?Company $company = null): ?Role
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return null;
        }

        $activeRoleName = session("active_role.{$company->id}");

        // Return cached instance if same company context and matching active role name
        if ($this->cachedActiveRole !== null
            && $this->cachedActiveRoleCompanyId === $company->id
            && ($activeRoleName === null || $this->cachedActiveRole->name === $activeRoleName)) {
            return $this->cachedActiveRole;
        }

        if ($activeRoleName) {
            $this->cachedActiveRole = $this->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->where('name', $activeRoleName)
                ->first();
        }

        if (! $this->cachedActiveRole) {
            $this->cachedActiveRole = $this->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->first();

            if ($this->cachedActiveRole) {
                session(["active_role.{$company->id}" => $this->cachedActiveRole->name]);
            }
        }

        $this->cachedActiveRoleCompanyId = $company->id;

        return $this->cachedActiveRole;
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
            'is_system' => 'boolean',
        ];
    }
}
