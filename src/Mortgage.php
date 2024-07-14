<?php

namespace Homeful\Mortgage;

use Brick\Money\Money;
use Homeful\Borrower\Borrower;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Payment\Enums\Cycle;
use Homeful\Property\Property;
use Whitecube\Price\Price;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Homeful\Payment\Payment;
use Homeful\Payment\Class\Term;
use Whitecube\Price\Modifier;
use Brick\Math\RoundingMode;
use Homeful\Mortgage\Classes\Input;
use Homeful\Mortgage\Traits\{HasBorrower, HasCashOuts, HasContractPrice, HasDownPayment, HasMiscellaneousFees, HasMultipliers, HasPromos, HasProperty,  HasTerms};

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
class Mortgage
{
    use HasBorrower;
    use HasProperty;
    use HasCashOuts;
    use HasContractPrice;
    use HasMiscellaneousFees;
    use HasTerms;
    use HasPromos;
    use HasMultipliers;
    use HasDownPayment;

    /**
     * @param Property $property
     * @param Borrower $borrower
     * @param array $params
     */
    public function __construct(Property $property, Borrower $borrower, array $params)
    {
        $this->initCashOuts()->setProperty($property)->setBorrower($borrower);

        $validated = Validator::validate($params, [
            Input::PERCENT_DP => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::BP_TERM => ['nullable', 'integer', 'min:1', 'max:30'],
            Input::BP_INTEREST_RATE => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::PERCENT_MF => ['nullable', 'numeric', 'min:0', 'max:1'],
            Input::CONSULTING_FEE => ['nullable', 'numeric', 'min:0.0', 'max:30000.0'],
            Input::DP_TERM => ['nullable', 'integer', 'min:1', 'max:24'],
            Input::LOW_CASH_OUT => ['nullable', 'numeric', 'min:0.0', 'max:100000.0'],
        ]);

        $this->update($validated);
    }

    /**
     * @param array $params
     * @return $this
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function update(array $params): self
    {
        $consulting_fee =  (float) Arr::get($params, Input::CONSULTING_FEE, 0.0);
        $dp_percent =  (float) Arr::get($params, Input::PERCENT_DP, 0.0);
        $bp_term = (int) Arr::get($params, Input::BP_TERM, 1);
        $bp_interest_rate = (float) Arr::get($params, Input::BP_INTEREST_RATE, 0.0);
        $mf_percent =  (float) Arr::get($params, Input::PERCENT_MF, 0.0);
        $dp_term = (int) Arr::get($params, Input::DP_TERM, 1);
        $low_cash_out = (float) Arr::get($params, Input::LOW_CASH_OUT, 0.0);

        $this
            ->setConsultingFee($consulting_fee)
            ->setPercentDownPayment($dp_percent)
            ->setBalancepaymentTerm($bp_term)
            ->setInterestRate($bp_interest_rate)
            ->setPercentMiscellaneousFees($mf_percent)
            ->setDownPaymentTerm($dp_term)
            ->setLowCashOut($low_cash_out)
        ;

        return $this;
    }

    /**
     * balance payment = total contract price - down payment
     *
     * @return Price
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
     * @return Payment
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
        return (new Payment)
            ->setPrincipal($loan)
            ->setTerm(new Term($this->getBalancePaymentTerm()))
            ->setInterestRate($this->getInterestRate());
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

    public function isPromotional(): bool
    {
        $deductible_cash_outs = $this->getCashOuts()->sum(function(CashOut $cash_out) {
            return $cash_out->getAmount()->inclusive()->getAmount()->toFloat();
        });

        return $this->getLowCashOut()->inclusive()->compareTo($deductible_cash_outs) == 1;
    }



    /**
     * @return Money
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     * @throws \Homeful\Payment\Exceptions\MaxCycleBreached
     * @throws \Homeful\Payment\Exceptions\MinTermBreached
     */
    public function getIncomeRequirement(): Money
    {
        $multiplier = $this->property->getDefaultDisposableIncomeRequirementMultiplier();

        return $this->getLoan()->getMonthlyAmortization()->inclusive()->dividedBy(that: $multiplier, roundingMode: RoundingMode::CEILING);
    }

//    /**
//     * @return Price
//     */
//    public function getLoan(): Price
//    {
//        $percent_bp = 1 - $this->getPercentDownPayment();
//        $tcp = new Price($this->getContractPrice()->base());
//
//        return $tcp->addModifier('balance', function (Modifier $modifier) use ($percent_bp) {
//            $this->isPromotional()
//                ? $modifier->multiply($percent_bp)->add($this->getMiscellaneousFees()->base())
//                : $modifier->add($this->getMiscellaneousFees()->base())->multiply($percent_bp);
//        });
//    }
}
