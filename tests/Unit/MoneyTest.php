<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_fiat_rounds_half_away_from_zero_to_two_places(): void
    {
        $this->assertSame('0.30', Money::fiat('0.295'));
        $this->assertSame('10.00', Money::fiat(9.999));
        $this->assertSame('-0.30', Money::fiat('-0.295'));
    }

    public function test_float_artifacts_do_not_leak(): void
    {
        // 0.1 + 0.2 is 0.30000000000000004 in binary floating point
        $this->assertSame('0.30', Money::fiat(Money::add('0.1', '0.2')));
        $this->assertSame(0, Money::cmp(Money::add('0.1', '0.2'), '0.3'));
    }

    public function test_floor_never_rounds_up(): void
    {
        $this->assertSame('3.33', Money::floorTo('3.339999', 2));
        $this->assertSame('0.00', Money::floorTo('0.009', 2));
    }

    public function test_percent_and_division(): void
    {
        $this->assertSame('15.00', Money::fiat(Money::percent('150.00', '10.00', 2)));
        $this->assertSame('3.33', Money::fiat(Money::div('10', '3')));
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::of('12,50');
    }
}
