@extends('layouts.app')

@section('title', 'Pengaturan '.$report->name)

@section('content')
@php
    $tab = in_array(request('tab'), ['general', 'categories', 'members', 'pdf', 'slide', 'audit'], true) ? request('tab') : 'general';
    $settingsUrl = fn (string $target) => route('reports.settings', $report).'?tab='.$target;
    $permissions = [
        'can_add_in' => 'Tambah Uang Masuk',
        'can_add_out' => 'Tambah Uang Keluar',
        'can_edit_own' => 'Ubah transaksi sendiri',
        'can_edit_all' => 'Ubah semua transaksi',
        'can_cancel' => 'Batalkan transaksi',
        'can_export_pdf' => 'Buat PDF',
    ];
@endphp

<div class="page-header">
    <div>
        <p class="eyebrow">{{ $report->name }}</p>
        <h1>Pengaturan Laporan</h1>
        <p class="muted">Kelola identitas, kategori, anggota, dan tampilan slide dari satu tempat.</p>
    </div>
</div>

<nav class="settings-tabs" aria-label="Bagian pengaturan Laporan">
    @foreach(['general' => 'Umum', 'categories' => 'Kategori', 'members' => 'Anggota & akses', 'pdf' => 'PDF', 'slide' => 'Slide TV', 'audit' => 'Audit'] as $key => $label)
        <a class="{{ $tab === $key ? 'active' : '' }}" href="{{ $settingsUrl($key) }}" @if($tab === $key) aria-current="page" @endif>
            {{ $label }}
            @if($key === 'categories')<span>{{ $categories->count() }}</span>@endif
            @if($key === 'members')<span>{{ $members->count() }}</span>@endif
        </a>
    @endforeach
</nav>

@if($tab === 'general')
    <form class="card settings-panel" method="post" action="{{ $settingsUrl('general') }}">
        @csrf @method('PUT')
        <div class="section-heading"><div><h2>Identitas dan saldo awal</h2><p class="muted">Perubahan saldo awal wajib disertai alasan dan masuk audit.</p></div></div>
        @include('reports.partials.fields', ['report' => $report])
        <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="ACTIVE" @selected($report->status === 'ACTIVE')>Aktif</option><option value="CLOSED" @selected($report->status === 'CLOSED')>Ditutup</option><option value="ARCHIVED" @selected($report->status === 'ARCHIVED')>Diarsipkan</option></select></div>
        <div class="field"><label for="opening_balance_reason">Alasan perubahan saldo awal <span class="optional">wajib bila saldo berubah</span></label><textarea class="input" id="opening_balance_reason" name="opening_balance_reason">{{ old('opening_balance_reason') }}</textarea></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan Laporan</button></div>
    </form>
@elseif($tab === 'pdf')
    @php($pdfSettings = array_replace(['title' => '', 'footer' => 'Dibuat oleh sistem', 'margin_mm' => 12], $report->pdf_settings ?? []))
    <form class="card settings-panel" method="post" action="{{ route('reports.pdf-settings.update', $report) }}">
        @csrf @method('PUT')
        <div class="section-heading"><div><h2>Pengaturan PDF</h2><p class="muted">Struktur enam kolom, header berulang, dan nomor halaman tetap dijaga oleh sistem.</p></div></div>
        <div class="field"><label for="pdf_title">Judul laporan <span class="optional">kosongkan untuk nama Laporan</span></label><input class="input" id="pdf_title" name="title" maxlength="160" value="{{ old('title', $pdfSettings['title']) }}" placeholder="Laporan Keuangan {{ $report->name }}"></div>
        <div class="field"><label for="pdf_footer">Teks footer</label><input class="input" id="pdf_footer" name="footer" maxlength="200" value="{{ old('footer', $pdfSettings['footer']) }}"></div>
        <div class="field"><label for="pdf_margin">Margin halaman (mm)</label><input class="input" id="pdf_margin" name="margin_mm" type="number" min="8" max="25" value="{{ old('margin_mm', $pdfSettings['margin_mm']) }}" required></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan pengaturan PDF</button><a class="btn btn-secondary" href="{{ route('period.show', $report) }}">Buka Laporan Periode</a></div>
    </form>
