<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Events\{DownPaymentUpdated, MortgageTermUpdated, PercentDownPaymentUpdated};
use Brick\Math\Exception\{NumberFormatException, RoundingNecessaryException};
use Homeful\Payment\Exceptions\{MaxCycleBreached, MinTermBreached};
use Brick\Money\Exception\UnknownCurrencyException;
use Homeful\Payment\Enums\Cycle;
use Homeful\Payment\Class\Term;
use Homeful\Mortgage\Mortgage;
use Homeful\Payment\Payment;

trait HasDownPayment
{
    protected float $percent_down_payment;
    protected int $down_payment_term;
    protected Payment $down_payment;

    /**
     * @return float
     */
    public function getPercentDownPayment(): float
    {
        return $this->percent_down_payment ?? 0.0;
    }

    /**
     * @param float $percent_down_payment
     * @param bool $send
     * @return Mortgage|HasMultipliers
     */
    public function setPercentDownPayment(float $percent_down_payment, bool $send = true): self
    {
        $this->percent_down_payment = $percent_down_payment;

        $send && PercentDownPaymentUpdated::dispatch($this);

        return $this;
    }

    /**
     * @return int
     */
    public function getDownPaymentTerm(): int
    {
        return $this->down_payment_term ?? 0;
    }

    /**
     * @param int $down_payment_term
     * @param bool $send
     * @return HasTerms|Mortgage
     */
    public function setDownPaymentTerm(int $down_payment_term, bool $send = true): self
    {
        $this->down_payment_term = $down_payment_term;
        $send && MortgageTermUpdated::dispatch($this);

        return $this;
    }

    /**
     * @return Payment
     */
    public function getDownPayment(): Payment
    {
        return $this->down_payment;
    }

    /**
     * @param Payment|float $down_payment
     * @param int $term
     * @return HasDownPayment|Mortgage
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws MaxCycleBreached
     * @throws MinTermBreached
     */
    public function setDownPayment(Payment|float $down_payment, int $term = null): self
    {
//        $term = is_null($term) ? $term : $this->getDownPaymentTerm() ?? 12;
        $term = $term ?: $this->getDownPaymentTerm();
        $this->down_payment = $down_payment instanceof Payment
            ? $down_payment
            : (new Payment)
                ->setPrincipal($down_payment)
                ->setTerm(new Term($term, Cycle::Monthly));
        DownPaymentUpdated::dispatch($this);

        return $this;
    }
}
