<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;

interface Approvable
{
    /**
     * The display title for this approval request (e.g., "Purchase Order #42").
     */
    public function getApprovalTitle(): string;

    /**
     * A URL linking to the full resource for deeper inspection.
     */
    public function getApprovalUrl(): string;

    /**
     * The user who submitted the approval request.
     */
    public function getApprovalRequester(): User;

    /**
     * The company this approval request belongs to.
     */
    public function getApprovalCompany(): Company;

    /**
     * Optional embedded preview content rendered inside the approval detail view.
     *
     * Return an array with a `view` key (Blade view name) and a `data` key
     * (array of view data). Return null to fall back to a simple title + link.
     *
     * @return array{view: string, data: array}|null
     */
    public function getApprovalPreview(): ?array;

    /**
     * Called when an approval request for this model is fully approved.
     */
    public function onApprovalCompleted(ApprovalRequest $request): void;

    /**
     * Called when an approval request for this model is rejected.
     */
    public function onApprovalRejected(ApprovalRequest $request): void;

    /**
     * Called when an approval request for this model is canceled.
     */
    public function onApprovalCanceled(ApprovalRequest $request): void;
}
