<?php

namespace App\Services\Payments;

use App\Enums\LedgerEntryCode;
use App\Enums\PaymentIntentStatus;
use App\Models\Merchant;
use App\Models\PaymentIntent;
use App\Models\PlatformAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use Illuminate\Support\Str;
use RuntimeException;

class WalletOperationService
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function getOrCreateWalletForUser(User $user, string $currency = 'USD'): Wallet
    {
        $wallet = Wallet::query()
            ->where('holder_type', User::class)
            ->where('holder_id', $user->id)
            ->where('currency', $currency)
            ->first();
        if ($wallet) {
            return $wallet;
        }

        return Wallet::query()->create([
            'uuid' => (string) Str::uuid(),
            'holder_type' => User::class,
            'holder_id' => $user->id,
            'currency' => $currency,
            'label' => 'Personal',
            'status' => 'active',
        ]);
    }

    public function getOrCreateWalletForMerchant(Merchant $merchant, string $currency = 'USD'): Wallet
    {
        $wallet = Wallet::query()
            ->where('holder_type', Merchant::class)
            ->where('holder_id', $merchant->id)
            ->where('currency', $currency)
            ->first();
        if ($wallet) {
            return $wallet;
        }

        return Wallet::query()->create([
            'uuid' => (string) Str::uuid(),
            'holder_type' => Merchant::class,
            'holder_id' => $merchant->id,
            'currency' => $currency,
            'label' => 'Business: '.$merchant->business_name,
            'status' => 'active',
        ]);
    }

    public function getSystemWallet(PlatformAccount $account, string $currency = 'USD'): Wallet
    {
        $wallet = Wallet::query()
            ->where('holder_type', PlatformAccount::class)
            ->where('holder_id', $account->id)
            ->where('currency', $currency)
            ->first();
        if (! $wallet) {
            throw new RuntimeException("System wallet not provisioned for {$account->key}.");
        }

        return $wallet;
    }

    /**
     * Simulated funding from external world into the user's wallet (e.g. card / bank on-rail).
     */
    public function deposit(
        User $user,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
        string $source = 'api',
    ): \App\Models\LedgerBatch {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Deposit amount must be positive.');
        }
        $userWallet = $this->getOrCreateWalletForUser($user, $currency);
        $clearing = PlatformAccount::query()->where('key', 'clearing')->firstOrFail();
        $clearingWallet = $this->getSystemWallet($clearing, $currency);

        return $this->ledger->postBatch(
            $idempotencyKey,
            'User deposit (simulated)',
            $source,
            [
                [
                    'wallet_id' => $clearingWallet->id,
                    'cents' => -$amountMinor,
                    'type' => LedgerEntryCode::Clearing,
                    'extra' => ['direction' => 'funding_in'],
                ],
                [
                    'wallet_id' => $userWallet->id,
                    'cents' => $amountMinor,
                    'type' => LedgerEntryCode::Deposit,
                ],
            ],
        );
    }

    /**
     * Payout to external destination (off-rail simulation).
     */
    public function withdraw(
        User $user,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
        string $source = 'api',
    ): \App\Models\LedgerBatch {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Withdrawal amount must be positive.');
        }
        $userWallet = $this->getOrCreateWalletForUser($user, $currency);
        if ($this->ledger->balanceMinor($userWallet) < $amountMinor) {
            throw new RuntimeException('Insufficient funds.');
        }
        $clearing = PlatformAccount::query()->where('key', 'clearing')->firstOrFail();
        $clearingWallet = $this->getSystemWallet($clearing, $currency);

        return $this->ledger->postBatch(
            $idempotencyKey,
            'User withdrawal (simulated)',
            $source,
            [
                [
                    'wallet_id' => $userWallet->id,
                    'cents' => -$amountMinor,
                    'type' => LedgerEntryCode::Withdrawal,
                ],
                [
                    'wallet_id' => $clearingWallet->id,
                    'cents' => $amountMinor,
                    'type' => LedgerEntryCode::Clearing,
                    'extra' => ['direction' => 'funding_out'],
                ],
            ],
        );
    }

    public function transfer(
        User $from,
        User $to,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
        string $source = 'api',
    ): \App\Models\LedgerBatch {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Transfer amount must be positive.');
        }
        if ($from->is($to)) {
            throw new RuntimeException('Cannot transfer to the same user.');
        }
        $a = $this->getOrCreateWalletForUser($from, $currency);
        $b = $this->getOrCreateWalletForUser($to, $currency);
        if ($this->ledger->balanceMinor($a) < $amountMinor) {
            throw new RuntimeException('Insufficient funds.');
        }

        return $this->ledger->postBatch(
            $idempotencyKey,
            'P2P transfer',
            $source,
            [
                [
                    'wallet_id' => $a->id,
                    'cents' => -$amountMinor,
                    'type' => LedgerEntryCode::TransferOut,
                ],
                [
                    'wallet_id' => $b->id,
                    'cents' => $amountMinor,
                    'type' => LedgerEntryCode::TransferIn,
                ],
            ],
        );
    }

    public function capturePaymentIntent(
        PaymentIntent $intent,
        string $idempotencyKey,
        string $source = 'api',
    ): PaymentIntent {
        if ($intent->status !== PaymentIntentStatus::RequiresPayment) {
            throw new RuntimeException('Intent is not payable in its current state.');
        }
        $payer = $intent->payer;
        if (! $payer) {
            throw new RuntimeException('Intent has no payer.');
        }
        $currency = $intent->currency;
        $gross = (int) $intent->amount_minor;
        $fee = (int) $intent->application_fee_minor;
        if ($fee > $gross) {
            throw new RuntimeException('Application fee cannot exceed amount.');
        }
        $toMerchant = $gross - $fee;

        $payerWallet = $this->getOrCreateWalletForUser($payer, $currency);
        if ($this->ledger->balanceMinor($payerWallet) < $gross) {
            throw new RuntimeException('Payer has insufficient balance.');
        }

        $merchantWallet = $this->getOrCreateWalletForMerchant($intent->merchant, $currency);
        $lines = [
            [
                'wallet_id' => $payerWallet->id,
                'cents' => -$gross,
                'type' => LedgerEntryCode::PaymentOut,
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ],
            [
                'wallet_id' => $merchantWallet->id,
                'cents' => $toMerchant,
                'type' => LedgerEntryCode::PaymentIn,
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ],
        ];
        if ($fee > 0) {
            $fees = PlatformAccount::query()->where('key', 'fees')->firstOrFail();
            $feesWallet = $this->getSystemWallet($fees, $currency);
            $lines[] = [
                'wallet_id' => $feesWallet->id,
                'cents' => $fee,
                'type' => LedgerEntryCode::ApplicationFee,
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ];
        }

        $batch = $this->ledger->postBatch($idempotencyKey, 'Card / wallet charge', $source, $lines);

        $intent->update([
            'status' => PaymentIntentStatus::Succeeded,
            'batch_id' => $batch->id,
        ]);

        return $intent->refresh();
    }

    public function refundPaymentIntent(
        PaymentIntent $intent,
        int $amountMinor,
        string $idempotencyKey,
        string $source = 'api',
    ): \App\Models\LedgerBatch {
        if ($intent->status !== PaymentIntentStatus::Succeeded) {
            throw new RuntimeException('Only captured payments can be refunded.');
        }
        if ($amountMinor <= 0 || $amountMinor > (int) $intent->amount_minor) {
            throw new RuntimeException('Invalid refund amount.');
        }
        $payer = $intent->payer;
        if (! $payer) {
            throw new RuntimeException('Original payer missing.');
        }
        $currency = $intent->currency;
        $payerWallet = $this->getOrCreateWalletForUser($payer, $currency);
        $merchantWallet = $this->getOrCreateWalletForMerchant($intent->merchant, $currency);

        $origFee = (int) $intent->application_fee_minor;
        $ratio = $amountMinor / (int) $intent->amount_minor;
        $refundFee = (int) round($origFee * $ratio);
        $toPayer = $amountMinor;
        $fromMerchantGross = $amountMinor - $refundFee;

        if ($this->ledger->balanceMinor($merchantWallet) < $fromMerchantGross) {
            throw new RuntimeException('Merchant wallet cannot cover this refund (insufficient business balance).');
        }
        $lines = [
            [
                'wallet_id' => $payerWallet->id,
                'cents' => $toPayer,
                'type' => LedgerEntryCode::RefundIn,
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ],
            [
                'wallet_id' => $merchantWallet->id,
                'cents' => -$fromMerchantGross,
                'type' => LedgerEntryCode::RefundOut,
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ],
        ];
        if ($refundFee > 0) {
            $fees = PlatformAccount::query()->where('key', 'fees')->firstOrFail();
            $feesWallet = $this->getSystemWallet($fees, $currency);
            if ($this->ledger->balanceMinor($feesWallet) < $refundFee) {
                throw new RuntimeException('Platform fee wallet cannot cover full proportional fee reversal in simulation.');
            }
            $lines[] = [
                'wallet_id' => $feesWallet->id,
                'cents' => -$refundFee,
                'type' => LedgerEntryCode::ApplicationFee,
                'extra' => ['reversal' => true],
                'link_type' => PaymentIntent::class,
                'link_id' => $intent->id,
            ];
        }

        $batch = $this->ledger->postBatch($idempotencyKey, 'Payment refund (simulated)', $source, $lines);

        if ($amountMinor === (int) $intent->amount_minor) {
            $intent->update(['status' => PaymentIntentStatus::Refunded]);
        }

        return $batch;
    }
}
