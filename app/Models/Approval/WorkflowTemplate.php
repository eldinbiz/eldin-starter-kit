<?php

declare(strict_types=1);

namespace App\Models\Approval;

use App\Enums\TemplateScope;
use App\Models\Company;
use Database\Factories\Approval\WorkflowTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends Model
{
    /** @use HasFactory<WorkflowTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'approval_workflow_templates';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'approvable_type',
        'is_active',
        'scope',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'scope' => TemplateScope::class,
        ];
    }

    /**
     * The company this workflow template belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The ordered groups in this template.
     *
     * @return HasMany<WorkflowGroup>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(WorkflowGroup::class, 'template_id')->orderBy('order');
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope templates available to a given company based on scope rules.
     *
     * - this_company: only templates owned by this exact company
     * - subsidiaries: templates owned by this company or any ancestor
     * - global: all templates regardless of company
     */
    public function scopeAvailableFor(Builder $query, Company $company): Builder
    {
        return $query->where(function (Builder $q) use ($company): void {
            // This Company
            $q->where(function (Builder $inner) use ($company): void {
                $inner->where('scope', TemplateScope::ThisCompany)
                    ->where('company_id', $company->id);
            });

            // This Company & Subsidiaries — owned by this company or any ancestor
            $q->orWhere(function (Builder $inner) use ($company): void {
                $ancestorIds = $company->allAncestors()->pluck('id')
                    ->push($company->id)
                    ->unique()
                    ->toArray();

                $inner->where('scope', TemplateScope::ThisCompanyAndSubsidiaries)
                    ->whereIn('company_id', $ancestorIds);
            });

            // Global
            $q->orWhere('scope', TemplateScope::Global);
        });
    }
}
