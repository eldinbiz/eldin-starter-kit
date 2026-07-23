<?php

declare(strict_types=1);

namespace App\Models\Approval;

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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
