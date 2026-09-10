<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="{{ $applicationSettings['primary_color'] }}">
    <title>Masuk — {{ $applicationSettings['app_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page" style="--app-primary: {{ $applicationSettings['primary_color'] }}; --app-accent: {{ $applicationSettings['accent_color'] }}">
<main class="login-shell">
    <section class="login-brand">
        @if(!empty($applicationSettings['logo_path']))<img class="login-logo" src="{{ route('identity.logo') }}" alt="Logo {{ $applicationSettings['app_name'] }}">@else<span class="brand-mark large">{{ $applicationSettings['short_name'] }}</span>@endif
        <p class="eyebrow light">{{ $applicationSettings['tagline'] }}</p>
        <h1>{{ $applicationSettings['app_name'] }}</h1>
        <p>Catat Uang Masuk, Uang Keluar, dan pantau Saldo sesuai ruang keuangan yang diberikan kepada akun Anda.</p>
    </section>
    <section class="surface login-panel" aria-labelledby="login-title">
        <p class="eyebrow">Akses aman</p>
        <h2 id="login-title">Masuk ke aplikasi</h2>
        <p class="help-text">Gunakan akun yang dibuat oleh administrator. Hanya Laporan sesuai hak akses yang akan ditampilkan.</p>
        @if(session('status'))<div class="notice notice-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice notice-danger" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.attempt') }}" data-login-form>
            @csrf
            <label class="field-label" for="email">Email</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" enterkeyhint="next" required autofocus>
            <label class="field-label" for="password">Kata sandi</label>
            <div class="password-control">
                <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" enterkeyhint="go" required data-login-password>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.6"/></svg>
                </button>
            </div>
            <label class="switch-row"><input type="checkbox" name="remember" value="1"><span><strong>Ingat saya</strong></span></label>
            <div class="form-actions"><button class="button button-primary" type="submit" data-login-submit>Masuk</button></div>
        </form>
        <p class="help-text"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
    </section>
</main>
</body>
</html>
