<?php

namespace App\Services\Payments;

/**
 * Application fee in minor units (cents) from the gross charge amount.
 */
final class PlatformApplicationFee
{
    /**
     * Platform fee: 5% of gross, rounded to the nearest cent.
     */
    public const BASIS_POINTS = 500;

    public static function minorFromGross(int $grossAmountMinor): int
    {
        if ($grossAmountMinor < 1) {
            return 0;
        }

        return (int) max(0, (int) round($grossAmountMinor * self::BASIS_POINTS / 10_000));
    }
}
