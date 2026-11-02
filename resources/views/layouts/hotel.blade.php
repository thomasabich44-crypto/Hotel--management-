<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Hotel desk') · NHotel</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:#202d2a;background:#f4f6f3;font-synthesis:none}
        *{box-sizing:border-box}body{margin:0}a{color:inherit;text-decoration:none}
        .shell{min-height:100vh;display:grid;grid-template-columns:238px 1fr}
        .sidebar{background:#173b35;color:#eef5f1;padding:28px 18px;display:flex;flex-direction:column;gap:32px}
        .brand{font-size:19px;font-weight:750;letter-spacing:.02em;padding:0 12px}.brand span{display:block;color:#a9c4b8;font-size:11px;font-weight:500;letter-spacing:.14em;margin-top:5px;text-transform:uppercase}
        nav{display:grid;gap:7px}nav a{padding:11px 12px;border-radius:8px;color:#c6d8d0;font-size:14px}nav a:hover,nav a.active{background:#28534a;color:white}
        .sidebar-note{margin-top:auto;color:#a9c4b8;font-size:12px;line-height:1.7;padding:0 12px}
        main{min-width:0;padding:34px clamp(20px,4vw,58px) 60px}
        .topline{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}.eyebrow{color:#71817b;font-size:12px;text-transform:uppercase;letter-spacing:.12em}.topline strong{font-size:13px;color:#52645d}
        h1{font-size:29px;letter-spacing:-.035em;margin:0 0 8px}h2{font-size:17px;margin:0}.subtitle{color:#71817b;margin:0 0 25px;font-size:14px}
        .cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:24px 0}
        .card,.panel{background:#fff;border:1px solid #e5ebe6;border-radius:12px;box-shadow:0 4px 18px rgba(31,55,43,.035)}
        .card{padding:18px}.card-label{font-size:12px;color:#71817b}.card-value{font-size:29px;font-weight:700;margin-top:8px;letter-spacing:-.04em}.card-detail{font-size:12px;color:#71817b;margin-top:4px}
        .panel{padding:21px;margin-bottom:18px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:17px}
        .split{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(250px,.8fr);gap:16px}
        table{border-collapse:collapse;width:100%;font-size:13px}th{text-align:left;color:#819089;font-weight:550;font-size:11px;text-transform:uppercase;letter-spacing:.07em;padding:10px 9px;border-bottom:1px solid #edf0ed}td{padding:13px 9px;border-bottom:1px solid #f0f2f0;vertical-align:middle}tr:last-child td{border-bottom:0}
        .muted{color:#788780}.small{font-size:12px}.pill{display:inline-block;border-radius:99px;background:#edf4ef;color:#3e7053;padding:5px 9px;font-size:11px;font-weight:650;text-transform:capitalize}.pill.checked_in{background:#eaf2ff;color:#355e9e}.pill.cancelled,.pill.maintenance{background:#fff1eb;color:#a15c38}.pill.checked_out{background:#f0eff7;color:#6d638b}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}label{font-size:12px;font-weight:650;color:#52645d}input,select,textarea{font:inherit;font-size:13px;color:#23352f;background:#fff;border:1px solid #dce4de;border-radius:7px;padding:10px 11px;width:100%}input:focus,select:focus,textarea:focus{outline:2px solid #aacbbb;border-color:#75a58d}textarea{resize:vertical;min-height:70px}
        .button{border:0;border-radius:7px;background:#1d5748;color:white;font:inherit;font-size:13px;font-weight:650;padding:10px 14px;cursor:pointer}.button:hover{background:#164739}.button.secondary{background:#eef4f0;color:#305b4a}.button.small-button{padding:8px 10px;font-size:12px}
        .room-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.room-option{border:1px solid #e1e9e3;border-radius:8px;padding:10px;display:flex;align-items:flex-start;gap:9px}.room-option input{width:auto;margin-top:3px}.room-option span{font-size:12px;line-height:1.5}.notice{padding:11px 14px;background:#eaf5ed;border:1px solid #d4e9d9;color:#2f6441;border-radius:8px;margin-bottom:16px;font-size:13px}.errors{padding:11px 14px;background:#fff0ed;border:1px solid #f1d3cb;color:#9a4737;border-radius:8px;margin-bottom:16px;font-size:13px}.inline-form{display:flex;align-items:center;gap:7px}.inline-form input{max-width:100px;padding:8px}.inline-form select{width:120px;padding:8px}.empty{padding:28px;text-align:center;color:#788780;font-size:13px}
        @media(max-width:950px){.cards{grid-template-columns:repeat(2,1fr)}.split{grid-template-columns:1fr}}
        @media(max-width:650px){.shell{grid-template-columns:1fr}.sidebar{padding:15px;gap:12px}.brand span,.sidebar-note{display:none}nav{grid-template-columns:repeat(3,1fr)}nav a{text-align:center;padding:9px 5px;font-size:12px}main{padding:24px 15px}.cards{gap:9px}.card{padding:13px}.card-value{font-size:24px}.panel{padding:15px;overflow-x:auto}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.room-options{grid-template-columns:1fr}table{min-width:500px}.topline{margin-bottom:20px}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">NHotel<span>Management desk</span></a>
        <nav aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Overview</a>
            <a href="{{ route('reservations.index') }}" @class(['active' => request()->routeIs('reservations.*') || request()->routeIs('payments.*')])>Reservations</a>
            <a href="{{ route('guests.index') }}" @class(['active' => request()->routeIs('guests.*')])>Guests</a>
            <a href="{{ route('rooms.index') }}" @class(['active' => request()->routeIs('rooms.*')])>Rooms</a>
            <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>Reports</a>
        </nav>
        <div class="sidebar-note">Hotel operations<br>Rooms · stays · payments</div>
    </aside>
    <main>
        <div class="topline">
            <span class="eyebrow">Hotel operations · {{ auth()->user()->role }}</span>
            <div style="display:flex;align-items:center;gap:14px">
                <strong>{{ now()->format('D, M j, Y') }}</strong>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="button secondary small-button" type="submit">Sign out</button></form>
            </div>
        </div>
        @if (session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="errors" role="alert"><strong>Please check the form:</strong><ul style="margin:7px 0 0;padding-left:20px">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
