<?php

namespace Homeful\Mortgage\Listeners;

use Homeful\Mortgage\Events\MortgageUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Mortgage\Mortgage;
use Brick\Math\RoundingMode;
use Homeful\Payment\Payment;

class UpdateDownPaymentProperties
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param MortgageUpdated $event
     * @return void
     */
    public function handle(MortgageUpdated $event): void
    {
        with($event->mortgage, function (Mortgage $mortgage) {
            $float_contract_price = $mortgage->getContractPrice()->inclusive()->getAmount()->toFloat();
            $float_principal = $mortgage->getDownPayment()->getPrincipal()->inclusive()->getAmount()->toFloat();
            $mortgage
                ->setPercentDownPayment($float_principal/$float_contract_price, false)
                ->setDownPaymentTerm($mortgage->getDownPayment()->getTerm()->value, false);
        });
    }
}
