<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
        'logo',
        'is_active',
    ];

    /**
     * Get the parent company.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    /**
     * Get child companies (subsidiaries).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    /**
     * Get all users belonging to this company, with pivot data.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['manager_id', 'job_title', 'status'])
            ->withTimestamps();
    }

    /**
     * Get only active users in this company.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'active');
    }

    /**
     * Get the root-level users (those with no manager) in this company.
     */
    public function rootUsers(): BelongsToMany
    {
        return $this->users()->wherePivotNull('manager_id');
    }

    /**
     * Scope a query to only active companies.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the human-readable type label from config.
     */
    public function getTypeLabelAttribute(): string
    {
        return config("company.types.{$this->type}", ucfirst((string) $this->type));
    }

    /**
     * Recursively get all descendant companies (children, grandchildren, etc.).
     *
     * @return Collection<int, Company>
     */
    public function allChildren(): Collection
    {
        $children = $this->children()->get();
        $result = $children;

        foreach ($children as $child) {
            $result = $result->merge($child->allChildren());
        }

        return $result;
    }

    /**
     * Get all ancestor companies up the chain (parent, grandparent, ...).
     *
     * @return Collection<int, Company>
     */
    public function allAncestors(): Collection
    {
        $ancestors = new Collection;
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Check if this company is a descendant of the given company.
     * Used for cycle detection when reparenting.
     */
    public function isDescendantOf(Company $target): bool
    {
        return $this->allAncestors()->contains('id', $target->id);
    }

    /**
     * Check if this company is a root (top-level, no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if all descendants are disabled.
     * Returns true if there are no children, or if all children (recursively) are disabled.
     */
    public function allChildrenDisabled(): bool
    {
        $children = $this->allChildren();

        return $children->every(fn (Company $child): bool => ! $child->is_active);
    }

    /**
     * Build a full nested tree from all companies, returning only root nodes
     * with their children populated recursively.
     *
     * @return Collection<int, Company>
     */
    public static function buildTree(): Collection
    {
        $allCompanies = self::withCount('users')->get();

        return self::nest($allCompanies, null);
    }

    /**
     * Recursively nest companies under a given parent ID.
     *
     * @param  Collection<int, Company>  $companies
     * @return Collection<int, Company>
     */
    private static function nest(Collection $companies, ?int $parentId): Collection
    {
        return $companies
            ->filter(fn (Company $company): bool => $company->parent_id === $parentId)
            ->map(function (Company $company) use ($companies): Company {
                $company->setRelation('children', self::nest($companies, $company->id));

                return $company;
            });
    }

    /**
     * Get system settings for this company.
     *
     * @return HasMany<SystemSetting, $this>
     */
    public function systemSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'company_id');
    }

    /**
     * Get email templates for this company.
     *
     * @return HasMany<EmailTemplate, $this>
     */
    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'company_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
