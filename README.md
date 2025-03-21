# 🏡 Mortgage Computation Package

This package encapsulates the mortgage loan processing logic of the Homeful platform. It manages everything from borrower evaluation to monthly amortization, income requirements, and cash-out computations. The design is modular, chainable, and test-driven.

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

## 🧪 Tests Included

- Fee configuration & validation
- Various mortgage packages (sample use cases)
- Income requirement matching
- Loan difference computation
- Cash-out validations
- Event handling

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

Behold, a new you awaits – with well-structured mortgage processing 🏡✨
