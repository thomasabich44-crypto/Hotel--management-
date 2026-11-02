<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff sign in · NHotel</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f6f3;color:#202d2a;font-family:system-ui,sans-serif}.panel{width:min(420px,calc(100% - 32px));padding:28px;background:#fff;border:1px solid #e5ebe6;border-radius:12px}h1{margin:0 0 8px;font-size:24px}.muted{color:#71817b;font-size:14px;margin:0 0 22px}.field{display:grid;gap:7px;margin:14px 0}label{font-size:13px;font-weight:650}input{font:inherit;padding:11px;border:1px solid #dce4de;border-radius:7px}button{width:100%;border:0;border-radius:7px;background:#1d5748;color:white;font:inherit;font-weight:650;padding:11px;cursor:pointer}.errors{color:#9a4737;background:#fff0ed;padding:10px;border-radius:7px;margin-bottom:15px}
    </style>
</head>
<body>
<main class="panel">
    <h1>Staff sign in</h1>
    <p class="muted">Sign in with your hotel staff account.</p>
    @if ($errors->any())
        <div class="errors" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <div class="field"><label><input type="checkbox" name="remember" value="1"> Remember me</label></div>
        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
