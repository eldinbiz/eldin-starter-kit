<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Resources\Api\v1;

use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
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
            'title' => $this->title,
            'amount' => $this->amount,
            'description' => $this->description,
            'approval_status' => $this->approval_status,
            'company_id' => $this->company_id,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
