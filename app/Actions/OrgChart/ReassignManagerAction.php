<?php

declare(strict_types=1);

namespace App\Actions\OrgChart;

use App\Models\Company;
use InvalidArgumentException;

final class ReassignManagerAction
{
    /**
     * Reassign a user's manager in the company, auto-promoting descendants if a cycle would occur.
     *
     * @param array{
     *     user_id: int|string,
     *     new_manager_id?: int|string|null,
     *     job_title?: string|null,
     *     status?: string|null
     * } $data
     *
     * @throws InvalidArgumentException
     */
    public function execute(Company $company, array $data): void
    {
        $userId = (int) $data['user_id'];
        $newManagerId = ! empty($data['new_manager_id']) ? (int) $data['new_manager_id'] : null;

        // Prevent self-referencing
        if ($userId === $newManagerId) {
            throw new InvalidArgumentException('A user cannot be their own manager.');
        }

        // Cycle detection: if newManagerId is a descendant of userId, auto-promote
        if ($newManagerId !== null) {
            $currentId = $newManagerId;
            $visited = [];
            $isDescendant = false;

            while ($currentId !== null) {
                if ($currentId === $userId) {
                    $isDescendant = true;
                    break;
                }
                if (in_array($currentId, $visited, true)) {
                    break; // safety: circular reference already exists
                }
                $visited[] = $currentId;

                $parentUser = $company->users()
                    ->wherePivot('user_id', $currentId)
                    ->first();

                $currentId = $parentUser?->pivot?->manager_id;
            }

            // If new manager is a descendant, promote them to root first
            if ($isDescendant) {
                $company->users()->updateExistingPivot($newManagerId, ['manager_id' => null]);
            }
        }

        // Build update data
        $updateData = ['manager_id' => $newManagerId];
        if (array_key_exists('job_title', $data) && $data['job_title'] !== null) {
            $updateData['job_title'] = (string) $data['job_title'];
        }
        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $updateData['status'] = (string) $data['status'];
        }

        $company->users()->updateExistingPivot($userId, $updateData);
    }
}
