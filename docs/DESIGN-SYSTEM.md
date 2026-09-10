# Sistem Desain — Tahap 1

Status: disetujui untuk dilanjutkan ke Tahap 2 pada 8 September 2026

## 1. Fondasi visual

Karakter visual: tenang, terpercaya, ringan, dan mudah dibaca pada layar lama. Tampilan memakai permukaan krem terang, hijau gelap untuk identitas/aksi utama, dan emas sebagai aksen. Antarmuka tidak memakai efek berat, blur, video, atau font eksternal.

Aksen latar memakai lingkaran/halo organik hijau dan emas dengan transparansi sekitar 6–20%. Aksen ditempatkan di sudut atau sebagian keluar kanvas, tidak berada tepat di belakang teks utama, tidak menerima interaksi, dan tidak mengurangi kontras. Permukaan kartu, input, modal, tabel, serta halaman PDF tetap solid agar keterbacaan terjaga.

## 2. Warna

| Token desain | Nilai | Penggunaan |
|---|---:|---|
| Hijau utama | `#12372A` | Header, tombol Uang Masuk, fokus merek |
| Hijau hover | `#0B2B20` | Hover/pressed hijau |
| Emas aksen | `#D6A84B` | Tombol Uang Keluar, sorotan |
| Emas gelap | `#7A5714` | Teks/garis aksen pada latar terang |
| Latar | `#F6F4EE` | Latar halaman |
| Permukaan | `#FFFFFF` | Kartu, modal, input |
| Teks utama | `#17211C` | Judul dan isi |
| Teks sekunder | `#526158` | Metadata |
| Garis | `#D9DED9` | Border dan separator |
| Bahaya | `#B42318` | Pembatalan, error, saldo negatif |
| Bahaya lembut | `#FDECEA` | Latar alert bahaya |
| Berhasil | `#176B45` | Status berhasil |
| Info | `#245A7A` | Status proses/informasi |

Aturan:

- Teks normal menargetkan kontras minimal 4,5:1; teks besar minimal 3:1.
- Emas tidak dipakai sebagai teks kecil di atas putih. Tombol emas memakai teks `#17211C`.
- Status selalu memiliki label/ikon; hijau dan merah tidak pernah menjadi satu-satunya pembeda.
- Saldo negatif memakai tanda minus, ikon, dan teks “Saldo negatif”.

## 3. Tipografi

Font: system stack `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif`.

| Gaya | Mobile | Desktop | Berat / line-height |
|---|---:|---:|---|
| Judul halaman | 28 px | 36 px | 700 / 1,2 |
| Judul bagian | 22 px | 24 px | 700 / 1,25 |
| Judul kartu | 18 px | 18 px | 650 / 1,3 |
| Isi | 16 px | 16 px | 400 / 1,5 |
| Metadata | 14 px | 14 px | 400 / 1,45 |
| Nilai Saldo | 30 px | 40 px | 750 / 1,1 |
| Tabel/PDF layar | 14 px | 14 px | 400 / 1,4 |
| Slide judul | 44 px @720p | 64 px @1080p | 700 / 1,15 |
| Slide nilai | 64 px @720p | 88 px @1080p | 750 / 1,05 |

Angka keuangan memakai `font-variant-numeric: tabular-nums`.

## 4. Spacing dan layout

- Skala: 4, 8, 12, 16, 24, 32, 48, 64 piksel.
- Padding halaman mobile 16 piksel; tablet 24; desktop 32.
- Jarak antarkomponen standar 16 piksel; antarbagian 32 piksel.
- Konten aplikasi maksimum 1200 piksel.
- Sidebar desktop 240 piksel.
- Radius input/tombol 10 piksel; kartu 16 piksel; badge 999 piksel.
- Border standar 1 piksel; shadow hanya `0 2px 12px rgba(18,55,42,.08)`.

## 5. Tombol

Semua tombol memiliki tinggi minimum 44 piksel, padding horizontal minimum 16 piksel, label berupa kata kerja, dan fokus 3 piksel.

| Varian | Contoh | Aturan |
|---|---|---|
| Utama hijau | Uang Masuk, Simpan | Latar hijau, teks putih |
| Utama emas | Uang Keluar | Latar emas, teks gelap |
| Sekunder | Kembali, Filter | Permukaan putih, border hijau |
| Bahaya | Ya, batalkan | Merah, hanya untuk tahap konfirmasi akhir |
| Tautan | Lihat detail | Garis bawah pada hover/fokus |
| Loading | Menyimpan… | Disabled, indikator teks, lebar tidak berubah |

- Mobile: CTA form dan dua aksi transaksi dapat selebar kontainer.
- Tombol disabled memiliki kontras cukup dan alasan tersedia di dekatnya.
- Jangan memakai ikon tanpa label untuk aksi kritis.

## 6. Input dan formulir

- Label berada di atas input dan tidak hilang saat mengetik.
- Tinggi minimum input/select 44 piksel; textarea minimum 104 piksel.
- Help text berada sebelum error; error berada tepat setelah input.
- Fokus: outline `3px solid #D6A84B`, offset 2 piksel.
- Error: border merah + ikon + teks; bukan border merah saja.
- Prefix “Rp” tampil secara visual tetapi nilai tersimpan berupa integer.
- File input menjelaskan tipe dan batas 5 MB.
- Form mobile selalu satu kolom; desktop maksimal dua kolom untuk field yang berkaitan.
- Ringkasan error di atas form menaut ke field bermasalah.

## 7. Kartu

