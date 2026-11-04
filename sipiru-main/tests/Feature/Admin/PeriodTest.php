<?php

namespace Tests\Feature\Admin;

use App\Enums\PeriodStatus;
use App\Models\Period;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_activating_a_period_deactivates_the_others(): void
    {
        $admin = $this->admin();
        $p2026 = Period::create(['year' => 2026, 'status' => PeriodStatus::Active]);
        $p2027 = Period::create(['year' => 2027, 'status' => PeriodStatus::Inactive]);

        $this->actingAs($admin)->patch(route('admin.periods.activate', $p2027))->assertSessionHasNoErrors();

        $this->assertSame(PeriodStatus::Inactive, $p2026->fresh()->status);
        $this->assertSame(PeriodStatus::Active, $p2027->fresh()->status);
        $this->assertSame(1, Period::where('status', 'active')->count());
    }

    public function test_database_allows_only_one_active_period(): void
    {
        Period::create(['year' => 2026, 'status' => PeriodStatus::Active]);

        $this->expectException(UniqueConstraintViolationException::class);
        Period::create(['year' => 2027, 'status' => PeriodStatus::Active]);
    }

    public function test_year_is_unique(): void
    {
        $admin = $this->admin();
        Period::create(['year' => 2026, 'status' => PeriodStatus::Inactive]);

        $this->actingAs($admin)->post(route('admin.periods.store'), ['year' => 2026])->assertSessionHasErrors('year');
        $this->actingAs($admin)->post(route('admin.periods.store'), ['year' => 2027, 'activate' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue(Period::where('year', 2027)->first()->isActive());
    }
}
