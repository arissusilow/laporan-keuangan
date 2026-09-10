# Deployment Docker Compose

1. Salin `.env.example` ke `.env`, buat `APP_KEY` dengan `php artisan key:generate --show`, dan isi secret unik.
2. Pasang reverse proxy/TLS di depan port web, lalu set `APP_URL=https://...`, `APP_ENV=production`, `APP_DEBUG=false`, dan `SESSION_SECURE_COOKIE=true`.
3. Jalankan `docker compose -f compose.yaml -f compose.production.yaml build` lalu `up -d`.
4. Buat Admin awal: `docker compose exec app php artisan app:create-super-admin` (input interaktif, password tidak dicetak).
5. Periksa `docker compose ps`, `/health`, worker, scheduler, backup, PDF, dan login.

Untuk deployment pada subpath, gunakan nilai yang sama pada URL aplikasi dan aset. Contoh:

```dotenv
APP_URL=https://secure.internal.example/laporan-keuangan
ASSET_URL=https://secure.internal.example/laporan-keuangan
PUBLIC_APP_URL=https://secure.internal.example/laporan-keuangan
SESSION_PATH=/laporan-keuangan
```

Reverse proxy harus menghapus prefix sebelum meneruskan request ke Nginx aplikasi, mengirim `X-Forwarded-Proto: https`, dan hanya mengekspos port aplikasi pada loopback host.

Update: buat backup, tarik commit yang disetujui, build image, jalankan migration, lalu recreate service. Rollback aplikasi memakai image/commit sebelumnya; jangan rollback migration destruktif tanpa restore teruji.
