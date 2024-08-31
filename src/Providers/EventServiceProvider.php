<?php

namespace Homeful\Mortgage\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Homeful\Mortgage\Events\PercentMiscellaneousFeesUpdated;
use Homeful\Mortgage\Listeners\UpdateDownPaymentProperties;
use Homeful\Mortgage\Listeners\UpdateMiscellaneousFees;
use Homeful\Mortgage\Events\PercentDownPaymentUpdated;
use Homeful\Mortgage\Listeners\UpdateDownPayment;
use Homeful\Mortgage\Events\ContractPriceUpdated;
use Homeful\Mortgage\Events\MortgageTermUpdated;
use Homeful\Mortgage\Events\DownPaymentUpdated;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Event::listen(MortgageTermUpdated::class, UpdateDownPayment::class);
        Event::listen(PercentDownPaymentUpdated::class, UpdateDownPayment::class);
        Event::listen(PercentMiscellaneousFeesUpdated::class, UpdateMiscellaneousFees::class);
        Event::listen(ContractPriceUpdated::class, UpdateDownPayment::class);
        Event::listen(ContractPriceUpdated::class, UpdateMiscellaneousFees::class);
        Event::listen(DownPaymentUpdated::class, UpdateDownPaymentProperties::class);
    }
}
