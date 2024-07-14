<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Property\Property;
use Homeful\Mortgage\Mortgage;

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
