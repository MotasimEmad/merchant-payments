<?php

namespace App\Enums;

enum PaymentIntentStatus: string
{
    case RequiresPayment = 'requires_payment';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';
    case Refunded = 'refunded';
}