@elseif($tab === 'categories')
    <section class="card settings-panel">
        <div class="section-heading"><div><h2>Kategori transaksi</h2><p class="muted">Kategori pemasukan dan pengeluaran hanya berlaku pada Laporan ini.</p></div><a class="btn btn-primary" href="#add-category">+ Tambah kategori</a></div>
        @if($categories->isEmpty())
            <div class="empty"><h3>Belum ada kategori</h3><p class="muted">Tambahkan kategori pertama untuk mulai mengelompokkan transaksi.</p></div>
        @else
            <div class="management-list">
                @foreach($categories as $category)
                    <article>
                        <span class="color-dot" style="background: {{ $category->color }}" aria-hidden="true"></span>
                        <div><strong>{{ $category->name }}</strong><span>{{ $category->type === 'IN' ? 'Pemasukan' : 'Pengeluaran' }} · urutan {{ $category->sort_order }}</span></div>
                        <div class="management-status">@if($category->is_default)<span class="badge">Bawaan</span>@endif<span class="badge {{ $category->active ? '' : 'badge-danger' }}">{{ $category->active ? 'Aktif' : 'Nonaktif' }}</span></div>
                        <div class="management-actions">
                            <a class="btn btn-secondary" href="#edit-category-{{ $category->id }}">Edit</a>
                            <form method="post" action="{{ route('categories.destroy', [$report, $category]).'?tab=categories' }}" onsubmit="return confirm('Hapus kategori {{ addslashes($category->name) }}? Kategori yang sudah dipakai transaksi tidak dapat dihapus.')">@csrf @method('DELETE')<button class="btn btn-danger-outline" type="submit">Hapus</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="modal-shell" id="add-category" role="dialog" aria-modal="true" aria-labelledby="add-category-title">
        <a class="modal-backdrop" href="{{ $settingsUrl('categories') }}" aria-label="Tutup popup"></a>
        <section class="modal-card">
            <div class="modal-header"><div><p class="eyebrow">Kategori baru</p><h2 id="add-category-title">Tambah kategori</h2></div><a class="modal-close" href="{{ $settingsUrl('categories') }}" aria-label="Tutup">×</a></div>
            <form method="post" action="{{ route('categories.store', $report).'?tab=categories' }}">@csrf
                <div class="field"><label for="category_type">Jenis</label><select class="input" id="category_type" name="type" required><option value="IN">Pemasukan</option><option value="OUT" selected>Pengeluaran</option></select></div>
                <div class="field"><label for="category_name">Nama</label><input class="input" id="category_name" name="name" value="{{ old('name') }}" required></div>
                <div class="form-columns"><div class="field"><label for="category_color">Warna</label><input class="input" id="category_color" type="color" name="color" value="#64748B" required></div><div class="field"><label for="category_order">Urutan</label><input class="input" id="category_order" type="number" name="sort_order" value="0" min="0" required></div></div>
                <label class="check-single"><input type="checkbox" name="is_default" value="1"> Jadikan kategori bawaan</label>
                <div class="modal-actions"><a class="btn btn-secondary" href="{{ $settingsUrl('categories') }}">Batal</a><button class="btn btn-primary" type="submit">Tambah kategori</button></div>
            </form>
        </section>
    </div>

    @foreach($categories as $category)
        <div class="modal-shell" id="edit-category-{{ $category->id }}" role="dialog" aria-modal="true" aria-labelledby="edit-category-title-{{ $category->id }}">
            <a class="modal-backdrop" href="{{ $settingsUrl('categories') }}" aria-label="Tutup popup"></a>
            <section class="modal-card">
                <div class="modal-header"><div><p class="eyebrow">Kategori</p><h2 id="edit-category-title-{{ $category->id }}">Edit {{ $category->name }}</h2></div><a class="modal-close" href="{{ $settingsUrl('categories') }}" aria-label="Tutup">×</a></div>
                <form method="post" action="{{ route('categories.update', [$report, $category]).'?tab=categories' }}">@csrf @method('PUT')
                    <div class="field"><label for="category-type-{{ $category->id }}">Jenis</label><select class="input" id="category-type-{{ $category->id }}" name="type" required><option value="IN" @selected($category->type === 'IN')>Pemasukan</option><option value="OUT" @selected($category->type === 'OUT')>Pengeluaran</option></select></div>
                    <div class="field"><label for="category-name-{{ $category->id }}">Nama</label><input class="input" id="category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" required></div>
                    <div class="form-columns"><div class="field"><label for="category-color-{{ $category->id }}">Warna</label><input class="input" id="category-color-{{ $category->id }}" type="color" name="color" value="{{ $category->color }}" required></div><div class="field"><label for="category-order-{{ $category->id }}">Urutan</label><input class="input" id="category-order-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" required></div></div>
                    <div class="check-grid"><label><input type="checkbox" name="active" value="1" @checked($category->active)> Aktif</label><label><input type="checkbox" name="is_default" value="1" @checked($category->is_default)> Kategori bawaan</label></div>
                    <div class="modal-actions"><a class="btn btn-secondary" href="{{ $settingsUrl('categories') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
                </form>
            </section>
        </div>
    @endforeach
