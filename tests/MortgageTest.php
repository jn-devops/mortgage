<?php

use Brick\Math\RoundingMode;
use Homeful\Mortgage\Classes\Amount;
use Homeful\Borrower\Borrower;
use Homeful\Property\Property;
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;
use Homeful\Payment\Payment;
use Homeful\Payment\Enums\Cycle;
use Homeful\Mortgage\Classes\CashOut;
use Illuminate\Support\Collection;
use Homeful\Mortgage\Classes\{Assert, Input};
use Jarouche\Financial\PMT;

it('accepts property and borrower and has configurable parameters', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of(850000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(850000, 'PHP')));
    $params = [
        Input::PERCENT_DP => 0/100,
        Input::BP_INTEREST_RATE => 6.25/100,
        Input::BP_TERM => 30,
        Input::PERCENT_MF => 0/100,
        Input::CONSULTING_FEE => 10000
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);

    expect($mortgage->updateContractPrice()->base()->compareTo(850000))->toBe(Amount::EQUAL);
    expect($mortgage->updateContractPrice()->inclusive()->compareTo(850000))->toBe(Amount::EQUAL);
    expect($mortgage->getPercentDownPayment())->toBe(0.0);
    expect($mortgage->getBalancepaymentTerm())->toBe(30);
    expect($mortgage->getPercentMiscellaneousFees())->toBe(0/100);
    expect($mortgage->getConsultingFee()->inclusive()->compareTo(10000))->toBe(Amount::EQUAL);

    with($mortgage->getDownPayment(), function (Payment $dp) {
        expect($dp->getPrincipal()->inclusive()->compareTo(0.0))->toBe(Amount::EQUAL);
        expect($dp->getTerm()->value)->toBe(0);
        expect($dp->getInterestRate())->toBe(0.0);
    });

    with($mortgage->getBalancePayment(), function (Payment $bp) use ($mortgage) {
        expect($bp->getPrincipal()->compareTo($mortgage->updateContractPrice()))->toBe(Amount::EQUAL);
        expect($bp->getTerm()->value)->toBe($mortgage->getBalancepaymentTerm());
        expect($bp->getInterestRate())->toBe($mortgage->getInterestRate());
        expect($bp->getMonthlyAmortization()->inclusive()->compareTo(5234.0))->toBe(Amount::EQUAL);
    });

    expect($mortgage->getConsultingFee()->inclusive()->compareTo(10000))->toBe(Amount::EQUAL);
    expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo(850000 * 0/100))->toBe(Amount::EQUAL);
})->skip();

it('has down payment', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 850000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(850000, 'PHP')));
    $params = [
        Input::PERCENT_DP => $percent_dp = 5/100,
        Input::PERCENT_MF => $percent_mf = 8.5/100,
        Input::DP_TERM => $dp_term = 12,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getPercentDownPayment())->toBe($percent_dp);
    with($mortgage->getDownPayment(), function (Payment $dp) use ($tcp, $percent_dp, $percent_mf, $dp_term) {
        expect($dp->getTerm()->value)->toBe($dp_term);
        expect($dp->getTerm()->monthsToPay())->toBe($dp_term);
        expect($dp->getTerm()->cycle)->toBe(Cycle::Monthly);
        expect($dp->getInterestRate())->toBe(0.0);
        expect($dp->getPrincipal()->base()->compareTo($tcp))->toBe(Amount::EQUAL);
        expect($dp->getPrincipal()->inclusive()->compareTo(  $tcp * (1 + $percent_mf) * $percent_dp))->toBe(Amount::EQUAL);
    });
})->skip();

