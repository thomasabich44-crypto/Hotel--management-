@extends('layouts.hotel')

@section('title', 'Guests')

@section('content')
    <h1>Guests</h1>
    <p class="subtitle">Guest contact details and reservation history.</p>
    <section class="panel">
        <div class="panel-head"><h2>Add a guest</h2></div>
        <form method="POST" action="{{ route('guests.store') }}" class="form-grid">
            @csrf
            <div class="field"><label for="first_name">First name</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="80"></div>
            <div class="field"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="80"></div>
            <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190"></div>
            <div class="field"><label for="phone">Phone <span class="muted">(optional)</span></label><input id="phone" name="phone" value="{{ old('phone') }}" maxlength="30"></div>
            <div class="field full"><button class="button" type="submit">Add guest</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Guest directory</h2><span class="muted small">{{ $guests->count() }} profiles</span></div>
        @if ($guests->isEmpty())<div class="empty">No guest profiles yet.</div>
        @else
            <table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Reservations</th><th>Actions</th></tr></thead><tbody>
            @foreach ($guests as $guest)
                <tr>
                    <td colspan="5">
                        <form method="POST" action="{{ route('guests.update', $guest) }}" class="form-grid">
                            @csrf
                            @method('PUT')
                            <div class="field"><label for="first_name_{{ $guest->id }}">First name</label><input id="first_name_{{ $guest->id }}" name="first_name" value="{{ $guest->first_name }}" maxlength="80" required></div>
                            <div class="field"><label for="last_name_{{ $guest->id }}">Last name</label><input id="last_name_{{ $guest->id }}" name="last_name" value="{{ $guest->last_name }}" maxlength="80" required></div>
                            <div class="field"><label for="email_{{ $guest->id }}">Email</label><input id="email_{{ $guest->id }}" name="email" type="email" value="{{ $guest->email }}" maxlength="190" required></div>
                            <div class="field"><label for="phone_{{ $guest->id }}">Phone</label><input id="phone_{{ $guest->id }}" name="phone" value="{{ $guest->phone }}" maxlength="30"></div>
                            <div><strong>{{ $guest->reservations_count }}</strong> reservations</div>
                            <div style="display:flex;gap:8px;align-items:center"><button class="button small-button" type="submit">Save</button>@if ($guest->reservations_count === 0)<button class="button secondary small-button" form="delete-guest-{{ $guest->id }}" type="submit">Delete</button>@endif</div>
                        </form>
                        @if ($guest->reservations_count === 0)
                            <form id="delete-guest-{{ $guest->id }}" method="POST" action="{{ route('guests.destroy', $guest) }}">@csrf @method('DELETE')</form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody></table>
        @endif
    </section>
@endsection
