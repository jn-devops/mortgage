<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Events\PercentDownPaymentUpdated;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Homeful\Mortgage\Events\MortgageTermUpdated;
use Homeful\Payment\Exceptions\MaxCycleBreached;
use Brick\Math\Exception\NumberFormatException;
use Homeful\Mortgage\Events\DownPaymentUpdated;
use Homeful\Payment\Exceptions\MinTermBreached;
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
     * default is 0%
     */
    public function getPercentDownPayment(): float
    {
        return $this->percent_down_payment ?? 0.0;
    }

    /**
     * @return Mortgage|HasMultipliers
     */
    public function setPercentDownPayment(float $percent_down_payment, bool $send = true): self
    {
        $this->percent_down_payment = $percent_down_payment;

        $send && PercentDownPaymentUpdated::dispatch($this);

        return $this;
    }

    /**
     * default is 0
     */
    public function getDownPaymentTerm(): int
    {
        return $this->down_payment_term ?? 0;
    }

    /**
     * @return HasTerms|Mortgage
     */
    public function setDownPaymentTerm(int $down_payment_term, bool $send = true): self
    {
        $this->down_payment_term = $down_payment_term;
        $send && MortgageTermUpdated::dispatch($this);

        return $this;
    }

    public function getDownPayment(): Payment
    {
        return $this->down_payment;
    }

    /**
     * @return HasDownPayment|Mortgage
     *
     * @throws MaxCycleBreached
     * @throws MinTermBreached
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function setDownPayment(Payment|float $down_payment, ?int $term = null): self
    {
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
