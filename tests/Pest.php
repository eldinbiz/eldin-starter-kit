<?php

declare(strict_types=1);

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\Request;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Eager Application Bootstrap (for Browser Tests)
|--------------------------------------------------------------------------
|
| The pest-plugin-browser fires its __markAsBrowserTest() filter during
| test discovery, which calls config() before createApplication() runs
| in setUp(). We force-boot the Laravel container at file-level so that
| the config() facade is available when the filter executes.
|
*/

foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array'] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

ini_set('memory_limit', '1024M');

$app = require __DIR__.'/../bootstrap/app.php';

$app->bootstrapWith([
    LoadEnvironmentVariables::class,
    LoadConfiguration::class,
]);

$app->instance('request', Request::create('/'));

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature', 'Browser', '../app-modules/*/tests');

// Ensure Browsable trait is stripped from Pest's dynamic test classes
// so that Tests\TestCase::visit() can intercept visit() and track active browser page.
try {
    $usesProp = new \ReflectionProperty(\Pest\Repositories\TestRepository::class, 'uses');
    $usesProp->setAccessible(true);
    $uses = $usesProp->getValue(\Pest\TestSuite::getInstance()->tests);
    foreach ($uses as $path => &$config) {
        if (isset($config[0]) && is_array($config[0])) {
            $config[0] = array_values(array_filter($config[0], fn ($c) => $c !== \Pest\Browser\Browsable::class));
        }
    }
    unset($config);
    $usesProp->setValue(\Pest\TestSuite::getInstance()->tests, $uses);
} catch (\Throwable) {
    //
}

// Automatic hook to capture the final rendered page snapshot at the end of every browser test case
uses()->afterEach(function (): void {
    if (method_exists($this, 'captureBrowserSnapshot')) {
        $this->captureBrowserSnapshot();
    }
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
