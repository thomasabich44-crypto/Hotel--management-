@extends('layouts.hotel')

@section('title', 'Overview')

@section('content')
    <h1>Good day. Here's your hotel.</h1>
    <p class="subtitle">A clear view of today's rooms, guests and upcoming stays.</p>
    <section class="cards" aria-label="Hotel summary">
        <div class="card"><div class="card-label">Total rooms</div><div class="card-value">{{ $roomCount }}</div><div class="card-detail">Across all room types</div></div>
        <div class="card"><div class="card-label">Occupied today</div><div class="card-value">{{ $occupiedCount }}</div><div class="card-detail">{{ $roomCount ? number_format(($occupiedCount / $roomCount) * 100) : 0 }}% of available rooms</div></div>
        <div class="card"><div class="card-label">Active stays today</div><div class="card-value">{{ $reservationCount }}</div><div class="card-detail">Currently in-house</div></div>
        <div class="card"><div class="card-label">Guest profiles</div><div class="card-value">{{ $guestCount }}</div><div class="card-detail">Registered guests</div></div>
    </section>
    <div class="split">
        <section class="panel">
            <div class="panel-head"><h2>Recent reservations</h2><a class="button secondary small-button" href="{{ route('reservations.index') }}">Manage stays</a></div>
            @if ($reservations->isEmpty())<div class="empty">No reservations yet. Add your first stay from Reservations.</div>
            @else
                <table><thead><tr><th>Guest</th><th>Room</th><th>Stay</th><th>Status</th></tr></thead><tbody>
                @foreach ($reservations as $reservation)
                    <tr><td><strong>{{ $reservation->guest->first_name }} {{ $reservation->guest->last_name }}</strong><div class="muted small">{{ $reservation->confirmation_code }}</div></td><td>{{ $reservation->rooms->pluck('room_number')->join(', ') }}</td><td>{{ $reservation->check_in->format('M j') }} – {{ $reservation->check_out->format('M j') }}</td><td><span class="pill {{ $reservation->status }}">{{ str_replace('_', ' ', $reservation->status) }}</span></td></tr>
                @endforeach
                </tbody></table>
            @endif
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Room types</h2><a class="button secondary small-button" href="{{ route('rooms.index') }}">View rooms</a></div>
            @foreach ($roomTypes as $type)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid #f0f2f0">
                    <div><strong style="font-size:13px">{{ $type->name }}</strong><div class="muted small">{{ $type->rooms_count }} rooms · sleeps {{ $type->capacity }}</div></div>
                    <strong style="font-size:13px">ETB {{ number_format((float) $type->base_rate, 2) }}<span class="muted small"> / night</span></strong>
                </div>
            @endforeach
            @if ($roomTypes->isEmpty())<div class="empty">No room types have been added.</div>@endif
        </section>
    </div>
@endsection
