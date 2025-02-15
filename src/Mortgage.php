<?php

namespace Homeful\Mortgage;

use Homeful\Mortgage\Actions\CalculateLoanDifference;
use Homeful\Mortgage\Classes\AddOnFeeToLoanAmortization;
use Homeful\Mortgage\Traits\HasMiscellaneousFees;
use Homeful\Mortgage\Traits\HasContractPrice;
use Homeful\Mortgage\Traits\HasDownPayment;
use Homeful\Mortgage\Traits\HasMultipliers;
use Homeful\Mortgage\Traits\HasAddOnFeesToLoanAmortization;
use Illuminate\Support\Facades\Validator;
use Homeful\Mortgage\Traits\HasProperty;
use Homeful\Mortgage\Traits\HasBorrower;
use Homeful\Mortgage\Traits\HasCashOuts;
use Homeful\Mortgage\Traits\HasConfig;
use Homeful\Mortgage\Traits\HasPromos;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Mortgage\Traits\HasTerms;
use Homeful\Payment\PresentValue;
use Homeful\Common\Classes\Input;
use Homeful\Payment\Class\Term;
use Homeful\Property\Property;
use Illuminate\Support\Carbon;
use Homeful\Borrower\Borrower;
use \Brick\Math\RoundingMode;
use Homeful\Payment\Payment;
use Illuminate\Support\Arr;
use Whitecube\Price\Price;
use Brick\Money\Money;
use Homeful\Borrower\Exceptions\BirthdateNotSet;

/**
 * Class Mortgage
 *
 * @method float getPercentDownPayment()
 * @method Mortgage setPercentDownPayment(float $percent_down_payment, bool $send = true)
 * @method float getDownPaymentTerm()
 * @method Mortgage setDownPaymentTerm(int $down_payment_term, bool $send = true)
 * @method Payment getDownPayment()
 * @method Mortgage setDownPayment(Payment|float $down_payment, int $term = null)
 * @method float getPercentMiscellaneousFees()
 * @method Mortgage setPercentMiscellaneousFees(float $percent_miscellaneous_fees)
 * @method Price getMiscellaneousFees()
 * @method Mortgage setMiscellaneousFees(Price|float $miscellaneous_fees)
 * @method Price getPartialMiscellaneousFees()
 * @method Price getBalanceMiscellaneousFees()
 * @method int getBalancePaymentTerm()
 * @method Mortgage setBalancePaymentTerm(int $balance_payment_term)
 */
final class Mortgage
{
    use HasBorrower;
    use HasCashOuts;
    use HasConfig;
    use HasContractPrice;
    use HasDownPayment;
    use HasMiscellaneousFees;
    use HasMultipliers;
    use HasPromos;
    use HasProperty;
    use HasTerms;
    use HasAddOnFeesToLoanAmortization;

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function __construct(Property $property, Borrower $borrower, array $params)
    {
        $this->initCashOuts()->setBorrower($borrower)->setProperty($property);

        $validated = Validator::validate($params, [
            Input::PERCENT_DP => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::BP_TERM => ['nullable', 'integer', 'min:1', 'max:30'],
            Input::BP_INTEREST_RATE => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::PERCENT_MF => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::CONSULTING_FEE => ['nullable', 'numeric', 'min:0.0', 'max:30000.0'],
            Input::DP_TERM => ['nullable', 'integer', 'min:1', 'max:24'],
            Input::LOW_CASH_OUT => ['nullable', 'numeric', 'min:0.0', 'max:100000.0'],
            Input::MORTGAGE_REDEMPTION_INSURANCE =>  ['nullable', 'numeric', 'min:0.0', 'max:10000.0'],
            Input::ANNUAL_FIRE_INSURANCE =>  ['nullable', 'numeric', 'min:0.0', 'max:10000.0'],
        ]);

        if (!isset($validated[Input::PERCENT_DP]))
            Arr::set($validated, Input::PERCENT_DP, $property->getPercentDownPayment());

        if (!isset($validated[Input::DP_TERM]))
            Arr::set($validated, Input::DP_TERM, $property->getDownPaymentTerm());

        if (!isset($validated[Input::BP_INTEREST_RATE]))
            Arr::set($validated, Input::BP_INTEREST_RATE, $property->getDefaultAnnualInterestRateFromBorrower($borrower));

        if (!isset($validated[Input::BP_TERM])) {
            try {
                Arr::set($validated, Input::BP_TERM, $borrower->getMaximumTermAllowed());
            }
            catch (BirthdateNotSet $exception) {}
        }

        if (!isset($validated[Input::PERCENT_MF]))
            Arr::set($validated, Input::PERCENT_MF, $property->getPercentMiscellaneousFees());//TODO: there might be an error in rounding off

        $this->update($validated);
    }

