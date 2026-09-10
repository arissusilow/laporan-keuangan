# Runbook Restore

Restore hanya dilakukan Super Admin dan diuji di database terpisah terlebih dahulu.

1. Hentikan penulisan aplikasi dan verifikasi satu set backup:

   ```bash
   cd /backups
   sha256sum -c backup-YYYYMMDD-HHMMSS.sha256
   ```

2. Buat database target kosong dengan kredensial berbeda.
3. Pulihkan database dengan `pg_restore --clean --if-exists --no-owner --dbname=<target> database-YYYYMMDD-HHMMSS.dump`.
4. Bandingkan jumlah users, laporan, transaksi, serta total saldo; lalu jalankan smoke test login, dashboard, laporan periode, dan PDF.
5. Pulihkan berkas privat bila diperlukan:

   ```bash
   tar -xzf private-files-YYYYMMDD-HHMMSS.tar.gz -C apps/web/storage/app/private
   ```

6. Catat operator, waktu, manifest, checksum, dan hasil verifikasi. Untuk production, ambil backup pra-restore serta minta konfirmasi target dan autentikasi ulang.
