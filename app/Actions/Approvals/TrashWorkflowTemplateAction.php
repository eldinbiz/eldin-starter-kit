<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\WorkflowTemplate;

final class TrashWorkflowTemplateAction
{
    /**
     * Soft-delete a disabled workflow template.
     */
    public function execute(WorkflowTemplate $workflow): void
    {
        if ($workflow->is_active) {
            abort(400, 'Only disabled templates can be deleted.');
        }

        $workflow->delete();
    }
}
