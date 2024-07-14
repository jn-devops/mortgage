<?php

namespace Homeful\Mortgage\Providers;

use Homeful\Mortgage\Events\{ContractPriceUpdated, DownPaymentUpdated, MortgageTermUpdated, PercentMiscellaneousFeesUpdated, PercentDownPaymentUpdated};
use Homeful\Mortgage\Listeners\{UpdateMiscellaneousFees, UpdateDownPayment, UpdateDownPaymentProperties};
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
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