@elseif($tab === 'members')
    <section class="card settings-panel">
        <div class="section-heading"><div><h2>Anggota dan hak akses</h2><p class="muted">Akses bersifat khusus untuk Laporan ini dan tetap diperiksa oleh server.</p></div><a class="btn btn-primary" href="#add-member">+ Tambah anggota</a></div>
        <div class="management-list member-management-list">
            @forelse($members as $member)
                <article>
                    <span class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->user->name, 0, 1)) }}</span>
                    <div><strong>{{ $member->user->name }}</strong><span>{{ $member->user->email }}</span></div>
                    <div class="management-status"><span class="badge">{{ ['ADMIN' => 'Admin Laporan', 'OFFICER' => 'Petugas', 'VIEWER' => 'Viewer'][$member->role] }}</span></div>
                    <div class="management-actions"><a class="btn btn-secondary" href="#edit-member-{{ $member->id }}">Edit</a><form method="post" action="{{ route('members.destroy', [$report, $member]).'?tab=members' }}" onsubmit="return confirm('Cabut akses {{ addslashes($member->user->name) }} dari Laporan ini?')">@csrf @method('DELETE')<button class="btn btn-danger-outline" type="submit">Hapus</button></form></div>
                </article>
            @empty
                <div class="empty"><h3>Belum ada anggota</h3><p class="muted">Tambahkan pengguna yang sudah terdaftar.</p></div>
            @endforelse
        </div>
    </section>

    <div class="modal-shell" id="add-member" role="dialog" aria-modal="true" aria-labelledby="add-member-title">
        <a class="modal-backdrop" href="{{ $settingsUrl('members') }}" aria-label="Tutup popup"></a>
        <section class="modal-card modal-card-wide">
            <div class="modal-header"><div><p class="eyebrow">Akses Laporan</p><h2 id="add-member-title">Tambah anggota</h2></div><a class="modal-close" href="{{ $settingsUrl('members') }}" aria-label="Tutup">×</a></div>
            <form method="post" action="{{ route('members.store', $report).'?tab=members' }}">@csrf
                <div class="field"><label for="member_email">Email pengguna terdaftar</label><input class="input" id="member_email" type="email" name="email" list="registered-users" placeholder="Ketik nama atau email…" autocomplete="off" required><datalist id="registered-users">@foreach($availableUsers as $user)<option value="{{ $user->email }}">{{ $user->name }}</option>@endforeach</datalist><p class="help-text">Saran otomatis hanya menampilkan pengguna aktif yang belum menjadi anggota.</p></div>
                @include('reports.partials.member-fields', ['member' => null, 'permissions' => $permissions, 'fieldSuffix' => 'new'])
                <div class="modal-actions"><a class="btn btn-secondary" href="{{ $settingsUrl('members') }}">Batal</a><button class="btn btn-primary" type="submit">Tambah anggota</button></div>
            </form>
        </section>
    </div>

    @foreach($members as $member)
        <div class="modal-shell" id="edit-member-{{ $member->id }}" role="dialog" aria-modal="true" aria-labelledby="edit-member-title-{{ $member->id }}">
            <a class="modal-backdrop" href="{{ $settingsUrl('members') }}" aria-label="Tutup popup"></a>
            <section class="modal-card modal-card-wide">
                <div class="modal-header"><div><p class="eyebrow">{{ $member->user->email }}</p><h2 id="edit-member-title-{{ $member->id }}">Edit akses {{ $member->user->name }}</h2></div><a class="modal-close" href="{{ $settingsUrl('members') }}" aria-label="Tutup">×</a></div>
                <form method="post" action="{{ route('members.update', [$report, $member]).'?tab=members' }}">@csrf @method('PUT')<input type="hidden" name="email" value="{{ $member->user->email }}">
                    @include('reports.partials.member-fields', ['member' => $member, 'permissions' => $permissions, 'fieldSuffix' => $member->id])
                    <div class="modal-actions"><a class="btn btn-secondary" href="{{ $settingsUrl('members') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan akses</button></div>
                </form>
            </section>
        </div>
    @endforeach
