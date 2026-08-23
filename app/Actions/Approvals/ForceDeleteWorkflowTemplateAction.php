<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\WorkflowTemplate;

final class ForceDeleteWorkflowTemplateAction
{
    /**
     * Permanently delete a workflow template (force delete).
     */
    public function execute(int|string|WorkflowTemplate $workflow): void
    {
        $template = $workflow instanceof WorkflowTemplate
            ? $workflow
            : WorkflowTemplate::withTrashed()->findOrFail((int) $workflow);

        $template->forceDelete();
    }
}
