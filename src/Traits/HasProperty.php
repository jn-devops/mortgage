<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;
use Homeful\Property\Property;

trait HasProperty
{
    protected Property $property;

    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @return Mortgage|HasProperty
     */
    public function setProperty(Property $property): self
    {
        $this->property = $property;

        return $this->updateContractPrice();
    }

    /**
     * @return Mortgage|HasProperty
     */
    public function updateContractPrice(): self
    {
        return $this->setContractPrice($this->property->getTotalContractPrice());
    }
}
