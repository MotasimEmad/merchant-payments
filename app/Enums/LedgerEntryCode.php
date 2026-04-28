<?php

namespace App\Enums;

enum LedgerEntryCode: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case PaymentIn = 'payment_in';
    case PaymentOut = 'payment_out';
    case ApplicationFee = 'application_fee';
    case RefundIn = 'refund_in';
    case RefundOut = 'refund_out';
    case Clearing = 'clearing';
}
