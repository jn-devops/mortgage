<?php

use Homeful\Property\Enums\MarketSegment;
use Homeful\Mortgage\Data\MortgageData;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Mortgage\Classes\Amount;
use Homeful\Common\Classes\Assert;
use Homeful\Common\Classes\Input;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Borrower\Borrower;
use Homeful\Mortgage\Mortgage;
use Homeful\Property\Property;
use Illuminate\Support\Carbon;
use Brick\Math\RoundingMode;
use Homeful\Payment\Payment;
use Jarouche\Financial\PMT;
use Jarouche\Financial\PV;
use Whitecube\Price\Price;
use Brick\Money\Money;

dataset('property', function () {
    return [
        [fn () => (new Property)->setTotalContractPrice(Price::of(849999, 'PHP'))->setDisposableIncomeRequirementMultiplier($this->multiplier)],
    ];
});

dataset('sample-loan-computation', function () {
    return [
        //sample computation agapeya 70/50 duplex @ 20-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 2500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 20,

            Assert::MISCELLANEOUS_FEES => 212500,
            Assert::DOWN_PAYMENT => 2500000 * 5 / 100,
            Assert::CASH_OUT => 2500000 * 5 / 100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 19978.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.35,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.35 * 50000,
            Assert::INCOME_REQUIREMENT => 57080.0,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 20 * 12, 0.35 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,257,194.00  ₱1,934,738.00
            Assert::LOAN_DIFFERENCE => 2576875.0 - 2257194.0, //319681.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation agapeya 70/50 duplex @ 25-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 2500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 25,

            Assert::MISCELLANEOUS_FEES => 212500,
            Assert::DOWN_PAYMENT => 2500000 * 5 / 100,
            Assert::CASH_OUT => 2500000 * 5 / 100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 18213.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.35,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.35 * 50000,
            Assert::INCOME_REQUIREMENT => 52037.15, //60710.0,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 25 * 12, 0.35 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,476,021.00
            Assert::LOAN_DIFFERENCE => 2576875.0 - 2476021.0, //100854.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation agapeya 70/50 duplex @ 30-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 2500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 30,

            Assert::MISCELLANEOUS_FEES => 212500,
            Assert::DOWN_PAYMENT => 2500000 * 5 / 100,
            Assert::CASH_OUT => 2500000 * 5 / 100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 17144.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.35,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.35 * 50000,
            Assert::INCOME_REQUIREMENT => 48982.86,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 30 * 12, 0.35 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,630,382.00
            Assert::LOAN_DIFFERENCE => 2576875.0 - 2630382.0, //-53507.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation ter-je 2br 40 sqm @ 20-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 4500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 20,

            Assert::MISCELLANEOUS_FEES => 382500,
            Assert::DOWN_PAYMENT => 4500000 * 5 / 100,
            Assert::CASH_OUT => 4500000 * 5 / 100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 35961.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.3,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.3 * 50000,
            Assert::INCOME_REQUIREMENT => 119870.0,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 20 * 12, 0.3 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱1,934,738.00
            Assert::LOAN_DIFFERENCE => 4638375.0 - 1934738.0, //2703637.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation ter-je 2br 40 sqm @ 25-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 4500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 25,

            Assert::MISCELLANEOUS_FEES => 382500,
            Assert::DOWN_PAYMENT => 4500000 * 5 / 100,
            Assert::CASH_OUT => 4500000 * 5 / 100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 32783.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.3,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.3 * 50000,
            Assert::INCOME_REQUIREMENT => 109276.67,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 25 * 12, 0.3 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,122,304.00
            Assert::LOAN_DIFFERENCE => 4638375.0 - 2122304.0, //2516071.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation ter-je 2br 40 sqm @ 30-year loan term
        fn () => [
            Input::WAGES => 50000,
            Input::TCP => 4500000,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 12,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 8.5 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 30,

            Assert::MISCELLANEOUS_FEES => 382500,
            Assert::DOWN_PAYMENT => 4500000 * 5 / 100,
            Assert::CASH_OUT => 4500000 * 5 / 100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 30859.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.3,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.3 * 50000,
            Assert::INCOME_REQUIREMENT => 102863.34,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 30 * 12, 0.3 * 50000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,254,614.00
            Assert::LOAN_DIFFERENCE => 4638375.0 - 2254614.0, //2383761.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
        //sample computation for housing
        fn () => [
            Input::WAGES => 20000,
            Input::TCP => 750000,
            Input::PERCENT_DP => 2 / 100,
            Input::DP_TERM => 3,
            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 0 / 100,
            Input::LOW_CASH_OUT => 0.0,
            Input::BP_TERM => 30,

            Assert::MISCELLANEOUS_FEES => 750000 * 0,
            Assert::DOWN_PAYMENT => 750000 * 2 / 100,//15,000
            Assert::CASH_OUT => 750000 * 2 / 100 + 0.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 5000.0,
            Assert::LOAN_AMOUNT => 735000.0,
            Assert::LOAN_AMORTIZATION => 4890.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 0.0,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.35,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.35 * 20000,
            Assert::INCOME_REQUIREMENT => 13971.43,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 30 * 12, 0.35 * 20000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,254,614.00
            Assert::LOAN_DIFFERENCE => 735000.0 - 1052153.0, //-317153.0

            Assert::BALANCE_CASH_OUT => 0.0,
        ],

        //sample computation agapeya 70/50 duplex @ 30-year loan term with default interest rate and default bp term
        fn () => [
            Input::WAGES => 60000,
            Input::TCP => 2707500.0,
            Input::PERCENT_DP => 5 / 100,
            Input::DP_TERM => 6,
//            Input::BP_INTEREST_RATE => 7 / 100,
            Input::PERCENT_MF => 5 / 100,
            Input::LOW_CASH_OUT => 0.0,
//            Input::BP_TERM => 30,

            Assert::MISCELLANEOUS_FEES => 135375, // 0.05 * 2,707,500.0
            Assert::DOWN_PAYMENT => 135375, // 0.05 * 2,707,500.0
            Assert::CASH_OUT => 135375 + 6768.75, //142,143.75
            Assert::DOWN_PAYMENT_AMORTIZATION => 22562.50, // 135,375/6,
            Assert::LOAN_AMOUNT => 2700731.25,
            Assert::LOAN_AMORTIZATION => 17968.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 6768.75,
            Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.30,
            Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.30 * 60000, //18,000
            Assert::INCOME_REQUIREMENT => 59893.34,
            Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of(round((new PV((7 / 100) / 12, 30 * 12, 0.30 * 60000))->evaluate()), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱2,630,382.00
            Assert::LOAN_DIFFERENCE => 2700731.25 - 2705536.0, //-4804.75

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
    ];
});

it('has configurable miscellaneous fees', function () {
    $params = [
        Input::TCP => 2500000,
        Input::PERCENT_MF => 8.5 / 100,
    ];
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $borrower = (new Borrower($property))
        ->setRegional(false)
        ->setGrossMonthlyIncome(50000);
    expect($borrower->getProperty()->getMarketSegment())->toBe(MarketSegment::ECONOMIC);
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::PERCENT_MF])->toBe($input_percent_mf = 8.5 / 100);
        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($input_percent_mf);
        $guess_mf = Money::of($input_tcp * $input_percent_mf, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_mf->compareTo(212500.0))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($guess_mf))->toBe(Amount::EQUAL);

        $mortgage->setMiscellaneousFees(250000);

        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        $guess_percent_mf = 250000 / 2500000;
        expect($guess_percent_mf)->toBe(10 / 100);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($guess_percent_mf);
    });
});

