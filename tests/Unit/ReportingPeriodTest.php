<?php

namespace Tests\Unit;

use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ReportingPeriodTest extends TestCase
{
    public function test_resolve_period_when_reference_day_is_on_or_after_26(): void
    {
        $period = ReportingPeriod::resolve(CarbonImmutable::parse('2026-04-26'));

        $this->assertSame('2026-04-26', $period['current_start']->toDateString());
        $this->assertSame('2026-05-25', $period['current_end']->toDateString());
        $this->assertSame('2026-03-26', $period['previous_start']->toDateString());
        $this->assertSame('2026-04-25', $period['previous_end']->toDateString());
    }

    public function test_resolve_period_when_reference_day_is_before_26(): void
    {
        $period = ReportingPeriod::resolve(CarbonImmutable::parse('2026-04-08'));

        $this->assertSame('2026-03-26', $period['current_start']->toDateString());
        $this->assertSame('2026-04-25', $period['current_end']->toDateString());
        $this->assertSame('2026-02-26', $period['previous_start']->toDateString());
        $this->assertSame('2026-03-25', $period['previous_end']->toDateString());
    }
}
