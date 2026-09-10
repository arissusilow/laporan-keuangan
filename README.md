# Laporan Keuangan

Status saat ini: **Prompt 4 disetujui dan deployment produksi aktif**. Aplikasi memakai Laravel 13, PostgreSQL 17, Blade, Tailwind CSS, Poppins lokal, database queue, Dompdf, Nginx, worker, dan scheduler. Seluruh layar utama memakai database serta policy nyata; Prompt 5 belum dimulai.

## Menjalankan dengan Docker

```bash
cp .env.example .env
# isi APP_KEY dan kredensial PostgreSQL dengan nilai lokal yang aman
docker compose up -d --build
docker compose exec -T app php artisan migrate --force
```

Image `laporan-keuangan-app:local` dibuat langsung dari source dan tidak diambil dari Docker Hub. Konfigurasi Compose melarang proses pull untuk image lokal tersebut, sehingga perintah di atas tidak memerlukan `docker login`. Image dasar publik seperti PostgreSQL, PHP, Composer, Node, dan Nginx tetap diunduh secara anonim dari Docker Hub.

Buka [http://localhost:8181](http://localhost:8181). Endpoint pemeriksaan tersedia di [http://localhost:8181/health](http://localhost:8181/health).

## Membuat Super Admin awal

Gunakan input interaktif agar kata sandi tidak masuk riwayat shell:

```bash
docker compose exec app php artisan app:create-super-admin
```

Alternatif deployment non-interaktif memakai `ADMIN_EMAIL`, `ADMIN_NAME`, dan `ADMIN_PASSWORD` dari secret environment lalu menjalankan command yang sama. Command ini idempoten untuk email Super Admin yang sama, tidak meningkatkan akun biasa menjadi Super Admin, tidak mencetak kata sandi, dan mewajibkan penggantian kata sandi saat login pertama.

Database awal tidak membuat Laporan, kategori, transaksi, atau data bisnis contoh.

## Pengembangan dan verifikasi

```bash
cd apps/web
composer install
npm ci
npm run build
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Dokumentasi backend ada di `docs/ARCHITECTURE.md` dan hasil verifikasi terakhir di `docs/TEST-REPORT.md`.

## Alur operasional

- Login mengarahkan pengguna satu akses langsung ke dashboard, atau ke pemilih Laporan bila aksesnya lebih dari satu.
- Operator mencatat Pemasukan/Pengeluaran melalui satu form, melihat agregasi Harian/Mingguan/Bulanan/Tahunan, detail transaksi, dan laporan periode/PDF sesuai izin.
- Admin Laporan mengelola anggota, kategori, slide publik, dan identitas PDF melalui tab Pengaturan Laporan.
- Super Admin mengelola pengguna dan konfigurasi global serta memantau database, antrean, storage, backup, dan audit.
- URL slide publik dan PDF QR tidak memerlukan login, tetapi hanya bekerja saat slide diaktifkan dan token valid.