it('has configurable down payment', function () {
    $params = [
        Input::TCP => 2500000,
        Input::DP_TERM => 24,
        Input::PERCENT_DP => 5 / 100,
    ];
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $borrower = (new Borrower($property))
        ->setRegional(false)
        ->setGrossMonthlyIncome(50000);
    expect($borrower->getProperty()->getMarketSegment())->toBe(MarketSegment::ECONOMIC);
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::DP_TERM])->toBe($input_dp_term = 24);
        expect($params[Input::PERCENT_DP])->toBe($input_percent_dp = 5 / 100);
        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        expect($mortgage->getPercentDownPayment())->toBe($input_percent_dp);

        $guess_dp = Money::of($input_tcp * $input_percent_dp, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp->compareTo(125000.0))->toBe(Amount::EQUAL);
        $guess_dp_amortization = Money::of($input_tcp * $input_percent_dp / $input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp_amortization->compareTo(5208.34))->toBe(Amount::EQUAL);
        with($mortgage->getDownPayment(), function (Payment $dp) use ($input_dp_term, $guess_dp, $guess_dp_amortization) {
            expect($dp->getPrincipal()->inclusive()->compareTo($guess_dp))->toBe(Amount::EQUAL);
            expect($dp->getTerm()->value)->toBe($input_dp_term);
            expect($dp->getTerm()->cycle)->toBe(Cycle::Monthly);
            expect($dp->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);
        });

        $mortgage->setDownPayment($input_dp = 250000.0);

        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);

        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        $guess_percent_dp = 10 / 100;
        expect($mortgage->getPercentDownPayment())->toBe($guess_percent_dp);
        with($mortgage->getDownPayment(), function (Payment $dp) use ($input_dp, $input_dp_term) {
            expect($dp->getPrincipal()->inclusive()->compareTo($input_dp))->toBe(Amount::EQUAL);
            expect($dp->getTerm()->value)->toBe($input_dp_term);
            expect($dp->getTerm()->cycle)->toBe(Cycle::Monthly);
            $guess_dp_amortization = Money::of($input_dp / $input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
            expect($guess_dp_amortization->compareTo(10416.67))->toBe(Amount::EQUAL);
            expect($dp->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);
        });
    });
});