- Permukaan putih, border netral, radius 16 piksel, padding 16–24 piksel.
- Kartu Laporan memuat nama, status, periode, peran, lalu satu tindakan.
- Kartu transaksi mobile memuat keterangan dan nominal di baris pertama, lalu tanggal/jenis/kategori/status; aksi di baris terakhir.
- Kartu metrik selalu memiliki label teks di atas angka.
- Kartu dapat diklik hanya bila seluruh kartu memiliki satu tujuan yang jelas dan fokus keyboard terlihat.

## 8. Tabel

- Header sticky hanya pada viewport tinggi yang memadai; bukan syarat fungsi.
- Angka rata kanan dan memakai digit tabular.
- Tanggal tidak terpotong; keterangan dapat membungkus.
- Zebra sangat ringan dan hover tidak menjadi satu-satunya petunjuk.
- Header tabel memakai scope kolom.
- Pada mobile tabel transaksi berubah menjadi daftar kartu; tidak dipaksa mengecil atau digulir horizontal.
- Transaksi batal menampilkan badge “Batal” dan alasan tersedia pada detail.

## 9. Badge dan status

Badge memakai ikon sederhana/teks serta pasangan warna:

- Aktif — lingkaran + “Aktif”.
- Ditutup — gembok + “Ditutup”.
- Diarsipkan — arsip + “Diarsipkan”.
- Batal — silang + “Batal”.
- Menunggu — jam + “Menunggu”.
- Diproses — spinner opsional + “Diproses”.
- Berhasil — centang + “Berhasil”.
- Gagal — tanda seru + “Gagal”.

Badge minimum tinggi 28 piksel; bukan kontrol interaktif.

## 10. Modal dan konfirmasi

- Desktop: dialog maksimum 520 piksel.
- Mobile: halaman penuh atau bottom sheet dengan tombol tetap terlihat tanpa menutupi input.
- Fokus masuk ke judul, terperangkap bila JavaScript aktif, kembali ke pemicu saat ditutup.
- Tanpa JavaScript, tindakan tersedia sebagai halaman konfirmasi biasa.
- Konfirmasi pembatalan menyebut nomor, tanggal, jumlah, akibat terhadap saldo, dan meminta alasan.
- Restore memakai halaman tersendiri, autentikasi ulang, pilihan target, dan konfirmasi ketik nama target.

## 11. Alert dan state

Alert memiliki ikon, judul, isi, dan tindakan opsional.

- Info: proses PDF/backup.
- Berhasil: transaksi tersimpan, PDF siap.
- Peringatan: Laporan baca-saja, saldo negatif, koneksi slide tertunda.
- Bahaya: validasi gagal, restore gagal.

Skeleton hanya enhancement; fallback teks “Memuat…” wajib tersedia.

## 12. Ikon

- SVG lokal berukuran 20 atau 24 piksel.
- Stroke sederhana 1,75–2 piksel.
- Ikon dekoratif `aria-hidden=true`.
- Ikon mandiri wajib memiliki label aksesibel dan target 44 × 44 piksel.

## 13. Aturan PDF A4

- A4 portrait; margin 12–15 mm.
- DejaVu Sans lokal.
- Judul 16 pt, subjudul 11 pt, isi 9 pt, footer 8 pt.
- Ringkasan menggunakan border/label agar tetap jelas hitam-putih.
- Lebar kolom: No 7%, Tanggal 13%, Keterangan 34%, Pengeluaran 15%, Pemasukan 15%, Kategori 16%.
- Header tabel berulang, angka rata kanan, teks membungkus, nomor urut berlanjut.
- Header ringkas dan footer nomor halaman muncul pada halaman lanjutan.
- Saldo negatif menggunakan “NEGATIF” dan tanda minus, tidak hanya warna.

## 14. Aturan slide TV

- Kanvas 16:9 untuk 1280×720 dan 1920×1080.
- Safe area minimum 5% di setiap sisi.
- Maksimum tiga nilai utama pada satu slide.
- Jarak baca: nilai utama minimum 64 piksel pada 720p.
- Kontras tinggi; grafik memiliki label langsung atau legenda dekat data.
- Animasi hanya fade 200–300 ms; dinonaktifkan untuk reduced motion.
- Progress rotasi bersifat sekunder dan tidak mengganggu isi.
- Waktu pembaruan selalu terlihat.
- Koneksi terputus menampilkan “Pembaruan tertunda”.
- Slide nonaktif hanya menampilkan status nonaktif tanpa angka terakhir.

## 15. Konten dan bahasa

- Gunakan “Laporan”, bukan “sesi”, “buku”, atau `report_session`.
- Gunakan “Uang Masuk”, “Uang Keluar”, “Saldo”, “Batalkan transaksi”.
- Hindari “delete/hapus” untuk transaksi karena perilakunya pembatalan.
- Pesan error menjelaskan tindakan berikutnya tanpa membocorkan detail server.
- Format tanggal layar: `DD/MM/YYYY`; input tanggal mengikuti kontrol browser.
- Format nominal: `Rp 1.250.000`; nilai negatif `−Rp 250.000`.

## 16. Checklist komponen

- [x] Warna dan aturan kontras.
- [x] Tipografi dan spacing.
- [x] Tombol, input, kartu, tabel, badge.
- [x] Modal/halaman konfirmasi dan alert.
- [x] Aturan mobile 360 piksel.
- [x] Aturan PDF A4.
- [x] Aturan TV 16:9.
- [x] Token dan komponen disetujui untuk frontend mockup Tahap 2.
