<?php

namespace Homeful\Mortgage\Data;

use Homeful\Borrower\Data\BorrowerData;
use Homeful\Property\Data\PropertyData;
use Homeful\Mortgage\Mortgage;
use Spatie\LaravelData\Data;

class MortgageData extends Data
{
    public function __construct(
        public BorrowerData $borrower,
        public PropertyData $property,
        public float $percent_down_payment,
        public float $dp_term,
        public float $bp_interest_rate,
        public float $percent_mf,
        public float $bp_term,
        public float $miscellaneous_fees,
        public float $down_payment,
        public float $cash_out,
        public float $dp_amortization,
        public float $loan_amount,
        public float $loan_amortization,
        public float $partial_miscellaneous_fees,
        public float $income_requirement_multiplier,
        public float $joint_disposable_monthly_income,
        public float $income_requirement,
        public float $present_value_from_monthly_disposable_income,
        public float $loan_difference,
    ) {}

    public static function fromObject(Mortgage $mortgage): MortgageData
    {
        $loan = $mortgage->getLoan();

        return new self(
            borrower: BorrowerData::fromObject($mortgage->getBorrower()),
            property: PropertyData::fromObject($mortgage->getProperty()),
            percent_down_payment: $mortgage->getPercentDownPayment(),
            dp_term: $mortgage->getDownPaymentTerm(),
            bp_interest_rate: $mortgage->getInterestRate(),
            percent_mf: $mortgage->getPercentMiscellaneousFees(),
            bp_term: $mortgage->getBalancePaymentTerm(),
            miscellaneous_fees: $mortgage->getMiscellaneousFees()->inclusive()->getAmount()->toFloat(),
            down_payment: $mortgage->getDownPayment()->getPrincipal()->inclusive()->getAmount()->toFloat(),
            cash_out: $mortgage->getTotalCashOut()->inclusive()->getAmount()->toFloat(),
            dp_amortization: $mortgage->getBalanceDownPayment()->getMonthlyAmortization()->inclusive()->getAmount()->toFloat(),
            loan_amount: $loan->getPrincipal()->inclusive()->getAmount()->toFloat(),
            loan_amortization: $loan->getMonthlyAmortization()->inclusive()->getAmount()->toFloat(),
            partial_miscellaneous_fees: $mortgage->getPartialMiscellaneousFees()->inclusive()->getAmount()->toFloat(),
            income_requirement_multiplier: $mortgage->getIncomeRequirement(),
            joint_disposable_monthly_income: $mortgage->getJointBorrowerDisposableMonthlyIncome()->inclusive()->getAmount()->toFloat(),
            income_requirement: $mortgage->getLoan()->getIncomeRequirement()->getAmount()->toFloat(),
            present_value_from_monthly_disposable_income: $mortgage->getPresentValueFromMonthlyDisposableIncomePayments()->getDiscountedValue()->inclusive()->getAmount()->toFloat(),
            loan_difference: $mortgage->getLoanDifference()->inclusive()->getAmount()->toFloat()
        );
    }
}
