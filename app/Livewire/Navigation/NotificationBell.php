<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Actions\Companies\SwitchActiveCompanyAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class NotificationBell extends Component
{
    public bool $open = false;

    #[Computed]
    public function unreadCount(): int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user ? $user->unreadNotifications()->count() : 0;
    }

    #[Computed]
    public function notifications(): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $user->notifications()
            ->take(5)
            ->get();
    }

    public function toggleDropdown(): void
    {
        $this->open = ! $this->open;
    }

    public function closeDropdown(): void
    {
        $this->open = false;
    }

    public function markAsRead(string $id, SwitchActiveCompanyAction $switchCompanyAction): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }

        $data = $notification?->data ?? [];
        $targetUrl = $data['url'] ?? null;
        $companyId = isset($data['company_id']) ? (int) $data['company_id'] : null;

        // Auto-switch company if notification belongs to a specific company user is member of
        if ($companyId && $user->belongsToCompany($companyId)) {
            $switchCompanyAction->execute($user, $companyId);
        }

        $this->closeDropdown();

        if ($targetUrl) {
            $this->redirect($targetUrl, navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    #[On('notification-sent')]
    public function refreshNotifications(): void
    {
        // Calling this method triggers re-evaluation of computed properties
        unset($this->unreadCount, $this->notifications);
    }

    public function render(): View
    {
        return view('livewire.navigation.notification-bell');
    }
}
