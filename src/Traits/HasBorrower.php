<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Borrower\Borrower;
use Homeful\Mortgage\Mortgage;

trait HasBorrower
{
    protected Borrower $borrower;

    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    /**
     * @return HasBorrower|Mortgage
     */
    public function setBorrower(Borrower $borrower): self
    {
        $this->borrower = $borrower;

        return $this;
    }
}
