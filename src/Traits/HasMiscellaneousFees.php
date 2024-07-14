<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Events\{MiscellaneousFeesUpdated, PercentMiscellaneousFeesUpdated};
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;

trait HasMiscellaneousFees
{
    protected float $percent_miscellaneous_fees;
    protected Price $miscellaneous_fees;

    /**
     * @return float
     */
    public function getPercentMiscellaneousFees(): float
    {
        return $this->percent_miscellaneous_fees ?? 0.0;
    }

    /**
     * @param float $percent_miscellaneous_fees
     * @return Mortgage|HasMiscellaneousFees
     */
    public function setPercentMiscellaneousFees(float $percent_miscellaneous_fees): self
    {
        $this->percent_miscellaneous_fees = $percent_miscellaneous_fees;
        PercentMiscellaneousFeesUpdated::dispatch($this);

        return $this;
    }

    /**
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getMiscellaneousFees(): Price
    {
        return $this->miscellaneous_fees ?? new Price(Money::of(0, 'PHP'));
    }

    /**
     * @param Price|float $miscellaneous_fees
     * @return Mortgage|HasMiscellaneousFees
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
     * @return void
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    protected function updatePercentMiscellaneousFees(): void
    {
        $float_miscellaneous_fees = $this->getMiscellaneousFees()->inclusive()->getAmount()->toFloat();
        $float_contract_price = $this->getContractPrice()->inclusive()->getAmount()->toFloat();

        $this->percent_miscellaneous_fees = $float_miscellaneous_fees/$float_contract_price;
    }
}
