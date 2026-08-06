<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model implements Approvable
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_requests';

    protected $fillable = [
        'company_id',
        'requester_id',
        'pr_number',
        'title',
        'department',
        'description',
        'total_amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
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

    // === Approvable Contract Implementation ===

    public function getApprovalTitle(): string
    {
        return "Purchase Request {$this->pr_number}: {$this->title}";
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

    public function onApprovalCompleted(ApprovalRequest $request): void
    {
        $this->update(['status' => 'approved']);
    }

    public function onApprovalRejected(ApprovalRequest $request): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function onApprovalCanceled(ApprovalRequest $request): void
    {
        $this->update(['status' => 'canceled']);
    }

    public static function getApprovalTypeLabel(): string
    {
        return 'Purchase Request';
    }

    // === Approval Relationships & Helpers ===

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
