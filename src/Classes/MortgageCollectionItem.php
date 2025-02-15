<?php

namespace Homeful\Mortgage\Classes;

use Brick\Math\RoundingMode;
use Whitecube\Price\Price;
use Brick\Money\Money;

abstract class MortgageCollectionItem
{
    protected string $name;

    protected Price $amount;

    protected bool $deductible;

    /**
     * @param string $name
     * @param Price|float $amount
     * @param bool $deductible
     */
    public function __construct(string $name, Price|float $amount, bool $deductible)
    {
        $this->setName($name)->setAmount($amount)->setDeductible($deductible);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAmount(): Price
    {
        return $this->amount;
    }

    public function setAmount(Price|float $amount): static
    {
        $this->amount = $amount instanceof Price ? $amount : new Price(Money::of($amount, 'PHP', roundingMode: RoundingMode::UP));

        return $this;
    }

    public function isDeductible(): bool
    {
        return $this->deductible;
    }

    public function setDeductible(bool $deductible): static
    {
        $this->deductible = $deductible;

        return $this;
    }
}
