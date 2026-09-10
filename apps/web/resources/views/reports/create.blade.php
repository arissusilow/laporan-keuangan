@extends('layouts.app')

@section('title', 'Buat Laporan')

@section('content')
    <div class="page-header">
        <div>
            <h1>Buat Laporan</h1>
            <p class="muted">Saldo berikutnya dihitung otomatis dari saldo awal dan transaksi.</p>
        </div>
    </div>

    <form class="card" style="max-width:720px" method="post" action="{{ route('reports.store') }}" data-submit-loading>
        @csrf
        @include('reports.partials.fields', ['report' => null])

        <button class="btn btn-primary" type="submit" data-loading-label="Membuat Laporan…">Buat Laporan</button>
    </form>
@endsection
