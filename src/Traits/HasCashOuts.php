<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Mortgage\Classes\CashOut;
use Illuminate\Support\Collection;
use Homeful\Common\Classes\Input;
use Homeful\Mortgage\Mortgage;
use Whitecube\Price\Price;
use Brick\Money\Money;

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

    /**
     * @return Collection
     */
    public function getCashOuts(): Collection
    {
        return $this->cashOuts;
    }

    /**
     * @param Collection $cashOuts
     * @return HasCashOuts|Mortgage
     */
    public function setCashOuts(Collection $cashOuts): self
    {
        $this->cashOuts = $cashOuts;

        return $this;
    }

    /**
     * @param CashOut $cashOut
     * @return HasCashOuts|Mortgage
     */
    public function addCashOut(CashOut $cashOut): self
    {
        $this->cashOuts->add($cashOut);

        return $this;
    }

    /**
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function getTotalCashOut(): Price
    {
        $total_cash_out = new Price(Money::of(0, 'PHP'));

        $this->getCashOuts()->each(function (CashOut $cash_out) use ($total_cash_out) {
            $total_cash_out->addModifier('cash out item', $cash_out->getAmount()->inclusive());
        });

        return $total_cash_out;
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
     * @param Price|float $consulting_fee
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
