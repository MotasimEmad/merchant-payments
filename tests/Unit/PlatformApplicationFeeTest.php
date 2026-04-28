<?php

namespace Tests\Unit;

use App\Services\Payments\PlatformApplicationFee;
use PHPUnit\Framework\TestCase;

class PlatformApplicationFeeTest extends TestCase
{
    public function test_five_percent_rounded_to_cents(): void
    {
        $this->assertSame(100, PlatformApplicationFee::minorFromGross(2000));
        $this->assertSame(5, PlatformApplicationFee::minorFromGross(100));
    }

    public function test_zero_for_non_positive_gross(): void
    {
        $this->assertSame(0, PlatformApplicationFee::minorFromGross(0));
    }
}
