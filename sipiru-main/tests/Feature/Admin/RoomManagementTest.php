<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Building $ict;

    private Building $fkip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
        $this->ict = Building::create(['name' => 'Gedung ICT', 'code' => 'ICT']);
        $this->fkip = Building::create(['name' => 'Gedung FKIP', 'code' => 'FKIP']);
    }

    private function createRoom(Building $building, string $name, string $code)
    {
        return $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'building_id' => $building->id, 'name' => $name, 'code' => $code, 'floor' => '1', 'capacity' => 50, 'is_active' => '1',
        ]);
    }

    public function test_admin_can_create_room(): void
    {
        $this->createRoom($this->ict, 'Ruang  Seminar A', 'ict-sa-01')->assertSessionHasNoErrors();

        $room = Room::firstOrFail();
        $this->assertSame('Ruang Seminar A', $room->name);
        $this->assertSame('ruang seminar a', $room->name_normalized);
        $this->assertSame('ICT-SA-01', $room->code);
        $this->assertSame(40, strlen($room->public_token));
    }

    /** Requirement §59 */
    public function test_duplicate_room_in_same_building_is_rejected(): void
    {
        $this->createRoom($this->ict, 'Ruang Seminar A', 'ICT-SA-01')->assertSessionHasNoErrors();

        foreach (['ruang seminar a', 'RUANG SEMINAR A', 'Ruang  Seminar  A'] as $i => $variant) {
            $this->createRoom($this->ict, $variant, 'ICT-X-'.$i)
                ->assertSessionHasErrors(['name' => 'Ruang "Ruang Seminar A" sudah ada di Gedung ICT.']);
        }
        $this->assertSame(1, Room::count());
    }

    /** Requirement §59 */
    public function test_same_room_name_in_different_building_is_allowed(): void
    {
        $this->createRoom($this->ict, 'Ruang Seminar A', 'ICT-SA-01');
        $this->createRoom($this->fkip, 'Ruang Seminar A', 'FKIP-SA-01')->assertSessionHasNoErrors();

        $this->assertSame(2, Room::where('name_normalized', 'ruang seminar a')->count());
    }

    public function test_duplicate_room_code_in_same_building_is_rejected(): void
    {
        $this->createRoom($this->ict, 'Ruang Seminar A', 'ICT-SA-01');

        $this->createRoom($this->ict, 'Ruang Seminar B', 'ict-sa-01')->assertSessionHasErrors('code');
        // Kode yang sama di gedung lain diperbolehkan
        $this->createRoom($this->fkip, 'Ruang Seminar B', 'ICT-SA-01')->assertSessionHasNoErrors();
    }

    public function test_database_unique_constraint_per_building(): void
    {
        Room::factory()->create(['building_id' => $this->ict->id, 'name' => 'Lab 1', 'code' => 'L1']);
        Room::factory()->create(['building_id' => $this->fkip->id, 'name' => 'Lab 1', 'code' => 'L1']);

        $this->expectException(UniqueConstraintViolationException::class);
        Room::factory()->create(['building_id' => $this->ict->id, 'name' => 'LAB  1', 'code' => 'L2']);
    }

    public function test_duplicate_check_endpoint_gives_info_about_other_buildings(): void
    {
        Room::factory()->create(['building_id' => $this->fkip->id, 'name' => 'Ruang Seminar A', 'code' => 'FKIP-SA-01']);

        $this->actingAs($this->admin)->getJson(route('admin.rooms.check', ['building_id' => $this->ict->id, 'name' => 'ruang seminar a']))
            ->assertOk()
            ->assertJson(['name_exists' => false, 'other_buildings' => ['Gedung FKIP']]);

        $this->actingAs($this->admin)->getJson(route('admin.rooms.check', ['building_id' => $this->fkip->id, 'name' => 'RUANG SEMINAR A']))
            ->assertJson(['name_exists' => true, 'building_name' => 'Gedung FKIP']);
    }

    public function test_room_update_is_logged_with_before_after(): void
    {
        $room = Room::factory()->create(['building_id' => $this->ict->id, 'name' => 'Ruang Seminar A', 'code' => 'ICT-SA-01', 'capacity' => 30]);

        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'building_id' => $this->ict->id, 'name' => 'Ruang Seminar A', 'code' => 'ICT-SA-01', 'capacity' => 50, 'floor' => $room->floor, 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $log = ActivityLog::where('action', 'UPDATE_ROOM')->firstOrFail();
        $this->assertSame('ICT-SA-01', $log->subject_label);
        $this->assertSame(['capacity' => 30], $log->properties['before']);
        $this->assertSame(['capacity' => 50], $log->properties['after']);
    }

    public function test_room_with_booking_history_cannot_be_deleted(): void
    {
        $this->activePeriod();
        $room = Room::factory()->create(['building_id' => $this->ict->id]);
        Booking::factory()->create(['room_id' => $room->id]);

        $this->actingAs($this->admin)->delete(route('admin.rooms.destroy', $room))->assertSessionHasErrors('room');
        $this->assertNotSoftDeleted($room);
    }

    public function test_rooms_can_be_filtered(): void
    {
        Room::factory()->create(['building_id' => $this->ict->id, 'name' => 'Lab Satu', 'floor' => '2', 'capacity' => 40]);
        Room::factory()->create(['building_id' => $this->fkip->id, 'name' => 'Aula Besar', 'floor' => '1', 'capacity' => 300]);

        $this->actingAs($this->admin)->get(route('admin.rooms.index', ['building' => $this->ict->id]))->assertSee('Lab Satu')->assertDontSee('Aula Besar');
        $this->actingAs($this->admin)->get(route('admin.rooms.index', ['min_capacity' => 100]))->assertSee('Aula Besar')->assertDontSee('Lab Satu');
        $this->actingAs($this->admin)->get(route('admin.rooms.index', ['floor' => '2']))->assertSee('Lab Satu')->assertDontSee('Aula Besar');
    }

    public function test_room_qr_pages_render(): void
    {
        $room = Room::factory()->create(['building_id' => $this->ict->id]);

        $this->actingAs($this->admin)->get(route('admin.rooms.qr', $room))->assertOk()->assertSee('Scan untuk mengajukan peminjaman ruangan');
        $this->actingAs($this->admin)->get(route('admin.rooms.qr-png', $room))->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
