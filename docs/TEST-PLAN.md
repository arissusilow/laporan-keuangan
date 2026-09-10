# Rencana Pengujian

| Kriteria | Bukti otomatis/manual |
|---|---|
| Login, wajib ganti password, throttle | Feature test dan inspeksi route |
| Isolasi Laporan/IDOR | `FinanceFlowTest::test_user_cannot_open_another_report` |
| Kategori OUT satu Laporan | feature test validasi lintas-Laporan + FK komposit |
| Saldo/periode/pembatalan | unit-integrasi `BalanceService` |
| Laporan tertutup baca-saja | feature test HTTP 409 |
| PDF queue/privat | queue feature test + smoke worker |
| Slide nonaktif tidak bocor | feature test 404 |
| Backup/checksum/restore | smoke `pg_restore --list`, runbook |
| 360/768/1280 dan 720p/1080p | browser screenshots dan inspeksi overflow |
| Tanpa mock/rekening/transfer | pencarian source |

Release gate: PHP lint, Pint, build Vite, seluruh PHPUnit, migration PostgreSQL kosong, health check, login smoke, PDF worker, backup checksum, dan restart persistence.
