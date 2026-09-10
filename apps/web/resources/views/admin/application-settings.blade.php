@extends('layouts.app')

@section('title', 'Konfigurasi Aplikasi')

@section('content')
<div class="page-header">
    <div><p class="eyebrow">Super Admin</p><h1>Konfigurasi Aplikasi</h1><p class="muted">Pengaturan global untuk pengguna, identitas, regional, keamanan, dan berkas.</p></div>
</div>

<nav class="settings-tabs" aria-label="Bagian konfigurasi" role="tablist" data-application-settings-tabs>
    <a class="active" href="#users" role="tab" aria-controls="users" aria-selected="true" data-settings-tab="users">Pengguna <span>{{ $users->count() }}</span></a>
    <a href="#identity" role="tab" aria-controls="identity" aria-selected="false" data-settings-tab="identity">Identitas</a>
    <a href="#regional" role="tab" aria-controls="regional" aria-selected="false" data-settings-tab="regional">Regional</a>
    <a href="#security" role="tab" aria-controls="security" aria-selected="false" data-settings-tab="security">Keamanan</a>
    <a href="#uploads" role="tab" aria-controls="uploads" aria-selected="false" data-settings-tab="uploads">Berkas</a>
</nav>

<section class="card settings-panel app-settings-panel" id="users" role="tabpanel" data-settings-panel="users">
    <div class="section-heading">
        <div><h2>Pengguna dan hak akses</h2><p class="muted">Akses ke setiap Laporan diatur dari Pengaturan Laporan masing-masing.</p></div>
        <a class="btn btn-primary" href="#add-user">+ Tambah pengguna</a>
    </div>
    <div class="management-list">
        @foreach($users as $user)
            <article>
                <span class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div><strong>{{ $user->name }}</strong><span>{{ $user->email }} · {{ $user->report_memberships_count }} akses Laporan</span></div>
                <div class="management-status">@if($user->is_super_admin)<span class="badge">Super Admin</span>@endif<span class="badge {{ $user->active ? '' : 'badge-danger' }}">{{ $user->active ? 'Aktif' : 'Nonaktif' }}</span></div>
                <div class="management-actions"><a class="btn btn-secondary" href="#edit-user-{{ $user->id }}">Edit</a></div>
            </article>
        @endforeach
    </div>
</section>

<form class="stack app-settings-form" method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-settings-form>@csrf @method('PUT')
    <input type="hidden" name="_settings_tab" value="{{ old('_settings_tab') }}" data-settings-active-input>
    <div class="grid two-col settings-columns">
        <div class="stack settings-column">
            <section class="card settings-panel app-settings-panel" id="identity" role="tabpanel" data-settings-panel="identity">
                <h2 class="section-title">Identitas aplikasi</h2>
                <div class="field"><label for="app_name">Nama aplikasi</label><input class="input" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required></div>
                <div class="field"><label for="short_name">Nama singkat</label><input class="input" id="short_name" name="short_name" maxlength="6" value="{{ old('short_name', $settings['short_name']) }}" required></div>
                <div class="field"><label for="tagline">Tagline</label><input class="input" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline']) }}"></div>
                <div class="field"><label for="app_logo">Logo aplikasi <span class="optional">PNG/JPG/WebP, maks. 2 MB</span></label><input class="input" id="app_logo" type="file" name="app_logo" accept="image/png,image/jpeg,image/webp"></div>
                <div class="form-columns"><div class="field"><label for="primary_color">Warna utama</label><input class="input" id="primary_color" type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}"></div><div class="field"><label for="accent_color">Warna aksen</label><input class="input" id="accent_color" type="color" name="accent_color" value="{{ old('accent_color', $settings['accent_color']) }}"></div></div>
            </section>
            <section class="card settings-panel app-settings-panel" id="regional" role="tabpanel" data-settings-panel="regional">
                <h2 class="section-title">Regional</h2>
                <div class="field"><label for="timezone">Zona waktu</label><select class="input" id="timezone" name="timezone"><option value="Asia/Jakarta">Asia/Jakarta</option></select></div>
                <div class="form-columns"><div class="field"><label for="locale">Bahasa</label><select class="input" id="locale" name="locale"><option value="id">Bahasa Indonesia</option></select></div><div class="field"><label for="currency">Mata uang</label><select class="input" id="currency" name="currency"><option value="IDR">Rupiah (IDR)</option></select></div></div>
                <div class="field"><label for="date_format">Format tanggal</label><select class="input" id="date_format" name="date_format"><option value="d/m/Y" @selected($settings['date_format'] === 'd/m/Y')>DD/MM/YYYY</option><option value="Y-m-d" @selected($settings['date_format'] === 'Y-m-d')>YYYY-MM-DD</option></select></div>
            </section>
        </div>
        <aside class="stack settings-column">
            <section class="card settings-panel app-settings-panel" id="security" role="tabpanel" data-settings-panel="security">
                <h2 class="section-title">Keamanan dan sesi</h2>
                <div class="field"><label for="session_lifetime">Durasi sesi (menit)</label><input class="input" id="session_lifetime" type="number" name="session_lifetime" min="15" max="1440" value="{{ old('session_lifetime', $settings['session_lifetime']) }}"></div>
                <div class="field"><label for="login_max_attempts">Maksimum percobaan login</label><input class="input" id="login_max_attempts" type="number" name="login_max_attempts" min="3" max="20" value="{{ old('login_max_attempts', $settings['login_max_attempts']) }}"></div>
                <div class="field"><label for="password_min_length">Minimum karakter kata sandi</label><input class="input" id="password_min_length" type="number" name="password_min_length" min="6" max="128" value="{{ old('password_min_length', $settings['password_min_length']) }}"></div>
            </section>
            <section class="card settings-panel app-settings-panel" id="uploads" role="tabpanel" data-settings-panel="uploads">
                <h2 class="section-title">Berkas dan unggahan</h2>
                <div class="field"><label for="attachment_max_mb">Batas bukti transaksi (MB)</label><input class="input" id="attachment_max_mb" type="number" name="attachment_max_mb" min="1" max="20" value="{{ old('attachment_max_mb', $settings['attachment_max_mb']) }}"></div>
                <div class="field"><label for="identity_max_mb">Batas gambar identitas (MB)</label><input class="input" id="identity_max_mb" type="number" name="identity_max_mb" min="1" max="10" value="{{ old('identity_max_mb', $settings['identity_max_mb']) }}"></div>
                <p class="muted">Format aman: JPG, PNG, WebP, dan PDF untuk bukti transaksi.</p>
            </section>
            <section class="card settings-panel app-settings-panel" role="tabpanel" data-settings-panel="uploads"><h2 class="section-title">Operasional sistem</h2><div class="linked-settings"><a href="{{ route('admin.system') }}"><span>Backup, status, dan audit</span><strong>→</strong></a><a href="{{ route('reports.index') }}"><span>Pengelolaan Laporan</span><strong>→</strong></a></div></section>
        </aside>
    </div>
    <div class="settings-footer" data-settings-form-footer><span>Perubahan penting dicatat pada audit sistem.</span><button class="btn btn-primary" type="submit">Simpan konfigurasi</button></div>
