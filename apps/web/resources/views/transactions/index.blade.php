@extends('layouts.app')

@section('title', 'Transaksi')

@section('content')
<div class="page-header">
    <div><p class="muted">{{ $report->name }}</p><h1>Transaksi</h1></div>
    @if($report->isWritable() && (auth()->user()->can('create', [\App\Models\FinancialTransaction::class, $report, 'IN']) || auth()->user()->can('create', [\App\Models\FinancialTransaction::class, $report, 'OUT'])))
        <div class="actions"><a class="btn btn-primary" href="{{ route('transactions.create', $report) }}">＋ Buat Transaksi</a></div>
    @endif
</div>

<section class="card transaction-browser no-print" aria-label="Navigasi periode transaksi">
    <div class="period-navigator">
        @if($previousPeriodUrl)<a href="{{ $previousPeriodUrl }}" aria-label="Periode sebelumnya">‹</a>@else<span class="period-arrow-placeholder" aria-hidden="true"></span>@endif
        @if($periodMode === 'year')
            <div><span>Periode</span><strong>{{ $periodLabel }}</strong></div>
        @else
            <details class="period-picker">
                <summary aria-label="Pilih bulan dan tahun"><span>Periode</span><strong>{{ $periodLabel }}</strong><small aria-hidden="true">⌄</small></summary>
                <div class="period-picker-popover">
                    <form method="get" action="{{ route('transactions.index', $report) }}">
                        <input type="hidden" name="view" value="{{ $periodMode }}">
                        @foreach(['type', 'category', 'q', 'sort'] as $filterName)
                            @if(filled($filters[$filterName] ?? null))<input type="hidden" name="{{ $filterName }}" value="{{ $filters[$filterName] }}">@endif
                        @endforeach
                        <label for="period-shortcut">Pilih bulan dan tahun</label>
                        <input class="input" id="period-shortcut" type="month" name="period" value="{{ $periodAnchor->format('Y-m') }}" min="1900-01" max="2100-12" required>
                        <button class="btn btn-primary" type="submit">Tampilkan</button>
                    </form>
                </div>
            </details>
        @endif
        @if($nextPeriodUrl)<a href="{{ $nextPeriodUrl }}" aria-label="Periode berikutnya">›</a>@else<span class="period-arrow-placeholder" aria-hidden="true"></span>@endif
    </div>
    <nav class="period-tabs" aria-label="Rentang transaksi">
        @foreach($periodTabs as $mode => $tab)
            <a href="{{ $tab['url'] }}" class="{{ $periodMode === $mode ? 'active' : '' }}" @if($periodMode === $mode) aria-current="page" @endif>{{ $tab['label'] }}</a>
        @endforeach
    </nav>
    <div class="period-summary" aria-label="Ringkasan periode">
        <div><span>Pemasukan</span><strong class="money-in">Rp {{ number_format($totals['incoming'], 0, ',', '.') }}</strong></div>
        <div><span>Pengeluaran</span><strong class="money-out">Rp {{ number_format($totals['outgoing'], 0, ',', '.') }}</strong></div>
        <div><span>Saldo</span><strong class="{{ $periodBalance < 0 ? 'money-out' : '' }}">Rp {{ number_format($periodBalance, 0, ',', '.') }}</strong></div>
    </div>
</section>

<form class="card transaction-filters no-print" method="get">
    <input type="hidden" name="view" value="{{ $periodMode }}">
    <input type="hidden" name="period" value="{{ $periodAnchor->toDateString() }}">
    <div class="field"><label for="type">Arus transaksi</label><select class="input" id="type" name="type"><option value="">Pemasukan dan Pengeluaran</option><option value="IN" @selected(($filters['type'] ?? '') === 'IN')>Pemasukan</option><option value="OUT" @selected(($filters['type'] ?? '') === 'OUT')>Pengeluaran</option></select></div>
    <div class="field"><label for="category">Kategori</label><select class="input" id="category" name="category"><option value="">Semua</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
    <div class="field"><label for="q">Cari</label><input class="input" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Keterangan"></div>
    <div class="filter-submit"><button class="btn btn-secondary">Terapkan filter</button>@if(array_filter($filters, fn ($value, $key) => in_array($key, ['type', 'category', 'q', 'sort'], true) && filled($value), ARRAY_FILTER_USE_BOTH))<a class="text-action" href="{{ route('transactions.index', $report) }}?view={{ $periodMode }}&period={{ $periodAnchor->toDateString() }}">Reset</a>@endif</div>