it('has configurable contract price', function () {
    $params = [
        Input::TCP => 2500000,
        Input::PERCENT_DP => 5 / 100,
        Input::PERCENT_MF => 8.5 / 100,
        Input::DP_TERM => 12,
        Input::BP_TERM => 20,
        Input::BP_INTEREST_RATE => 7 / 100,
    ];
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $borrower = (new Borrower($property))
        ->setRegional(false)
        ->setGrossMonthlyIncome(50000);
    expect($property->getMarketSegment())->toBe(MarketSegment::ECONOMIC);
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($property, $params) {
        //confirm inputs
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::PERCENT_DP])->toBe($input_percent_dp = 5 / 100);
        expect($params[Input::PERCENT_MF])->toBe($input_percent_mf = 8.5 / 100);
        expect($params[Input::DP_TERM])->toBe($input_dp_term = 12);
        expect($params[Input::BP_TERM])->toBe($input_bp_term = 20);
        expect($params[Input::BP_INTEREST_RATE])->toBe($input_bp_interest_rate = 7 / 100);

        //confirm properties from inputs
        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getPercentDownPayment())->toBe($input_percent_dp);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($input_percent_mf);
        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        expect($mortgage->getBalancePaymentTerm())->toBe($input_bp_term);
        expect($mortgage->getInterestRate())->toBe($input_bp_interest_rate);

        //down payment
        $guess_dp = Money::of($input_tcp * $input_percent_dp, 'PHP');
        expect($guess_dp->compareTo(125000))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getPrincipal()->inclusive()->compareTo($guess_dp))->toBe(Amount::EQUAL);
        $guess_dp_amortization = Money::of($input_tcp * $input_percent_dp / $input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp_amortization->compareTo(10416.67))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);

        //miscellaneous fees
        $guess_mf = Money::of($input_tcp * $input_percent_mf, 'PHP');
        expect($guess_mf->compareTo(212500.0))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($guess_mf))->toBe(Amount::EQUAL);
        $guess_partial_mf = $guess_mf->multipliedBy($input_percent_dp);
        expect($guess_partial_mf->compareTo(10625.0))->toBe(Amount::EQUAL);
        expect($mortgage->getPartialMiscellaneousFees()->inclusive()->compareTo($guess_partial_mf))->toBe(Amount::EQUAL);

        //balance payment
        $guess_bp = Money::of($input_tcp * (1 - $input_percent_dp), 'PHP');
        expect($guess_bp->compareTo(2375000.0))->toBe(Amount::EQUAL);
        expect($mortgage->getBalancePayment()->inclusive()->compareTo($guess_bp))->toBe(Amount::EQUAL);

        //loan @ 20-year term
        $guess_loan = $guess_bp->plus($guess_mf->minus($guess_partial_mf));
        expect($guess_loan->getAmount()->compareTo(2576875.0))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getPrincipal()->inclusive()->compareTo($guess_loan))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->cycle)->toBe(Cycle::Yearly);
        $monthly_interest_rate = $input_bp_interest_rate / 12;
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(19978.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(57080.0))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 25-year term
        $input_bp_term = 25;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(18213.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(52037.15))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 30-year term
        $input_bp_term = 30;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(17144.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(48982.86))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //change contract price
        $mortgage->setContractPrice($new_tcp = 4500000.0);//TODO: make sure the market segment is synchronized in the future

        //confirm properties from inputs
        expect($mortgage->getContractPrice()->inclusive()->compareTo($new_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getPercentDownPayment())->toBe($input_percent_dp);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($input_percent_mf);
        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        expect($mortgage->getBalancePaymentTerm())->toBe($input_bp_term);

        //down payment
        $guess_dp = Money::of($new_tcp * $input_percent_dp, 'PHP');
        expect($guess_dp->compareTo(225000.0))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getPrincipal()->inclusive()->compareTo($guess_dp))->toBe(Amount::EQUAL);
        $guess_dp_amortization = Money::of($new_tcp * $input_percent_dp / $input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp_amortization->compareTo(18750.0))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);

        //miscellaneous fees
        $guess_mf = Money::of($new_tcp * $input_percent_mf, 'PHP');
        expect($guess_mf->compareTo(382500.0))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($guess_mf))->toBe(Amount::EQUAL);
        $guess_partial_mf = $guess_mf->multipliedBy($input_percent_dp);
        expect($guess_partial_mf->compareTo(19125.0))->toBe(Amount::EQUAL);
        expect($mortgage->getPartialMiscellaneousFees()->inclusive()->compareTo($guess_partial_mf))->toBe(Amount::EQUAL);

        //balance payment
        $guess_bp = Money::of($new_tcp * (1 - $input_percent_dp), 'PHP');
        expect($guess_bp->compareTo(4275000.0))->toBe(Amount::EQUAL);
        expect($mortgage->getBalancePayment()->inclusive()->compareTo($guess_bp))->toBe(Amount::EQUAL);

        //loan @ 20-year term
        $input_bp_term = 20;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        $guess_loan = $guess_bp->plus($guess_mf->minus($guess_partial_mf));
        expect($guess_loan->getAmount()->compareTo(4638375.0))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getPrincipal()->inclusive()->compareTo($guess_loan))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->cycle)->toBe(Cycle::Yearly);
        $monthly_interest_rate = $input_bp_interest_rate / 12;
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(35961.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(102745.72))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 25-year term
        $input_bp_term = 25;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(32783.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(93665.72))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 30-year term
        $input_bp_term = 30;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(30859.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization / $property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(88168.58))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);
    });
});

