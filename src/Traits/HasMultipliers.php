<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;

trait HasMultipliers
{
    protected float $interest_rate;

    protected ?float $income_requirement = null;

    /**
     * @param float $interest_rate
     * @return Mortgage|HasMultipliers
     */
    public function setInterestRate(float $interest_rate): self
    {
        $this->interest_rate = $interest_rate;

        return $this;
    }

    public function getInterestRate(): float
    {
        return $this->interest_rate ?? 0.0;
    }

    public function setIncomeRequirement(?float $income_requirement = null): self
    {
        $this->income_requirement = $income_requirement;

        return $this;
    }

    public function getIncomeRequirement(): ?float
    {
        // Return the income requirement if set, otherwise return the disposable income requirement multiplier from the property
        return $this->income_requirement ?? $this->getProperty()?->getDisposableIncomeRequirementMultiplier();
    }
}
