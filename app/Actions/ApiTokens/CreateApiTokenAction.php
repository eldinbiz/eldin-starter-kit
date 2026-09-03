<?php

declare(strict_types=1);

namespace App\Actions\ApiTokens;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

final class CreateApiTokenAction
{
    /**
     * Create a system bot and issue a new Sanctum API token.
     *
     * @param array{
     *     name: string,
     *     expiry?: string|null,
     *     abilities?: array<int, string>|null
     * } $data
     */
    public function execute(array $data, ?User $actingUser = null): NewAccessToken
    {
        $name = (string) $data['name'];

        $expiresAt = match ($data['expiry'] ?? null) {
            '30' => now()->addDays(30),
            '60' => now()->addDays(60),
            '90' => now()->addDays(90),
            '365' => now()->addYear(),
            default => null,
        };

        $selectedAbilities = (array) ($data['abilities'] ?? []);
        $abilities = (empty($selectedAbilities) || in_array('*', $selectedAbilities, true))
            ? ['*']
            : array_values(array_unique($selectedAbilities));

        $botEmail = 'bot.'.Str::slug($name).'@system.local';

        $bot = User::firstOrCreate(
            ['email' => $botEmail],
            [
                'name' => $name.' (System Bot)',
                'password' => Hash::make(Str::random(32)),
                'is_system' => true,
                'auth_method' => 'local',
            ]
        );

        if (! $bot->is_system) {
            $bot->update(['is_system' => true]);
        }

        $activeCompany = $actingUser?->getActiveCompany() ?? Company::first();

        if ($activeCompany) {
            if (! $bot->companies()->where('company_id', $activeCompany->id)->exists()) {
                $bot->companies()->attach($activeCompany->id, ['status' => 'active']);
            }
        }

        return $bot->createToken($name, $abilities, $expiresAt);
    }
}
