<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokensDataTable extends DataTable
{
    public function query(): Builder
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereHasMorph('tokenable', [User::class], function (Builder $q): void {
                $q->where('is_system', true);
            })
            ->with('tokenable');
    }

    public function columns(): array
    {
        return [
            Column::make('tokenable')
                ->label('Service Account')
                ->searchable(),
            Column::make('name')
                ->label('Token Name')
                ->sortable()
                ->searchable(),
            Column::make('abilities')
                ->label('Scopes / Permissions'),
            Column::make('expires_at')
                ->label('Expires')
                ->sortable(),
            Column::make('last_used_at')
                ->label('Last Used')
                ->sortable(),
            Column::make('created_at')
                ->label('Created')
                ->sortable(),
            Column::make('actions')
                ->label('Actions')
                ->align('right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::make('search')
                ->type('text')
                ->label('Search')
                ->placeholder('Search by name, service account...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->where('name', 'like', "%{$value}%")
                            ->orWhereHasMorph('tokenable', [User::class], function (Builder $sq) use ($value): void {
                                $sq->where('name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%");
                            });
                    });
                }),

            Filter::make('status')
                ->type('select')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'expiring_soon' => 'Expiring Soon (<= 7 days)',
                    'expired' => 'Expired',
                    'never' => 'Never Expires',
                ])
                ->apply(function (Builder $q, string $value): void {
                    match ($value) {
                        'active' => $q->where(fn (Builder $sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                        'expiring_soon' => $q->whereNotNull('expires_at')->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(7)),
                        'expired' => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                        'never' => $q->whereNull('expires_at'),
                        default => null,
                    };
                }),
        ];
    }

    public function renderRow(mixed $row): string
    {
        /** @var PersonalAccessToken $row */
        return view('admin.api-tokens._row', [
            'token' => $row,
            'user' => $row->tokenable,
        ])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        /** @var PersonalAccessToken $row */
        return view('admin.api-tokens._mobile_row', [
            'token' => $row,
            'user' => $row->tokenable,
        ])->render();
    }
}
