<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BrandTheme;
use PHPUnit\Framework\TestCase;

class BrandThemeTest extends TestCase
{
    public function test_it_defines_exactly_twenty_distinct_brand_themes(): void
    {
        $cases = BrandTheme::cases();
        $this->assertCount(20, $cases);

        $keys = array_map(fn (BrandTheme $theme): string => $theme->value, $cases);
        $this->assertCount(20, array_unique($keys));
    }

    public function test_every_theme_has_valid_hex_dot_color_and_complete_css_variables(): void
    {
        $requiredVariables = [
            '--color-brand-50',
            '--color-brand-100',
            '--color-brand-200',
            '--color-brand-300',
            '--color-brand-400',
            '--color-brand-500',
            '--color-brand-600',
            '--color-brand-700',
        ];

        foreach (BrandTheme::cases() as $theme) {
            $this->assertNotEmpty($theme->label());
            $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $theme->dotColor(), "Theme {$theme->value} has invalid dotColor");

            $variables = $theme->cssVariables();
            foreach ($requiredVariables as $var) {
                $this->assertArrayHasKey($var, $variables, "Theme {$theme->value} is missing variable {$var}");
                $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $variables[$var], "Theme {$theme->value} variable {$var} has invalid hex format");
            }
        }
    }

    public function test_options_method_returns_structured_dictionary_for_all_themes(): void
    {
        $options = BrandTheme::options();
        $this->assertCount(20, $options);
        $this->assertArrayHasKey('brand', $options);
        $this->assertArrayHasKey('emerald', $options);
        $this->assertSame('Emerald Green', $options['emerald']['label']);
        $this->assertSame('#059669', $options['emerald']['dot']);
    }
}
