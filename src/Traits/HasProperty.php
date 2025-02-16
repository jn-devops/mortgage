<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;
use Homeful\Property\Property;

trait HasProperty
{
    protected Property $property;

    /**
     * @return Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @param Property $property
     * @return Mortgage|HasProperty
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function setProperty(Property $property): self
    {
        $this->property = $property;

        return $this->updateBorrowerProperty()->updateContractPrice();
    }

    /**
     * @return Mortgage|HasProperty
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function updateContractPrice(): self
    {
        return $this->setContractPrice($this->property->getTotalContractPrice());
    }

    /**
     * @return Mortgage|HasProperty
     */
    protected function updateBorrowerProperty(): self
    {
        if (isset($this->borrower)) {
            $this->getBorrower()->setProperty($this->getProperty());
        }

        return $this;
    }
}
