@extends('layouts.app')

@section('title', 'Ganti kata sandi')

@section('content')
<div class="card" style="max-width:560px;margin:auto">
    <h1>Ganti kata sandi awal</h1>
    <p class="muted">Untuk keamanan, buat kata sandi baru sebelum memakai aplikasi.</p>
    <form method="post" action="{{ route('password.first.update') }}">
        @csrf @method('PUT')
        <div class="field">
            <label for="current_password">Kata sandi awal</label>
            <div class="password-control">
                <input class="input" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="current_password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.6"/></svg>
                </button>
            </div>
        </div>
        <div class="field">
            <label for="password">Kata sandi baru (minimal 6 karakter)</label>
            <div class="password-control">
                <input class="input" id="password" type="password" name="password" minlength="6" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password password_confirmation" aria-label="Tampilkan kata sandi baru dan konfirmasi" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.6"/></svg>
                </button>
            </div>
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input class="input" id="password_confirmation" type="password" name="password_confirmation" minlength="6" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Simpan kata sandi</button>
    </form>
</div>
@endsection
