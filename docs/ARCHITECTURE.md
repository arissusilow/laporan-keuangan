# Arsitektur Terintegrasi — Prompt 4

Aplikasi adalah monolith modular Laravel 13/PHP 8.4 dengan Blade server-rendered, Tailwind CSS build-time, Poppins lokal, PostgreSQL 17, database queue, Dompdf, dan Nginx. Docker Compose memisahkan layanan `web`, `app`, `worker`, `scheduler`, dan `db`. Jaringan PostgreSQL bersifat internal dan tidak memublikasikan port ke host.

## Batas data dan model

`report_sessions` adalah batas isolasi data keuangan. `report_session_members` menghubungkan pengguna ke Laporan dengan role `ADMIN`, `OFFICER`, atau `VIEWER` serta izin rinci Petugas. Super Admin adalah flag global pada `users`.

Entitas backend:

| Tabel | Fungsi utama | Ikatan Laporan |
|---|---|---|
| `users` | Identitas login, status aktif, Super Admin, wajib ganti sandi | Melalui membership |
| `report_sessions` | Nama, slug, periode, saldo awal, mata uang, status | Batas utama |
| `report_session_members` | Role dan izin pengguna per Laporan | FK wajib |
| `expense_categories` | Kategori transaksi bertipe IN/OUT per Laporan; nama tabel lama dipertahankan untuk kompatibilitas migration | FK wajib |
| `transactions` | IN/OUT, nominal integer, status aktif/batal | FK wajib |
| `attachments` | Metadata berkas bukti privat | FK Laporan dan transaksi komposit |
| `pdf_exports` | Permintaan dan hasil PDF privat | FK wajib |
| `slide_configs` | Token publik ter-hash dan pengaturan slide | FK unik |
| `backup_jobs` | Status, lokasi, checksum, dan metadata backup | Global/opsional per Laporan |
| `audit_logs` | Jejak aksi dan nilai sebelum/sesudah | Global/opsional per Laporan |
| `application_settings` | Identitas, regional, keamanan, dan unggahan global | Global |

Foreign key, index, unique constraint, dan PostgreSQL check constraint menjaga status, role, tipe transaksi, nominal positif, serta konsistensi kategori. FK komposit mencegah lampiran menunjuk transaksi dari Laporan lain. Query controller dan policy tetap memeriksa akses server-side untuk mencegah IDOR.

## Aturan domain

`BalanceService` adalah satu-satunya sumber perhitungan saldo: `saldo awal + total IN aktif - total OUT aktif`. Saldo berjalan tidak disimpan sebagai angka bebas. Perhitungan periode menghasilkan saldo sebelum periode, total masuk, total keluar, dan saldo akhir. Transaksi yang dibatalkan tetap tersimpan untuk audit tetapi dikeluarkan dari perhitungan.

Transaksi hanya menerima tipe `IN` atau `OUT` dan nominal integer lebih dari nol. OUT wajib memakai kategori OUT dan IN wajib memakai kategori IN. Foreign key komposit PostgreSQL mengikat Laporan, kategori, dan tipe transaksi sekaligus. Operator dapat memakai autocomplete untuk memilih kategori aktif atau membuat kategori baru sesuai tipe transaksi; server tetap menjadi sumber validasi. Laporan berstatus Ditutup atau Diarsipkan tidak dapat menerima perubahan transaksi. Perubahan, pembatalan, konfigurasi, login, dan aksi penting mencatat audit sebelum/sesudah di dalam database transaction bila relevan.

## Autentikasi dan otorisasi

Login memakai autentikasi session Laravel, regenerasi session ID, logout dengan invalidasi session, CSRF, pembatasan lima kegagalan per menit per email/IP, pesan login generik, serta penolakan akun nonaktif. Reset kata sandi memakai token broker Laravel; URL reset tidak mengungkap keberadaan email. Password awal dan hasil reset minimal 6 karakter dengan huruf dan angka.

Middleware memastikan akun tetap aktif dan memaksa penggantian kata sandi awal. Policy tersedia untuk Laporan, transaksi, kategori, lampiran, PDF, slide, backup, pengguna, audit, dan konfigurasi aplikasi. Admin Laporan dibatasi ke Laporan keanggotaannya; Petugas mengikuti izin rinci; Viewer hanya membaca; Super Admin mempunyai kewenangan global. Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan.

## File, antrean, dan operasi

Lampiran, gambar latar slide, hasil PDF, dan backup berada pada storage/volume privat. Unduhan selalu melewati route berotorisasi. Gambar slide divalidasi sebagai PNG/JPG/WebP, ditampilkan dengan Fill/Crop (`cover`) berpusat, dan opacity 5–30%. Token slide publik hanya disimpan dalam bentuk SHA-256.

Generasi PDF dan backup memakai database queue. Worker dan scheduler berjalan sebagai container terpisah. Jadwal serta retensi backup dibaca dari `application_settings` dan hanya dapat diubah Super Admin. Backup PostgreSQL memakai format custom, checksum SHA-256, dan retensi terkonfigurasi. Halaman Sistem membaca kondisi database, antrean, storage, backup, dan audit yang sebenarnya.

PDF mempunyai konfigurasi per Laporan pada JSON `report_sessions.pdf_settings`. Template A4 merender ringkasan dan transaksi aktif langsung dari database, mengulang identitas/kepala tabel, membungkus teks, dan memberi nomor halaman. Ekspor biasa berada di storage privat dan diunduh melalui policy; endpoint PDF untuk QR hanya tersedia melalui token slide aktif dan rate limit.

Daftar transaksi memakai `TransactionBrowserService` untuk agregasi SQL Harian, Mingguan, Bulanan, dan Tahunan. Pada mode Harian, pagination terjadi pada kelompok tanggal lalu rincian hanya diambil untuk tanggal halaman aktif. Pendekatan ini menghindari pemuatan seluruh riwayat transaksi ke memori.

## Bootstrap dan data awal

`php artisan app:create-super-admin` adalah satu-satunya mekanisme bootstrap data. Identitas dan kata sandi berasal dari environment/secret prompt, command idempoten untuk email yang sama, tidak mencetak kata sandi, dan menandai akun untuk mengganti kata sandi saat login pertama. `DatabaseSeeder` tidak mengisi data. Tidak ada fallback atau fixture bisnis di runtime.

## Status integrasi

Prompt 4 menghubungkan seluruh layar operator, admin Laporan, dan Super Admin ke data serta policy production. Slide publik, QR, PDF, antrean, status sistem, backup schedule, audit, dan konfigurasi aplikasi sudah terintegrasi. Prompt 5 belum dimulai dan tetap menunggu persetujuan eksplisit pemilik produk.
