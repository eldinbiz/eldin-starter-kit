<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $theme = (string) config('ui.theme', 'tailadmin');
        $fallback = (string) config('ui.fallback', 'tailadmin');

        $themePath = resource_path("views/themes/{$theme}");

        // Graceful fallback if configured theme directory does not exist
        if (! is_dir($themePath)) {
            $theme = $fallback;
            $themePath = resource_path("views/themes/{$theme}");
        }

        // 1. Register View Namespace: 'theme' => resources/views/themes/{theme}
        if (is_dir($themePath)) {
            View::replaceNamespace('theme', $themePath);
            $finder = View::getFinder();
            if (method_exists($finder, 'flush')) {
                $finder->flush();
            }
        }

        // 2. Register Blade Anonymous Component Namespace: <x-ui::*> => theme::components.*
        Blade::anonymousComponentNamespace('theme::components', 'ui');

        // 3. Register @themeIs('theme_name') Blade directive
        Blade::if('themeIs', static fn (string $name): bool => $theme === $name);

        // 4. Share active theme metadata globally and with view composers
        $themeConfig = config("ui.themes.{$theme}", [
            'name' => ucfirst($theme),
            'font' => 'Outfit',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap',
        ]);

        View::share('activeUiTheme', $theme);
        View::share('activeThemeConfig', $themeConfig);

        View::composer(['layouts.app', 'layouts.auth', 'theme::*'], static function ($view) use ($theme, $themeConfig): void {
            $view->with([
                'activeUiTheme' => $theme,
                'activeThemeConfig' => $themeConfig,
            ]);
        });
    }
}
