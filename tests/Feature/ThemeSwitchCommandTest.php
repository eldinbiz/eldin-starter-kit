<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

it('switches to shadcn theme via artisan command', function (): void {
    $this->artisan('ui:theme', ['theme' => 'shadcn'])
        ->expectsOutputToContain('Theme successfully switched to [shadcn/ui] (shadcn)')
        ->assertSuccessful();
});

it('switches to tailadmin theme via artisan command', function (): void {
    $this->artisan('ui:theme', ['theme' => 'tailadmin'])
        ->expectsOutputToContain('Theme successfully switched to [TailAdmin] (tailadmin)')
        ->assertSuccessful();
});

it('rejects invalid theme argument with error code', function (): void {
    $this->artisan('ui:theme', ['theme' => 'nonexistent_theme'])
        ->expectsOutputToContain("Invalid theme 'nonexistent_theme'")
        ->assertFailed();
});
