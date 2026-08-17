<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrgChartController extends Controller
{
    /**
     * Render the organization chart page.
     */
    public function index(): View
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        return view('admin.org-chart.index', [
            'company' => $activeCompany,
            'canManage' => auth()->user()?->can('manage-org-chart') ?? false,
        ]);
    }

    /**
     * Return the full org chart tree as JSON for the active company.
     */
    public function tree(): JsonResponse
    {
        $user = auth()->user();
        $activeCompany = $user?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        $allUsers = $activeCompany->users()
            ->get()
            ->map(function (User $u): array {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'initials' => strtoupper(substr($u->name, 0, 1)),
                    'job_title' => $u->pivot->job_title ?? 'No Title',
                    'status' => $u->pivot->status ?? 'active',
                    'manager_id' => $u->pivot->manager_id,
                ];
            });

        // Defensive: strip manager_id references to users not in this company
        $userIds = $allUsers->pluck('id');
        $allUsers = $allUsers->map(function (array $u) use ($userIds, $activeCompany): array {
            if ($u['manager_id'] !== null && ! $userIds->contains($u['manager_id'])) {
                Log::warning('Org chart: dangling manager reference detected', [
                    'company_id' => $activeCompany->id,
                    'user_id' => $u['id'],
                    'missing_manager_id' => $u['manager_id'],
                ]);
                $u['manager_id'] = null;
            }

            return $u;
        });

        return response()->json([
            'company' => [
                'id' => $activeCompany->id,
                'name' => $activeCompany->name,
            ],
            'users' => $allUsers,
        ]);
    }

    /**
     * Reassign a user's manager within the active company.
     */
    public function updateManager(Request $request): JsonResponse
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'new_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,on_leave,inactive'],
        ]);

        $userId = (int) $request->input('user_id');
        $newManagerId = $request->input('new_manager_id') ? (int) $request->input('new_manager_id') : null;

        // Prevent self-referencing
        if ($userId === $newManagerId) {
            return response()->json(['error' => 'A user cannot be their own manager.'], 422);
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

                $parentUser = $activeCompany->users()
                    ->wherePivot('user_id', $currentId)
                    ->first();

                $currentId = $parentUser?->pivot?->manager_id;
            }

            // If new manager is a descendant, promote them to root first
            if ($isDescendant) {
                $activeCompany->users()->updateExistingPivot($newManagerId, ['manager_id' => null]);
            }
        }

        // Build update data (manager_id always present, others optional)
        $updateData = ['manager_id' => $newManagerId];
        if ($request->has('job_title')) {
            $updateData['job_title'] = $request->input('job_title');
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->input('status');
        }

        $activeCompany->users()->updateExistingPivot($userId, $updateData);

        return response()->json(['success' => true]);
    }

    /**
     * Trace the reporting line from a user up to the root.
     */
    public function trace(int $user): JsonResponse
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        $chain = [];
        $currentUserId = $user;
        $visited = [];

        while ($currentUserId) {
            if (in_array($currentUserId, $visited, true)) {
                break; // circular reference protection
            }

            $visited[] = $currentUserId;

            $parentUser = $activeCompany->users()
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

            $currentUserId = $parentUser?->pivot?->manager_id;
        }

        return response()->json(['chain' => $chain]);
    }
}
