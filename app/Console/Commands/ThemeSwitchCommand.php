<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\select;

final class ThemeSwitchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ui:theme {theme? : The UI theme to activate (tailadmin or shadcn)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch the active UI theme, update .env, and clear caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $validThemes = (array) config('ui.themes', [
            'tailadmin' => [
                'name' => 'TailAdmin',
                'description' => 'Modern Tailwind admin dashboard with rounded curves and rich gradients',
            ],
            'shadcn' => [
                'name' => 'shadcn',
                'description' => 'Minimalist, high-density UI inspired by shadcn/ui and Radix',
            ],
        ]);

        $theme = $this->argument('theme');

        if (! $theme) {
            $options = [];
            foreach ($validThemes as $key => $meta) {
                $name = $meta['name'] ?? ucfirst((string) $key);
                $desc = $meta['description'] ?? '';
                $options[$key] = "{$name} - {$desc}";
            }

            $currentTheme = (string) config('ui.theme', 'tailadmin');
            $theme = select(
                label: 'Select the UI theme to activate:',
                options: $options,
                default: array_key_exists($currentTheme, $options) ? $currentTheme : 'tailadmin',
            );
        }

        $theme = strtolower((string) $theme);

        if (! array_key_exists($theme, $validThemes)) {
            $validList = implode(', ', array_keys($validThemes));
            $this->error("Invalid theme '{$theme}'. Supported themes: {$validList}");

            return self::FAILURE;
        }

        $this->updateEnvironmentFile('APP_UI_THEME', $theme);

        $this->callSilent('view:clear');
        $this->callSilent('config:clear');

        $themeName = $validThemes[$theme]['name'] ?? ucfirst($theme);
        $this->components->info("Theme successfully switched to [{$themeName}] ({$theme}).");
        $this->components->twoColumnDetail('Environment Key', "APP_UI_THEME={$theme}");
        $this->components->twoColumnDetail('Views Namespace', "resources/views/themes/{$theme}");
        $this->components->twoColumnDetail('Component Prefix', '<x-ui::* />');

        return self::SUCCESS;
    }

    /**
     * Update the given key in the .env file.
     */
    private function updateEnvironmentFile(string $key, string $value): void
    {
        $envPath = $this->laravel->environmentFilePath();

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        if ($content === false) {
            return;
        }

        $pattern = "/^{$key}=(.*)$/m";

        if (preg_match($pattern, $content)) {
            $updated = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $updated = rtrim($content) . "\n{$key}={$value}\n";
        }

        if ($updated !== null) {
            file_put_contents($envPath, $updated);
        }
    }
}
