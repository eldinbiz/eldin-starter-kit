<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Browser tests use the Laravel TestCase (which has the pest-plugin-browser
| Browsable trait applied via Plugin::uses). Tests in the Browser/ directory
| are automatically detected as browser tests.
|
*/

pest()->extend(TestCase::class)
    ->in('Browser');
