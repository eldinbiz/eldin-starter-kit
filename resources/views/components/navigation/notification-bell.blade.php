<?php

use App\Actions\Companies\SwitchActiveCompanyAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
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
        unset($this->unreadCount, $this->notifications);
    }
};
?>

<div
    class="relative"
    wire:poll.30s="refreshNotifications"
    x-data
    @click.outside="$wire.closeDropdown()"
>
    <button
        type="button"
        wire:click="toggleDropdown"
        class="relative flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-hidden"
        title="Notifications"
    >
        <svg class="fill-current text-gray-500 dark:text-gray-400" width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M12 22C13.1 22 14 21.1 14 20H10C10 21.1 10.9 22 12 22ZM18 16V11C18 7.93 16.37 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 10.5 4V4.68C7.64 5.36 6 7.92 6 11V16L4 18V19H20V18L18 16ZM16 17H8V11C8 8.52 9.51 6.5 12 6.5C14.49 6.5 16 8.52 16 11V17Z"/>
        </svg>

        @if($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-error-500 px-1 text-[10px] font-bold text-white shadow-xs animate-pulse">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    @if($open)
        <div
            class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 z-9999 text-gray-800 dark:text-gray-200"
        >
            {{-- Popover Header --}}
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h4>
                    @if($this->unreadCount > 0)
                        <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-xs font-medium text-brand-500">
                            {{ $this->unreadCount }} unread
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <button
                        type="button"
                        wire:click="refreshNotifications"
                        class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
                        title="Refresh"
                    >
                        <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    @if($this->unreadCount > 0)
                        <button
                            type="button"
                            wire:click="markAllAsRead"
                            class="text-brand-500 hover:underline font-medium text-[11px]"
                        >
                            Mark all read
                        </button>
                    @endif
                </div>
            </div>

            {{-- Notification List --}}
            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 my-1">
                @forelse($this->notifications as $item)
                    @php
                        $data = $item->data ?? [];
                        $isUnread = empty($item->read_at);
                    @endphp
                    <div
                        wire:key="notif-{{ $item->id }}"
                        wire:click="markAsRead('{{ $item->id }}')"
                        class="flex flex-col gap-1 p-3 rounded-lg cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-white/5 {{ $isUnread ? 'bg-brand-50/40 dark:bg-brand-500/5' : '' }}"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                @if($isUnread)
                                    <span class="h-2 w-2 rounded-full bg-brand-500 shrink-0"></span>
                                @endif
                                @if(!empty($data['company_name']))
                                    <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 dark:text-gray-300 truncate">
                                        {{ $data['company_name'] }}
                                    </span>
                                @endif
                                <span class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $data['title'] ?? 'Notification' }}
                                </span>
                            </div>
                            <span class="text-[10px] text-gray-400 shrink-0">
                                {{ $item->created_at?->diffForHumans() ?? 'Just now' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 pl-3.5">
                            {{ $data['message'] ?? '' }}
                        </p>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-400 dark:text-gray-500">
                        No notifications available
                    </div>
                @endforelse
            </div>

            {{-- Popover Footer --}}
            <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-center">
                <a
                    href="{{ route('notifications.index') }}"
                    wire:navigate
                    @click="$wire.closeDropdown()"
                    class="text-xs font-semibold text-brand-500 hover:text-brand-600 hover:underline inline-block py-1"
                >
                    View All Notifications &rarr;
                </a>
            </div>
        </div>
    @endif
</div>
