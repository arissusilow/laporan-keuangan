@extends('layouts.app')

@section('title', $report->name)

@section('content')
<div class="page-header">
    <div>
        <span class="badge">{{ $report->status === 'ACTIVE' ? 'Aktif' : ($report->status === 'CLOSED' ? 'Ditutup' : 'Diarsipkan') }}</span>
        <h1>{{ $report->name }}</h1>
        <p class="muted">{{ $report->starts_on?->locale('id')->translatedFormat('d M Y') ?: 'Tanpa tanggal mulai' }} — {{ $report->ends_on?->locale('id')->translatedFormat('d M Y') ?: 'Berjalan' }}</p>
    </div>
    @if($report->isWritable() && (auth()->user()->can('create', [\App\Models\FinancialTransaction::class, $report, 'IN']) || auth()->user()->can('create', [\App\Models\FinancialTransaction::class, $report, 'OUT'])))
        <div class="actions"><a class="btn btn-primary" href="{{ route('transactions.create', $report) }}">＋ Buat Transaksi</a></div>
    @endif
</div>

@unless($report->isWritable())
    <div class="alert alert-warning"><strong>Laporan baca-saja.</strong> Laporan telah {{ $report->status === 'CLOSED' ? 'ditutup' : 'diarsipkan' }}; transaksi tidak dapat diubah.</div>
@endunless
@if($balance < 0)
    <div class="alert alert-danger"><strong>Saldo negatif.</strong> Pengeluaran telah melampaui saldo tersedia.</div>
@endif

<section class="grid metrics dashboard-metrics" aria-label="Ringkasan keuangan">
    <div class="card dashboard-metric metric-balance"><div class="metric-icon" aria-hidden="true">◎</div><div class="metric-label">Saldo saat ini</div><div class="metric-value {{ $balance < 0 ? 'negative' : '' }}">Rp {{ number_format($balance, 0, ',', '.') }}</div><small>Posisi keuangan terkini</small></div>
    <div class="card dashboard-metric metric-income"><div class="metric-icon" aria-hidden="true">↙</div><div class="metric-label">Total Pemasukan</div><div class="metric-value money-in">Rp {{ number_format($totals['incoming'], 0, ',', '.') }}</div><small>Sejak laporan dimulai</small></div>
    <div class="card dashboard-metric metric-expense"><div class="metric-icon" aria-hidden="true">↗</div><div class="metric-label">Total Pengeluaran</div><div class="metric-value money-out">Rp {{ number_format($totals['outgoing'], 0, ',', '.') }}</div><small>Sejak laporan dimulai</small></div>
    <div class="card dashboard-metric metric-month"><div class="metric-icon" aria-hidden="true">▦</div><div class="metric-label">Bulan ini</div><div class="month-flow"><strong class="money-in">Masuk Rp {{ number_format($month['incoming'], 0, ',', '.') }}</strong><span class="money-out">Keluar Rp {{ number_format($month['outgoing'], 0, ',', '.') }}</span></div><small>{{ now()->locale('id')->translatedFormat('F Y') }}</small></div>
</section>

<div class="grid two-col dashboard-content-grid">
    <section class="card dashboard-panel">
        <div class="dashboard-panel-heading"><div><span class="panel-kicker">Aktivitas</span><h2 class="section-title">Lima transaksi terbaru</h2></div><a href="{{ route('transactions.index', $report) }}">Lihat semua</a></div>
        @forelse($latest as $tx)
            <div class="dashboard-transaction"><div><strong>{{ $tx->description ?: $tx->category?->name ?: 'Tanpa keterangan' }}</strong><div class="muted">{{ $tx->transaction_date->locale('id')->translatedFormat('d M Y') }} · {{ $tx->type === 'IN' ? 'Pemasukan' : 'Pengeluaran' }}</div></div><strong class="{{ $tx->type === 'IN' ? 'money-in' : 'money-out' }}">{{ $tx->type === 'IN' ? '+' : '−' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}</strong></div>
        @empty
            <div class="empty"><p>Belum ada transaksi.</p></div>
        @endforelse
    </section>
    <section class="card dashboard-panel category-panel">
        <div class="dashboard-panel-heading"><div><span class="panel-kicker">Komposisi</span><h2 class="section-title">Pengeluaran per kategori</h2></div></div>
        @forelse($categories as $category)
            <div class="dashboard-category"><span><i style="background:{{ $category->color }}"></i>{{ $category->name }}</span><strong>Rp {{ number_format($category->total, 0, ',', '.') }}</strong></div>
        @empty
            <p class="muted">Belum ada pengeluaran.</p>
        @endforelse
    </section>
</div>
@endsection