    /**
     * @return $this
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function update(array $params): self
    {
        $consulting_fee = (float) Arr::get($params, Input::CONSULTING_FEE, 0.0);
        $dp_percent = (float) Arr::get($params, Input::PERCENT_DP, 0.0);
        $bp_term = (int) Arr::get($params, Input::BP_TERM, 1);
        $bp_interest_rate = (float) Arr::get($params, Input::BP_INTEREST_RATE, 0.0);
        $mf_percent = (float) Arr::get($params, Input::PERCENT_MF, 0.0);
        $dp_term = (int) Arr::get($params, Input::DP_TERM, 1);
        $low_cash_out = (float) Arr::get($params, Input::LOW_CASH_OUT, 0.0);

        $this->mortgage_redemption_insurance = (float) Arr::get($params, Input::MORTGAGE_REDEMPTION_INSURANCE, 0.0);
        $this->monthly_fire_insurance = (float) Arr::get($params, Input::ANNUAL_FIRE_INSURANCE, 0.0);

        $this
            ->setConsultingFee($consulting_fee)
            ->setPercentDownPayment($dp_percent)
            ->setBalancepaymentTerm($bp_term)
            ->setInterestRate($bp_interest_rate)
            ->setPercentMiscellaneousFees($mf_percent)
            ->setDownPaymentTerm($dp_term)
            ->setLowCashOut($low_cash_out);

        $this->addCashOut(new CashOut(name: Input::DOWN_PAYMENT, amount: $this->getDownPayment()->getPrincipal(), deductible: false));
        $this->addCashOut(new CashOut(name: Input::PARTIAL_MISCELLANEOUS_FEES, amount: $this->getPartialMiscellaneousFees(), deductible: false));

//        $this->addAddOnFeeToLoanAmortization(new AddOnFeeToLoanAmortization(name: Input::MORTGAGE_REDEMPTION_INSURANCE, amount: $mortgage_redemption_insurance, deductible: false));
//        $this->addAddOnFeeToLoanAmortization(new AddOnFeeToLoanAmortization(name: Input::ANNUAL_FIRE_INSURANCE, amount: $monthly_fire_insurance, deductible: false));

        return $this;
    }

    /**
     * balance payment = total contract price - down payment
     */
    public function getBalancePayment(): Price
    {
        $down_payment = $this->getDownPayment()->getPrincipal()->inclusive();
        $balance_payment = $this->getContractPrice()->inclusive()->minus($down_payment);

        return new Price($balance_payment);
    }