it('has balance payment and amortization', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::PERCENT_DP => $percent_dp = 5/100,
        Input::PERCENT_MF => $percent_mf = 8.5/100,
        Input::BP_TERM => $bp_term = 20,
        Input::BP_INTEREST_RATE => $bp_interest = 7/100,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getPercentDownPayment())->toBe($percent_dp);
    with($mortgage->getBalancePayment(), function (Payment $bp) use ($tcp, $percent_dp, $percent_mf, $bp_term, $bp_interest) {
        expect($bp->getTerm()->value)->toBe($bp_term);
        expect($bp->getTerm()->monthsToPay())->toBe($bp_term * 12);
        expect($bp->getTerm()->cycle)->toBe(Cycle::Yearly);
        expect($bp->getInterestRate())->toBe($bp_interest);
        expect($bp->getPrincipal()->base()->compareTo($tcp * (1-$percent_dp)))->toBe(Amount::EQUAL);
        expect($bp->getPrincipal()->inclusive()->compareTo(  $tcp * (1-$percent_dp) * (1 + $percent_mf)))->toBe(Amount::EQUAL);
        expect($bp->getMonthlyAmortization()->inclusive()->compareTo(19978.0 ))->toBe(Amount::EQUAL );
    });
})->skip();

it('has low cash out', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 30000,
        Input::CONSULTING_FEE => $consulting_fee = 10000
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getLowCashOut()->inclusive()->compareTo($low_cash_out))->toBe(Amount::EQUAL);
    expect($mortgage->getBalanceCashOut()->inclusive()->compareTo($low_cash_out - $consulting_fee))->toBe(Amount::EQUAL);
    expect($mortgage->isPromotional())->toBeTrue();
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 0,
        Input::CONSULTING_FEE => 10000
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getLowCashOut()->inclusive()->compareTo($low_cash_out))->toBe(Amount::EQUAL);
    expect($mortgage->getBalanceCashOut()->inclusive()->compareTo(0.0))->toBe(Amount::EQUAL);
    expect($mortgage->isPromotional())->toBeFalse();
})->skip();

//it('has base and mf inclusive loan component', function () {
//    $borrower = (new Borrower)
//        ->setRegional(false)
//        ->addWages(14500);
//    $property = (new Property)
//        ->setTotalContractPrice(new Price(Money::of(850000, 'PHP')))
//        ->setAppraisedValue(new Price(Money::of(850000, 'PHP')));
//    $params = [
//        Input::PERCENT_MF => 8.5/100,
//        Input::PERCENT_DP => $percent_dp = 5/100,
//        Input::CONSULTING_FEE => 10000,
//        Input::LOW_CASH_OUT => 30000
//    ];
//    $percent_bp = 1 - $percent_dp;
//    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
//    expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo(72250.0))->toBe(Amount::EQUAL);
//    expect($mortgage->getLoan()->base()->compareTo(850000))->toBe(Amount::EQUAL);
//    $guess = $mortgage->isPromotional() ? (850000 * $percent_bp) + 72250.0 : (850000 + 72250.0) * $percent_bp;
//    expect($mortgage->getLoan()->inclusive()->compareTo($guess))->toBe(Amount::EQUAL);
//});

