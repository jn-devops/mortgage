<?php

namespace Homeful\Mortgage\Classes;

use Amp\ByteStream\Pipe;
use Brick\Money\Money;
use Whitecube\Price\Price;

class CashOut
{
    protected string $name;
    protected Price $amount;
    protected bool $deductible;

    /**
     * @param string $name
     * @param Price $amount
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

    public function setName(string $name): CashOut
    {
        $this->name = $name;

        return $this;
    }

    public function getAmount(): Price
    {
        return $this->amount;
    }

    public function setAmount(Price|float $amount): CashOut
    {
        $this->amount = $amount instanceof Price ? $amount : new Price(Money::of($amount, 'PHP'));

        return $this;
    }

    public function isDeductible(): bool
    {
        return $this->deductible;
    }

    public function setDeductible(bool $deductible): CashOut
    {
        $this->deductible = $deductible;

        return $this;
    }
}
