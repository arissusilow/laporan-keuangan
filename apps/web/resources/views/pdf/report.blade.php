@php
    $pdfSettings = array_replace(['title' => '', 'footer' => 'Dibuat oleh sistem', 'margin_mm' => 12], $report->pdf_settings ?? []);
    $defaultPdfTitle = str_starts_with(mb_strtolower($report->name), 'laporan keuangan') ? $report->name : 'Laporan Keuangan '.$report->name;
    $pdfTitle = filled($pdfSettings['title']) ? $pdfSettings['title'] : $defaultPdfTitle;
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm {{ max(8, min(25, (int) $pdfSettings['margin_mm'])) }}mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #17211c; }
        .page-heading { border-bottom: 2px solid #12372a; margin-bottom: 12px; padding-bottom: 8px; }
        h1 { margin: 0; font-size: 18px; }
        .summary { width: 100%; margin: 12px 0; border-collapse: separate; border-spacing: 5px; }
        .summary td { border: 1px solid #ccd4cf; padding: 8px; }
        .label { color: #5d6a63; font-size: 8px; }
        .value { font-size: 12px; font-weight: bold; }
        .negative { color: #8a1c13; text-decoration: underline; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data thead { display: table-header-group; }
        .data tr { page-break-inside: avoid; }
        .data th, .data td { border: 1px solid #c9d0cc; padding: 5px; vertical-align: top; overflow-wrap: anywhere; }
        .data th { background: #e8eee9; }
        .data .continuation-header th { padding: 6px; background: #12372a; color: #fff; text-align: left; }
        .num { text-align: right; }
        .footer { position: fixed; right: 0; bottom: -6mm; left: 0; color: #5d6a63; text-align: center; }
        .page-number::after { content: counter(page); }
    </style>
</head>
<body>
<div class="footer">{{ $pdfSettings['footer'] }} · Halaman <span class="page-number"></span></div>
<div class="page-heading">
    <h1>{{ $pdfTitle }}</h1>
    <div>Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>
<table class="summary">
    <tr>
        <td><div class="label">Saldo sebelum periode</div><div class="value">Rp {{ number_format($summary['before'], 0, ',', '.') }}</div></td>
        <td><div class="label">Total pemasukan</div><div class="value">Rp {{ number_format($summary['incoming'], 0, ',', '.') }}</div></td>
        <td><div class="label">Total pengeluaran</div><div class="value">Rp {{ number_format($summary['outgoing'], 0, ',', '.') }}</div></td>
        <td><div class="label">Saldo akhir</div><div class="value {{ $summary['ending'] < 0 ? 'negative' : '' }}">Rp {{ number_format($summary['ending'], 0, ',', '.') }}{{ $summary['ending'] < 0 ? ' (NEGATIF)' : '' }}</div></td>
    </tr>
</table>
<table class="data">
    <thead>
        <tr class="continuation-header"><th colspan="6">{{ $pdfTitle }} · {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}-{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</th></tr>
        <tr><th style="width:5.5%">No</th><th style="width:12.5%">Tanggal</th><th style="width:29%">Keterangan</th><th style="width:17.5%">Pengeluaran</th><th style="width:17.5%">Pemasukan</th><th style="width:18%">Kategori</th></tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                <td>{{ $transaction->description ?: 'Tanpa keterangan' }}</td>
                <td class="num">{{ $transaction->type === 'OUT' ? 'Rp '.number_format($transaction->amount, 0, ',', '.') : '—' }}</td>
                <td class="num">{{ $transaction->type === 'IN' ? 'Rp '.number_format($transaction->amount, 0, ',', '.') : '—' }}</td>
                <td>{{ $transaction->category?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center">Tidak ada transaksi.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
