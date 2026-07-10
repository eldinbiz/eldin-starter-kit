<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->wherePivot('status', 'active')
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
        ]);

        $userId = (int) $request->input('user_id');
        $newManagerId = $request->input('new_manager_id') ? (int) $request->input('new_manager_id') : null;

        // Prevent self-referencing
        if ($userId === $newManagerId) {
            return response()->json(['error' => 'A user cannot be their own manager.'], 422);
        }

        // Update the pivot entry
        DB::table('company_user')
            ->where('company_id', $activeCompany->id)
            ->where('user_id', $userId)
            ->update(['manager_id' => $newManagerId]);

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

            $pivot = DB::table('company_user')
                ->where('company_id', $activeCompany->id)
                ->where('user_id', $currentUserId)
                ->first();

            $currentUser = User::find($currentUserId);
            if ($currentUser) {
                $chain[] = [
                    'id' => $currentUser->id,
                    'name' => $currentUser->name,
                    'initials' => strtoupper(substr($currentUser->name, 0, 1)),
                    'job_title' => $pivot?->job_title ?? 'No Title',
                ];
            }

            $currentUserId = $pivot?->manager_id ?? null;
        }

        return response()->json(['chain' => $chain]);
    }
}
