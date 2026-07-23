<?php

declare(strict_types=1);

namespace App\Models\Approval;

use App\Enums\ResolverType;
use App\Enums\StepState;
use App\Models\User;
use Database\Factories\Approval\ApprovalRequestStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestStep extends Model
{
    /** @use HasFactory<ApprovalRequestStepFactory> */
    use HasFactory;

    protected $table = 'approval_request_steps';

    protected $fillable = [
        'approval_request_id',
        'group_name',
        'step_label',
        'order',
        'resolver_type',
        'resolver_payload',
        'approver_id',
        'state',
        'action_at',
        'notes',
        'delegated_from_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolver_type' => ResolverType::class,
            'resolver_payload' => 'array',
            'state' => StepState::class,
            'action_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function delegatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from_id');
    }

    public function scopePending($query)
    {
        return $query->where('state', StepState::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('state', StepState::Approved);
    }

    public function scopeRejected($query)
    {
        return $query->where('state', StepState::Rejected);
    }

    public function isPending(): bool
    {
        return $this->state === StepState::Pending;
    }

    /**
     * Get the display label for this step.
     * Falls back to the approver's job title if step_label is empty.
     */
    public function getDisplayLabelAttribute(): string
    {
        if (! empty($this->step_label)) {
            return $this->step_label;
        }

        $companyId = $this->request?->company_id;
        $approver = $this->approver;

        if ($approver && $companyId) {
            $membership = $approver->companies->firstWhere('id', $companyId);

            return $membership?->pivot?->job_title ?: 'Approver';
        }

        return 'Approver';
    }
}
