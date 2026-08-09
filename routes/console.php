<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune expired Sanctum API tokens daily (retaining revoked/expired tokens for 24 hours)
Schedule::command('sanctum:prune-expired --hours=24')->daily();
