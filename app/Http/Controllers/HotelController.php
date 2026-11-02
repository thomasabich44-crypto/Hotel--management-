<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function dashboard(): View
    {
        $today = now()->toDateString();

        return view('hotel.dashboard', [
            'roomCount' => Room::count(),
            'occupiedCount' => Room::whereHas('reservations', function ($query) use ($today) {
                $query->whereIn('status', ['confirmed', 'checked_in'])
                    ->whereDate('check_in', '<=', $today)
                    ->whereDate('check_out', '>', $today);
            })->count(),
            'guestCount' => Guest::count(),
            'reservationCount' => Reservation::whereIn('status', ['confirmed', 'checked_in'])
                ->whereDate('check_in', '<=', $today)
                ->whereDate('check_out', '>', $today)
                ->count(),
            'reservations' => Reservation::with(['guest', 'rooms.roomType'])
                ->latest('check_in')
                ->limit(8)
                ->get(),
            'roomTypes' => RoomType::withCount('rooms')->orderBy('base_rate')->get(),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function guests(): View
    {
        return view('hotel.guests', ['guests' => Guest::withCount('reservations')->orderBy('last_name')->get()]);
    }

    public function storeGuest(Request $request): RedirectResponse
    {
        $guest = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:guests,email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        Guest::create($guest);

        return redirect()->route('guests.index')->with('success', 'Guest added successfully.');
    }

    public function rooms(): View
    {
        return view('hotel.rooms', [
            'rooms' => Room::with('roomType')->withCount('reservations')->orderBy('room_number')->get(),
            'roomTypes' => RoomType::withCount('rooms')->orderBy('base_rate')->get(),
        ]);
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:12', 'unique:rooms,room_number'],
            'floor' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'in:available,maintenance'],
        ]);

        Room::create($data);

        return redirect()->route('rooms.index')->with('success', 'Room added successfully.');
    }

    public function updateRoom(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:12', 'unique:rooms,room_number,'.$room->id],
            'floor' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'in:available,maintenance'],
        ]);

        $room->update($data);

        return redirect()->route('rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroyRoom(Room $room): RedirectResponse
    {
        if ($room->reservations()->exists()) {
            throw ValidationException::withMessages([
                'room' => 'A room with reservation history cannot be deleted. Set it to maintenance instead.',
            ]);
        }

        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }

    public function storeRoomType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:room_types,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:255'],
            'base_rate' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ]);

        RoomType::create($data);

        return redirect()->route('rooms.index')->with('success', 'Room type added successfully.');
    }

    public function updateRoomType(Request $request, RoomType $roomType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:room_types,name,'.$roomType->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:255'],
            'base_rate' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ]);

        $roomType->update($data);

        return redirect()->route('rooms.index')->with('success', 'Room type updated successfully.');
    }

    public function destroyRoomType(RoomType $roomType): RedirectResponse
    {
        if ($roomType->rooms()->exists()) {
            throw ValidationException::withMessages([
                'room_type' => 'A room type assigned to rooms cannot be deleted.',
            ]);
        }

        $roomType->delete();

        return redirect()->route('rooms.index')->with('success', 'Room type deleted successfully.');
    }

    public function reservations(): View
    {
        return view('hotel.reservations', [
            'reservations' => Reservation::with(['guest', 'rooms.roomType', 'services', 'payments'])
                ->latest('check_in')->get(),
            'guests' => Guest::orderBy('last_name')->orderBy('first_name')->get(),
            'rooms' => Room::with('roomType')->where('status', 'available')->orderBy('room_number')->get(),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function updateGuest(Request $request, Guest $guest): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:guests,email,'.$guest->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $guest->update($data);

        return redirect()->route('guests.index')->with('success', 'Guest updated successfully.');
    }

    public function destroyGuest(Guest $guest): RedirectResponse
    {
        if ($guest->reservations()->exists()) {
            throw ValidationException::withMessages([
                'guest' => 'A guest with reservation history cannot be deleted.',
            ]);
        }

        $guest->delete();

        return redirect()->route('guests.index')->with('success', 'Guest deleted successfully.');
    }

    public function storeReservation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['required', 'exists:guests,id'],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['required', 'integer', 'distinct', 'exists:services,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'status' => ['sometimes', 'in:pending,confirmed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $rooms = Room::with('roomType')
                ->whereIn('id', $data['room_ids'])
                ->where('status', 'available')
                ->lockForUpdate()
                ->get();

            if ($rooms->count() !== count($data['room_ids'])) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are not available for booking.',
                ]);
            }

            $conflictingRoomIds = DB::table('reservation_rooms')
                ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
                ->whereIn('reservation_rooms.room_id', $rooms->modelKeys())
                ->whereIn('reservations.status', ['pending', 'confirmed', 'checked_in'])
                ->where('reservations.check_in', '<', $data['check_out'])
                ->where('reservations.check_out', '>', $data['check_in'])
                ->pluck('reservation_rooms.room_id');

            if ($conflictingRoomIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'room_ids' => 'A selected room is already booked for some or all of those dates.',
                ]);
            }

            $reservation = Reservation::create([
                'guest_id' => $data['guest_id'],
                'created_by' => Auth::id(),
                'confirmation_code' => strtoupper(bin2hex(random_bytes(5))),
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'status' => $data['status'] ?? 'confirmed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($rooms as $room) {
                $reservation->rooms()->attach($room->id, ['nightly_rate' => $room->roomType->base_rate]);
            }

            $services = Service::whereIn('id', $data['service_ids'] ?? [])->get();
            foreach ($services as $service) {
                $reservation->services()->attach($service->id, ['quantity' => 1, 'unit_price' => $service->price]);
            }
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation created successfully.');
    }

    public function editReservation(Reservation $reservation): View
    {
        abort_if(! in_array($reservation->status, ['pending', 'confirmed'], true), 403);
        abort_if($reservation->payments()->where('status', 'paid')->exists(), 403);

        return view('hotel.reservation-edit', [
            'reservation' => $reservation->load(['guest', 'rooms', 'services']),
            'guests' => Guest::orderBy('last_name')->orderBy('first_name')->get(),
            'rooms' => Room::with('roomType')->where(function ($query) use ($reservation) {
                $query->where('status', 'available')
                    ->orWhereIn('id', $reservation->rooms->modelKeys());
            })
                ->orderBy('room_number')->get(),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function updateReservation(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['required', 'exists:guests,id'],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['required', 'integer', 'distinct', 'exists:services,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $reservation): void {
            $lockedReservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedReservation->status, ['pending', 'confirmed'], true)
                || $lockedReservation->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'reservation' => 'Only unpaid pending or confirmed reservations can be edited.',
                ]);
            }

            $existingRoomIds = $lockedReservation->rooms()->pluck('rooms.id');
            $rooms = Room::with('roomType')->whereIn('id', $data['room_ids'])
                ->where(function ($query) use ($existingRoomIds) {
                    $query->where('status', 'available')
                        ->orWhereIn('id', $existingRoomIds);
                })
                ->lockForUpdate()->get();
            if ($rooms->count() !== count($data['room_ids'])) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are not available for booking.',
                ]);
            }

            $maintenanceRoomSelected = $rooms->contains(
                fn (Room $room): bool => $room->status !== 'available'
            );
            if ($maintenanceRoomSelected
                && (collect($rooms->modelKeys())->sort()->values()->all()
                    !== $existingRoomIds->sort()->values()->all()
                    || $data['check_in'] !== $lockedReservation->check_in->toDateString()
                    || $data['check_out'] !== $lockedReservation->check_out->toDateString())) {
                throw ValidationException::withMessages([
                    'room_ids' => 'Replace the maintenance room with an available room before changing the reservation dates or room assignments.',
                ]);
            }

            $conflict = DB::table('reservation_rooms')
                ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
                ->whereIn('reservation_rooms.room_id', $rooms->modelKeys())
                ->where('reservations.id', '!=', $lockedReservation->id)
                ->whereIn('reservations.status', ['pending', 'confirmed', 'checked_in'])
                ->where('reservations.check_in', '<', $data['check_out'])
                ->where('reservations.check_out', '>', $data['check_in'])
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_ids' => 'A selected room is already booked for some or all of those dates.',
                ]);
            }

            $lockedReservation->update([
                'guest_id' => $data['guest_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'notes' => $data['notes'] ?? null,
            ]);
            $lockedReservation->rooms()->sync($rooms->mapWithKeys(
                fn (Room $room): array => [$room->id => ['nightly_rate' => $room->roomType->base_rate]]
            ));
            $services = Service::whereIn('id', $data['service_ids'] ?? [])->get();
            $lockedReservation->services()->sync($services->mapWithKeys(
                fn (Service $service): array => [$service->id => ['quantity' => 1, 'unit_price' => $service->price]]
            ));
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation updated successfully.');
    }

    public function destroyReservation(Reservation $reservation): RedirectResponse
    {
        DB::transaction(function () use ($reservation): void {
            $lockedReservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($lockedReservation->payments()->exists()) {
                throw ValidationException::withMessages([
                    'reservation' => 'A reservation with payment history cannot be deleted; cancel it instead.',
                ]);
            }
            if (in_array($lockedReservation->status, ['checked_in', 'checked_out'], true)) {
                throw ValidationException::withMessages([
                    'reservation' => 'An in-progress or completed stay cannot be deleted.',
                ]);
            }

            $lockedReservation->delete();
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation deleted successfully.');
    }

    public function reports(): View
    {
        $reservations = Reservation::with(['rooms', 'services', 'payments'])->get();
        $paidCents = $reservations->sum(fn (Reservation $reservation): int => $reservation->payments
            ->where('status', 'paid')
            ->sum(fn (Payment $payment): int => $this->decimalToCents((string) $payment->amount)));
        $outstandingCents = $reservations
            ->whereNotIn('status', ['cancelled'])
            ->sum(function (Reservation $reservation): int {
                $nights = $reservation->check_in->diffInDays($reservation->check_out);
                $roomTotal = $reservation->rooms->sum(
                    fn (Room $room): int => $this->decimalToCents((string) $room->pivot->nightly_rate) * $nights
                );
                $serviceTotal = $reservation->services->sum(
                    fn (Service $service): int => $this->decimalToCents((string) $service->pivot->unit_price) * $service->pivot->quantity
                );
                $paid = $reservation->payments->where('status', 'paid')
                    ->sum(fn (Payment $payment): int => $this->decimalToCents((string) $payment->amount));

                return max(0, $roomTotal + $serviceTotal - $paid);
            });

        return view('hotel.reports', [
            'reservationCount' => $reservations->count(),
            'guestCount' => Guest::count(),
            'roomCount' => Room::count(),
            'paidTotal' => $paidCents / 100,
            'outstandingTotal' => $outstandingCents / 100,
            'recentPayments' => Payment::with('reservation.guest')->latest('paid_at')->limit(10)->get(),
            'statusCounts' => $reservations->groupBy('status')->map->count(),
        ]);
    }

    public function updateReservationStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,checked_in,checked_out,cancelled'],
        ]);

        DB::transaction(function () use ($data, $reservation): void {
            $reservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['checked_in', 'cancelled'],
                'checked_in' => ['checked_out'],
                'checked_out' => [],
                'cancelled' => [],
            ];

            if (! in_array($data['status'], $allowedTransitions[$reservation->status], true)) {
                throw ValidationException::withMessages([
                    'status' => 'That reservation status change is not allowed.',
                ]);
            }

            if ($data['status'] === 'checked_in'
                && ($reservation->check_in->gt(today()) || $reservation->check_out->lte(today()))) {
                throw ValidationException::withMessages([
                    'status' => 'A reservation can only be checked in during its stay dates.',
                ]);
            }

            if ($data['status'] === 'cancelled'
                && $reservation->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'A reservation with recorded payments cannot be cancelled.',
                ]);
            }

            $reservation->update(['status' => $data['status']]);
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation status updated successfully.');
    }

    public function storePayment(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'method' => ['required', 'in:cash,card,bank_transfer'],
        ]);

        DB::transaction(function () use ($data, $reservation): void {
            $reservation = Reservation::with('rooms')
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'reservation' => 'A payment cannot be recorded for a cancelled reservation.',
                ]);
            }

            $amountCents = $this->decimalToCents((string) $data['amount']);
            $nights = $reservation->check_in->diffInDays($reservation->check_out);
            $totalDueCents = $reservation->rooms->sum(
                fn (Room $room): int => $this->decimalToCents((string) $room->pivot->nightly_rate) * $nights
            );
            $totalDueCents += $reservation->services->sum(
                fn (Service $service): int => $this->decimalToCents((string) $service->pivot->unit_price) * $service->pivot->quantity
            );
            $paidCents = $reservation->payments()
                ->where('status', 'paid')
                ->get(['amount'])
                ->sum(fn (Payment $payment): int => $this->decimalToCents((string) $payment->amount));

            if ($amountCents > $totalDueCents - $paidCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment exceeds the remaining reservation balance.',
                ]);
            }

            Payment::create([
                'reservation_id' => $reservation->id,
                'recorded_by' => Auth::id(),
                'amount' => $data['amount'],
                'method' => $data['method'],
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        return redirect()->route('reservations.index')->with('success', 'Payment recorded successfully.');
    }

    private function decimalToCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
