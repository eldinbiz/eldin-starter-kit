<?php

declare(strict_types=1);

use App\Providers\ThemeServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

it('renders form inputs correctly in tailadmin theme', function (): void {
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    // Input
    $renderedInput = Blade::render(
        '<x-ui::input name="email" label="Email Address" required helper="We will never share your email" placeholder="user@example.com" />'
    );
    expect($renderedInput)
        ->toContain('Email Address')
        ->toContain('*')
        ->toContain('user@example.com')
        ->toContain('border-gray-300')
        ->toContain('We will never share your email');

    // Select
    $renderedSelect = Blade::render(
        '<x-ui::select name="role" label="User Role" :options="[\'admin\' => \'Administrator\', \'user\' => \'Standard User\']" />'
    );
    expect($renderedSelect)
        ->toContain('User Role')
        ->toContain('Administrator')
        ->toContain('border-gray-300');

    // Textarea
    $renderedTextarea = Blade::render(
        '<x-ui::textarea name="bio" label="Biography" rows="4" placeholder="About yourself..." />'
    );
    expect($renderedTextarea)
        ->toContain('Biography')
        ->toContain('rows="4"')
        ->toContain('border-gray-300');

    // Checkbox
    $renderedCheckbox = Blade::render(
        '<x-ui::checkbox name="terms" label="I accept terms" description="Read terms carefully" checked />'
    );
    expect($renderedCheckbox)
        ->toContain('I accept terms')
        ->toContain('Read terms carefully')
        ->toContain('checked')
        ->toContain('text-brand-500');

    // Toggle
    $renderedToggle = Blade::render(
        '<x-ui::toggle name="notifications" label="Enable Notifications" checked />'
    );
    expect($renderedToggle)
        ->toContain('Enable Notifications')
        ->toContain('bg-brand-500');
});

it('renders form inputs correctly in shadcn theme', function (): void {
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    // Input
    $renderedInput = Blade::render(
        '<x-ui::input name="email" label="Email Address" required helper="We will never share your email" placeholder="user@example.com" />'
    );
    expect($renderedInput)
        ->toContain('Email Address')
        ->toContain('*')
        ->toContain('user@example.com')
        ->toContain('border-zinc-200')
        ->toContain('We will never share your email');

    // Select
    $renderedSelect = Blade::render(
        '<x-ui::select name="role" label="User Role" :options="[\'admin\' => \'Administrator\', \'user\' => \'Standard User\']" />'
    );
    expect($renderedSelect)
        ->toContain('User Role')
        ->toContain('Administrator')
        ->toContain('border-zinc-200');

    // Textarea
    $renderedTextarea = Blade::render(
        '<x-ui::textarea name="bio" label="Biography" rows="4" placeholder="About yourself..." />'
    );
    expect($renderedTextarea)
        ->toContain('Biography')
        ->toContain('rows="4"')
        ->toContain('border-zinc-200');

    // Checkbox
    $renderedCheckbox = Blade::render(
        '<x-ui::checkbox name="terms" label="I accept terms" description="Read terms carefully" checked />'
    );
    expect($renderedCheckbox)
        ->toContain('I accept terms')
        ->toContain('Read terms carefully')
        ->toContain('checked')
        ->toContain('text-zinc-900');

    // Toggle
    $renderedToggle = Blade::render(
        '<x-ui::toggle name="notifications" label="Enable Notifications" checked />'
    );
    expect($renderedToggle)
        ->toContain('Enable Notifications')
        ->toContain('bg-zinc-900');
});

it('renders modal overlay correctly in both themes', function (): void {
    // TailAdmin modal
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $tailAdminModal = Blade::render(
        '<x-ui::modal id="tailadmin-modal" title="Confirm Action" description="Are you sure?">
            <p>Body Content</p>
            <x-slot:footer>
                <button>Close</button>
            </x-slot:footer>
        </x-ui::modal>'
    );
    expect($tailAdminModal)
        ->toContain('Confirm Action')
        ->toContain('Are you sure?')
        ->toContain('Body Content')
        ->toContain('Close')
        ->toContain('rounded-2xl');

    // shadcn modal
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $shadcnModal = Blade::render(
        '<x-ui::modal id="shadcn-modal" title="Confirm Action" description="Are you sure?">
            <p>Body Content</p>
            <x-slot:footer>
                <button>Close</button>
            </x-slot:footer>
        </x-ui::modal>'
    );
    expect($shadcnModal)
        ->toContain('Confirm Action')
        ->toContain('Are you sure?')
        ->toContain('Body Content')
        ->toContain('Close')
        ->toContain('rounded-lg');
});

