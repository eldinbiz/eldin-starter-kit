<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Session status check — only EncryptCookies middleware runs.
// No StartSession → no session renewal. No ShareErrorsFromSession → no crash.
Route::middleware(EncryptCookies::class)
    ->get('/session/status', function (Request $request) {
        $cookieName = config('session.cookie');
        $sessionId = $request->cookie($cookieName);

        if (! $sessionId) {
            return response()->json(['authenticated' => false]);
        }

        $session = DB::table(config('session.table'))
            ->where('id', $sessionId)
            ->first();

        if (! $session) {
            return response()->json(['authenticated' => false]);
        }

        $lifetime = (int) config('session.lifetime', 120);
        if ($session->last_activity < now()->subMinutes($lifetime)->timestamp) {
            return response()->json(['authenticated' => false]);
        }

        // Direct user_id column check
        if ($session->user_id !== null) {
            return response()->json(['authenticated' => true]);
        }

        // Fallback: inspect encoded session payload for active login or impersonation keys
        if (! empty($session->payload)) {
            $raw = base64_decode((string) $session->payload, true);
            if ($raw !== false) {
                // Check JSON serialization
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    $impersonateKey = config('laravel-impersonate.session_key', 'impersonated_by');
                    $hasAuth = ! empty($data[$impersonateKey])
                        || collect($data)->keys()->contains(fn ($key) => str_starts_with((string) $key, 'login_'));

                    if ($hasAuth) {
                        return response()->json(['authenticated' => true]);
                    }
                } elseif (str_contains($raw, 'login_') || str_contains($raw, 'impersonated_by')) {
                    // String fallback (PHP serialized format or raw pattern match)
                    return response()->json(['authenticated' => true]);
                }
            }
        }

        return response()->json(['authenticated' => false]);
    })->name('session.status');

