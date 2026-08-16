<?php

declare(strict_types=1);

namespace App\Enums;

enum BrandTheme: string
{
    case Brand = 'brand';
    case Indigo = 'indigo';
    case Blue = 'blue';
    case Sky = 'sky';
    case Cyan = 'cyan';
    case Teal = 'teal';
    case Emerald = 'emerald';
    case Green = 'green';
    case Lime = 'lime';
    case Amber = 'amber';
    case Orange = 'orange';
    case Red = 'red';
    case Rose = 'rose';
    case Pink = 'pink';
    case Fuchsia = 'fuchsia';
    case Purple = 'purple';
    case Violet = 'violet';
    case Slate = 'slate';
    case Zinc = 'zinc';
    case Stone = 'stone';

    /**
     * Human-readable label for the theme.
     */
    public function label(): string
    {
        return match ($this) {
            self::Brand => 'TailAdmin Brand (Default)',
            self::Indigo => 'Classic Indigo',
            self::Blue => 'Royal Blue',
            self::Sky => 'Sky Azure',
            self::Cyan => 'Electric Cyan',
            self::Teal => 'Deep Teal',
            self::Emerald => 'Emerald Green',
            self::Green => 'Forest Green',
            self::Lime => 'Fresh Lime',
            self::Amber => 'Amber Gold',
            self::Orange => 'Sunset Orange',
            self::Red => 'Crimson Red',
            self::Rose => 'Velvet Rose',
            self::Pink => 'Hot Pink',
            self::Fuchsia => 'Magenta Fuchsia',
            self::Purple => 'Royal Purple',
            self::Violet => 'Electric Violet',
            self::Slate => 'Slate Steel',
            self::Zinc => 'Zinc Modern',
            self::Stone => 'Warm Stone',
        };
    }

    /**
     * Primary 500 hex color for UI swatch preview.
     */
    public function dotColor(): string
    {
        return match ($this) {
            self::Brand => '#465fff',
            self::Indigo => '#4f46e5',
            self::Blue => '#2563eb',
            self::Sky => '#0284c7',
            self::Cyan => '#0891b2',
            self::Teal => '#0d9488',
            self::Emerald => '#059669',
            self::Green => '#16a34a',
            self::Lime => '#65a30d',
            self::Amber => '#d97706',
            self::Orange => '#ea580c',
            self::Red => '#dc2626',
            self::Rose => '#e11d48',
            self::Pink => '#db2777',
            self::Fuchsia => '#c026d3',
            self::Purple => '#9333ea',
            self::Violet => '#7c3aed',
            self::Slate => '#64748b',
            self::Zinc => '#71717a',
            self::Stone => '#78716c',
        };
    }

