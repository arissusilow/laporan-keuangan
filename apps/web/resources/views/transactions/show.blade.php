@extends('layouts.app')

@section('title', 'Detail '.$transaction->number)

@section('content')
<div class="page-header">
    <div><p class="muted">{{ $report->name }}</p><h1>Detail Transaksi</h1></div>
    <div class="actions">
        @can('update', $transaction)<a class="btn btn-primary" href="{{ route('transactions.edit', [$report, $transaction]) }}">Ubah transaksi</a>@endcan
        <a class="btn btn-secondary" href="{{ route('transactions.index', $report) }}">Kembali</a>
    </div>
</div>

<section class="card transaction-detail">
    <div><span>Nomor</span><strong>{{ $transaction->number }}</strong></div>
    <div><span>Status</span><strong class="badge {{ $transaction->status === 'CANCELLED' ? 'badge-danger' : '' }}">{{ $transaction->status === 'ACTIVE' ? 'Aktif' : 'Batal' }}</strong></div>
    <div><span>Tanggal</span><strong>{{ $transaction->transaction_date->locale('id')->translatedFormat('d F Y') }}</strong></div>
    <div><span>Jenis</span><strong>{{ $transaction->type === 'IN' ? 'Pemasukan' : 'Pengeluaran' }}</strong></div>
    <div><span>Jumlah</span><strong class="{{ $transaction->type === 'IN' ? 'money-in' : 'money-out' }}">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong></div>
    <div><span>Kategori</span><strong>{{ $transaction->category?->name ?? 'Tanpa kategori' }}</strong></div>
    <div class="detail-wide"><span>Keterangan</span><strong>{{ $transaction->description ?: 'Tidak ada keterangan' }}</strong></div>
    <div><span>Dibuat oleh</span><strong>{{ $transaction->creator?->name ?? 'Pengguna tidak tersedia' }}</strong></div>
    <div><span>Terakhir diubah</span><strong>{{ $transaction->updated_at->format('d/m/Y H:i') }}</strong></div>
    @if($transaction->status === 'CANCELLED')
        <div class="detail-wide"><span>Alasan pembatalan</span><strong>{{ $transaction->cancellation_reason }}</strong><small>{{ $transaction->canceller?->name ?? 'Pengguna tidak tersedia' }} · {{ $transaction->cancelled_at?->format('d/m/Y H:i') }}</small></div>
    @endif
    @if($transaction->attachments->isNotEmpty())
        <div class="detail-wide"><span>Bukti transaksi</span>@foreach($transaction->attachments as $attachment)<a href="{{ route('attachments.download', $attachment) }}">Unduh {{ $attachment->original_name }}</a>@endforeach</div>
    @endif
</section>

@can('cancel', $transaction)
    <form class="card cancel-card" method="post" action="{{ route('transactions.cancel', [$report, $transaction]) }}">
        @csrf
        <h2 class="section-title">Batalkan transaksi</h2>
        <p class="muted">Transaksi tetap tersimpan untuk audit dan dikeluarkan dari perhitungan saldo.</p>
        <div class="field"><label for="reason">Alasan pembatalan</label><textarea class="input" id="reason" name="reason" required minlength="5" maxlength="500"></textarea></div>
        <button class="btn btn-danger" type="submit">Ya, batalkan transaksi</button>
    </form>
@endcan
@endsection
