@extends('layouts.hotel')

@section('title', 'Reports')

@section('content')
    <h1>Reports</h1>
    <p class="subtitle">A current snapshot of reservations, hotel activity and recorded payments.</p>
    <section class="cards" aria-label="Hotel report summary">
        <div class="card"><div class="card-label">Reservations</div><div class="card-value">{{ $reservationCount }}</div><div class="card-detail">All recorded statuses</div></div>
        <div class="card"><div class="card-label">Guests</div><div class="card-value">{{ $guestCount }}</div><div class="card-detail">Registered profiles</div></div>
        <div class="card"><div class="card-label">Rooms</div><div class="card-value">{{ $roomCount }}</div><div class="card-detail">Inventory units</div></div>
        <div class="card"><div class="card-label">Recorded payments</div><div class="card-value">ETB {{ number_format($paidTotal, 2) }}</div><div class="card-detail">Paid transactions</div></div>
    </section>
    <div class="split">
        <section class="panel">
            <div class="panel-head"><h2>Reservation status</h2></div>
            @forelse (['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'] as $status)
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f0f2f0"><span class="pill {{ $status }}">{{ str_replace('_', ' ', $status) }}</span><strong>{{ $statusCounts->get($status, 0) }}</strong></div>
            @empty
                <div class="empty">No reservations yet.</div>
            @endforelse
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Outstanding balance</h2></div>
            <div class="card-value">ETB {{ number_format($outstandingTotal, 2) }}</div>
            <p class="muted small">Calculated from booked room rates and service charges, less paid payments. Cancelled stays are excluded.</p>
        </section>
    </div>
    <section class="panel">
        <div class="panel-head"><h2>Recent payments</h2></div>
        @if ($recentPayments->isEmpty())
            <div class="empty">No payments have been recorded.</div>
        @else
            <table><thead><tr><th>Paid at</th><th>Confirmation</th><th>Guest</th><th>Method</th><th>Status</th><th>Amount</th></tr></thead><tbody>
            @foreach ($recentPayments as $payment)
                <tr><td>{{ $payment->paid_at->format('M j, Y H:i') }}</td><td>{{ $payment->reservation->confirmation_code }}</td><td>{{ $payment->reservation->guest->first_name }} {{ $payment->reservation->guest->last_name }}</td><td>{{ str_replace('_', ' ', $payment->method) }}</td><td>{{ $payment->status }}</td><td>ETB {{ number_format((float) $payment->amount, 2) }}</td></tr>
            @endforeach
            </tbody></table>
        @endif
    </section>
@endsection
