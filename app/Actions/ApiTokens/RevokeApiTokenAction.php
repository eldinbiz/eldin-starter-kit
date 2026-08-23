<?php

declare(strict_types=1);

namespace App\Actions\ApiTokens;

use Laravel\Sanctum\PersonalAccessToken;

final class RevokeApiTokenAction
{
    /**
     * Revoke / delete a Sanctum personal access token by ID or instance.
     */
    public function execute(int|string|PersonalAccessToken $token): bool
    {
        $accessToken = $token instanceof PersonalAccessToken
            ? $token
            : PersonalAccessToken::findOrFail($token);

        return (bool) $accessToken->delete();
    }
}
