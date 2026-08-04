<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Approvable;
use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * A persisted Eloquent model implementing the Approvable contract for tests.
 *
 * The backing table (fake_approvables) is created on demand via ensureTable(),
 * so no permanent migration is required for this test-only artifact.
 */
class FakeApprovable extends Model implements Approvable
{
    protected $table = 'fake_approvables';

    protected $fillable = [
        'requester_id',
        'company_id',
        'is_completed',
        'is_rejected',
        'is_canceled',
    ];

    /**
     * Create the backing table if it does not already exist.
     */
    public static function ensureTable(): void
    {
        if (Schema::hasTable('fake_approvables')) {
            return;
        }

        Schema::create('fake_approvables', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('company_id');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->boolean('is_canceled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Create a persisted FakeApprovable for the given requester and company.
     */
    public static function createFor(User $requester, Company $company): self
    {
        static::ensureTable();

        return static::create([
            'requester_id' => $requester->id,
            'company_id' => $company->id,
        ]);
    }

    public function getApprovalTitle(): string
    {
        return "Fake Approvable #{$this->id}";
    }

    public function getApprovalUrl(): string
    {
        return route('dashboard');
    }

    public function getApprovalRequester(): User
    {
        return User::findOrFail((int) $this->requester_id);
    }

    public function getApprovalCompany(): Company
    {
        return Company::findOrFail((int) $this->company_id);
    }

    /**
     * @return array{view: string, data: array}|null
     */
    public function getApprovalPreview(): ?array
    {
        return null;
    }

    public function onApprovalCompleted(ApprovalRequest $request): void
    {
        $this->update(['is_completed' => true]);
    }

    public function onApprovalRejected(ApprovalRequest $request): void
    {
        $this->update(['is_rejected' => true]);
    }

    public function onApprovalCanceled(ApprovalRequest $request): void
    {
        $this->update(['is_canceled' => true]);
    }

    public static function getApprovalTypeLabel(): string
    {
        return 'Fake Approvable';
    }
}
