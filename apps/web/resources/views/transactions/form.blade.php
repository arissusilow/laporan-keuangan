@extends('layouts.app')
@section('title',$transaction?'Ubah Transaksi':'Buat Transaksi')
@section('content')
<div class="page-header"><div><p class="muted">{{ $report->name }}</p><h1>{{ $transaction?'Ubah Transaksi':'Buat Transaksi' }}</h1></div></div>
<form class="card transaction-form-card" style="max-width:680px" method="post" enctype="multipart/form-data" action="{{ $transaction?route('transactions.update',[$report,$transaction]):route('transactions.store',[$report,strtolower($type)]) }}" data-transaction-form data-store-in="{{ route('transactions.store', [$report, 'in']) }}" data-store-out="{{ route('transactions.store', [$report, 'out']) }}" data-current-type="{{ $type }}" onsubmit="this.querySelector('button[type=submit]').disabled=true">
@csrf @if($transaction)@method('PUT')@endif
@unless($transaction)<fieldset class="transaction-type-field"><legend>Jenis transaksi</legend><div class="transaction-type-switch" role="group" aria-label="Pilih jenis transaksi">@if(in_array('OUT', $allowedTypes, true))<button type="button" data-transaction-type="OUT" aria-pressed="{{ $type === 'OUT' ? 'true' : 'false' }}">Pengeluaran</button>@endif @if(in_array('IN', $allowedTypes, true))<button type="button" data-transaction-type="IN" aria-pressed="{{ $type === 'IN' ? 'true' : 'false' }}">Pemasukan</button>@endif</div><p class="field-help" data-transaction-type-help>{{ $type === 'IN' ? 'Pemasukan menambah saldo.' : 'Pengeluaran mengurangi saldo.' }}</p></fieldset>@endunless
<div class="field"><label for="transaction_date">Tanggal</label><input class="input" id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date',$transaction?->transaction_date?->format('Y-m-d')??now()->format('Y-m-d')) }}" required></div>
<div class="field"><label for="amount">Jumlah (Rupiah)</label><input class="input" id="amount" name="amount" type="text" inputmode="numeric" pattern="[0-9.]+" value="{{ old('amount',$transaction?->amount) }}" aria-describedby="amount-help" autocomplete="off" data-rupiah-input required><small class="field-help" id="amount-help">Pemisah ribuan ditambahkan otomatis, contoh: 1.500.000.</small><div class="amount-presets" aria-label="Pilihan nominal cepat">@foreach([50000, 100000, 150000, 200000, 500000, 1000000] as $preset)<button type="button" data-rupiah-preset="{{ $preset }}">Rp{{ number_format($preset, 0, ',', '.') }}</button>@endforeach</div></div>
<div class="field">
    @if($transaction)
        <label for="expense_category_id">Kategori {{ $type === 'IN' ? 'pemasukan' : 'pengeluaran' }}</label>
        <select class="input" id="expense_category_id" name="expense_category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('expense_category_id', $transaction->expense_category_id) == $category->id) @disabled(!$category->active)>{{ $category->name }}{{ !$category->active ? ' (nonaktif)' : '' }}</option>@endforeach</select>
    @else
        <label for="new_category_name" data-category-label>Kategori {{ $type === 'IN' ? 'pemasukan' : 'pengeluaran' }}</label>
        <div class="category-autocomplete">
            <input class="input" id="new_category_name" name="new_category_name" maxlength="100" value="{{ old('new_category_name') }}" placeholder="Ketik atau pilih kategori" list="category-suggestions-{{ strtolower($type) }}" autocomplete="off" aria-autocomplete="list" aria-controls="category-suggestion-list" aria-expanded="false" data-category-autocomplete required>
            <div class="category-suggestion-list" id="category-suggestion-list" role="listbox" aria-label="Saran kategori" data-category-suggestion-list hidden>
                @foreach($categories->where('active', true) as $category)<button type="button" role="option" data-category-suggestion data-category-type="{{ $category->type }}" data-category-value="{{ $category->name }}">{{ $category->name }}</button>@endforeach
            </div>
        </div>
        @foreach(['IN', 'OUT'] as $categoryType)
            <datalist id="category-suggestions-{{ strtolower($categoryType) }}">
                @foreach($categories->where('type', $categoryType)->where('active', true) as $category)<option value="{{ $category->name }}"></option>@endforeach
            </datalist>
        @endforeach
        <small class="field-help">Pilih dari saran yang tersedia. Jika nama belum ada, kategori baru akan dibuat saat transaksi disimpan.</small>
    @endif
</div>
<div class="field"><label for="description">Keterangan <span class="optional">opsional</span></label><textarea class="input" id="description" name="description" rows="4" maxlength="1000">{{ old('description',$transaction?->description) }}</textarea></div>
@unless($transaction)<div class="field"><label for="attachment">Bukti transaksi (opsional, JPG/PNG/PDF maks. {{ $applicationSettings['attachment_max_mb'] }} MB)</label><input class="input" id="attachment" name="attachment" type="file" accept="image/jpeg,image/png,application/pdf"></div>@endunless
@if($transaction && $transaction->attachments->isNotEmpty())<div class="field"><strong>Bukti transaksi</strong>@foreach($transaction->attachments as $attachment)<a href="{{ route('attachments.download',$attachment) }}">Unduh {{ $attachment->original_name }}</a>@endforeach</div>@endif
<div class="actions"><button type="submit" class="btn {{ $type==='IN'?'btn-primary':'btn-accent' }}" data-transaction-submit>Simpan Transaksi</button><a class="btn btn-secondary" href="{{ route('transactions.index',$report) }}">Kembali</a></div></form>
@if($transaction)
    @can('cancel', $transaction)
        <form class="card" style="max-width:680px;margin-top:1rem;border-color:#b42318" method="post" action="{{ route('transactions.cancel',[$report,$transaction]) }}">
            @csrf
            <h2 class="section-title">Batalkan transaksi</h2>
            <p class="muted">Transaksi batal tetap tersimpan untuk audit dan tidak dihitung dalam saldo.</p>
            <div class="field"><label for="reason">Alasan pembatalan</label><textarea class="input" id="reason" name="reason" required minlength="5" maxlength="500"></textarea></div>
            <button class="btn btn-danger">Batalkan transaksi</button>
        </form>
    @endcan
@endif
@endsection
