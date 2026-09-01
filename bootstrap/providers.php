<?php

use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\ThemeServiceProvider;
use Livewire\LivewireServiceProvider;

return [
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    LivewireServiceProvider::class,
    ThemeServiceProvider::class,
];
