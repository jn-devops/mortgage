<?php

namespace Homeful\Mortgage\Actions;

use Brick\Math\RoundingMode;
use Homeful\Payment\{Payment, PresentValue};
use Whitecube\Price\Price;
use Illuminate\Support\Facades\Validator;


class CalculateLoanDifference
{
    /**
     * @param Payment $loan
     * @param array $attributes
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function get(Payment $loan, array $attributes): Price
    {
        $validated = Validator::validate($attributes, [
            'payment' => 'required',
            'term' => 'required',
            'interest_rate' => 'required'
        ]);

        $present_value = (new PresentValue)->setPayment($validated['payment'])->setTerm($validated['term'])->setInterestRate($validated['interest_rate']);

        $loan_difference = $loan->getPrincipal()->inclusive()
            ->minus($present_value->getDiscountedValue()->inclusive(), roundingMode: RoundingMode::CEILING);

        return new Price($loan_difference);
    }
}
