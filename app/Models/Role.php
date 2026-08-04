<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'permissions', 'is_active'];

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
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
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

        $rolePermissions = $this->permissions ?? [];

        return in_array('*', $rolePermissions, true) || in_array($permission, $rolePermissions, true);
    }

    /**
     * Check if this role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $rolePermissions = $this->permissions ?? [];

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

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

    /**
     * Scope to only include active roles.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to only include disabled roles.
     */
    public function scopeDisabled($query): void
    {
        $query->where('is_active', false);
    }
}
