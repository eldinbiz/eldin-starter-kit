<?php

declare(strict_types=1);

namespace Tests;

use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Pest\Browser\Api\ArrayablePendingAwaitablePage;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Browsable;
use Pest\Browser\Playwright\Page as PlaywrightPage;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use Browsable {
        Browsable::visit as browsableVisit;
    }

    public static ?PendingAwaitablePage $activeBrowserPage = null;

    /**
     * @template TUrl of array<int, string>|string
     *
     * @param  TUrl  $url
     * @param  array<string, mixed>  $options
     * @return (TUrl is array<int, string> ? ArrayablePendingAwaitablePage : PendingAwaitablePage)
     */
    public function visit(array|string $url, array $options = []): ArrayablePendingAwaitablePage|PendingAwaitablePage
    {
        $page = $this->browsableVisit($url, $options);

        if ($page instanceof PendingAwaitablePage) {
            self::$activeBrowserPage = $page;
        }

        return $page;
    }

    /**
     * Get the categorized snapshot subdirectory name derived from the test class name.
     */
    public function getBrowserSnapshotSubdirectory(?string $customClassName = null): string
    {
        $className = $customClassName ?? (method_exists($this, 'getPrintableTestCaseName')
            ? $this::getPrintableTestCaseName()
            : static::class);

        $className = ltrim((string) preg_replace('/^P\\\\/', '', $className), '\\');
        $subDir = str_replace(['\\', '/'], '-', $className);
        $subDir = (string) preg_replace('/[^a-zA-Z0-9_-]/', '-', $subDir);

        return trim((string) preg_replace('/-+/', '-', $subDir), '-');
    }

    /**
     * Capture the final snapshot of the active browser page.
     */
    public function captureBrowserSnapshot(?string $targetDir = null): ?string
    {
        if (self::$activeBrowserPage === null) {
            return null;
        }

        try {
            $pendingPageRef = new ReflectionProperty(PendingAwaitablePage::class, 'waitablePage');
            $pendingPageRef->setAccessible(true);
            /** @var AwaitableWebpage|null $waitablePage */
            $waitablePage = $pendingPageRef->getValue(self::$activeBrowserPage);

            if (! $waitablePage instanceof AwaitableWebpage) {
                return null;
            }

            $awaitableRef = new ReflectionProperty(AwaitableWebpage::class, 'page');
            $awaitableRef->setAccessible(true);
            /** @var PlaywrightPage $playwrightPage */
            $playwrightPage = $awaitableRef->getValue($waitablePage);

            $method = new ReflectionMethod($playwrightPage, 'screenshotBinary');
            $method->setAccessible(true);
            $binary = $method->invoke($playwrightPage, true);

            if (! is_string($binary) || $binary === '') {
                return null;
            }

            $subDir = $this->getBrowserSnapshotSubdirectory();
            $targetBaseDir = $targetDir ?? (getenv('BROWSER_SNAPSHOT_DIR') ?: base_path('tests/Browser/Screenshots'));
            $targetFullDir = str_ends_with(str_replace('\\', '/', $targetBaseDir), '/'.$subDir)
                ? $targetBaseDir
                : rtrim($targetBaseDir, '/\\').DIRECTORY_SEPARATOR.$subDir;

            if (! is_dir($targetFullDir)) {
                @mkdir($targetFullDir, 0755, true);
            }

            $testName = str_replace('__pest_evaluable_', '', $this->name());
            $testName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName);
            $testName = trim((string) preg_replace('/_+/', '_', (string) $testName), '_');
            $filePath = rtrim($targetFullDir, '/\\').DIRECTORY_SEPARATOR.$testName.'.png';

            file_put_contents($filePath, (string) base64_decode($binary, true));

            $screenshotsBaseDir = base_path('tests/Browser/Screenshots');
            $screenshotsFullDir = rtrim($screenshotsBaseDir, '/\\').DIRECTORY_SEPARATOR.$subDir;
            if (! is_dir($screenshotsFullDir)) {
                @mkdir($screenshotsFullDir, 0755, true);
            }
            file_put_contents(rtrim($screenshotsFullDir, '/\\').DIRECTORY_SEPARATOR.$testName.'.png', (string) base64_decode($binary, true));

            return $filePath;
        } catch (Throwable) {
            return null;
        } finally {
            self::$activeBrowserPage = null;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingService::class)->flushRuntimeCache();
        app(FeatureService::class)->flush();

        if (method_exists($this, '__addAfterEach')) {
            $this->__addAfterEach(function (): void {
                $this->captureBrowserSnapshot();
            });
        }
    }

    protected function tearDown(): void
    {
        self::$activeBrowserPage = null;

        app(SettingService::class)->flushRuntimeCache();
        app(FeatureService::class)->flush();

        parent::tearDown();
    }
}
