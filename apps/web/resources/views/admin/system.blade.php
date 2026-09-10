@extends('layouts.app')

@section('title', 'Sistem dan Audit')

@section('content')
<div class="page-header">
    <div><h1>Sistem &amp; Audit</h1><p class="muted">Kesehatan layanan, backup, dan jejak perubahan aplikasi.</p></div>
    <form method="post" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary" type="submit">Jalankan backup</button></form>
</div>

<section class="grid metrics" aria-label="Status sistem">
    <div class="card"><div class="metric-label">Database</div><div class="metric-value {{ $systemStatus['database'] ? 'money-in' : 'negative' }}">{{ $systemStatus['database'] ? 'Terhubung' : 'Bermasalah' }}</div><small>Diperiksa {{ $systemStatus['checked_at']->format('d/m/Y H:i:s') }}</small></div>
    <div class="card"><div class="metric-label">Antrean</div><div class="metric-value">{{ number_format($systemStatus['queue_pending'] ?? 0) }} menunggu</div><small>{{ number_format($systemStatus['queue_failed'] ?? 0) }} gagal · {{ $systemStatus['queue_connection'] }}</small></div>
    <div class="card"><div class="metric-label">Penyimpanan tersedia</div><div class="metric-value">{{ $systemStatus['storage_free_bytes'] !== null ? number_format($systemStatus['storage_free_bytes'] / 1073741824, 1, ',', '.').' GB' : 'Tidak tersedia' }}</div><small>Volume aplikasi saat ini</small></div>
    <div class="card"><div class="metric-label">Jumlah Laporan</div><div class="metric-value">{{ $reportCount }}</div><small>{{ $users->count() }} pengguna terdaftar</small></div>
</section>

<div class="grid two-col" style="margin-top:1rem">
    <div class="stack">
        <section class="card">
            <h2 class="section-title">Pengaturan backup</h2>
            <form method="post" action="{{ route('admin.backups.settings') }}">@csrf @method('PUT')
                <div class="field"><label for="backup_frequency">Jadwal</label><select class="input" id="backup_frequency" name="backup_frequency"><option value="OFF" @selected($backupSettings['backup_frequency'] === 'OFF')>Nonaktif</option><option value="DAILY" @selected($backupSettings['backup_frequency'] === 'DAILY')>Harian</option><option value="WEEKLY" @selected($backupSettings['backup_frequency'] === 'WEEKLY')>Mingguan, setiap Senin</option></select></div>
                <div class="form-columns"><div class="field"><label for="backup_time">Jam</label><input class="input" id="backup_time" name="backup_time" type="time" value="{{ $backupSettings['backup_time'] }}" required></div><div class="field"><label for="backup_retention_daily">Jumlah backup disimpan</label><input class="input" id="backup_retention_daily" name="backup_retention_daily" type="number" min="1" max="365" value="{{ $backupSettings['backup_retention_daily'] }}" required></div></div>
                <button class="btn btn-secondary" type="submit">Simpan pengaturan backup</button>
            </form>
        </section>
        <section class="card">
            <h2 class="section-title">Riwayat backup</h2>
            @forelse($backups as $backup)
                <p><strong>{{ $backup->type }} · {{ $backup->status }}</strong><br><span class="muted">{{ $backup->created_at->format('d/m/Y H:i') }} @if($backup->requester)· {{ $backup->requester->name }}@endif @if($backup->checksum)· checksum {{ substr($backup->checksum, 0, 12) }}…@endif</span>@if($backup->error)<br><span class="negative">{{ $backup->error }}</span>@endif</p>
            @empty<p class="muted">Belum ada backup.</p>@endforelse
        </section>
    </div>
    <section class="card">
        <h2 class="section-title">Audit sistem</h2>
        @forelse($audits as $audit)
            <p><strong>{{ $audit->action }}</strong><br><span class="muted">{{ $audit->created_at->format('d/m/Y H:i') }} · {{ $audit->user?->name ?? 'Sistem' }} @if($audit->report)· {{ $audit->report->name }}@endif</span></p>
        @empty<p class="muted">Belum ada audit.</p>@endforelse
    </section>
</div>
@endsection