it('computes different loan packages', function (array $params) {
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $borrower = (new Borrower($property))
        ->setBirthdate(Carbon::parse('1999-03-17'))
        ->setRegional(false)
        ->setGrossMonthlyIncome($params[Input::WAGES]);
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($mortgage->getContractPrice()->inclusive()->compareTo($params[Input::TCP]))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($params[Assert::MISCELLANEOUS_FEES]))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getPrincipal()->inclusive()->compareTo($params[Assert::DOWN_PAYMENT]))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getMonthlyAmortization()->inclusive()->compareTo($params[Assert::DOWN_PAYMENT_AMORTIZATION]))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getPrincipal()->inclusive()->compareTo($params[Assert::LOAN_AMOUNT]))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($params[Assert::LOAN_AMORTIZATION]))->toBe(Amount::EQUAL);
        expect($mortgage->getPartialMiscellaneousFees()->inclusive()->compareTo($params[Assert::PARTIAL_MISCELLANEOUS_FEES]))->toBe(Amount::EQUAL);
        //        expect($mortgage->getIncomeRequirement()->compareTo($params[Assert::GROSS_MONTHLY_INCOME]))->toBe(Amount::EQUAL);
        expect($mortgage->getProperty()->getDefaultDisposableIncomeRequirementMultiplier())->toBe($params[Assert::INCOME_REQUIREMENT_MULTIPLIER]);
