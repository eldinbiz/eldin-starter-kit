<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\WorkflowTemplate;

final class ToggleWorkflowTemplateAction
{
    /**
     * Disable a workflow template (set is_active = false).
     */
    public function disable(WorkflowTemplate $workflow): void
    {
        $workflow->update(['is_active' => false]);
    }

    /**
     * Enable / restore a workflow template (set is_active = true, restore if trashed).
     */
    public function enable(int|string|WorkflowTemplate $workflow): WorkflowTemplate
    {
        $template = $workflow instanceof WorkflowTemplate
            ? $workflow
            : WorkflowTemplate::withTrashed()->findOrFail((int) $workflow);

        $template->update(['is_active' => true]);

        if ($template->trashed()) {
            $template->restore();
        }

        return $template;
    }
}
