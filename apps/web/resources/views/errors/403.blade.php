<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tidak berwenang · Laporan Keuangan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="shell content">
    <section class="surface empty-state error-state">
        <span aria-hidden="true">!</span>
        <p class="eyebrow">Akses dibatasi</p>
        <h1>Tidak berwenang</h1>
        <p>Akun Anda tidak memiliki hak untuk membuka ruang keuangan ini.</p>
        <a class="button button-primary" href="{{ route('reports.index') }}">Kembali ke pilihan Laporan</a>
    </section>
</main>
</body>
</html>
