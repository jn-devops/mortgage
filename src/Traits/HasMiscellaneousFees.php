<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Events\PercentMiscellaneousFeesUpdated;
use Homeful\Mortgage\Events\MiscellaneousFeesUpdated;
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;

trait HasMiscellaneousFees
{
    protected float $percent_miscellaneous_fees;

    protected Price $miscellaneous_fees;

    public function getPercentMiscellaneousFees(): float
    {
        return $this->percent_miscellaneous_fees ?? 0.0;
    }

    /**
     * @return Mortgage|HasMiscellaneousFees
     */
    public function setPercentMiscellaneousFees(float $percent_miscellaneous_fees): self
    {
        $this->percent_miscellaneous_fees = $percent_miscellaneous_fees;
        PercentMiscellaneousFeesUpdated::dispatch($this);

        return $this;
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getMiscellaneousFees(): Price
    {
        return $this->miscellaneous_fees ?? new Price(Money::of(0, 'PHP'));
    }

    /**
     * @return Mortgage|HasMiscellaneousFees
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function setMiscellaneousFees(Price|float $miscellaneous_fees): self
    {
        $this->miscellaneous_fees = $miscellaneous_fees instanceof Price
            ? $miscellaneous_fees
            : new Price(Money::of($miscellaneous_fees, 'PHP'));
        MiscellaneousFeesUpdated::dispatch($this);
        $this->updatePercentMiscellaneousFees();

        return $this;
    }

    /**
     * partial miscellaneous fees = miscellaneous fees x percent down payment
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getPartialMiscellaneousFees(): Price
    {
        $percent_dp = $this->getPercentDownPayment();
        $partial_mf = $this->getMiscellaneousFees()->inclusive()->multipliedBy($percent_dp);

        return new Price($partial_mf);
    }

    /**
     * balance miscellaneous fees = miscellaneous fees - partial miscellaneous fees
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getBalanceMiscellaneousFees(): Price
    {
        $balance_mf = $this->getMiscellaneousFees()->inclusive()->minus($this->getPartialMiscellaneousFees()->inclusive());

        return new Price($balance_mf);
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    protected function updatePercentMiscellaneousFees(): void
    {
        $float_miscellaneous_fees = $this->getMiscellaneousFees()->inclusive()->getAmount()->toFloat();
        $float_contract_price = $this->getContractPrice()->inclusive()->getAmount()->toFloat();

        $this->percent_miscellaneous_fees = $float_miscellaneous_fees / $float_contract_price;
    }
}