it('renders alert banners correctly in both themes', function (): void {
    // TailAdmin alert
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $tailAdminAlert = Blade::render(
        '<x-ui::alert type="success" title="TailAdmin Success" dismissible>Action completed successfully.</x-ui::alert>'
    );
    expect($tailAdminAlert)
        ->toContain('TailAdmin Success')
        ->toContain('Action completed successfully.')
        ->toContain('bg-emerald-50')
        ->toContain('rounded-xl');

    // shadcn alert
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $shadcnAlert = Blade::render(
        '<x-ui::alert type="success" title="shadcn Success" dismissible>Action completed successfully.</x-ui::alert>'
    );
    expect($shadcnAlert)
        ->toContain('shadcn Success')
        ->toContain('Action completed successfully.')
        ->toContain('rounded-lg');
});

it('renders dropdown menus correctly in both themes', function (): void {
    // TailAdmin dropdown
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $tailAdminDropdown = Blade::render(
        '<x-ui::dropdown>
            <x-slot:trigger><button>TailAdmin Options</button></x-slot:trigger>
            <a href="#">Item 1</a>
        </x-ui::dropdown>'
    );
    expect($tailAdminDropdown)
        ->toContain('TailAdmin Options')
        ->toContain('Item 1')
        ->toContain('rounded-2xl');

    // shadcn dropdown
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $shadcnDropdown = Blade::render(
        '<x-ui::dropdown>
            <x-slot:trigger><button>shadcn Options</button></x-slot:trigger>
            <a href="#">Item 1</a>
        </x-ui::dropdown>'
    );
    expect($shadcnDropdown)
        ->toContain('shadcn Options')
        ->toContain('Item 1')
        ->toContain('rounded-md');
});

it('renders tables correctly in both themes', function (): void {
    // TailAdmin table
    Config::set('ui.theme', 'tailadmin');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $tailAdminTable = Blade::render(
        '<x-ui::table>
            <thead>
                <x-ui::table.row>
                    <x-ui::table.header>TailAdmin Name</x-ui::table.header>
                    <x-ui::table.header align="right">Amount</x-ui::table.header>
                </x-ui::table.row>
            </thead>
            <tbody>
                <x-ui::table.row>
                    <x-ui::table.cell>Item A</x-ui::table.cell>
                    <x-ui::table.cell align="right">$100</x-ui::table.cell>
                </x-ui::table.row>
            </tbody>
        </x-ui::table>'
    );
    expect($tailAdminTable)
        ->toContain('TailAdmin Name')
        ->toContain('Amount')
        ->toContain('Item A')
        ->toContain('$100')
        ->toContain('rounded-2xl')
        ->toContain('text-right');

    // shadcn table
    Config::set('ui.theme', 'shadcn');
    $this->app->make(ThemeServiceProvider::class, ['app' => $this->app])->boot();

    $shadcnTable = Blade::render(
        '<x-ui::table>
            <thead>
                <x-ui::table.row>
                    <x-ui::table.header>shadcn Name</x-ui::table.header>
                    <x-ui::table.header align="right">Amount</x-ui::table.header>
                </x-ui::table.row>
            </thead>
            <tbody>
                <x-ui::table.row>
                    <x-ui::table.cell>Item A</x-ui::table.cell>
                    <x-ui::table.cell align="right">$100</x-ui::table.cell>
                </x-ui::table.row>
            </tbody>
        </x-ui::table>'
    );
    expect($shadcnTable)
        ->toContain('shadcn Name')
        ->toContain('Amount')
        ->toContain('Item A')
        ->toContain('$100')
        ->toContain('rounded-lg')
        ->toContain('text-right');
});
