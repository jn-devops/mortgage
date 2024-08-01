<?php

namespace Homeful\Mortgage\Listeners;

use Homeful\Mortgage\Events\MortgageUpdated;
use Homeful\Mortgage\Mortgage;

class UpdateDownPaymentProperties
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(MortgageUpdated $event): void
    {
        with($event->mortgage, function (Mortgage $mortgage) {
            $float_contract_price = $mortgage->getContractPrice()->inclusive()->getAmount()->toFloat();
            $float_principal = $mortgage->getDownPayment()->getPrincipal()->inclusive()->getAmount()->toFloat();
            $mortgage
                ->setPercentDownPayment($float_principal / $float_contract_price, false)
                ->setDownPaymentTerm($mortgage->getDownPayment()->getTerm()->value, false);
        });
    }
}
