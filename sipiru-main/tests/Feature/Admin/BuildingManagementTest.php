<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BuildingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
    }

    private function createBuilding(string $name, string $code)
    {
        return $this->actingAs($this->admin)->post(route('admin.buildings.store'), [
            'name' => $name, 'code' => $code, 'is_active' => '1',
        ]);
    }

    public function test_admin_can_create_building(): void
    {
        $this->createBuilding('  Gedung   ICT ', 'ict')->assertRedirect()->assertSessionHasNoErrors();

        $building = Building::firstOrFail();
        $this->assertSame('Gedung ICT', $building->name);
        $this->assertSame('gedung ict', $building->name_normalized);
        $this->assertSame('ICT', $building->code);
        $this->assertSame('ict', $building->code_normalized);
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_BUILDING', 'subject_id' => $building->id]);
    }

    public static function duplicateNames(): array
    {
        return [['Gedung ICT'], ['gedung ict'], ['GEDUNG ICT'], ['Gedung  ICT'], [" Gedung\tICT "]];
    }

    /** Requirement §58: variasi penulisan nama yang sama DITOLAK. */
    #[DataProvider('duplicateNames')]
    public function test_duplicate_building_names_are_rejected(string $duplicate): void
    {
        $this->createBuilding('Gedung ICT', 'ICT')->assertSessionHasNoErrors();

        $this->createBuilding($duplicate, 'ICT2')->assertSessionHasErrors(['name' => 'Gedung dengan nama tersebut sudah terdaftar.']);
        $this->assertSame(1, Building::count());
    }

    /** Requirement §58: nama berbeda DITERIMA. */
    public function test_different_building_is_accepted(): void
    {
        $this->createBuilding('Gedung ICT', 'ICT');
        $this->createBuilding('Gedung FKIP', 'FKIP')->assertSessionHasNoErrors();

        $this->assertSame(2, Building::count());
    }

    public function test_duplicate_building_code_is_rejected_case_insensitively(): void
    {
        $this->createBuilding('Gedung ICT', 'ICT');

        $this->createBuilding('Gedung Lain', ' ict ')->assertSessionHasErrors('code');
    }

    public function test_database_constraint_is_the_last_line_of_defense(): void
    {
        Building::create(['name' => 'Gedung ICT', 'code' => 'ICT']);

        $this->expectException(UniqueConstraintViolationException::class);
        // Melewati validasi request sama sekali → tetap ditolak oleh unique index
        Building::create(['name' => 'GEDUNG  ict', 'code' => 'XYZ']);
    }

    public function test_update_to_existing_name_is_rejected_but_self_update_allowed(): void
    {
        Building::create(['name' => 'Gedung ICT', 'code' => 'ICT']);
        $fkip = Building::create(['name' => 'Gedung FKIP', 'code' => 'FKIP']);

        $this->actingAs($this->admin)->put(route('admin.buildings.update', $fkip), ['name' => 'gedung ict', 'code' => 'FKIP', 'is_active' => '1'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->put(route('admin.buildings.update', $fkip), ['name' => 'GEDUNG FKIP', 'code' => 'FKIP', 'is_active' => '1', 'address' => 'Kampus'])
            ->assertSessionHasNoErrors();
    }

    public function test_update_is_logged_with_before_and_after(): void
    {
        $building = Building::create(['name' => 'Gedung ICT', 'code' => 'ICT', 'address' => 'Lama']);

        $this->actingAs($this->admin)->put(route('admin.buildings.update', $building), [
            'name' => 'Gedung ICT', 'code' => 'ICT', 'address' => 'Baru', 'is_active' => '1',
        ]);

        $log = ActivityLog::where('action', 'UPDATE_BUILDING')->firstOrFail();
        $this->assertSame(['address' => 'Lama'], $log->properties['before']);
        $this->assertSame(['address' => 'Baru'], $log->properties['after']);
        $this->assertSame($this->admin->id, $log->user_id);
    }

    public function test_duplicate_check_endpoint_reports_exact_and_similar_matches(): void
    {
        Building::create(['name' => 'Gedung ICT USK', 'code' => 'ICT']);

        $this->actingAs($this->admin)->getJson(route('admin.buildings.check', ['name' => 'gedung  ict usk']))
            ->assertOk()
            ->assertJson(['name_exists' => true, 'name_match' => ['name' => 'Gedung ICT USK', 'code' => 'ICT', 'status' => 'Aktif']]);

        $this->actingAs($this->admin)->getJson(route('admin.buildings.check', ['name' => 'Gedung ICT-Universitas Syiah Kuala']))
            ->assertOk()
            ->assertJson(['name_exists' => false])
            ->assertJsonPath('similar.0.name', 'Gedung ICT USK');

        $this->actingAs($this->admin)->getJson(route('admin.buildings.check', ['name' => 'Gedung FKIP', 'code' => 'fkip']))
            ->assertJson(['name_exists' => false, 'code_exists' => false, 'similar' => []]);
    }

    public function test_building_with_rooms_cannot_be_deleted(): void
    {
        $building = $this->building();
        $this->room(['building_id' => $building->id]);

        $this->actingAs($this->admin)->delete(route('admin.buildings.destroy', $building))->assertSessionHasErrors('building');
        $this->assertNotSoftDeleted($building);
    }

    public function test_empty_building_can_be_soft_deleted_and_name_reused(): void
    {
        $building = Building::create(['name' => 'Gedung Lama', 'code' => 'OLD']);

        $this->actingAs($this->admin)->delete(route('admin.buildings.destroy', $building))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($building);

        $this->createBuilding('Gedung Lama', 'OLD')->assertSessionHasNoErrors();
    }

    public function test_building_can_be_deactivated_and_activated(): void
    {
        $building = $this->building();

        $this->actingAs($this->admin)->patch(route('admin.buildings.toggle', $building));
        $this->assertFalse($building->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'DEACTIVATE_BUILDING']);

        $this->actingAs($this->admin)->patch(route('admin.buildings.toggle', $building));
        $this->assertTrue($building->fresh()->is_active);
    }

    public function test_buildings_can_be_searched(): void
    {
        Building::create(['name' => 'Gedung ICT', 'code' => 'ICT']);
        Building::create(['name' => 'Gedung FKIP', 'code' => 'FKIP']);

        $this->actingAs($this->admin)->get(route('admin.buildings.index', ['q' => 'fkip']))
            ->assertOk()->assertSee('Gedung FKIP')->assertDontSee('Gedung ICT');
    }
}
