@extends('layouts.hotel')

@section('title', 'Reservations')

@section('content')
    <h1>Reservations</h1>
    <p class="subtitle">Book rooms, review upcoming stays, and record payments.</p>
    <section class="panel">
        <div class="panel-head"><h2>Create a reservation</h2></div>
        @if ($guests->isEmpty() || $rooms->isEmpty())
            <div class="empty">Add at least one guest and one available room before creating a reservation.</div>
        @else
            <form method="POST" action="{{ route('reservations.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="guest_id">Guest</label><select id="guest_id" name="guest_id" required><option value="">Choose a guest</option>@foreach ($guests as $guest)<option value="{{ $guest->id }}" @selected(old('guest_id') == $guest->id)>{{ $guest->last_name }}, {{ $guest->first_name }} — {{ $guest->email }}</option>@endforeach</select></div>
                <div class="field"><label for="check_in">Check-in date</label><input id="check_in" type="date" name="check_in" value="{{ old('check_in') }}" min="{{ today()->toDateString() }}" required></div>
                <div class="field"><label for="check_out">Check-out date</label><input id="check_out" type="date" name="check_out" value="{{ old('check_out') }}" min="{{ today()->addDay()->toDateString() }}" required></div>
                <div class="field full"><label>Choose room(s)</label><div class="room-options">
                    @foreach ($rooms as $room)
                        <label class="room-option"><input type="checkbox" name="room_ids[]" value="{{ $room->id }}" @checked(in_array($room->id, (array) old('room_ids', [])))><span><strong>Room {{ $room->room_number }}</strong> · {{ $room->roomType->name }}<br><span class="muted">ETB {{ number_format((float) $room->roomType->base_rate, 2) }} / night</span></span></label>
                    @endforeach
                </div></div>
                @if ($services->isNotEmpty())
                    <div class="field full"><label>Optional hotel services</label><div class="room-options">
                        @foreach ($services as $service)
                            <label class="room-option"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, (array) old('service_ids', [])))><span><strong>{{ $service->name }}</strong><br><span class="muted">ETB {{ number_format((float) $service->price, 2) }} one-time</span></span></label>
                        @endforeach
                    </div></div>
                @endif
                <div class="field"><label for="status">Initial status</label><select id="status" name="status"><option value="confirmed" @selected(old('status', 'confirmed') === 'confirmed')>Confirmed</option><option value="pending" @selected(old('status') === 'pending')>Pending</option></select></div>
                <div class="field full"><label for="notes">Notes <span class="muted">(optional)</span></label><textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea></div>
                <div class="field full"><button class="button" type="submit">Save reservation</button></div>
            </form>
        @endif
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Reservation register</h2><span class="muted small">{{ $reservations->count() }} records</span></div>
        @if ($reservations->isEmpty())<div class="empty">No stays have been booked yet.</div>
        @else
            <table><thead><tr><th>Confirmation</th><th>Guest</th><th>Room(s)</th><th>Stay dates</th><th>Status</th><th>Payments</th><th>Actions</th></tr></thead><tbody>
            @foreach ($reservations as $reservation)
                <tr>
                    <td><strong>{{ $reservation->confirmation_code }}</strong></td>
                    <td>{{ $reservation->guest->first_name }} {{ $reservation->guest->last_name }}</td>
                    <td>{{ $reservation->rooms->pluck('room_number')->join(', ') }}</td>
                    <td>{{ $reservation->check_in->format('M j, Y') }} – {{ $reservation->check_out->format('M j, Y') }}</td>
                    <td><span class="pill {{ $reservation->status }}">{{ str_replace('_', ' ', $reservation->status) }}</span></td>
                    <td>
                        @php
                            $nights = $reservation->check_in->diffInDays($reservation->check_out);
                            $roomTotal = $reservation->rooms->sum(fn ($room) => (float) $room->pivot->nightly_rate * $nights);
                            $serviceTotal = $reservation->services->sum(fn ($service) => (float) $service->pivot->unit_price * $service->pivot->quantity);
                            $paidTotal = (float) $reservation->payments->where('status', 'paid')->sum('amount');
                        @endphp
                        <div class="small" style="margin-bottom:6px">ETB {{ number_format($paidTotal, 2) }} paid / ETB {{ number_format($roomTotal + $serviceTotal, 2) }} total</div>
                        @can('record-payments')
                        @if ($reservation->status !== 'cancelled' && $paidTotal < $roomTotal + $serviceTotal)
                            <form class="inline-form" method="POST" action="{{ route('payments.store', $reservation) }}">
                                @csrf
                                <input type="number" name="amount" min="0.01" step="0.01" placeholder="ETB amount" aria-label="Payment amount in Ethiopian Birr" required>
                                <select name="method" aria-label="Payment method" required><option value="card">Card</option><option value="cash">Cash</option><option value="bank_transfer">Transfer</option></select>
                                <button class="button small-button" type="submit">Record</button>
                            </form>
                        @endif
                        @endcan
                    </td>
                    <td>
                        @if (in_array($reservation->status, ['pending', 'confirmed'], true))
                            <form method="POST" action="{{ route('reservations.status', $reservation) }}" style="display:flex;gap:6px;flex-wrap:wrap">
                                @csrf
                                @method('PATCH')
                                @if ($reservation->status === 'pending')<button class="button small-button" type="submit" name="status" value="confirmed">Confirm</button>@else<button class="button small-button" type="submit" name="status" value="checked_in">Check in</button>@endif
                                <button class="button secondary small-button" type="submit" name="status" value="cancelled">Cancel</button>
                            </form>
                            <a class="button secondary small-button" href="{{ route('reservations.edit', $reservation) }}">Edit</a>
                            @if ($reservation->payments->isEmpty())<form method="POST" action="{{ route('reservations.destroy', $reservation) }}" style="margin-top:6px">@csrf @method('DELETE')<button class="button secondary small-button" type="submit">Delete</button></form>@endif
                        @elseif ($reservation->status === 'checked_in')
                            <form method="POST" action="{{ route('reservations.status', $reservation) }}">
                                @csrf
                                @method('PATCH')
                                <button class="button small-button" type="submit" name="status" value="checked_out">Check out</button>
                            </form>
                        @else
                            <span class="muted small">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody></table>
        @endif
    </section>
@endsection
