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

        $expired = ! $session
            || $session->user_id === null
            || $session->last_activity < now()->subMinutes((int) config('session.lifetime'))->timestamp;

        return response()->json(['authenticated' => ! $expired]);
    })->name('session.status');
