<?php

namespace Homeful\Mortgage\Traits;

use Brick\Money\Money;
use Homeful\Common\Classes\Input;
use Homeful\Mortgage\Classes\CashOut;
use Homeful\Mortgage\Mortgage;
use Illuminate\Support\Collection;
use Whitecube\Price\Price;

trait HasCashOuts
{
    protected Collection $cashOuts;

    /**
     * @return HasCashOuts|Mortgage
     */
    protected function initCashOuts(): self
    {
        $this->cashOuts = new Collection;

        return $this;
    }

    public function getCashOuts(): Collection
    {
        return $this->cashOuts;
    }

    /**
     * @return HasCashOuts|Mortgage
     */
    public function setCashOuts(Collection $cashOuts): self
    {
        $this->cashOuts = $cashOuts;

        return $this;
    }

    /**
     * @return HasCashOuts|Mortgage
     */
    public function addCashOut(CashOut $cashOut): self
    {
        $this->cashOuts->add($cashOut);

        return $this;
    }

    /**
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getConsultingFee(): Price
    {
        $cash_out = $this->getCashOuts()->where(function (CashOut $cash_out) {
            return $cash_out->getName() == Input::CONSULTING_FEE;
        })->first();

        return $cash_out instanceof CashOut ? $cash_out->getAmount() : new Price(Money::of(0, 'PHP'));
    }

    /**
     * @return Mortgage|HasCashOuts
     */
    public function setConsultingFee(Price|float $consulting_fee): self
    {
        $value = $consulting_fee instanceof Price ? $consulting_fee->inclusive()->getAmount()->toFloat() : $consulting_fee;
        if ($value > 0.0) {
            $this->addCashOut(new CashOut(name: Input::CONSULTING_FEE, amount: $consulting_fee, deductible: true));
        }

        return $this;
    }
}