//                dd($mortgage->getDisposableMonthlyIncome()->inclusive()->getAmount()->toFloat());
        expect($mortgage->getJointBorrowerDisposableMonthlyIncome()->inclusive()->compareTo($params[Assert::JOINT_DISPOSABLE_MONTHLY_INCOME]))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($params[Assert::INCOME_REQUIREMENT]))->toBe(Amount::EQUAL);
        //        dd($mortgage->getMaximumPaymentFromDisposableMonthlyIncome()->getMonthlyDiscountedValue()->inclusive()->getAmount()->toFloat());
        //        expect($mortgage->getMaximumPaymentFromDisposableMonthlyIncome()->getMonthlyDiscountedValue()->inclusive()->compareTo($params[Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME]))->toBe(Amount::EQUAL);
        //        echo($mortgage->getLoanDifference()->inclusive()->getAmount()->toFloat());
        expect($mortgage->getLoanDifference()->inclusive()->compareTo($params[Assert::LOAN_DIFFERENCE]))->toBe(Amount::EQUAL);
        expect($mortgage->getTotalCashOut()->inclusive()->getAmount()->toFloat())->toBe($params[Assert::CASH_OUT]);
    });
})->with('sample-loan-computation');

it('can simulate loan calculator', function () {
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 4500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $params = [
        Input::WAGES => 110000,
        Input::TCP => 4500000,
        Input::PERCENT_DP => 5 / 100,
        Input::DP_TERM => 12,
        Input::BP_INTEREST_RATE => 7 / 100,
        Input::PERCENT_MF => 8.5 / 100,
        Input::LOW_CASH_OUT => 0.0,
        Input::BP_TERM => 20,

        Assert::MISCELLANEOUS_FEES => 382500,
        Assert::DOWN_PAYMENT => 4500000 * 5 / 100,
        Assert::CASH_OUT => 4500000 * 5 / 100 + 19125.0,
        Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
        Assert::LOAN_AMOUNT => 4638375.0,
        Assert::LOAN_AMORTIZATION => 35961.0,
        Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
        Assert::GROSS_MONTHLY_INCOME => 119870.0,
        Assert::INCOME_REQUIREMENT_MULTIPLIER => 0.3,
        Assert::INCOME_REQUIREMENT => 119870.0,
        Assert::JOINT_DISPOSABLE_MONTHLY_INCOME => 0.3 * 119870.0,
        Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME => Money::of((new PV((7 / 100) / 12, 20 * 12, 0.3 * 50000))->evaluate(), 'PHP', roundingMode: RoundingMode::CEILING)->getAmount()->toFloat(), //₱1,934,737.6
        Assert::LOAN_DIFFERENCE => 4638375.0 - 1934737.6, //2703637.4

        Assert::BALANCE_CASH_OUT => 0.0,
    ];

    with(Mortgage::createWithTypicalBorrower($property, $params), function (Mortgage $mortgage) use ($params) {
        /** Total Contract Price = ₱4,500,000.00 **/
        /** derived from the SKU of The Property **/
        expect($mortgage->getProperty()->getTotalContractPrice()->inclusive()
            ->compareTo($params[Input::TCP]))
            ->toBe(Amount::EQUAL);

        /** Borrower Age = 25 years old **/
        /** default from configuration ***/
        expect(round($mortgage->getBorrower()->getBirthdate()
            ->diffInYears(Carbon::now())))
            ->toBe(Mortgage::getDefaultAge());

        /** Computed Gross Monthly Income = ₱119,870.00 **/
        /** required monthly income to buy The Product ***/
        expect($mortgage->getBorrower()->getGrossMonthlyIncome()->base()
            ->compareTo($params[Assert::GROSS_MONTHLY_INCOME]))
            ->toBe(Amount::EQUAL);

        /** % Disposable Income Requirement = 30% **/
        /** taken from the property segment matrix */
        expect($mortgage->getProperty()->getDefaultDisposableIncomeRequirementMultiplier())
            ->toBe($params[Assert::INCOME_REQUIREMENT_MULTIPLIER]);

        /** Income Requirement = ₱35,961.00 ÷ 30% = ₱119,870.00 **/
        /** Loan Amortization ÷ % Disposable Income Requirement **/
        expect($mortgage->getLoan()->getIncomeRequirement()->compareTo($params[Assert::INCOME_REQUIREMENT]))
            ->toBe(Amount::EQUAL);

        /** Disposable Monthly Income = 30% * ₱119,870.00 = ₱35,961.0 **/
        expect($mortgage->getJointBorrowerDisposableMonthlyIncome()->inclusive()
            ->compareTo($params[Assert::JOINT_DISPOSABLE_MONTHLY_INCOME]))
            ->toBe(Amount::EQUAL);

        /** Percent Down Payment = ₱35,961.00 ÷ 30% = ₱119,870.00 **/
        expect($mortgage->getPercentDownPayment())
            ->toBe($params[Input::PERCENT_DP]);

        /** Down Payment = ₱225,000.0 **/
        /** 5% x ₱4,500,000.00 (TCP)  **/
        expect($mortgage->getDownPayment()->getPrincipal()->inclusive()
            ->compareTo($params[Assert::DOWN_PAYMENT]))
            ->toBe(Amount::EQUAL);

        /** Down Payment Term = 12 months **/
        expect($mortgage->getDownPayment()->getTerm()->monthsToPay())
            ->toBe($params[Input::DP_TERM]);

        /** Down Payment Amortization = ₱18,750.00 **/
        /** ₱225,000.0 (down payment) ÷ 12 months ***/
        expect($mortgage->getDownPayment()->getMonthlyAmortization()->inclusive()
            ->compareTo($params[Assert::DOWN_PAYMENT_AMORTIZATION]))
            ->toBe(Amount::EQUAL);

        /** Loan Value or Balance Payment = ₱4,638,375.00 ***/
        /** (TCP [₱4,500,000.00] + MF [₱382,500.00]) x 95% **/
        expect($mortgage->getLoan()->getPrincipal()->inclusive()
            ->compareTo($params[Assert::LOAN_AMOUNT]))
            ->toBe(Amount::EQUAL);

        //        dd($mortgage->getBorrower()->getGrossMonthlyIncome()->inclusive()->getAmount()->toFloat());

        expect($mortgage->getBorrower()->getGrossMonthlyIncome()->inclusive()->compareTo(119870.0))->toBe(Amount::EQUAL);
        $mortgage->getBorrower()->setGrossMonthlyIncome(110000);
        expect($mortgage->getBorrower()->getGrossMonthlyIncome()->inclusive()->compareTo(110000))->toBe(Amount::EQUAL);
        expect($mortgage->getPresentValueFromMonthlyDisposableIncomePayments()->getDiscountedValue()->inclusive()->compareTo(4256423.0))->toBe(Amount::EQUAL);
        expect($mortgage->getLoanDifference()->inclusive()->compareTo(381952.0))->toBe(Amount::EQUAL);
        expect($mortgage->getLoanDifference()->inclusive()->compareTo(381952.0))->toBe(Amount::EQUAL);

        /** Loan Term = 20 years **/
        with($mortgage->getLoan(), function (Payment $loan) use ($params) {
            expect($loan->getTerm()->yearsToPay())
                ->toBe(Mortgage::getDefaultLoanTerm()->yearsToPay());
            /** Loan Amortization = ₱35,961.00 **/
            expect($loan->getMonthlyAmortization()->inclusive()
                ->compareTo($params[Assert::LOAN_AMORTIZATION]))
                ->toBe(Amount::EQUAL);
            /** Required GMI = ₱119,870.00 **/
            expect($loan->getIncomeRequirement()
                ->compareTo(119870.0))
                ->toBe(Amount::EQUAL);
        });

        /** Loan Term = 25 years **/
        with($mortgage->getLoan()->setTerm(new Term(25)), function (Payment $loan) {
            expect($loan->getTerm()->yearsToPay())->toBe(25);
            /** Loan Amortization = ₱32,783.00 **/
            expect($loan->getMonthlyAmortization()->inclusive()
                ->compareTo(32783.0))
                ->toBe(Amount::EQUAL);
            /** Required GMI = ₱109,276.67 **/
            expect($loan->getIncomeRequirement()
                ->compareTo(109276.67))
                ->toBe(Amount::EQUAL);
        });

        /** Loan Term = 30 years **/
        with($mortgage->getLoan()->setTerm(new Term(30)), function (Payment $loan) {
            expect($loan->getTerm()->yearsToPay())->toBe(30);
            /** Loan Amortization = ₱30,859.00 **/
            expect($loan->getMonthlyAmortization()->inclusive()
                ->compareTo(30859.0))
                ->toBe(Amount::EQUAL);
            /** Required GMI = ₱102,863.34 **/
            expect($loan->getIncomeRequirement()
                ->compareTo(102863.34))
                ->toBe(Amount::EQUAL);
        });

        /** Cash Outs **/
        $mortgage->getCashOuts()->each(function (CashOut $cash_out) use ($mortgage) {
            match ($cash_out->getName()) {
                Input::DOWN_PAYMENT => expect($mortgage->getDownPayment()->getPrincipal()->inclusive()->compareTo($cash_out->getAmount()->inclusive()))->toBe(Amount::EQUAL),
                Input::PARTIAL_MISCELLANEOUS_FEES => expect($mortgage->getPartialMiscellaneousFees()->inclusive()->compareTo($cash_out->getAmount()->inclusive()))->toBe(Amount::EQUAL),
                default => true
            };
        });
    });
});

