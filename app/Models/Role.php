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
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions, true);
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
        return $this->users()->count();
    }
}
