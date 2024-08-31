<?php

namespace Homeful\Mortgage\Actions;

use Illuminate\Support\Facades\Validator;
use Homeful\Payment\PresentValue;
use Brick\Math\RoundingMode;
use Homeful\Payment\Payment;
use Whitecube\Price\Price;

class CalculateLoanDifference
{
    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function get(Payment $loan, array $attributes): Price
    {
        $validated = Validator::validate($attributes, [
            'payment' => 'required',
            'term' => 'required',
            'interest_rate' => 'required',
        ]);

        $present_value = (new PresentValue)->setPayment($validated['payment'])->setTerm($validated['term'])->setInterestRate($validated['interest_rate']);

        $loan_difference = $loan->getPrincipal()->inclusive()
            ->minus($present_value->getDiscountedValue()->inclusive(), roundingMode: RoundingMode::CEILING);

        return new Price($loan_difference);
    }
}
