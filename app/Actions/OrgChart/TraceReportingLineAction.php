<?php

declare(strict_types=1);

namespace App\Actions\OrgChart;

use App\Models\Company;
use App\Models\User;

final class TraceReportingLineAction
{
    /**
     * Trace the managerial chain from the target user up to top-level leadership.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     initials: string,
     *     job_title: string
     * }>
     */
    public function execute(Company $company, int $userId): array
    {
        $chain = [];
        $currentUserId = $userId;
        $visited = [];

        while ($currentUserId) {
            if (in_array($currentUserId, $visited, true)) {
                break; // circular reference protection
            }

            $visited[] = $currentUserId;

            $parentUser = $company->users()
                ->wherePivot('user_id', $currentUserId)
                ->first();

            $currentUser = User::find($currentUserId);
            if ($currentUser) {
                $chain[] = [
                    'id' => $currentUser->id,
                    'name' => $currentUser->name,
                    'initials' => strtoupper(substr($currentUser->name, 0, 1)),
                    'job_title' => $parentUser?->pivot?->job_title ?? 'No Title',
                ];
            }

            $currentUserId = $parentUser?->pivot?->manager_id ? (int) $parentUser->pivot->manager_id : null;
        }

        return $chain;
    }
}
