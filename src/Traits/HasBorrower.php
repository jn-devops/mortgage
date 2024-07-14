<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Borrower\Borrower;
use Homeful\Mortgage\Mortgage;

trait HasBorrower
{
    protected Borrower $borrower;

    /**
     * @return Borrower
     */
    public function getBorrower(): Borrower
    {
        return $this->borrower;
    }

    /**
     * @param Borrower $borrower
     * @return HasBorrower|Mortgage
     */
    public function setBorrower(Borrower $borrower): self
    {
        $this->borrower = $borrower;

        return $this;
    }
}
