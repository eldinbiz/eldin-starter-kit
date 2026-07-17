<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'permissions'];

    /**
     * The users that have this role within specific companies.
     */
    public function companyUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user_role')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Always-enabled permissions are granted regardless of stored state
        if (config("permissions.list.{$permission}.always_enabled", false)) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Check if this role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $rolePermissions = $this->permissions ?? [];

        foreach ($permissions as $permission) {
            if (in_array($permission, $rolePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the number of users assigned to this role.
     */
    public function getUsersCountAttribute(): int
    {
        return $this->companyUsers()->count();
    }
}
