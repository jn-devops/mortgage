<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;

trait HasMultipliers
{
    protected float $interest_rate;

    public function getInterestRate(): float
    {
        return $this->interest_rate ?? 0.0;
    }

    /**
     * @param float $interest_rate
     * @return Mortgage|HasMultipliers
     */
    public function setInterestRate(float $interest_rate): self
    {
        $this->interest_rate = $interest_rate;

        return $this;
    }
}
