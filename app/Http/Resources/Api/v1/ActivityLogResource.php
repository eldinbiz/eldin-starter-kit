<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $changes = is_string($this->changes ?? null)
            ? json_decode($this->changes, true)
            : ($this->changes ?? []);

        return [
            'id' => $this->id,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'event' => $this->event,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'actor_label' => $this->actor_label,
            'changes' => $changes,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}