</form>

<section class="card">
    <div class="section-heading transaction-list-heading"><div><h2>{{ ['day' => 'Transaksi harian', 'week' => 'Ringkasan mingguan', 'month' => 'Ringkasan bulanan', 'year' => 'Ringkasan tahunan'][$periodMode] }}</h2><p class="muted">{{ $periodLabel }} · {{ number_format($breakdownTransactionCount, 0, ',', '.') }} transaksi aktif</p></div></div>
    @if($breakdown->isEmpty())
        <div class="empty"><h2>Belum ada transaksi</h2><p class="muted">Ubah filter atau catat transaksi baru.</p></div>
    @else
        <div class="table-wrap period-breakdown-table desktop-table">
            <table>
                <thead><tr><th>Periode</th><th class="number">Pemasukan</th><th class="number">Pengeluaran</th><th>Transaksi</th></tr></thead>
                <tbody>
                    @foreach($breakdown as $period)
                        <tr class="period-total-row">
                            <td><strong>{{ $period['label'] }}</strong><span>{{ $period['secondary'] }}</span></td>
                            <td class="number money-in">Rp {{ number_format($period['incoming'], 0, ',', '.') }}</td>
                            <td class="number money-out">Rp {{ number_format($period['outgoing'], 0, ',', '.') }}</td>
                            <td>{{ $period['count'] }} transaksi</td>
                        </tr>
                        @if($periodMode === 'day')
                            @foreach($period['transactions'] as $tx)
                                <tr class="period-detail-row {{ $tx->status === 'CANCELLED' ? 'status-cancelled' : '' }}">
                                    <td><span>{{ $tx->category?->name ?? 'Tanpa kategori' }}</span>@if(filled($tx->description))<small>{{ $tx->description }}</small>@endif @if($tx->status === 'CANCELLED')<small>Batal</small>@endif</td>
                                    <td class="number money-in">{{ $tx->type === 'IN' ? 'Rp '.number_format($tx->amount, 0, ',', '.') : '—' }}</td>
                                    <td class="number money-out">{{ $tx->type === 'OUT' ? 'Rp '.number_format($tx->amount, 0, ',', '.') : '—' }}</td>
                                    <td>
                                        <a href="{{ route('transactions.show', [$report, $tx]) }}">Detail</a>
                                        @can('update', $tx) · <a href="{{ route('transactions.edit', [$report, $tx]) }}">Ubah</a>@endcan
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mobile-list period-breakdown-list">
            @foreach($breakdown as $period)
                <article class="period-breakdown-item">
                    <div class="period-breakdown-total">
                        <div><strong>{{ $period['label'] }}</strong><span>{{ $period['secondary'] }}</span></div>
                        <strong class="money-in">Rp {{ number_format($period['incoming'], 0, ',', '.') }}</strong>
                        <strong class="money-out">Rp {{ number_format($period['outgoing'], 0, ',', '.') }}</strong>
                    </div>
                    @if($periodMode === 'day')
                        <div class="period-transactions">
                            @foreach($period['transactions'] as $tx)
                                <div class="period-transaction {{ $tx->status === 'CANCELLED' ? 'status-cancelled' : '' }}">
                                    <div><strong>{{ $tx->category?->name ?? 'Tanpa kategori' }}</strong>@if(filled($tx->description))<span>{{ $tx->description }}</span>@endif</div>
                                    <strong class="{{ $tx->type === 'IN' ? 'money-in' : 'money-out' }}">{{ $tx->type === 'IN' ? '+' : '−' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}</strong>
                                    <a href="{{ route('transactions.show', [$report, $tx]) }}" aria-label="Lihat detail transaksi kategori {{ $tx->category?->name ?? 'tanpa kategori' }}">Detail</a>
                                    @can('update', $tx)<a href="{{ route('transactions.edit', [$report, $tx]) }}" aria-label="Ubah transaksi kategori {{ $tx->category?->name ?? 'tanpa kategori' }}">Ubah</a>@endcan
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        @if($breakdown instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $breakdown->onEachSide(2)->links('vendor.pagination.finance') }}
        @endif
    @endif
</section>
@endsection
