<?php

namespace Homeful\Mortgage\Listeners;

use Homeful\Mortgage\Events\MortgageUpdated;
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;

class UpdateMiscellaneousFees
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Homeful\Payment\Exceptions\MaxCycleBreached
     * @throws \Homeful\Payment\Exceptions\MinTermBreached
     */
    public function handle(MortgageUpdated $event): void
    {
        with($event->mortgage, function (Mortgage $mortgage) {
            $contract_price = $mortgage->getContractPrice()->inclusive() instanceof Money
                ? $mortgage->getContractPrice()->inclusive()
                : null;

            $mortgage->setMiscellaneousFees(new Price($contract_price->multipliedBy($mortgage->getPercentMiscellaneousFees())));
        });
    }
}
