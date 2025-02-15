<?php

namespace Homeful\Mortgage\Traits;


use Homeful\Common\Classes\AddOnFeeToPayment;
use Illuminate\Support\Collection;

trait HasAddOnFeesToLoanAmortization
{
    protected ?float $mortgage_redemption_insurance = null;
    protected ?float $monthly_fire_insurance = null;

    public function getAddOnFeesToLoanAmortization(): Collection|null
    {
        $collection = new Collection();

        if ($this->mortgage_redemption_insurance) {
            $collection->add(new AddOnFeeToPayment('mortgage redemption insurance', $this->mortgage_redemption_insurance, false));
        }
        if ($this->monthly_fire_insurance) {
            $collection->add(new AddOnFeeToPayment('fire insurance', $this->monthly_fire_insurance/12, false));
        }

        return ($collection->count() > 0) ? $collection : null;
    }
}