</form>

<div class="modal-shell" id="add-user" role="dialog" aria-modal="true" aria-labelledby="add-user-title">
    <a class="modal-backdrop" href="#users" aria-label="Tutup popup"></a>
    <section class="modal-card"><div class="modal-header"><div><p class="eyebrow">Pengguna baru</p><h2 id="add-user-title">Tambah pengguna</h2></div><a class="modal-close" href="#users" aria-label="Tutup">×</a></div>
        <form method="post" action="{{ route('admin.users.store') }}" data-ajax-user-form novalidate>@csrf
            <div class="notice notice-danger ajax-form-errors" role="alert" data-ajax-form-errors hidden><strong>Periksa kembali isian:</strong><ul></ul></div>
            <div class="field"><label for="new-user-name">Nama</label><input class="input" id="new-user-name" name="name" autocomplete="name" required><p class="field-error" data-error-for="name" hidden></p></div>
            <div class="field"><label for="new-user-email">Email</label><input class="input" id="new-user-email" name="email" type="email" autocomplete="email" required><p class="field-error" data-error-for="email" hidden></p></div>
            <label class="switch-row default-password-option"><input type="checkbox" name="use_default_password" value="1" data-default-password-toggle><span><strong>Gunakan password awal aplikasi</strong><small>Password diambil dari konfigurasi server dan tidak ditampilkan di halaman.</small></span></label>
            <div class="field" data-manual-password-field><label for="new-user-password">Kata sandi awal <span class="optional">minimal 6 karakter, huruf dan angka</span></label><div class="password-control"><input class="input" id="new-user-password" name="password" type="password" minlength="6" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle aria-controls="new-user-password" aria-label="Tampilkan kata sandi" aria-pressed="false"><span aria-hidden="true"></span></button></div><p class="field-error" data-error-for="password" hidden></p></div>
            <label class="check-single"><input type="checkbox" name="is_super_admin" value="1"> Super Admin</label>
            <div class="modal-actions"><a class="btn btn-secondary" href="#users">Batal</a><button class="btn btn-primary" type="submit" data-ajax-user-submit>Buat pengguna</button></div>
        </form>
    </section>
</div>

@foreach($users as $user)
    <div class="modal-shell" id="edit-user-{{ $user->id }}" role="dialog" aria-modal="true" aria-labelledby="edit-user-title-{{ $user->id }}">
        <a class="modal-backdrop" href="#users" aria-label="Tutup popup"></a>
        <section class="modal-card"><div class="modal-header"><div><p class="eyebrow">Pengguna</p><h2 id="edit-user-title-{{ $user->id }}">Edit {{ $user->name }}</h2></div><a class="modal-close" href="#users" aria-label="Tutup">×</a></div>
            <form method="post" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
                <div class="field"><label for="user-name-{{ $user->id }}">Nama</label><input class="input" id="user-name-{{ $user->id }}" name="name" value="{{ $user->name }}" required></div>
                <div class="field"><label for="user-email-{{ $user->id }}">Email</label><input class="input" id="user-email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required></div>
                <input type="hidden" name="active" value="0"><input type="hidden" name="is_super_admin" value="0">
                <div class="check-grid"><label><input type="checkbox" name="active" value="1" @checked($user->active)> Aktif</label><label><input type="checkbox" name="is_super_admin" value="1" @checked($user->is_super_admin)> Super Admin</label></div>
                <div class="modal-actions"><a class="btn btn-secondary" href="#users">Batal</a><button class="btn btn-primary" type="submit">Simpan pengguna</button></div>
            </form>
            <form method="post" action="{{ route('admin.users.password-reset', $user) }}" onsubmit="return confirm('Reset kata sandi {{ addslashes($user->name) }} ke password awal aplikasi?')">@csrf<button class="text-action" type="submit">Reset ke password awal</button></form>
        </section>
    </div>
@endforeach
@endsection
