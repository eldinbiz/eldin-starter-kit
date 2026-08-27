<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\PurchaseRequests\Livewire\PurchaseRequestTable;
use Modules\PurchaseRequests\Livewire\PurchaseRequestWizard;

class PurchaseRequestsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Livewire::component('purchase-requests.purchase-request-wizard', PurchaseRequestWizard::class);
        Livewire::component('purchase-requests.purchase-request-table', PurchaseRequestTable::class);
    }
}
