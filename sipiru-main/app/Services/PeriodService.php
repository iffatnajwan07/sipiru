<?php

namespace App\Services;

use App\Enums\PeriodStatus;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    public function __construct(private ActivityLogger $logger) {}

    public function create(int $year, bool $activate = false): Period
    {
        return DB::transaction(function () use ($year, $activate) {
            $period = Period::create(['year' => $year, 'status' => PeriodStatus::Inactive]);
            $this->logger->log('CREATE_PERIOD', $period, "Membuat periode {$year}");

            return $activate ? $this->activate($period) : $period;
        });
    }

    /** Mengaktifkan satu periode; periode aktif lain otomatis menjadi inactive. */
    public function activate(Period $period): Period
    {
        return DB::transaction(function () use ($period) {
            Period::query()->lockForUpdate()->get();
            Period::where('status', PeriodStatus::Active->value)->whereKeyNot($period->getKey())
                ->update(['status' => PeriodStatus::Inactive->value]);
            $period->update(['status' => PeriodStatus::Active]);
            $this->logger->log('ACTIVATE_PERIOD', $period, "Mengaktifkan periode {$period->year}");

            return $period->refresh();
        });
    }

    public function deactivate(Period $period): Period
    {
        return DB::transaction(function () use ($period) {
            $period->update(['status' => PeriodStatus::Inactive]);
            $this->logger->log('DEACTIVATE_PERIOD', $period, "Menonaktifkan periode {$period->year}");

            return $period;
        });
    }
}