    /**
     * loan principal = balance payment + balance miscellaneous fees
     * loan term = balance payment term
     *
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Homeful\Payment\Exceptions\MaxCycleBreached
     * @throws \Homeful\Payment\Exceptions\MinTermBreached
     */
    public function getLoan(): Payment
    {
        $balance_mf = $this->getBalanceMiscellaneousFees()->inclusive();
        $balance_payment = $this->getBalancePayment()->inclusive();
        $loan = new Price($balance_payment->plus($balance_mf));

        $payment = (new Payment)
            ->setPrincipal($loan)
            ->setTerm(new Term($this->getBalancePaymentTerm()))
            ->setInterestRate($this->getInterestRate())
            ->setPercentDisposableIncomeRequirement($this->getProperty()->getDisposableIncomeRequirementMultiplier())
            ;

        if ($fees = $this->getAddOnFeesToLoanAmortization()) {
            $payment->setAddOnFeesToPayment($fees);
//            dd($payment->getTotalAddOnFeesToPayment()->inclusive()->getAmount()->toFloat());
        }

        return $payment;
    }

    //    public function getBalanceCashOut(): Price
    //    {
    //        $amount = Money::of(0, 'PHP');
    //        if ($this->low_cash_out instanceof Price) {
    //            $low_cash_out = $this->low_cash_out->inclusive();
    //            if ($low_cash_out->compareTo(0) == 1) {
    //                $deductible_cash_outs = $this->getCashOuts()->sum(function(CashOut $cash_out) {
    //                    return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
    //                });
    //                $amount = $low_cash_out->minus($deductible_cash_outs);
    //            }
    //        }
    //
    //        return new Price($amount);
    //    }

    /**
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\MoneyMismatchException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function isPromotional(): bool
    {
        $deductible_cash_outs = $this->getCashOuts()->sum(function (CashOut $cash_out) {
            return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
        });

        return $this->getLowCashOut()->inclusive()->compareTo($deductible_cash_outs) == 1;
    }

    public function getJointBorrowerDisposableMonthlyIncome(): Price
    {
        return $this->getBorrower()->getJointMonthlyDisposableIncome($this->property);
    }

    public function getPresentValueFromMonthlyDisposableIncomePayments(): PresentValue
    {
        return (new PresentValue)
            ->setPayment($this->getJointBorrowerDisposableMonthlyIncome())
            ->setTerm(new Term($this->getBalancePaymentTerm()))
            ->setInterestRate($this->getInterestRate());
    }

    public function getLoanDifference(): Price
    {
        return (new CalculateLoanDifference)->get($this->getLoan(), [
            'payment' => $this->getJointBorrowerDisposableMonthlyIncome(),
            'term' => new Term($this->getBalancePaymentTerm()),
            'interest_rate' => $this->getInterestRate(),
        ]);
    }

//    public function getTotalCashOut(): Price
//    {
//        $total_cash_out = new Price(Money::of(0, 'PHP'));
//
//        $this->getCashOuts()->each(function (CashOut $cash_out) use ($total_cash_out) {
//            $total_cash_out->addModifier('cash out item', $cash_out->getAmount()->inclusive());
//        });
//
//        return $total_cash_out;
//    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Homeful\Borrower\Exceptions\MaximumBorrowingAgeBreached
     * @throws \Homeful\Borrower\Exceptions\MinimumBorrowingAgeNotMet
     */
    public static function createWithTypicalBorrower(Property $property, array $params): Mortgage
    {
        $tcp = ($property->getTotalContractPrice()->inclusive()->getAmount()->toFloat() * (1.085)) * .95;

        $disposable_income_requirement = (new Payment)
            ->setPrincipal($tcp)->setTerm(self::getDefaultLoanTerm())->setInterestRate(self::getDefaultInterestRate())
            ->getMonthlyAmortization()->addModifier('gmi', function ($modifier) use ($property) {
                $modifier->divide($property->getDisposableIncomeRequirementMultiplier(), RoundingMode::CEILING);
            })->inclusive();

        $disposable_income_requirement = $disposable_income_requirement instanceof Money ? $disposable_income_requirement : null;
        $birthdate_from_default_age = Carbon::now()->addYears(-1 * self::getDefaultAge());
        $borrower = (new Borrower)->setGrossMonthlyIncome($disposable_income_requirement)->setBirthdate($birthdate_from_default_age);
        return new self($property, $borrower, $params);
    }
}
