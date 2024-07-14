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

class UpdateDownPayment
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
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Homeful\Payment\Exceptions\MaxCycleBreached
     * @throws \Homeful\Payment\Exceptions\MinTermBreached
     */
    public function handle(MortgageUpdated $event): void
    {
        with($event->mortgage, function (Mortgage $mortgage) {
            $percent_dp = $mortgage->getPercentDownPayment();
            $partial_tcp = $mortgage->getContractPrice()->inclusive()->multipliedBy($percent_dp, roundingMode: RoundingMode::CEILING)->getAmount()->toFloat();
//            $deductible_cash_outs = $mortgage->getCashOuts()->sum(function(CashOut $cash_out) {
//                return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
//            });
            $deductible_cash_outs = 0;
            $dp = $mortgage->isPromotional() ? 0.0 : $partial_tcp - $deductible_cash_outs;
            $down_payment = (new Payment)
                ->setPrincipal($dp)
                ->setTerm(new Term($mortgage->getDownPaymentTerm(), Cycle::Monthly))
                ->setInterestRate(0);
            $mortgage->setDownPayment($down_payment);
        });
    }
}
