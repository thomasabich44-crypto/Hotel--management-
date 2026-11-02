@extends('layouts.hotel')

@section('title', 'Rooms')

@section('content')
    <h1>Rooms</h1>
    <p class="subtitle">Manage the hotel room inventory and room categories.</p>
    @can('manage-inventory')
        <section class="panel">
            <div class="panel-head"><h2>Add a room</h2></div>
            <form method="POST" action="{{ route('rooms.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="room_number">Room number</label><input id="room_number" name="room_number" maxlength="12" required></div>
                <div class="field"><label for="room_type_id">Room type</label><select id="room_type_id" name="room_type_id" required>@foreach ($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                <div class="field"><label for="floor">Floor</label><input id="floor" type="number" name="floor" min="0" value="1" required></div>
                <div class="field"><label for="room_status">Condition</label><select id="room_status" name="status"><option value="available">Available</option><option value="maintenance">Maintenance</option></select></div>
                <div class="field full"><button class="button" type="submit">Add room</button></div>
            </form>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Add room type</h2></div>
            <form method="POST" action="{{ route('room-types.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="type_name">Name</label><input id="type_name" name="name" maxlength="80" required></div>
                <div class="field"><label for="capacity">Capacity</label><input id="capacity" type="number" name="capacity" min="1" max="255" required></div>
                <div class="field"><label for="base_rate">Base rate / night (ETB)</label><input id="base_rate" type="number" name="base_rate" min="0.01" step="0.01" required></div>
                <div class="field"><label for="description">Description</label><input id="description" name="description" maxlength="2000"></div>
                <div class="field full"><button class="button" type="submit">Add room type</button></div>
            </form>
        </section>
    @endcan
    <section class="panel">
        <div class="panel-head"><h2>Room inventory</h2><span class="muted small">{{ $rooms->count() }} rooms</span></div>
        @if ($rooms->isEmpty())<div class="empty">No rooms have been added. Seed the demo database to load sample rooms.</div>
        @else
            <table><thead><tr><th>Room</th><th>Type</th><th>Floor</th><th>Capacity</th><th>Rate / night</th><th>Condition</th>@can('manage-inventory')<th>Actions</th>@endcan</tr></thead><tbody>
            @foreach ($rooms as $room)
                <tr>
                    <td><strong>{{ $room->room_number }}</strong></td><td>{{ $room->roomType->name }}</td><td>{{ $room->floor }}</td><td>{{ $room->roomType->capacity }} guests</td><td>ETB {{ number_format((float) $room->roomType->base_rate, 2) }}</td><td><span class="pill {{ $room->status }}">{{ $room->status }}</span></td>
                    @can('manage-inventory')
                        <td>
                            <form method="POST" action="{{ route('rooms.update', $room) }}" class="inline-form">
                                @csrf @method('PUT')
                                <input name="room_number" aria-label="Room number" value="{{ $room->room_number }}" maxlength="12" required>
                                <select name="room_type_id" aria-label="Room type">@foreach ($roomTypes as $type)<option value="{{ $type->id }}" @selected($room->room_type_id === $type->id)>{{ $type->name }}</option>@endforeach</select>
                                <input type="number" name="floor" aria-label="Floor" value="{{ $room->floor }}" min="0" required>
                                <select name="status" aria-label="Condition"><option value="available" @selected($room->status === 'available')>Available</option><option value="maintenance" @selected($room->status === 'maintenance')>Maintenance</option></select>
                                <button class="button small-button" type="submit">Save</button>
                            </form>
                            @if ($room->reservations_count === 0)<form method="POST" action="{{ route('rooms.destroy', $room) }}" style="margin-top:6px">@csrf @method('DELETE')<button class="button secondary small-button" type="submit">Delete</button></form>@endif
                        </td>
                    @endcan
                </tr>
            @endforeach
            </tbody></table>
        @endif
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Room types</h2></div>
        <table><thead><tr><th>Type</th><th>Description</th><th>Capacity</th><th>Rooms</th><th>Base rate</th>@can('manage-inventory')<th>Actions</th>@endcan</tr></thead><tbody>
        @foreach ($roomTypes as $type)
            <tr>
                <td><strong>{{ $type->name }}</strong></td><td>{{ $type->description ?: '—' }}</td><td>{{ $type->capacity }}</td><td>{{ $type->rooms_count }}</td><td>ETB {{ number_format((float) $type->base_rate, 2) }}</td>
                @can('manage-inventory')
                    <td>
                        <form method="POST" action="{{ route('room-types.update', $type) }}" class="inline-form">
                            @csrf @method('PUT')
                            <input name="name" aria-label="Type name" value="{{ $type->name }}" maxlength="80" required>
                            <input name="description" aria-label="Description" value="{{ $type->description }}" maxlength="2000">
                            <input type="number" name="capacity" aria-label="Capacity" value="{{ $type->capacity }}" min="1" max="255" required>
                            <input type="number" name="base_rate" aria-label="Base rate in ETB" value="{{ number_format((float) $type->base_rate, 2, '.', '') }}" min="0.01" step="0.01" required>
                            <button class="button small-button" type="submit">Save</button>
                        </form>
                        @if ($type->rooms_count === 0)<form method="POST" action="{{ route('room-types.destroy', $type) }}" style="margin-top:6px">@csrf @method('DELETE')<button class="button secondary small-button" type="submit">Delete</button></form>@endif
                    </td>
                @endcan
            </tr>
        @endforeach
        @if ($roomTypes->isEmpty())<tr><td colspan="6" class="empty">No room types have been added.</td></tr>@endif
        </tbody></table>
    </section>
@endsection
