<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model implements Approvable
{
    protected $fillable = [
        'company_id',
        'requester_id',
        'title',
        'description',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function getApprovalTitle(): string
    {
        return "Purchase Request #{$this->id}: {$this->title}";
    }

    public function getApprovalUrl(): string
    {
        return route('purchase-requests.show', $this->id);
    }

    public function getApprovalRequester(): User
    {
        return $this->requester;
    }

    public function getApprovalCompany(): Company
    {
        return $this->company;
    }

    public function getApprovalPreview(): ?array
    {
        return [
            'view' => 'purchase-requests._approval-preview',
            'data' => ['purchaseRequest' => $this],
        ];
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function isPendingApproval(): bool
    {
        return $this->approvalRequests()
            ->where('status', ApprovalStatus::Pending)
            ->exists();
    }
}
