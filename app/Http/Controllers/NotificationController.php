<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query = $user->unreadNotifications();
        } elseif ($filter === 'read') {
            $query = $user->readNotifications();
        }

        $notifications = $query->paginate(10)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    public function latest(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadCount = $user->unreadNotifications()->count();

        $notifications = $user->notifications()
            ->take(5)
            ->get()
            ->map(function (DatabaseNotification $n): array {
                $data = $n->data;

                return [
                    'id' => $n->id,
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'type' => $data['type'] ?? 'system',
                    'url' => $data['url'] ?? null,
                    'action_text' => $data['action_text'] ?? 'View',
                    'company_id' => $data['company_id'] ?? null,
                    'company_name' => $data['company_name'] ?? null,
                    'level' => $data['level'] ?? 'info',
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->diffForHumans() ?? 'Just now',
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }

        $data = $notification?->data ?? [];
        $targetUrl = $data['url'] ?? route('notifications.index');
        $companyId = isset($data['company_id']) ? (int) $data['company_id'] : null;

        // Auto-switch company if notification belongs to a specific company user is member of
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $user->belongsToCompany($company)) {
                session(['active_company_id' => $company->id]);
                $role = $user->getActiveRole($company);
                if ($role) {
                    session(["active_role.{$company->id}" => $role->name]);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => $targetUrl,
            ]);
        }

        return redirect()->to($targetUrl);
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('status', 'All notifications marked as read.');
    }
}
