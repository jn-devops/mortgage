<?php

namespace Homeful\Mortgage\Enums;

enum Account: string
{
    case CASH_OUT = 'cash_out';
    case DOWN_PAYMENT = 'down_payment';
    case LOAN_AMOUNT = 'loan_amount';
}