it('has mortgage data', function (array $params) {
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    $borrower = (new Borrower($property))
        ->setRegional(false)
        ->setAge(25)
        ->setGrossMonthlyIncome($params[Input::WAGES]);
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        $data = MortgageData::fromObject($mortgage);
        expect($data->borrower->gross_monthly_income)->toBe((float) $params[Input::WAGES]);
        expect($data->property->total_contract_price)->toBe((float) $params[Input::TCP]);
        expect($data->percent_down_payment)->toBe($params[Input::PERCENT_DP]);
        expect($data->dp_term)->toBe((float) $mortgage->getDownPaymentTerm());
        expect($data->bp_interest_rate)->toBe($mortgage->getInterestRate());
        expect($data->bp_term)->toBe((float) $mortgage->getBalancePaymentTerm());
        expect($data->miscellaneous_fees)->toBe((float) $params[Assert::MISCELLANEOUS_FEES]);
        expect($data->down_payment)->toBe((float) $params[Assert::DOWN_PAYMENT]);
        expect($data->cash_out)->toBe((float) $params[Assert::CASH_OUT]);
        expect($data->dp_amortization)->toBe((float) $params[Assert::DOWN_PAYMENT_AMORTIZATION]);
        expect($data->loan_amount)->toBe((float) $params[Assert::LOAN_AMOUNT]);
        expect($data->loan_amortization)->toBe((float) $params[Assert::LOAN_AMORTIZATION]);
        expect($data->partial_miscellaneous_fees)->toBe((float) $params[Assert::PARTIAL_MISCELLANEOUS_FEES]);
        expect($data->income_requirement_multiplier)->toBe((float) $params[Assert::INCOME_REQUIREMENT_MULTIPLIER]);
        expect($data->joint_disposable_monthly_income)->toBe((float) $params[Assert::JOINT_DISPOSABLE_MONTHLY_INCOME]);
        expect($data->income_requirement)->toBe((float) $params[Assert::INCOME_REQUIREMENT]);
        expect($data->present_value_from_monthly_disposable_income)->toBe($params[Assert::MAXIMUM_PAYMENT_FROM_MONTHLY_INCOME]);
        expect($data->loan_difference)->toBe((float) $params[Assert::LOAN_DIFFERENCE]);
    });
})->with('sample-loan-computation');