dataset('agapeya-promo', function () {
    return [
        //sample computation agapeya 70/50 duplex
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 20, 'guess_loan_amount' => 2576875.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 19978.0, 'guess_down_payment_amount' => 135625.0, 'guess_dp_amortization_amount' => 9583.34],
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 25, 'guess_loan_amount' => 2576875.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 18213.0, 'guess_down_payment_amount' => 135625.0, 'guess_dp_amortization_amount' => 9583.34],
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 30, 'guess_loan_amount' => 2576875.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 17144.0, 'guess_down_payment_amount' => 135625.0, 'guess_dp_amortization_amount' => 9583.34],
//        //sample computation ter-je 2br 40 sqm
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 20, 'guess_loan_amount' => 4638375.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 35961.0, 'guess_down_payment_amount' => 244125.0, 'guess_dp_amortization_amount' => 17916.67],
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 25, 'guess_loan_amount' => 4638375.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 32783.0, 'guess_down_payment_amount' => 244125.0, 'guess_dp_amortization_amount' => 17916.67],
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 8.5 / 100, Input::LOW_CASH_OUT => 0.0, Input::BP_TERM => 30, 'guess_loan_amount' => 4638375.0, 'guess_balance_cash_out_amount' => 0.0, 'guess_loan_amortization_amount' => 30859.0, 'guess_down_payment_amount' => 244125.0, 'guess_dp_amortization_amount' => 17916.67],
//        //sample computation agapeya 70/50 duplex (low cash out)
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 30000, Input::BP_TERM => 20, 'guess_loan_amount' => 2500000.0, 'guess_balance_cash_out_amount' => 20000, 'guess_loan_amortization_amount' => 19382.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 30000, Input::BP_TERM => 25, 'guess_loan_amount' => 2500000.0, 'guess_balance_cash_out_amount' => 20000, 'guess_loan_amortization_amount' => 17669.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
        fn () => ['total_contract_price' => 2500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 30000, Input::BP_TERM => 30, 'guess_loan_amount' => 2500000.0, 'guess_balance_cash_out_amount' => 20000, 'guess_loan_amortization_amount' => 16633.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
//        //sample computation ter-je 2br 40 sqm (low cash out)
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 50000, Input::BP_TERM => 20, 'guess_loan_amount' => 4500000.0, 'guess_balance_cash_out_amount' => 40000, 'guess_loan_amortization_amount' => 34888.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 50000, Input::BP_TERM => 25, 'guess_loan_amount' => 4500000.0, 'guess_balance_cash_out_amount' => 40000, 'guess_loan_amortization_amount' => 31805.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
        fn () => ['total_contract_price' => 4500000, Input::CONSULTING_FEE => 10000, Input::PERCENT_DP => 5 / 100, Input::DP_TERM => 12, Input::BP_INTEREST_RATE => 7 / 100, Input::PERCENT_MF => 5 / 100, Input::LOW_CASH_OUT => 50000, Input::BP_TERM => 30, 'guess_loan_amount' => 4500000.0, 'guess_balance_cash_out_amount' => 40000, 'guess_loan_amortization_amount' => 29939.0, 'guess_down_payment_amount' => 0.0, 'guess_dp_amortization_amount' => 0.0],
    ];
});

it('has cash outs', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 30000,
        Input::CONSULTING_FEE => $consulting_fee = 10000
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    $mortgage->addCashOut(new CashOut('Processing Fee',11000.0,true));
    $mortgage->addCashOut($holding_fee = new CashOut('Holding Fee',12000.0,true));
    expect($mortgage->getCashOuts())->toBeInstanceOf(Collection::class);
    with ($mortgage->getCashOuts(), function (Collection $cash_outs) use ($holding_fee) {
        expect($cash_outs)->toHaveCount(3);
        with($cash_outs->first(), function (CashOut $cash_out) {
            expect($cash_out->getName())->toBe(Input::CONSULTING_FEE);
        });
        expect($cash_outs->where(function (CashOut $cash_out) {
            return $cash_out->getName() == 'Holding Fee';
        })->first())->toBe($holding_fee);
        expect($cash_outs->sum(function(CashOut $cash_out) {
             return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
        }))->toBe(10000.0 + 11000.0 + 12000.0);
    });
})->skip();

dataset('loan-remedy', function () {
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
            Assert::DOWN_PAYMENT => 2500000 * 5/100,
            Assert::CASH_OUT => 2500000 * 5/100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 19978.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::GROSS_MONTHLY_INCOME => 66593.34,

            Assert::BALANCE_CASH_OUT => 0.0
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
            Assert::DOWN_PAYMENT => 2500000 * 5/100,
            Assert::CASH_OUT => 2500000 * 5/100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 18213.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::GROSS_MONTHLY_INCOME => 60710.0,

            Assert::BALANCE_CASH_OUT => 0.0
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
            Assert::DOWN_PAYMENT => 2500000 * 5/100,
            Assert::CASH_OUT => 2500000 * 5/100 + 10625.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 10416.67,
            Assert::LOAN_AMOUNT => 2576875.0,
            Assert::LOAN_AMORTIZATION => 17144.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 10625.0,
            Assert::GROSS_MONTHLY_INCOME => 57146.67,

            Assert::BALANCE_CASH_OUT => 0.0
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
            Assert::DOWN_PAYMENT => 4500000 * 5/100,
            Assert::CASH_OUT => 4500000 * 5/100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 35961.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::GROSS_MONTHLY_INCOME => 119870.0,

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
            Assert::DOWN_PAYMENT => 4500000 * 5/100,
            Assert::CASH_OUT => 4500000 * 5/100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 32783.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::GROSS_MONTHLY_INCOME => 109276.67,

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
            Assert::DOWN_PAYMENT => 4500000 * 5/100,
            Assert::CASH_OUT => 4500000 * 5/100 + 19125.0,
            Assert::DOWN_PAYMENT_AMORTIZATION => 18750.0,
            Assert::LOAN_AMOUNT => 4638375.0,
            Assert::LOAN_AMORTIZATION => 30859.0,
            Assert::PARTIAL_MISCELLANEOUS_FEES => 19125.0,
            Assert::GROSS_MONTHLY_INCOME => 102863.34,

            Assert::BALANCE_CASH_OUT => 0.0,
        ],
    ];
});

