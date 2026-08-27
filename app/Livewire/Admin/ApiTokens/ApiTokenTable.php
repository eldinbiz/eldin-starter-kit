<?php

declare(strict_types=1);

namespace App\Livewire\Admin\ApiTokens;

use App\Actions\ApiTokens\CreateApiTokenAction;
use App\Actions\ApiTokens\RevokeApiTokenAction;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Livewire\DataTables\DataTableComponent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

final class ApiTokenTable extends DataTableComponent
{
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

    /**
     * Initialize all abilities from endpoints registry.
     */
    private function initAllAbilities(): void
    {
        $this->selectedAbilities = array_map(
            fn (array $ep): string => (string) $ep['ability'],
            ApiTokenController::AVAILABLE_ENDPOINTS
        );
        $this->selectAllAbilities = true;
    }

    /**
     * Base Eloquent query for system API tokens.
     */
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

    /**
     * Toggle selection of all abilities in create modal.
     */
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

    /**
     * Create a new API token.
     */
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

    /**
     * Open scopes inspection modal.
     */
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

    /**
     * Prompt token revocation confirmation.
     */
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

    /**
     * Execute revocation of token.
     */
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

    public function render(): View
    {
        return view('livewire.admin.api-tokens.api-token-table');
    }
}
