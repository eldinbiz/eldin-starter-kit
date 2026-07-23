<?php

declare(strict_types=1);

namespace App\Models\Approval;

use App\Enums\GroupType;
use Database\Factories\Approval\WorkflowGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowGroup extends Model
{
    /** @use HasFactory<WorkflowGroupFactory> */
    use HasFactory;

    protected $table = 'approval_workflow_groups';

    protected $fillable = [
        'template_id',
        'name',
        'order',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GroupType::class,
        ];
    }

    /**
     * @return BelongsTo<WorkflowTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<WorkflowStep>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'group_id')->orderBy('order');
    }

    public function isSequential(): bool
    {
        return $this->type === GroupType::Sequential;
    }

    public function isParallel(): bool
    {
        return $this->type === GroupType::Parallel;
    }
}
