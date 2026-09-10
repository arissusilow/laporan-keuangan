<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset kata sandi — Laporan Keuangan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
<main class="login-shell">
    <div class="login-brand"><p class="eyebrow light">Pemulihan akun</p><h1>Laporan Keuangan</h1><p>Buat kata sandi baru untuk akun Anda.</p></div>
    <section class="surface login-panel">
        <h2>Reset kata sandi</h2>
        @if($errors->any())<div class="notice notice-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="field-label" for="email">Email</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
            <label class="field-label" for="password">Kata sandi baru <span class="optional">minimal 6 karakter</span></label>
            <div class="password-control">
                <input class="form-control" id="password" type="password" name="password" minlength="6" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.6"/></svg></button>
            </div>
            <label class="field-label" for="password_confirmation">Ulangi kata sandi baru</label>
            <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" minlength="6" autocomplete="new-password" required>
            <div class="form-actions"><button class="button button-primary" type="submit">Simpan kata sandi</button></div>
        </form>
    </section>
</main>
</body>
</html>
