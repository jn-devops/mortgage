<?php

use Homeful\Mortgage\Data\MortgageData;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Mortgage\Classes\Amount;
use Illuminate\Support\Collection;
use Homeful\Common\Classes\Assert;
use Homeful\Common\Classes\Input;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Mortgage\Mortgage;
use Homeful\Borrower\Borrower;
use Homeful\Property\Property;
use Illuminate\Support\Carbon;
use Brick\Math\RoundingMode;
use Homeful\Payment\Payment;
use Jarouche\Financial\PMT;
use Jarouche\Financial\PV;
use Whitecube\Price\Price;
use Brick\Money\Money;

it('accepts property and borrower and has configurable parameters', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->setGrossMonthlyIncome(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of(850000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(850000, 'PHP')));
    $params = [
        Input::PERCENT_DP => 0 / 100,
        Input::BP_INTEREST_RATE => 6.25 / 100,
        Input::BP_TERM => 30,
        Input::PERCENT_MF => 0 / 100,
        Input::CONSULTING_FEE => 10000,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);

    expect($mortgage->updateContractPrice()->base()->compareTo(850000))->toBe(Amount::EQUAL);
    expect($mortgage->updateContractPrice()->inclusive()->compareTo(850000))->toBe(Amount::EQUAL);
    expect($mortgage->getPercentDownPayment())->toBe(0.0);
    expect($mortgage->getBalancepaymentTerm())->toBe(30);
    expect($mortgage->getPercentMiscellaneousFees())->toBe(0 / 100);
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
    expect($mortgage->getMiscellaneousFees()->inclusive()->compareTo(850000 * 0 / 100))->toBe(Amount::EQUAL);
})->skip();

it('has down payment', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->setGrossMonthlyIncome(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 850000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(850000, 'PHP')));
    $params = [
        Input::PERCENT_DP => $percent_dp = 5 / 100,
        Input::PERCENT_MF => $percent_mf = 8.5 / 100,
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
        expect($dp->getPrincipal()->inclusive()->compareTo($tcp * (1 + $percent_mf) * $percent_dp))->toBe(Amount::EQUAL);
    });
})->skip();

it('has balance payment and amortization', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->setGrossMonthlyIncome(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::PERCENT_DP => $percent_dp = 5 / 100,
        Input::PERCENT_MF => $percent_mf = 8.5 / 100,
        Input::BP_TERM => $bp_term = 20,
        Input::BP_INTEREST_RATE => $bp_interest = 7 / 100,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getPercentDownPayment())->toBe($percent_dp);
    with($mortgage->getBalancePayment(), function (Payment $bp) use ($tcp, $percent_dp, $percent_mf, $bp_term, $bp_interest) {
        expect($bp->getTerm()->value)->toBe($bp_term);
        expect($bp->getTerm()->monthsToPay())->toBe($bp_term * 12);
        expect($bp->getTerm()->cycle)->toBe(Cycle::Yearly);
        expect($bp->getInterestRate())->toBe($bp_interest);
        expect($bp->getPrincipal()->base()->compareTo($tcp * (1 - $percent_dp)))->toBe(Amount::EQUAL);
        expect($bp->getPrincipal()->inclusive()->compareTo($tcp * (1 - $percent_dp) * (1 + $percent_mf)))->toBe(Amount::EQUAL);
        expect($bp->getMonthlyAmortization()->inclusive()->compareTo(19978.0))->toBe(Amount::EQUAL);
    });
})->skip();

it('has low cash out', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->setGrossMonthlyIncome(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 30000,
        Input::CONSULTING_FEE => $consulting_fee = 10000,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getLowCashOut()->inclusive()->compareTo($low_cash_out))->toBe(Amount::EQUAL);
    expect($mortgage->getBalanceCashOut()->inclusive()->compareTo($low_cash_out - $consulting_fee))->toBe(Amount::EQUAL);
    expect($mortgage->isPromotional())->toBeTrue();
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 0,
        Input::CONSULTING_FEE => 10000,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    expect($mortgage->getLowCashOut()->inclusive()->compareTo($low_cash_out))->toBe(Amount::EQUAL);
    expect($mortgage->getBalanceCashOut()->inclusive()->compareTo(0.0))->toBe(Amount::EQUAL);
    expect($mortgage->isPromotional())->toBeFalse();
})->skip();

//it('has base and mf inclusive loan component', function () {
//    $borrower = (new Borrower)
//        ->setRegional(false)
//        ->setGrossMonthlyIncome(14500);
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

it('has cash outs', function () {
    $borrower = (new Borrower)
        ->setRegional(false)
        ->setGrossMonthlyIncome(14500);
    $property = (new Property)
        ->setTotalContractPrice(new Price(Money::of($tcp = 2500000, 'PHP')))
        ->setAppraisedValue(new Price(Money::of(2500000, 'PHP')));
    $params = [
        Input::LOW_CASH_OUT => $low_cash_out = 30000,
        Input::CONSULTING_FEE => $consulting_fee = 10000,
    ];
    $mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
    $mortgage->addCashOut(new CashOut('Processing Fee', 11000.0, true));
    $mortgage->addCashOut($holding_fee = new CashOut('Holding Fee', 12000.0, true));
    expect($mortgage->getCashOuts())->toBeInstanceOf(Collection::class);
    with($mortgage->getCashOuts(), function (Collection $cash_outs) use ($holding_fee) {
        expect($cash_outs)->toHaveCount(3);
        with($cash_outs->first(), function (CashOut $cash_out) {
            expect($cash_out->getName())->toBe(Input::CONSULTING_FEE);
        });
        expect($cash_outs->where(function (CashOut $cash_out) {
            return $cash_out->getName() == 'Holding Fee';
        })->first())->toBe($holding_fee);
        expect($cash_outs->sum(function (CashOut $cash_out) {
            return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
        }))->toBe(10000.0 + 11000.0 + 12000.0);
    });
})->skip();

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
