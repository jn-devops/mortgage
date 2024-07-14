<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;

trait HasPromos
{
    protected Price $low_cash_out;

    /**
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getLowCashOut(): Price
    {
        return $this->low_cash_out ?? new Price(Money::of(0, 'PHP'));
    }

    /**
     * @param Price|float $low_cash_out
     * @return HasPromos|Mortgage
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function setLowCashOut(Price|float $low_cash_out): self
    {
        $this->low_cash_out = $low_cash_out instanceof Price ? $low_cash_out : new Price(Money::of($low_cash_out, 'PHP'));

        return $this;
    }
}