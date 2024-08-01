<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Mortgage;

trait HasTerms
{
    protected int $balance_payment_term;

    /**
     * default is 0
     */
    public function getBalancePaymentTerm(): int
    {
        return $this->balance_payment_term ?? 0;
    }

    /**
     * @return HasTerms|Mortgage
     */
    public function setBalancePaymentTerm(int $balance_payment_term): self
    {
        $this->balance_payment_term = $balance_payment_term;

        return $this;
    }
}
