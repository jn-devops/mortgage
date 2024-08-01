<?php

namespace Homeful\Mortgage\Traits;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Homeful\Mortgage\Events\ContractPriceUpdated;
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;

trait HasContractPrice
{
    protected Price $contract_price;

    public function getContractPrice(): Price
    {
        return $this->contract_price;
    }

    /**
     * @return Mortgage|HasContractPrice
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function setContractPrice(Price|float $contract_price, bool $send = true): self
    {
        $this->contract_price = $contract_price instanceof Price
            ? $contract_price
            : new Price(Money::of($contract_price, 'PHP'));
        $send && ContractPriceUpdated::dispatch($this);

        return $this;
    }
}
