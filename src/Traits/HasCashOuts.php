<?php

namespace Homeful\Mortgage\Traits;

use Homeful\Common\Classes\DeductibleFeeFromPayment;
use Homeful\Common\Classes\AmountCollectionItem;
use Homeful\Common\Classes\AddOnFeeToPayment;
use Homeful\Mortgage\Enums\Account;
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
     * @param AmountCollectionItem $cashOut
     * @return HasCashOuts|Mortgage
     */
    public function addCashOut(AmountCollectionItem $cashOut): self
    {
        $this->cashOuts->add($cashOut);

        return $this;
    }

    /**
     * Calculate the total cash out amount from the cash‐out items.
     *
     * This method iterates over the cash‐out items returned by `getCashOuts()`.
     * If an Account filter is provided, only items whose tag matches the given account’s value
     * are considered; otherwise, all items are summed.
     * Each matching item’s inclusive monetary amount is added as a modifier to a base Price of zero.
     *
     * **Usage Examples:**
     * - To sum all cash‐out items:
     *   ```php
     *   $total = $this->getTotalCashOut();
     *   ```
     * - To sum cash‐out items for a specific account:
     *   ```php
     *   $total = $this->getTotalCashOut($account);
     *   ```
     *
     * **Return:**
     * - A Price object in the default currency (PHP) representing the total cash out.
     *
     * @param Account|null $account Optional filter; if provided, only items matching this account are summed.
     * @return Price The total cash out amount.
     *
     * @throws \Brick\Math\Exception\NumberFormatException If a monetary conversion fails.
     * @throws \Brick\Math\Exception\RoundingNecessaryException If rounding is required but not allowed.
     * @throws \Brick\Money\Exception\UnknownCurrencyException If an unknown currency is encountered.
     */
    public function getTotalCashOut(?Account $account = null): Price
    {
        // Initialize total as zero in the default currency
        $totalCashOut = new Price(Money::of(0, 'PHP'));

        // Filter the cash-out collection if an account filter is provided
        $this->getCashOuts()
            ->when($account, function ($collection) use ($account) {
                return $collection->filter(fn(AmountCollectionItem $item) => $item->getTag() === $account->value);
            })
            ->each(function (AmountCollectionItem $item) use ($totalCashOut) {
                // Add each item's inclusive amount as a modifier to the total
                $totalCashOut->addModifier('amount collection item', $item->getAmount()->inclusive());
            });

        return $totalCashOut;
    }

    /**
     * Retrieve the consulting fee from the cash-out collection.
     *
     * This method filters the cash-out collection for an item whose name matches
     * the consulting fee identifier defined by `Input::CONSULTING_FEE`. If such an
     * item is found, it returns its associated amount as a Price object. Otherwise,
     * it returns a Price object representing zero PHP.
     *
     * @return \Whitecube\Price\Price The consulting fee as a Price object.
     *
     * @throws \Brick\Math\Exception\NumberFormatException If an error occurs during number formatting.
     * @throws \Brick\Math\Exception\RoundingNecessaryException If rounding is required but cannot be performed.
     * @throws \Brick\Money\Exception\UnknownCurrencyException If the currency provided is unknown.
     */
    public function getConsultingFee(): Price
    {
        $cashOut = $this->getCashOuts()->first(fn(AmountCollectionItem $item) => $item->getName() === Input::CONSULTING_FEE);

        return $cashOut instanceof AmountCollectionItem
            ? $cashOut->getAmount()
            : new Price(Money::of(0, 'PHP'));
    }

    /**
     * Set the consulting fee for the mortgage/payment.
     *
     * This method accepts a consulting fee, either as a Price object or as a float, and stores it in the payment.
     * It first converts the fee to a numeric value by retrieving the inclusive amount if the fee is a Price instance.
     * If the fee is greater than zero, the method adds the fee as an add-on fee using the
     * `AddOnFeeToPayment` class. The fee is identified by the constant `Input::CONSULTING_FEE` and is tagged
     * with the value from `Account::DOWN_PAYMENT->value` for categorization.
     *
     * @param Price|float $consulting_fee The consulting fee amount.
     * @return HasCashOuts|Mortgage Returns the current instance for method chaining.
     */
    public function setConsultingFee(Price|float $consulting_fee): self
    {
        // Convert the consulting fee to a float, handling Price instances accordingly.
        $value = $consulting_fee instanceof Price
            ? $consulting_fee->inclusive()->getAmount()->toFloat()
            : $consulting_fee;

        // If the fee is greater than zero, add it as an add-on fee.
        if ($value > 0.0) {
            $this->addCashOut(new AddOnFeeToPayment(
                name: Input::CONSULTING_FEE,
                amount: $consulting_fee,
                tag: Account::DOWN_PAYMENT->value
            ));
        }

        return $this;
    }

    /**
     * Retrieve the processing fee from the cash-out collection.
     *
     * This method searches through the cash-out collection for the first item whose name
     * matches the processing fee identifier (defined by `Input::PROCESSING_FEE`). If found,
     * it returns the associated amount as a Price object. Otherwise, it returns a Price object
     * representing zero PHP.
     *
     * @return \Whitecube\Price\Price The processing fee as a Price object.
     *
     * @throws \Brick\Math\Exception\NumberFormatException If an error occurs during number formatting.
     * @throws \Brick\Math\Exception\RoundingNecessaryException If rounding is necessary but cannot be performed.
     * @throws \Brick\Money\Exception\UnknownCurrencyException If the specified currency is unknown.
     */
    public function getProcessingFee(): \Whitecube\Price\Price
    {
        $feeItem = $this->getCashOuts()->first(fn(AmountCollectionItem $item) => $item->getName() === Input::PROCESSING_FEE);

        return $feeItem instanceof AmountCollectionItem
            ? $feeItem->getAmount()
            : new \Whitecube\Price\Price(\Brick\Money\Money::of(0, 'PHP'));
    }

    /**
     * Set the processing fee for the mortgage/payment.
     *
     * This method accepts a processing fee and an optional waived amount, both of which can be provided
     * as either a Price object or a float. It calculates the net processing fee by subtracting the
     * waived amount from the processing fee and ensuring the result is not negative.
     *
     * If the calculated fee is greater than zero, it adds a deductible fee to the cash-out collection.
     * The fee is identified by the constant `Input::PROCESSING_FEE` and is tagged with the value
     * from `Account::DOWN_PAYMENT->value`.
     *
     * @param Price|float $processing_fee The processing fee amount.
     * @param Price|float|null $waived_amount The optional waived amount to reduce the processing fee.
     * @return HasCashOuts|Mortgage Returns the current instance for method chaining.
     */
    public function setProcessingFee(Price|float $processing_fee, Price|float|null $waived_amount = null): self
    {
        // Convert processing fee and waived amount to float, handling Price instances accordingly.
        $fee_value = $processing_fee instanceof Price
            ? $processing_fee->inclusive()->getAmount()->toFloat()
            : $processing_fee;

        $waived_value = $waived_amount instanceof Price
            ? $waived_amount->inclusive()->getAmount()->toFloat()
            : ($waived_amount ?? 0.0);

        // Calculate the net processing fee, ensuring it is not negative.
        $net_value = max(0, $fee_value - $waived_value);

        // If the net fee is greater than zero, add it as a deductible fee.
        if ($net_value > 0.0) {
            $this->addCashOut(new DeductibleFeeFromPayment(
                name: Input::PROCESSING_FEE,
                amount: $net_value,
                tag: Account::DOWN_PAYMENT->value
            ));
        }

        return $this;
    }

//    /**
//     * Set the processing fee for the mortgage/payment.
//     *
//     * This method accepts a processing fee (either as a Price object or a float). It converts the fee into
//     * a numeric value using the inclusive amount if it is a Price instance. If the fee is greater than zero,
//     * it adds a deductible fee to the cash-out collection. The fee is identified by the constant
//     * `Input::PROCESSING_FEE` and is tagged with the value from `Account::DOWN_PAYMENT->value`.
//     *
//     * @param Price|float $processing_fee The processing fee amount.
//     * @return HasCashOuts|Mortgage Returns the current instance for method chaining.
//     */
//    public function setProcessingFee(Price|float $processing_fee): self
//    {
//        // Convert the processing fee to a float, handling Price instances accordingly.
//        $value = $processing_fee instanceof Price
//            ? $processing_fee->inclusive()->getAmount()->toFloat()
//            : $processing_fee;
//
//        // If the fee is greater than zero, add it as a deductible fee.
//        if ($value > 0.0) {
//            $this->addCashOut(new DeductibleFeeFromPayment(
//                name: Input::PROCESSING_FEE,
//                amount: $processing_fee,
//                tag: Account::DOWN_PAYMENT->value
//            ));
//        }
//
//        return $this;
//    }
}
