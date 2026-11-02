@extends('layouts.hotel')

@section('title', 'Edit reservation')

@section('content')
    <h1>Edit reservation</h1>
    <p class="subtitle">Update {{ $reservation->confirmation_code }}. Paid or in-progress stays cannot be edited.</p>
    <section class="panel">
        <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="form-grid">
            @csrf
            @method('PUT')
            <div class="field"><label for="guest_id">Guest</label><select id="guest_id" name="guest_id" required>@foreach ($guests as $guest)<option value="{{ $guest->id }}" @selected(old('guest_id', $reservation->guest_id) == $guest->id)>{{ $guest->last_name }}, {{ $guest->first_name }} — {{ $guest->email }}</option>@endforeach</select></div>
            <div class="field"><label for="check_in">Check-in date</label><input id="check_in" type="date" name="check_in" value="{{ old('check_in', $reservation->check_in->toDateString()) }}" min="{{ today()->toDateString() }}" required></div>
            <div class="field"><label for="check_out">Check-out date</label><input id="check_out" type="date" name="check_out" value="{{ old('check_out', $reservation->check_out->toDateString()) }}" min="{{ today()->addDay()->toDateString() }}" required></div>
            <div class="field full"><label>Rooms</label><div class="room-options">
                @foreach ($rooms as $room)
                    <label class="room-option"><input type="checkbox" name="room_ids[]" value="{{ $room->id }}" @checked(in_array($room->id, (array) old('room_ids', $reservation->rooms->modelKeys()), true))><span><strong>Room {{ $room->room_number }}</strong> · {{ $room->roomType->name }} · ETB {{ number_format((float) $room->roomType->base_rate, 2) }} / night @if ($room->status === 'maintenance') <strong>· Maintenance: replace this room to change the dates or room assignment</strong> @endif</span></label>
                @endforeach
            </div></div>
            @if ($services->isNotEmpty())
                <div class="field full"><label>Optional hotel services</label><div class="room-options">
                    @foreach ($services as $service)
                        <label class="room-option"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, (array) old('service_ids', $reservation->services->modelKeys()), true))><span><strong>{{ $service->name }}</strong> · ETB {{ number_format((float) $service->price, 2) }}</span></label>
                    @endforeach
                </div></div>
            @endif
            <div class="field full"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="1000">{{ old('notes', $reservation->notes) }}</textarea></div>
            <div class="field full" style="display:flex;gap:8px;align-items:center"><button class="button" type="submit">Save changes</button><a class="button secondary" href="{{ route('reservations.index') }}">Back</a></div>
        </form>
    </section>
@endsection
