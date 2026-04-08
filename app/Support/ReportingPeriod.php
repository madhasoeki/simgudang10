<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ReportingPeriod
{
    /**
     * Resolve active and previous reporting periods using 26-25 cycle.
     *
     * @return array{
     *     current_start: CarbonImmutable,
     *     current_end: CarbonImmutable,
     *     previous_start: CarbonImmutable,
     *     previous_end: CarbonImmutable,
     * }
     */
    public static function resolve(?CarbonInterface $referenceDate = null): array
    {
        $reference = $referenceDate
            ? CarbonImmutable::instance($referenceDate)
            : CarbonImmutable::today(config('app.timezone'));

        if ($reference->day >= 26) {
            $currentStart = $reference->day(26);
            $currentEnd = $reference->addMonth()->day(25);
        } else {
            $currentStart = $reference->subMonth()->day(26);
            $currentEnd = $reference->day(25);
        }

        $previousEnd = $currentStart->subDay();
        $previousStart = $previousEnd->day(26)->subMonth();

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * Apply overlap filter for period ranges.
     */
    public static function applyOverlapFilter(
        EloquentBuilder|QueryBuilder $query,
        string $startColumn,
        string $endColumn,
        CarbonInterface $filterStart,
        CarbonInterface $filterEnd
    ): void {
        $query->where(function ($builder) use ($startColumn, $endColumn, $filterStart, $filterEnd) {
            $builder->where($startColumn, '<=', $filterEnd)
                ->where($endColumn, '>=', $filterStart);
        });
    }
}
