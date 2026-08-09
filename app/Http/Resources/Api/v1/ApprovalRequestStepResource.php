<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_request_id' => $this->approval_request_id,
            'step_order' => $this->step_order,
            'status' => $this->status,
            'approver_type' => $this->approver_type,
            'approver_id' => $this->approver_id,
            'actioned_by_id' => $this->actioned_by_id,
            'actioned_at' => $this->actioned_at,
            'comments' => $this->comments,
            'actioned_by' => new UserResource($this->whenLoaded('actionedBy')),
        ];
    }
}