it('has configurable miscellaneous fees', function () {
    $params = [
        Input::TCP => 2500000,
        Input::PERCENT_MF => 8.5/100,
    ];
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(50000);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));

    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::PERCENT_MF])->toBe($input_percent_mf = 8.5/100);
        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($input_percent_mf);
        $guess_mf = Money::of($input_tcp * $input_percent_mf, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_mf->compareTo(212500.0))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($guess_mf))->toBe(Amount::EQUAL);

        $mortgage->setMiscellaneousFees(250000);

        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        $guess_percent_mf = 250000/2500000;
        expect($guess_percent_mf)->toBe(10/100);
        expect($mortgage->getPercentMiscellaneousFees())->toBe($guess_percent_mf);
    });
})->skip();

it('has configurable down payment', function () {
    $params = [
        Input::TCP => 2500000,
        Input::DP_TERM => 24,
        Input::PERCENT_DP => 5/100,
    ];
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(50000);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));
    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::DP_TERM])->toBe($input_dp_term = 24);
        expect($params[Input::PERCENT_DP])->toBe($input_percent_dp = 5/100);
        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        expect($mortgage->getPercentDownPayment())->toBe($input_percent_dp);

        $guess_dp = Money::of($input_tcp * $input_percent_dp, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp->compareTo(125000.0))->toBe(Amount::EQUAL);
        $guess_dp_amortization = Money::of($input_tcp*$input_percent_dp/$input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_dp_amortization->compareTo(5208.34 ))->toBe(Amount::EQUAL);
        with ($mortgage->getDownPayment(), function (Payment $dp) use ($input_dp_term, $guess_dp, $guess_dp_amortization) {
            expect($dp->getPrincipal()->inclusive()->compareTo($guess_dp))->toBe(Amount::EQUAL);
            expect($dp->getTerm()->value)->toBe($input_dp_term);
            expect($dp->getTerm()->cycle)->toBe(Cycle::Monthly);
            expect($dp->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);
        });

        $mortgage->setDownPayment($input_dp = 250000.0);

        expect($mortgage->getContractPrice()->inclusive()->compareTo($input_tcp))->toBe(Amount::EQUAL);

        expect($mortgage->getDownPaymentTerm())->toBe($input_dp_term);
        $guess_percent_dp = 10/100;
        expect($mortgage->getPercentDownPayment())->toBe($guess_percent_dp);
        with ($mortgage->getDownPayment(), function (Payment $dp) use ($input_dp, $input_dp_term) {
            expect($dp->getPrincipal()->inclusive()->compareTo($input_dp))->toBe(Amount::EQUAL);
            expect($dp->getTerm()->value)->toBe($input_dp_term);
            expect($dp->getTerm()->cycle)->toBe(Cycle::Monthly);
            $guess_dp_amortization = Money::of($input_dp/$input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
            expect($guess_dp_amortization->compareTo(10416.67))->toBe(Amount::EQUAL);
            expect($dp->getMonthlyAmortization()->inclusive()->compareTo($guess_dp_amortization))->toBe(Amount::EQUAL);
        });
    });
})->skip();

