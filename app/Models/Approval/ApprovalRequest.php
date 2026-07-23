<?php

declare(strict_types=1);

namespace App\Models\Approval;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Models\Company;
use App\Models\User;
use Database\Factories\Approval\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'approval_requests';

    protected $fillable = [
        'company_id',
        'requestor_id',
        'workflow_template_id',
        'approvable_type',
        'approvable_id',
        'workflow_snapshot',
        'status',
        'submitted_at',
        'completed_at',
        'canceled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workflow_snapshot' => 'array',
            'status' => ApprovalStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    /**
     * @return BelongsTo<WorkflowTemplate, $this>
     */
    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ApprovalRequestStep>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalRequestStep::class, 'approval_request_id')->orderBy('order');
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::Approved);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ApprovalStatus::Rejected);
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', ApprovalStatus::Canceled);
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    public function isCancelable(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    /**
     * Get the loaded approvable model, ensuring it implements the contract.
     */
    public function getApprovable(): ?Approvable
    {
        $model = $this->approvable;

        if ($model instanceof Approvable) {
            return $model;
        }

        return null;
    }

    /**
     * Find the current active step(s) — those in the first incomplete group.
     *
     * @return Collection<int, ApprovalRequestStep>
     */
    public function currentSteps()
    {
        // Collect groups from snapshot, find first with pending steps
        $snapshot = $this->workflow_snapshot;

        if (! $snapshot || ! isset($snapshot['groups'])) {
            return collect();
        }

        $pendingSteps = $this->steps->where('state', StepState::Pending);

        if ($pendingSteps->isEmpty()) {
            return collect();
        }

        // Find the group of the first pending step by order
        $firstPendingOrder = $pendingSteps->min('order');

        // All steps in the same group share a group_name; find the group boundary
        $firstPendingStep = $this->steps->firstWhere('order', $firstPendingOrder);
        $groupName = $firstPendingStep?->group_name;

        return $pendingSteps->where('group_name', $groupName);
    }
}