    /**
     * Complete CSS custom property dictionary for Tailwind v4 `@theme` tokens.
     *
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        return match ($this) {
            self::Brand => [
                '--color-brand-50' => '#ecf3ff',
                '--color-brand-100' => '#dde9ff',
                '--color-brand-200' => '#c2d6ff',
                '--color-brand-300' => '#9cb9ff',
                '--color-brand-400' => '#7592ff',
                '--color-brand-500' => '#465fff',
                '--color-brand-600' => '#3641f5',
                '--color-brand-700' => '#2a31d8',
            ],
            self::Indigo => [
                '--color-brand-50' => '#eef2ff',
                '--color-brand-100' => '#e0e7ff',
                '--color-brand-200' => '#c7d2fe',
                '--color-brand-300' => '#a5b4fc',
                '--color-brand-400' => '#818cf8',
                '--color-brand-500' => '#4f46e5',
                '--color-brand-600' => '#4338ca',
                '--color-brand-700' => '#3730a3',
            ],
            self::Blue => [
                '--color-brand-50' => '#eff6ff',
                '--color-brand-100' => '#dbeafe',
                '--color-brand-200' => '#bfdbfe',
                '--color-brand-300' => '#93c5fd',
                '--color-brand-400' => '#60a5fa',
                '--color-brand-500' => '#2563eb',
                '--color-brand-600' => '#1d4ed8',
                '--color-brand-700' => '#1e40af',
            ],
            self::Sky => [
                '--color-brand-50' => '#f0f9ff',
                '--color-brand-100' => '#e0f2fe',
                '--color-brand-200' => '#bae6fd',
                '--color-brand-300' => '#7dd3fc',
                '--color-brand-400' => '#38bdf8',
                '--color-brand-500' => '#0284c7',
                '--color-brand-600' => '#0369a1',
                '--color-brand-700' => '#075985',
            ],
            self::Cyan => [
                '--color-brand-50' => '#ecfeff',
                '--color-brand-100' => '#cffafe',
                '--color-brand-200' => '#a5f3fc',
                '--color-brand-300' => '#67e8f9',
                '--color-brand-400' => '#22d3ee',
                '--color-brand-500' => '#0891b2',
                '--color-brand-600' => '#0e7490',
                '--color-brand-700' => '#155e75',
            ],
            self::Teal => [
                '--color-brand-50' => '#f0fdfa',
                '--color-brand-100' => '#ccfbf1',
                '--color-brand-200' => '#99f6e4',
                '--color-brand-300' => '#5eead4',
                '--color-brand-400' => '#2dd4bf',
                '--color-brand-500' => '#0d9488',
                '--color-brand-600' => '#0f766e',
                '--color-brand-700' => '#115e59',
            ],
            self::Emerald => [
                '--color-brand-50' => '#ecfdf5',
                '--color-brand-100' => '#d1fae5',
                '--color-brand-200' => '#a7f3d0',
                '--color-brand-300' => '#6ee7b7',
                '--color-brand-400' => '#34d399',
                '--color-brand-500' => '#059669',
                '--color-brand-600' => '#047857',
                '--color-brand-700' => '#065f46',
            ],
            self::Green => [
                '--color-brand-50' => '#f0fdf4',
                '--color-brand-100' => '#dcfce7',
                '--color-brand-200' => '#bbf7d0',
                '--color-brand-300' => '#86efac',
                '--color-brand-400' => '#4ade80',
                '--color-brand-500' => '#16a34a',
                '--color-brand-600' => '#15803d',
                '--color-brand-700' => '#166534',
            ],
            self::Lime => [
                '--color-brand-50' => '#f7fee7',
                '--color-brand-100' => '#ecfccb',
                '--color-brand-200' => '#d9f99d',
                '--color-brand-300' => '#bef264',
                '--color-brand-400' => '#a3e635',
                '--color-brand-500' => '#65a30d',
                '--color-brand-600' => '#4d7c0f',
                '--color-brand-700' => '#3f6212',
            ],
            self::Amber => [
                '--color-brand-50' => '#fffbeb',
                '--color-brand-100' => '#fef3c7',
                '--color-brand-200' => '#fde68a',
                '--color-brand-300' => '#fcd34d',
                '--color-brand-400' => '#fbbf24',
                '--color-brand-500' => '#d97706',
                '--color-brand-600' => '#b45309',
                '--color-brand-700' => '#92400e',
            ],
            self::Orange => [
                '--color-brand-50' => '#fff7ed',
                '--color-brand-100' => '#ffedd5',
                '--color-brand-200' => '#fed7aa',
                '--color-brand-300' => '#fdba74',
                '--color-brand-400' => '#fb923c',
                '--color-brand-500' => '#ea580c',
                '--color-brand-600' => '#c2410c',
                '--color-brand-700' => '#9a3412',
            ],
            self::Red => [
                '--color-brand-50' => '#fef2f2',
                '--color-brand-100' => '#fee2e2',
                '--color-brand-200' => '#fecaca',
                '--color-brand-300' => '#fca5a5',
                '--color-brand-400' => '#f87171',
                '--color-brand-500' => '#dc2626',
                '--color-brand-600' => '#b91c1c',
                '--color-brand-700' => '#991b1b',
            ],
            self::Rose => [
                '--color-brand-50' => '#fff1f2',
                '--color-brand-100' => '#ffe4e6',
                '--color-brand-200' => '#fecdd3',
                '--color-brand-300' => '#fda4af',
                '--color-brand-400' => '#fb7185',
                '--color-brand-500' => '#e11d48',
                '--color-brand-600' => '#be123c',
                '--color-brand-700' => '#9f1239',
            ],
            self::Pink => [
                '--color-brand-50' => '#fdf2f8',
                '--color-brand-100' => '#fce7f3',
                '--color-brand-200' => '#fbcfe8',
                '--color-brand-300' => '#f9a8d4',
                '--color-brand-400' => '#f472b6',
                '--color-brand-500' => '#db2777',
                '--color-brand-600' => '#be185d',
                '--color-brand-700' => '#9d174d',
            ],
            self::Fuchsia => [
                '--color-brand-50' => '#fdf4ff',
                '--color-brand-100' => '#fae8ff',
                '--color-brand-200' => '#f5d0fe',
                '--color-brand-300' => '#f0abfc',
                '--color-brand-400' => '#e879f9',
                '--color-brand-500' => '#c026d3',
                '--color-brand-600' => '#a21caf',
                '--color-brand-700' => '#86198f',
            ],
            self::Purple => [
                '--color-brand-50' => '#faf5ff',
                '--color-brand-100' => '#f3e8ff',
                '--color-brand-200' => '#e9d5ff',
                '--color-brand-300' => '#d8b4fe',
                '--color-brand-400' => '#c084fc',
                '--color-brand-500' => '#9333ea',
                '--color-brand-600' => '#7e22ce',
                '--color-brand-700' => '#6b21a8',
            ],
            self::Violet => [
                '--color-brand-50' => '#f5f3ff',
                '--color-brand-100' => '#ede9fe',
                '--color-brand-200' => '#ddd6fe',
                '--color-brand-300' => '#c4b5fd',
                '--color-brand-400' => '#a78bfa',
                '--color-brand-500' => '#7c3aed',
                '--color-brand-600' => '#6d28d9',
                '--color-brand-700' => '#5b21b6',
            ],
            self::Slate => [
                '--color-brand-50' => '#f8fafc',
                '--color-brand-100' => '#f1f5f9',
                '--color-brand-200' => '#e2e8f0',
                '--color-brand-300' => '#cbd5e1',
                '--color-brand-400' => '#94a3b8',
                '--color-brand-500' => '#64748b',
                '--color-brand-600' => '#475569',
                '--color-brand-700' => '#334155',
            ],
            self::Zinc => [
                '--color-brand-50' => '#fafafa',
                '--color-brand-100' => '#f4f4f5',
                '--color-brand-200' => '#e4e4e7',
                '--color-brand-300' => '#d4d4d8',
                '--color-brand-400' => '#a1a1aa',
                '--color-brand-500' => '#71717a',
                '--color-brand-600' => '#52525b',
                '--color-brand-700' => '#3f3f46',
            ],
            self::Stone => [
                '--color-brand-50' => '#fafaf9',
                '--color-brand-100' => '#f5f5f4',
                '--color-brand-200' => '#e7e5e4',
                '--color-brand-300' => '#d6d3d1',
                '--color-brand-400' => '#a8a29e',
                '--color-brand-500' => '#78716c',
                '--color-brand-600' => '#57534e',
                '--color-brand-700' => '#44403c',
            ],
        };
    }

    /**
     * Get associative array of all options for UI select or swatch grids.
     *
     * @return array<string, array{key: string, label: string, dot: string, variables: array<string, string>}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = [
                'key' => $case->value,
                'label' => $case->label(),
                'dot' => $case->dotColor(),
                'variables' => $case->cssVariables(),
            ];
        }

        return $options;
    }
}
