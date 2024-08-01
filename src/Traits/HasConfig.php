<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Payment\Class\Term;

trait HasConfig
{
    public static function getDefaultInterestRate(): float
    {
        return config('mortgage.default_interest_rate');
    }

    public static function getDefaultLoanTerm(): Term
    {
        return new Term(config('mortgage.default_loan_term', 20));
    }

    public static function getDefaultAge(): float
    {
        return config('mortgage.default_age', 25);
    }
}
