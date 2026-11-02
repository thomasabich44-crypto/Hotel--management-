<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class HotelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::factory()->create(['role' => 'administrator']));
    }

    public function test_dashboard_and_hotel_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('Total rooms')->assertSee('ETB');
        $this->get('/guests')->assertOk()->assertSee('Guest directory');
        $this->get('/rooms')->assertOk()->assertSee('Room inventory');
        $this->get('/reservations')->assertOk()->assertSee('Reservation register');
        $this->get('/reports')->assertOk()->assertSee('Outstanding balance')->assertSee('ETB');
    }

    public function test_administrative_pages_require_login(): void
    {
        auth()->logout();

        $this->get('/')->assertRedirect('/login');
        $this->post('/reservations/1/payments', ['amount' => 1, 'method' => 'cash'])->assertRedirect('/login');
    }

    public function test_staff_can_log_in_and_log_out(): void
    {
        $this->post('/logout')->assertRedirect('/login');

        User::factory()->create([
            'email' => 'staff@example.test',
            'password' => Hash::make('SecurePass123'),
            'role' => 'receptionist',
        ]);

        $this->post('/login', [
            'email' => 'staff@example.test',
            'password' => 'SecurePass123',
        ])->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_reservation_can_be_created_with_room_assignment(): void
    {
        $response = $this->post('/reservations', [
            'guest_id' => 1,
            'room_ids' => [2],
            'check_in' => today()->addDays(10)->toDateString(),
            'check_out' => today()->addDays(12)->toDateString(),
        ]);

        $response->assertRedirect('/reservations')->assertSessionHas('success');
        $this->assertDatabaseCount('reservations', 4);
        $this->assertSame(1, DB::table('reservations')
            ->where('guest_id', 1)
            ->whereDate('check_in', today()->addDays(10)->toDateString())
            ->whereDate('check_out', today()->addDays(12)->toDateString())
            ->where('status', 'confirmed')
            ->count());
        $this->assertDatabaseHas('reservation_rooms', ['room_id' => 2, 'nightly_rate' => 4500.00]);
    }

    public function test_room_cannot_be_booked_for_overlapping_active_dates(): void
    {
        $response = $this->from('/reservations')->post('/reservations', [
            'guest_id' => 2,
            'room_ids' => [1],
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect('/reservations')->assertSessionHasErrors('room_ids');
        $this->assertDatabaseCount('reservations', 3);
    }

    public function test_cancelled_reservation_does_not_block_room_availability(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $this->patch(route('reservations.status', $reservation), ['status' => 'cancelled'])
            ->assertSessionHas('success');

        $this->post('/reservations', [
            'guest_id' => 2,
            'room_ids' => [1],
            'check_in' => today()->addDays(2)->toDateString(),
            'check_out' => today()->addDays(4)->toDateString(),
        ])->assertSessionHas('success');
    }

    public function test_pending_reservation_blocks_its_room_until_cancelled(): void
    {
        $payload = [
            'guest_id' => 1,
            'room_ids' => [2],
            'check_in' => today()->addDays(10)->toDateString(),
            'check_out' => today()->addDays(12)->toDateString(),
            'status' => 'pending',
        ];
        $this->post('/reservations', $payload)->assertSessionHas('success');
        $reservation = Reservation::latest('id')->firstOrFail();

        $this->from('/reservations')->post('/reservations', $payload)
            ->assertSessionHasErrors('room_ids');
        $this->patch(route('reservations.status', $reservation), ['status' => 'cancelled'])
            ->assertSessionHas('success');
        $this->post('/reservations', $payload)->assertSessionHas('success');
    }

    public function test_payment_cannot_exceed_the_remaining_reservation_balance(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();

        $response = $this->from('/reservations')->post(route('payments.store', $reservation), [
            'amount' => '6501.00',
            'method' => 'card',
        ]);

        $response->assertRedirect('/reservations')->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_for_the_exact_remaining_balance_is_accepted(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();

        $response = $this->post(route('payments.store', $reservation), [
            'amount' => '6500.00',
            'method' => 'card',
        ]);

        $response->assertRedirect('/reservations')->assertSessionHas('success');
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_service_charges_are_included_in_the_payment_balance(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $service = Service::where('name', 'Breakfast')->firstOrFail();
        $reservation->services()->attach($service->id, ['quantity' => 1, 'unit_price' => 500.00]);

        $this->from('/reservations')
            ->post(route('payments.store', $reservation), ['amount' => '9501.00', 'method' => 'cash'])
            ->assertSessionHasErrors('amount');

        $this->post(route('payments.store', $reservation), ['amount' => '9500.00', 'method' => 'cash'])
            ->assertSessionHas('success');
    }

    public function test_reservation_and_payment_record_the_signed_in_staff_member(): void
    {
        $staffId = auth()->id();

        $this->post('/reservations', [
            'guest_id' => 1,
            'room_ids' => [2],
            'check_in' => today()->addDays(14)->toDateString(),
            'check_out' => today()->addDays(15)->toDateString(),
        ])->assertSessionHas('success');

        $reservation = Reservation::whereDate('check_in', today()->addDays(14))->firstOrFail();
        $this->assertSame($staffId, $reservation->created_by);

        $this->post(route('payments.store', $reservation), [
            'amount' => '1000.00',
            'method' => 'cash',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'recorded_by' => $staffId,
            'amount' => '1000.00',
        ]);
    }

    public function test_reservation_can_be_checked_in_on_its_stay_date(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $reservation->update([
            'check_in' => today()->toDateString(),
            'check_out' => today()->addDays(2)->toDateString(),
        ]);

        $this->patch(route('reservations.status', $reservation), ['status' => 'checked_in'])
            ->assertRedirect('/reservations')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_reservation_cannot_be_checked_in_before_its_stay_date(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();

        $this->from('/reservations')
            ->patch(route('reservations.status', $reservation), ['status' => 'checked_in'])
            ->assertRedirect('/reservations')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_checked_in_reservation_can_be_checked_out(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();

        $this->patch(route('reservations.status', $reservation), ['status' => 'checked_out'])
            ->assertRedirect('/reservations')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_out',
        ]);
    }

    public function test_reservation_with_a_recorded_payment_cannot_be_cancelled(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $reservation->payments()->create([
            'amount' => 10.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->from('/reservations')
            ->patch(route('reservations.status', $reservation), ['status' => 'cancelled'])
            ->assertRedirect('/reservations')
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_seeding_demo_data_repeatedly_does_not_duplicate_sample_payment(): void
    {
        $this->seed();

        $this->assertDatabaseCount('guests', 3);
        $this->assertDatabaseCount('rooms', 5);
        $this->assertDatabaseCount('reservations', 3);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('services', 3);
    }

    public function test_receptionist_cannot_manage_room_inventory(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($receptionist)->post('/rooms', [
            'room_type_id' => 1,
            'room_number' => '999',
            'floor' => 9,
            'status' => 'available',
        ])->assertForbidden();

        $this->assertDatabaseMissing('rooms', ['room_number' => '999']);
    }

    public function test_room_management_crud_is_validated_and_protected(): void
    {
        $this->post('/rooms', [
            'room_type_id' => 1,
            'room_number' => '999',
            'floor' => 9,
            'status' => 'available',
        ])->assertSessionHas('success');

        $room = Room::where('room_number', '999')->firstOrFail();
        $this->put(route('rooms.update', $room), [
            'room_type_id' => 2,
            'room_number' => '999',
            'floor' => 10,
            'status' => 'maintenance',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'maintenance']);

        $this->delete(route('rooms.destroy', $room))->assertSessionHas('success');
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_room_type_can_be_created_updated_and_deleted_when_unused(): void
    {
        $this->post('/room-types', [
            'name' => 'Family',
            'description' => 'A family room.',
            'capacity' => 5,
            'base_rate' => '249.00',
        ])->assertSessionHas('success');

        $roomType = RoomType::where('name', 'Family')->firstOrFail();
        $this->put(route('room-types.update', $roomType), [
            'name' => 'Family Plus',
            'description' => 'A larger family room.',
            'capacity' => 6,
            'base_rate' => '279.00',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id, 'name' => 'Family Plus']);
        $this->delete(route('room-types.destroy', $roomType))->assertSessionHas('success');
        $this->assertDatabaseMissing('room_types', ['id' => $roomType->id]);
    }

    public function test_guest_can_be_updated_and_deleted_when_unreferenced(): void
    {
        $guest = Guest::create([
            'first_name' => 'Taylor',
            'last_name' => 'Smith',
            'email' => 'taylor@example.test',
            'phone' => null,
        ]);

        $this->put(route('guests.update', $guest), [
            'first_name' => 'Taylor',
            'last_name' => 'Jones',
            'email' => 'taylor.jones@example.test',
            'phone' => '+1 555 010 1110',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'last_name' => 'Jones']);
        $this->delete(route('guests.destroy', $guest))->assertSessionHas('success');
        $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
    }

    public function test_reservation_can_be_updated_and_unpaid_record_deleted(): void
    {
        $this->post('/reservations', [
            'guest_id' => 1,
            'room_ids' => [2],
            'check_in' => today()->addDays(10)->toDateString(),
            'check_out' => today()->addDays(12)->toDateString(),
        ])->assertSessionHas('success');
        $reservation = Reservation::latest('id')->firstOrFail();

        $this->get(route('reservations.edit', $reservation))->assertOk();
        $this->put(route('reservations.update', $reservation), [
            'guest_id' => 2,
            'room_ids' => [2],
            'check_in' => today()->addDays(11)->toDateString(),
            'check_out' => today()->addDays(13)->toDateString(),
            'service_ids' => [Service::firstOrFail()->id],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'guest_id' => 2]);
        $this->assertSame(today()->addDays(11)->toDateString(), $reservation->fresh()->check_in->toDateString());
        $this->delete(route('reservations.destroy', $reservation))->assertSessionHas('success');
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_reservation_with_maintenance_room_can_be_edited_without_changing_its_booking(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $room = $reservation->rooms()->firstOrFail();
        $room->update(['status' => 'maintenance']);

        $this->get(route('reservations.edit', $reservation))
            ->assertOk()
            ->assertSee('Maintenance: replace this room');

        $this->put(route('reservations.update', $reservation), [
            'guest_id' => $reservation->guest_id,
            'room_ids' => [$room->id],
            'check_in' => $reservation->check_in->toDateString(),
            'check_out' => $reservation->check_out->toDateString(),
            'notes' => 'Updated notes',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_maintenance_room_must_be_replaced_before_changing_reservation_dates(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $room = $reservation->rooms()->firstOrFail();
        $room->update(['status' => 'maintenance']);

        $this->from(route('reservations.edit', $reservation))
            ->put(route('reservations.update', $reservation), [
                'guest_id' => $reservation->guest_id,
                'room_ids' => [$room->id],
                'check_in' => $reservation->check_in->copy()->addDay()->toDateString(),
                'check_out' => $reservation->check_out->copy()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('reservations.edit', $reservation))
            ->assertSessionHasErrors('room_ids');

        $this->assertSame(
            $reservation->check_in->toDateString(),
            $reservation->fresh()->check_in->toDateString()
        );
    }

    public function test_paid_reservation_cannot_be_deleted(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1001')->firstOrFail();
        $reservation->payments()->create([
            'amount' => 1,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->from('/reservations')->delete(route('reservations.destroy', $reservation))
            ->assertSessionHasErrors('reservation');
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'amount' => 1,
        ]);
    }

    public function test_payment_history_is_not_deleted_when_reservation_deletion_is_rejected(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();
        $payment = $reservation->payments()->firstOrFail();

        $this->from('/reservations')->delete(route('reservations.destroy', $reservation))
            ->assertSessionHasErrors('reservation');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'reservation_id' => $reservation->id,
        ]);
    }

    public function test_database_restricts_deleting_a_reservation_with_payment_history(): void
    {
        $reservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();
        $payment = $reservation->payments()->firstOrFail();

        try {
            $reservation->delete();
        } catch (QueryException) {
            $this->assertDatabaseHas('payments', ['id' => $payment->id]);

            return;
        }

        $this->fail('The database allowed deletion of a reservation with payment history.');
    }

    public function test_dashboard_active_stays_count_only_reservations_in_house_today(): void
    {
        Reservation::create([
            'guest_id' => 1,
            'confirmation_code' => 'DEMOEXPIRED',
            'check_in' => today()->subDays(2)->toDateString(),
            'check_out' => today()->toDateString(),
            'status' => 'confirmed',
        ]);
        Reservation::create([
            'guest_id' => 1,
            'confirmation_code' => 'DEMOFUTURE',
            'check_in' => today()->addDays(2)->toDateString(),
            'check_out' => today()->addDays(4)->toDateString(),
            'status' => 'confirmed',
        ]);

        $response = $this->get('/');
        preg_match(
            '/<div class="card"><div class="card-label">Active stays today<\/div><div class="card-value">(\d+)<\/div>/',
            $response->getContent(),
            $matches
        );

        $this->assertSame('1', $matches[1] ?? null);
    }
}
