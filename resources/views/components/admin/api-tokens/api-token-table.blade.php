<?php

use App\Actions\ApiTokens\CreateApiTokenAction;
use App\Actions\ApiTokens\RevokeApiTokenAction;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Livewire\DataTables\WithDataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    #[Url(as: 'sort', history: true, except: 'created_at')]
    public string $sortField = 'created_at';

    #[Url(as: 'dir', history: true, except: 'desc')]
    public string $sortDirection = 'desc';

    // Generation Modal State
    public bool $showCreateModal = false;

    public string $tokenName = '';

    public string $tokenExpiry = '90';

    /**
     * @var array<int, string>
     */
    public array $selectedAbilities = [];

    public bool $selectAllAbilities = true;

    // Generated Token Success Modal State
    public ?string $newlyCreatedToken = null;

    public ?string $newlyCreatedTokenName = null;

    // Scopes Inspector Modal State
    public bool $showScopesModal = false;

    public ?int $inspectingTokenId = null;

    public ?string $inspectingTokenName = null;

    public ?string $inspectingUserName = null;

    public ?string $inspectingUserEmail = null;

    /**
     * @var array<int, string>
     */
    public array $inspectingAbilities = [];

    // Revocation Modal State
    public ?int $tokenToRevokeId = null;

    public ?string $tokenToRevokeName = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(): void
    {
        $this->initAllAbilities();
    }

    private function initAllAbilities(): void
    {
        $this->selectedAbilities = array_map(
            fn (array $ep): string => (string) $ep['ability'],
            ApiTokenController::AVAILABLE_ENDPOINTS
        );
        $this->selectAllAbilities = true;
    }

    public function query(): Builder
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereHasMorph('tokenable', [User::class], function (Builder $q): void {
                $q->where('is_system', true);
            })
            ->with('tokenable');
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $term = $this->search;
        $query->where(function (Builder $sub) use ($term): void {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhereHasMorph('tokenable', [User::class], function (Builder $sq) use ($term): void {
                    $sq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['status'])) {
            $status = $this->filterValues['status'];
            match ($status) {
                'active' => $query->where(fn (Builder $sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                'expiring_soon' => $query->whereNotNull('expires_at')->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(7)),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                'never' => $query->whereNull('expires_at'),
                default => null,
            };
        }
    }

    protected function applySorting(Builder $query): void
    {
        $allowed = ['name', 'expires_at', 'last_used_at', 'created_at', 'id'];

        if (in_array($this->sortField, $allowed, true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    public function toggleSelectAllAbilities(): void
    {
        if ($this->selectAllAbilities) {
            $this->selectedAbilities = array_map(
                fn (array $ep): string => (string) $ep['ability'],
                ApiTokenController::AVAILABLE_ENDPOINTS
            );
        } else {
            $this->selectedAbilities = [];
        }
    }

    public function updatedSelectedAbilities(): void
    {
        $allCount = count(ApiTokenController::AVAILABLE_ENDPOINTS);
        $this->selectAllAbilities = count($this->selectedAbilities) === $allCount;
    }

    public function openCreateModal(): void
    {
        $this->tokenName = '';
        $this->tokenExpiry = '90';
        $this->initAllAbilities();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->tokenName = '';
    }

    public function generateToken(CreateApiTokenAction $action): void
    {
        $this->authorize('manage-api-tokens');

        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
            'tokenExpiry' => ['required', 'in:30,60,90,365,never'],
            'selectedAbilities' => ['array'],
        ]);

        $allCount = count(ApiTokenController::AVAILABLE_ENDPOINTS);
        $abilities = (count($this->selectedAbilities) === $allCount || in_array('*', $this->selectedAbilities, true))
            ? ['*']
            : $this->selectedAbilities;

        /** @var User|null $authUser */
        $authUser = Auth::user();

        $token = $action->execute([
            'name' => $this->tokenName,
            'expiry' => $this->tokenExpiry,
            'abilities' => $abilities,
        ], $authUser);

        $this->newlyCreatedToken = $token->plainTextToken;
        $this->newlyCreatedTokenName = $this->tokenName;
        $this->showCreateModal = false;
        $this->flashSuccess = 'Token Generated Successfully';

        $this->resetPage();
    }

    public function closeTokenBanner(): void
    {
        $this->newlyCreatedToken = null;
        $this->newlyCreatedTokenName = null;
    }

    public function inspectScopes(int $tokenId): void
    {
        $token = PersonalAccessToken::with('tokenable')->find($tokenId);

        if (! $token) {
            return;
        }

        $this->inspectingTokenId = $token->id;
        $this->inspectingTokenName = $token->name;
        $this->inspectingUserName = $token->tokenable?->name;
        $this->inspectingUserEmail = $token->tokenable?->email;
        $this->inspectingAbilities = $token->abilities ?? ['*'];
        $this->showScopesModal = true;
    }

    public function closeScopesModal(): void
    {
        $this->showScopesModal = false;
        $this->inspectingTokenId = null;
    }

    public function promptRevoke(int $tokenId): void
    {
        $token = PersonalAccessToken::find($tokenId);

        if (! $token) {
            return;
        }

        $this->tokenToRevokeId = $token->id;
        $this->tokenToRevokeName = $token->name;
    }

    public function cancelRevoke(): void
    {
        $this->tokenToRevokeId = null;
        $this->tokenToRevokeName = null;
    }

    public function revokeToken(RevokeApiTokenAction $action): void
    {
        $this->authorize('manage-api-tokens');

        if (! $this->tokenToRevokeId) {
            return;
        }

        $token = PersonalAccessToken::find($this->tokenToRevokeId);

        if (! $token) {
            $this->cancelRevoke();

            return;
        }

        $action->execute($token);
        $this->flashSuccess = 'API Token "'.$this->tokenToRevokeName.'" revoked successfully.';
        $this->cancelRevoke();
        $this->resetPage();
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function endpoints(): array
    {
        return ApiTokenController::AVAILABLE_ENDPOINTS;
    }

    #[Computed]
    public function inspectingGrantedEndpoints(): array
    {
        if (empty($this->inspectingAbilities) || in_array('*', $this->inspectingAbilities, true)) {
            return ApiTokenController::AVAILABLE_ENDPOINTS;
        }

        return array_values(array_filter(
            ApiTokenController::AVAILABLE_ENDPOINTS,
            fn (array $ep): bool => in_array($ep['ability'], $this->inspectingAbilities, true)
        ));
    }
};
?>

<div>
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Token Generated Success Notice (One-time Secret Banner) --}}
    @if ($newlyCreatedToken)
        <div class="mb-6 rounded-2xl border border-success-200 bg-success-50/70 p-5 dark:border-success-800 dark:bg-success-900/20">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div>
                        <h4 class="text-sm font-semibold text-success-900 dark:text-success-300">
                            API Token Generated: {{ $newlyCreatedTokenName }}
                        </h4>
                        <p class="text-xs text-success-700 dark:text-success-400">
                            Please copy this secret token immediately. For your security, it will not be displayed again.
                        </p>
                    </div>
                </div>

                <button type="button" wire:click="closeTokenBanner" class="text-success-600 hover:text-success-800 dark:text-success-400 cursor-pointer">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 flex items-center gap-2 rounded-xl border border-success-200 bg-white p-3 dark:border-success-800/80 dark:bg-gray-900" x-data="{ copied: false }">
                <code class="flex-1 break-all font-mono text-xs text-gray-800 dark:text-gray-200 select-all" id="generatedTokenText">{{ $newlyCreatedToken }}</code>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $newlyCreatedToken }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600 cursor-pointer transition"
                >
                    <span x-text="copied ? 'Copied!' : 'Copy Token'"></span>
                </button>
            </div>
        </div>
    @endif

    {{-- Top Controls Header --}}
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                System API Tokens
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage access tokens, endpoint permissions, and lifecycles for service accounts and integrations.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ url('/docs/api') }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-hidden transition"
            >
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                View API Docs
            </a>

            <button
                type="button"
                wire:click="openCreateModal"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden cursor-pointer transition"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 4.16666V15.8333M4.16663 10H15.8333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Generate Token
            </button>
        </div>
    </div>

    {{-- Filter Header Bar --}}
    @include('livewire.datatables.header', [
        'placeholder' => 'Search tokens by name or service bot...',
        'hasFilters' => true,
    ])

    {{-- Collapsible Filter Panel --}}
    @if ($this->filterPanelOpen)
        <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50/75 p-4 dark:border-gray-800 dark:bg-gray-800/40">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Token Status
                    </label>
                    <select
                        wire:model.live="filterValues.status"
                        class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="expiring_soon">Expiring Soon (<= 7 days)</option>
                        <option value="expired">Expired</option>
                        <option value="never">Never Expires</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs font-medium text-error-600 hover:text-error-700 dark:text-error-400 dark:hover:text-error-300 cursor-pointer"
                >
                    Reset all filters
                </button>
            </div>
        </div>
    @endif

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips', [
        'filterLabels' => [
            'status' => 'Status',
        ],
    ])

    {{-- Loading Skeleton --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 6, 'rows' => 5])
    </div>

    {{-- Table Content --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            {{-- Desktop Table View --}}
            <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800/60 dark:border-gray-800">
                        <tr>
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Service Account', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'name', 'label' => 'Token Name', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Scopes / Permissions', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'expires_at', 'label' => 'Expires', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'last_used_at', 'label' => 'Last Used', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'created_at', 'label' => 'Created', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Actions', 'sortable' => false, 'align' => 'right'])
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-transparent">
                        @foreach ($this->rows as $token)
                            <tr class="hover:bg-gray-50/75 dark:hover:bg-white/[0.02] transition-colors">
                                {{-- Service Account --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <span class="h-9 w-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs dark:bg-blue-500/10 dark:text-blue-400 shrink-0">
                                            BOT
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">
                                                {{ $token->tokenable?->name ?? 'System' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $token->tokenable?->email ?? '&mdash;' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Token Name --}}
                                <td class="py-3.5 px-4">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                        {{ $token->name }}
                                    </p>
                                </td>

                                {{-- Scopes --}}
                                <td class="py-3.5 px-4">
                                    @php
                                        $abilities = $token->abilities ?? ['*'];
                                        $isFullAccess = in_array('*', $abilities, true);
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="inspectScopes({{ $token->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium cursor-pointer transition {{ $isFullAccess ? 'bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-500/15 dark:text-purple-400' : 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400' }}"
                                    >
                                        <span>{{ $isFullAccess ? 'Full Access (*)' : count($abilities).' Scope(s)' }}</span>
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </td>

                                {{-- Expires --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if (! $token->expires_at)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Never
                                        </span>
                                    @elseif ($token->expires_at->isPast())
                                        <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                                            Expired ({{ $token->expires_at->format('M d, Y') }})
                                        </span>
                                    @elseif ($token->expires_at->diffInDays(now()) <= 7)
                                        <span class="inline-flex items-center rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                                            {{ $token->expires_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $token->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Last Used --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="text-gray-500 text-xs dark:text-gray-400">
                                        {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                                    </p>
                                </td>

                                {{-- Created --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="text-gray-500 text-xs dark:text-gray-400">
                                        {{ $token->created_at ? $token->created_at->format('M d, Y') : '&mdash;' }}
                                    </p>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        wire:click="promptRevoke({{ $token->id }})"
                                        title="Revoke token"
                                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards Grid View --}}
            <div class="grid grid-cols-1 gap-4 sm:hidden">
                @foreach ($this->rows as $token)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h4 class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">
                                {{ $token->name }}
                            </h4>
                            <button
                                type="button"
                                wire:click="promptRevoke({{ $token->id }})"
                                class="text-error-500 hover:text-error-600 text-xs font-medium cursor-pointer"
                            >
                                Revoke
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            {{ $token->tokenable?->name ?? 'System' }} ({{ $token->tokenable?->email ?? '&mdash;' }})
                        </p>

                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @php
                                $abilities = $token->abilities ?? ['*'];
                                $isFullAccess = in_array('*', $abilities, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="inspectScopes({{ $token->id }})"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isFullAccess ? 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400' : 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400' }}"
                            >
                                {{ $isFullAccess ? 'Full Access (*)' : count($abilities).' Scope(s)' }}
                            </button>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Expires: {{ $token->expires_at ? $token->expires_at->format('M d, Y') : 'Never' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @include('livewire.datatables.pagination', ['paginator' => $this->rows])
        @else
            {{-- Empty State --}}
            @include('livewire.datatables.empty', [
                'title' => 'No API tokens found',
                'message' => 'No active system tokens found. Click "Generate Token" to create one.',
            ])
        @endif
    </div>

    {{-- Generate New API Token Modal --}}
    @if ($showCreateModal)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.closeCreateModal()"
                class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800 max-h-[90vh] flex flex-col"
            >
                <div class="flex items-center justify-between mb-4 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Generate New API Token
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Configure token identifier, lifecycle, and endpoint permissions.
                        </p>
                    </div>
                    <button type="button" wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-500 cursor-pointer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="generateToken" class="flex flex-col flex-1 overflow-hidden">
                    <div class="overflow-y-auto pr-1 space-y-4 flex-1">
                        {{-- Token Name --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                                Token / Integration Name <span class="text-error-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="tokenName"
                                required
                                placeholder="e.g., Zapier Approvals Sync, Billing Webhook"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                            />
                            @error('tokenName') <span class="text-xs text-error-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Expiration Selector --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                                Token Expiration
                            </label>
                            <select
                                wire:model="tokenExpiry"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                            >
                                <option value="30">30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days (Recommended)</option>
                                <option value="365">1 Year</option>
                                <option value="never">Never (No Expiration)</option>
                            </select>
                        </div>

                        {{-- Endpoint Permissions --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium text-gray-800 dark:text-white/90">
                                    Endpoint Permissions
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-brand-600 dark:text-brand-400 font-medium">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectAllAbilities"
                                        wire:change="toggleSelectAllAbilities"
                                        class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800"
                                    />
                                    Select All / Full Access (*)
                                </label>
                            </div>

                            <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 max-h-56 overflow-y-auto">
                                @foreach ($this->endpoints as $ep)
                                    <div class="flex items-center justify-between p-2 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                        <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                            <input
                                                type="checkbox"
                                                value="{{ $ep['ability'] }}"
                                                wire:model.live="selectedAbilities"
                                                class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800"
                                            />
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                                    {{ $ep['method'] }}
                                                </span>
                                                <code class="text-xs font-mono font-medium text-gray-800 dark:text-gray-200 truncate">
                                                    {{ $ep['path'] }}
                                                </code>
                                                <span class="text-xs text-gray-400 hidden sm:inline truncate">
                                                    — {{ $ep['label'] }}
                                                </span>
                                            </div>
                                        </label>
                                        <a
                                            href="{{ url('/docs/api') }}#/operations/{{ $ep['doc_anchor'] ?? '' }}"
                                            target="_blank"
                                            class="ml-2 text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline flex-shrink-0 inline-flex items-center gap-1"
                                            title="View OpenAPI documentation reference"
                                        >
                                            Docs
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex-shrink-0">
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 shadow-theme-xs cursor-pointer transition"
                        >
                            Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Inspect Scopes Modal --}}
    @if ($showScopesModal)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.closeScopesModal()"
                class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800 max-h-[90vh] flex flex-col"
            >
                <div class="flex items-center justify-between mb-4 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Token Permissions: <span class="text-brand-500">{{ $inspectingTokenName }}</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Service Account: {{ $inspectingUserName }} ({{ $inspectingUserEmail }})
                        </p>
                    </div>
                    <button type="button" wire:click="closeScopesModal" class="text-gray-400 hover:text-gray-500 cursor-pointer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Accessible Endpoints ({{ count($this->inspectingGrantedEndpoints) }})
                    </span>
                    @if (empty($inspectingAbilities) || in_array('*', $inspectingAbilities, true))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            Full Platform Access (*)
                        </span>
                    @endif
                </div>

                <div class="overflow-y-auto pr-1 space-y-2 flex-1 max-h-72 rounded-lg border border-gray-200 p-3 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                    @foreach ($this->inspectingGrantedEndpoints as $ep)
                        <div class="flex items-center justify-between p-2 rounded bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ $ep['method'] }}
                                </span>
                                <code class="text-xs font-mono font-medium text-gray-800 dark:text-gray-200 truncate">
                                    {{ $ep['path'] }}
                                </code>
                                <span class="text-xs text-gray-400 hidden sm:inline truncate">
                                    — {{ $ep['label'] }}
                                </span>
                            </div>
                            <a
                                href="{{ url('/docs/api') }}#/operations/{{ $ep['doc_anchor'] ?? '' }}"
                                target="_blank"
                                class="ml-2 text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline flex-shrink-0 inline-flex items-center gap-1 font-medium"
                                title="View OpenAPI documentation reference"
                            >
                                Docs
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex-shrink-0">
                    <button
                        type="button"
                        wire:click="closeScopesModal"
                        class="rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Revoke Token Confirmation Modal --}}
    @if ($tokenToRevokeId)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.cancelRevoke()"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 mb-4">
                    <svg class="h-7 w-7 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">
                    Revoke API Token?
                </h3>

                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you want to revoke <strong class="text-error-600 dark:text-error-400">{{ $tokenToRevokeName }}</strong>? All applications using this token will immediately lose access.
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelRevoke"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50 cursor-pointer transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="revokeToken"
                        class="inline-flex items-center justify-center rounded-lg bg-error-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-700 shadow-theme-xs transition cursor-pointer"
                    >
                        Revoke Token
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