it('has configurable contract price', function () {
    $params = [
        Input::TCP => 2500000,
        Input::PERCENT_DP => 5/100,
        Input::PERCENT_MF => 8.5/100,
        Input::DP_TERM => 12,
        Input::BP_TERM => 20,
        Input::BP_INTEREST_RATE => 7/100
    ];
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages(50000);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));

    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($property, $params) {
        //confirm inputs
        expect($params[Input::TCP])->toBe($input_tcp = 2500000);
        expect($params[Input::PERCENT_DP])->toBe($input_percent_dp = 5/100);
        expect($params[Input::PERCENT_MF])->toBe($input_percent_mf = 8.5/100);
        expect($params[Input::DP_TERM])->toBe($input_dp_term = 12);
        expect($params[Input::BP_TERM])->toBe($input_bp_term = 20);
        expect($params[Input::BP_INTEREST_RATE])->toBe($input_bp_interest_rate = 7/100);

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
        $guess_dp_amortization = Money::of($input_tcp * $input_percent_dp/$input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
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
        $monthly_interest_rate = $input_bp_interest_rate/12;
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(19978.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(66593.34))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 25-year term
        $input_bp_term = 25;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(18213.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(60710.0))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 30-year term
        $input_bp_term = 30;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(17144.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(57146.67))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //change contract price
        $mortgage->setContractPrice($new_tcp = 4500000.0);

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
        $guess_dp_amortization = Money::of($new_tcp * $input_percent_dp/$input_dp_term, 'PHP', roundingMode: RoundingMode::CEILING);
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
        $guess_bp = Money::of($new_tcp * (1-$input_percent_dp), 'PHP');
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
        $monthly_interest_rate = $input_bp_interest_rate/12;
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(35961.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(119870.0))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 25-year term
        $input_bp_term = 25;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(32783.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(109276.67))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);

        //loan @ 30-year term
        $input_bp_term = 30;
        $mortgage->setBalancePaymentTerm($input_bp_term);
        expect($mortgage->getLoan()->getTerm()->value)->toBe($input_bp_term);
        $months_to_pay = $mortgage->getLoan()->getTerm()->monthsToPay();
        $guess_loan_amortization = round((new PMT($monthly_interest_rate, $months_to_pay, $guess_loan->getAmount()->toFloat()))->evaluate());
        expect($guess_loan_amortization)->toBe(30859.0);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($guess_loan_amortization))->toBe(Amount::EQUAL);
        $guess_income_requirement = Money::of($guess_loan_amortization/$property->getDefaultDisposableIncomeRequirementMultiplier(), 'PHP', roundingMode: RoundingMode::CEILING);
        expect($guess_income_requirement->compareTo(102863.34))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($guess_income_requirement))->toBe(Amount::EQUAL);
    });
});

it('simulates', function (array $params) {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->addWages($params[Input::WAGES]);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = $params[Input::TCP], 'PHP')))
        ->setAppraisedValue(new Price(Money::of($tcp, 'PHP')));

    with(new Mortgage(property: $property, borrower: $borrower, params: $params), function (Mortgage $mortgage) use ($params) {
        expect($mortgage->getContractPrice()->inclusive()->compareTo($params[Input::TCP]))->toBe(Amount::EQUAL);
        expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo($params[Assert::MISCELLANEOUS_FEES]))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getPrincipal()->inclusive()->compareTo($params[Assert::DOWN_PAYMENT]))->toBe(Amount::EQUAL);
        expect($mortgage->getDownPayment()->getMonthlyAmortization()->inclusive()->compareTo($params[Assert::DOWN_PAYMENT_AMORTIZATION]))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getPrincipal()->inclusive()->compareTo($params[Assert::LOAN_AMOUNT]))->toBe(Amount::EQUAL);
        expect($mortgage->getLoan()->getMonthlyAmortization()->inclusive()->compareTo($params[Assert::LOAN_AMORTIZATION]))->toBe(Amount::EQUAL);
        expect($mortgage->getPartialMiscellaneousFees()->inclusive()->compareTo($params[Assert::PARTIAL_MISCELLANEOUS_FEES]))->toBe(Amount::EQUAL);
        expect($mortgage->getIncomeRequirement()->compareTo($params[Assert::GROSS_MONTHLY_INCOME]))->toBe(Amount::EQUAL);
    });
})->with('loan-remedy');
