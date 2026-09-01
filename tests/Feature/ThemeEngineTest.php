<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

it('defaults to tailadmin theme configuration', function (): void {
    expect(config('ui.theme'))->toBe('tailadmin');
    expect(config('ui.fallback'))->toBe('tailadmin');
    expect(config('ui.themes.tailadmin.name'))->toBe('TailAdmin');
    expect(config('ui.themes.shadcn.name'))->toBe('shadcn/ui');
});

it('renders dashboard with tailadmin theme by default', function (): void {
    $user = User::factory()->create();

    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('sidebar', false);
    $response->assertViewHas('activeUiTheme', 'tailadmin');
});

it('renders dashboard with shadcn theme when configured', function (): void {
    $user = User::factory()->create();

    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('commandPaletteOpen', false);
    $response->assertViewHas('activeUiTheme', 'shadcn');
});

it('renders login page under tailadmin and shadcn themes', function (): void {
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();
    $this->get(route('login'))->assertSuccessful();

    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();
    $this->get(route('login'))->assertSuccessful();
});

it('falls back gracefully to tailadmin when an invalid theme is configured', function (): void {
    $user = User::factory()->create();

    Config::set('ui.theme', 'non_existent_theme');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertViewHas('activeUiTheme', 'tailadmin');
});

it('renders abstract ui components in active theme', function (): void {
    // TailAdmin component rendering
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $renderedTailAdminButton = Blade::render('<x-ui::button variant="primary">TailAdmin Click</x-ui::button>', deleteCachedView: true);
    expect($renderedTailAdminButton)->toContain('bg-brand-500');

    $renderedTailAdminCard = Blade::render('<x-ui::card title="TailAdmin Title">TailAdmin Content</x-ui::card>', deleteCachedView: true);
    expect($renderedTailAdminCard)->toContain('rounded-2xl');

    // shadcn component rendering
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $renderedShadcnButton = Blade::render('<x-ui::button variant="default">Shadcn Click</x-ui::button>', deleteCachedView: true);
    expect($renderedShadcnButton)->toContain('bg-zinc-900');

    $renderedShadcnCard = Blade::render('<x-ui::card title="Shadcn Title">Shadcn Content</x-ui::card>', deleteCachedView: true);
    expect($renderedShadcnCard)->toContain('rounded-lg');
});
