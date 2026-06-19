<?php

namespace App\DTOs\Reports;

use App\Enums\ReportPeriod;
use Illuminate\Support\Carbon;
use Throwable;

final readonly class ReportFilterData
{
    public function __construct(
        public ReportPeriod $period,
        public Carbon $startDate,
        public Carbon $endDate,
    ) {}

    public static function fromRequest(array $data): self
    {
        $period = ReportPeriod::tryFrom((string) ($data['period'] ?? 'monthly')) ?? ReportPeriod::Monthly;
        $anchor = now();

        if (filled($data['date'] ?? null)) {
            try {
                $anchor = Carbon::parse($data['date']);
            } catch (Throwable) {
                $anchor = now();
            }
        }

        [$start, $end] = match ($period) {
            ReportPeriod::Daily => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            ReportPeriod::Weekly => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            ReportPeriod::Monthly => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            ReportPeriod::Yearly => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
        };

        return new self($period, $start, $end);
    }
}
