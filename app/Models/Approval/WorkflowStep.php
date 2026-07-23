<?php

declare(strict_types=1);

namespace App\Models\Approval;

use App\Enums\ResolverType;
use Database\Factories\Approval\WorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    /** @use HasFactory<WorkflowStepFactory> */
    use HasFactory;

    protected $table = 'approval_workflow_steps';

    protected $fillable = [
        'group_id',
        'label',
        'order',
        'resolver_type',
        'resolver_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolver_type' => ResolverType::class,
            'resolver_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<WorkflowGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(WorkflowGroup::class, 'group_id');
    }
}