@elseif($tab === 'slide')
    <form class="card settings-panel" method="post" action="{{ route('slides.update', $report).'?tab=slide' }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="section-heading"><div><h2>Slide TV</h2><p class="muted">Gambar identitas otomatis memakai Fill/Crop berpusat untuk layar 16:9.</p></div><div class="actions"><a class="btn btn-secondary" href="{{ route('slides.preview', $report) }}" target="_blank" rel="noopener">Pratinjau Admin ↗</a>@if($slidePublicUrl)<a class="btn btn-primary" href="{{ $slidePublicUrl }}" target="_blank" rel="noopener">Buka TV tanpa login ↗</a>@endif</div></div>
        <div class="check-grid"><label><input type="checkbox" name="enabled" value="1" @checked($report->slideConfig?->enabled)> Aktifkan slide</label><label><input type="checkbox" name="show_latest_transactions" value="1" @checked($report->slideConfig?->show_latest_transactions)> Tampilkan transaksi terbaru</label></div>
        <div class="form-columns"><div class="field"><label for="slide_start">Mulai</label><input class="input" id="slide_start" type="date" name="starts_on" value="{{ $report->slideConfig?->starts_on?->format('Y-m-d') }}"></div><div class="field"><label for="slide_end">Selesai</label><input class="input" id="slide_end" type="date" name="ends_on" value="{{ $report->slideConfig?->ends_on?->format('Y-m-d') }}"></div><div class="field"><label for="duration">Durasi per slide (detik)</label><input class="input" id="duration" type="number" name="duration_seconds" min="5" max="120" value="{{ $report->slideConfig?->duration_seconds ?? 12 }}" required></div><div class="field"><label for="refresh">Pembaruan data (detik)</label><input class="input" id="refresh" type="number" name="refresh_seconds" min="15" max="3600" value="{{ $report->slideConfig?->refresh_seconds ?? 60 }}" required></div></div>
        <div class="field"><label for="background_image">Gambar latar <span class="optional">PNG/JPG/WebP, maks. {{ $applicationSettings['identity_max_mb'] }} MB</span></label><input class="input" id="background_image" type="file" name="background_image" accept="image/png,image/jpeg,image/webp"></div>
        <div class="field"><label for="background_opacity">Transparansi gambar (5–30%)</label><input class="input" id="background_opacity" type="number" name="background_opacity" min="5" max="30" value="{{ $report->slideConfig?->settings['background_opacity'] ?? 14 }}"></div>
        <div class="check-grid">@if(isset($report->slideConfig?->settings['background_path']))<label><input type="checkbox" name="remove_background" value="1"> Hapus gambar latar saat ini</label>@endif<label><input type="checkbox" name="rotate_token" value="1"> Buat/ganti token URL publik</label></div>
        @if(session('slide_token'))<div class="notice notice-warning"><strong>Salin sekarang; token hanya ditampilkan sekali.</strong><a href="{{ route('slides.show', session('slide_token')) }}" target="_blank" rel="noopener">Buka URL slide TV</a></div>@endif
        @if($slidePublicUrl)<div class="notice notice-success"><strong>URL TV aktif.</strong> Tautan ini dapat dibuka tanpa login dan tetap tersedia setelah halaman ditutup.</div>@else<div class="notice notice-warning"><strong>Tayangan TV belum aktif.</strong> Aktifkan slide lalu simpan untuk membuat URL TV tanpa login.</div>@endif
        <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan pengaturan slide</button></div>
    </form>
@else
    <section class="card settings-panel">
        <div class="section-heading"><div><h2>Audit terbaru</h2><p class="muted">Riwayat ini hanya dapat dibaca dan tidak dapat diubah dari UI.</p></div></div>
        <div class="audit-list">@forelse($audits as $audit)<article><div><strong>{{ $audit->action }}</strong><span>Target {{ class_basename($audit->target_type ?? 'Sistem') }} #{{ $audit->target_id ?? '—' }}</span></div><time datetime="{{ $audit->created_at->toIso8601String() }}">{{ $audit->created_at->format('d/m/Y H:i') }}</time></article>@empty<div class="empty"><p>Belum ada audit.</p></div>@endforelse</div>
    </section>
@endif
@endsection
