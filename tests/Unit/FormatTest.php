<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function test_inr_uses_indian_digit_grouping(): void
    {
        $this->assertSame('₹999', Format::inr(999));
        $this->assertSame('₹1,50,000', Format::inr(150000));
        $this->assertSame('₹12,34,567.50', Format::inr(1234567.5));
        $this->assertSame('-₹1,000', Format::inr(-1000));
    }

    public function test_label_humanises_snake_case(): void
    {
        $this->assertSame('Fully compliant', Format::label('fully_compliant'));
    }
}
