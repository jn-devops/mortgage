
# 🏡 Mortgage Computation Package

This package encapsulates the mortgage loan processing logic of the Homeful platform. It manages everything from borrower evaluation to monthly amortization, income requirements, and cash-out computations. The design is modular, chainable, and test-driven.

## Installation

```bash
composer require homeful/mortgage
```

---

## ✨ Features Overview

- **Encapsulated `Mortgage` Class**: Central to processing and configuring a mortgage loan.
- **Modular Traits**: Responsibilities are split across focused, reusable traits.
- **Supports Add-on & Deductible Fees**: MRI, Fire Insurance, Consulting, Processing fees, etc.
- **Dynamic GMI & PV-based Eligibility**: Computes present value from disposable income.
- **Test-driven**: Includes comprehensive real-world loan scenarios with expected outputs.
- **Fluent API**: Chainable configuration methods (`setX()->setY()`).

---

## 🔧 Trait Responsibilities

### 🔢 Financial Computation
- `HasContractPrice`: Manages TCP (Total Contract Price)
- `HasDownPayment`: Computes down payment and amortization
- `HasMiscellaneousFees`: Additional fees (computed or set)
- `HasMultipliers`: Interest rates, income multipliers
- `HasTerms`: Manages loan balance terms

### 💸 Cash Flow Management
- `HasCashOuts`: Abstract layer for any outgoing cash items
- `HasAddOnFeesToLoanAmortization`: Adds MRI & Fire Insurance to monthly amortization

### 👥 Parties
- `HasProperty`: Assigns & syncs `Property` instance
- `HasBorrower`: Assigns borrower & GMI rules

### 🎁 Promotions
- `HasPromos`: Low-cash-out promo logic
- `isPromotional()`: Detects promotional packages

### ⚙️ Static Config
- `HasConfig`: Reads default term, interest rate, borrower age from config

---

## 🧮 Computation Flow

1. **Validation**: Inputs validated using Laravel validator
2. **Defaults**: Loaded from `Property` and `Borrower` if not provided
3. **Processing**:
    - Down Payment
    - Balance Payment
    - Miscellaneous Fees
    - Cash Outs
    - Loan Details (term, interest, income requirement)

### 🔍 Formula Highlights

- **Balance Payment** = TCP – Down Payment
- **Loan Principal** = Balance Payment + Balance Misc. Fees
- **Loan Amortization** = Payment + (MRI + Fire Insurance if applicable)

---

## ✅ Eligibility Tools

- `getLoanDifference()`: Checks if borrower can afford based on income
- `getPresentValueFromMonthlyDisposableIncomePayments()`: PV computation using borrower income
- `getJointBorrowerDisposableMonthlyIncome()`: Computes combined disposable income

---

## 🧪 Test Coverage

Includes over 15 real-world scenarios such as:

- ✅ Agapeya 70/50 Duplex @ 20, 25, and 30 years
- ✅ Ter-Je 2BR 40sqm @ 20, 25, 30 years
- ✅ Low-income housing simulation with ₱750K TCP
- ✅ Relaxed GMI multiplier cases (30% vs 35%)
- ✅ Promo: zero down payment + waived fees
- ✅ Add-on MRI + Fire Insurance added to monthly amortization
- ✅ Present Value edge cases
- ✅ GMI-based maximum loan calculations
- ✅ Full simulation using `createWithTypicalBorrower(...)`

Each test checks:

- Down Payment (amount + amortization)
- Balance Payment
- Miscellaneous Fees (partial & full)
- Loan Amount
- Loan Amortization
- GMI / Disposable Income & Present Value eligibility
- Add-on and deductible fee mechanics
- Cash out summaries
- Promo eligibility flags

---

## 🧠 Design Insights

- **Event Driven**: Traits dispatch events on update
- **Chainable API**: Fluent configuration methods
- **Precision Math**: Uses `Brick\Money` + `Whitecube\Price`
- **Compliance Ready**: Validates ranges for fees, DP, terms
- **Fully Tested**: Aligns with `PMT`, `PV` financial functions for accuracy

---

## 🔗 Key Integrations

- `Homeful\Payment\Payment`: Loan logic & computation
- `Homeful\Property\Property`: Provides TCP, MF %, etc.
- `Homeful\Borrower\Borrower`: Age, income, region
- `Homeful\Common\Classes`: Shared value object helpers

---

## 🧰 Usage

```php
use Homeful\Mortgage\Mortgage;
use Homeful\Borrower\Borrower;
use Homeful\Property\Property;
use Homeful\Common\Classes\Input;
use Homeful\Mortgage\Data\MortgageData;
use Illuminate\Support\Carbon;
use Whitecube\Price\Price;

$params = [
    Input::WAGES => 50000,
    Input::TCP => 2500000,
    Input::PERCENT_DP => 5 / 100,
    Input::DP_TERM => 12,
    Input::BP_INTEREST_RATE => 7 / 100,
    Input::PERCENT_MF => 8.5 / 100,
    Input::BP_TERM => 20,
];

$property = (new Property)
    ->setTotalContractPrice(Price::of($params[Input::TCP], 'PHP'))
    ->setAppraisedValue(Price::of($params[Input::TCP], 'PHP'));

$borrower = (new Borrower($property))
    ->setBirthdate(Carbon::parse('1999-03-17'))
    ->setGrossMonthlyIncome($params[Input::WAGES]);

$mortgage = new Mortgage(property: $property, borrower: $borrower, params: $params);
$data = MortgageData::fromObject($mortgage);

dd($data->toArray());
```

---

## 📊 Summary

The Mortgage package serves as the computation core for:

- Housing affordability simulations
- Loan qualification engines
- Down payment amortization planners
- Present value loan caps
- Promotional packages with waived fees
- Cross-segment real estate products

Behold, a new you awaits – with well-structured mortgage processing 🏡✨
