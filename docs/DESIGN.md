# Desain Produk — Tahap 1

Status: disetujui untuk dilanjutkan ke Tahap 2 pada 8 September 2026
Sumber kebutuhan: PRD v0.2, 7 September 2026

## 1. Prinsip desain

1. Fokus pada tiga konsep: **Uang Masuk**, **Uang Keluar**, dan **Saldo**.
2. Dua aksi pencatatan selalu mudah ditemukan, tetapi hanya tampil bila pengguna memiliki izin.
3. Antarmuka inti tetap dapat dipakai tanpa JavaScript dan nyaman mulai lebar 360 piksel.
4. Setiap halaman selalu menunjukkan Laporan aktif untuk mencegah salah ruang pencatatan.
5. Warna selalu disertai teks, ikon, pola, atau tanda agar bukan satu-satunya pembeda.
6. Perubahan berisiko memakai konfirmasi dan jejak audit; transaksi tidak dihapus permanen.
7. Viewer hanya melihat. Petugas hanya melihat tindakan yang memang diizinkan pada Laporan aktif.
8. Tidak ada rekening, akun kas, transfer, rekonsiliasi, jurnal, neraca, atau fitur investasi.

## 2. Arsitektur informasi dan sitemap

```text
Login
├── Satu Laporan aktif → Dashboard
└── Beberapa Laporan → Pilih Laporan → Dashboard

Laporan aktif
├── Dashboard
├── Uang Masuk
├── Uang Keluar
├── Transaksi
│   └── Detail → Ubah / Batalkan sesuai izin
├── Laporan Periode
│   ├── Pratinjau
│   └── Pembuatan / Unduhan PDF
├── Slide TV (Admin)
└── Pengaturan (Admin)
    ├── Laporan dan Saldo Awal
    ├── Anggota dan Hak Akses
    ├── Kategori Pengeluaran
    ├── PDF
    └── Slide

Sistem (Super Admin)
├── Pengguna
├── Semua Laporan
├── Backup dan Restore
├── Audit
└── Status Sistem
```

### Navigasi

- Mobile: header ringkas berisi nama Laporan dan tombol menu; navigasi utama berada dalam drawer/halaman menu. Dua aksi transaksi muncul sebagai tombol besar pada Dashboard.
- Tablet: header dan navigasi horizontal ringkas; konten memakai satu atau dua kolom.
- Desktop: sidebar tetap selebar 240 piksel, header berisi pemilih Laporan dan akun.
- Pergantian Laporan selalu menampilkan nama target dan tidak mempertahankan filter/kategori dari Laporan sebelumnya.
- Menu dan tombol mutasi disembunyikan bila pengguna tidak berizin; server tetap harus memeriksa izin pada tahap backend.

## 3. Alur pengguna

### 3.1 Masuk dan memilih Laporan

1. Pengguna mengisi email/nama pengguna dan kata sandi.
2. Sistem menampilkan error generik bila gagal.
3. Satu Laporan aktif mengarahkan langsung ke Dashboard.
4. Lebih dari satu Laporan menampilkan layar pemilihan.
5. Laporan Ditutup/Diarsipkan tetap dapat dipilih bila berhak, dengan label baca-saja.

### 3.2 Mencatat Uang Masuk

`Dashboard → Uang Masuk → tanggal + jumlah + keterangan + bukti opsional → Simpan → saldo diperbarui`

- Nomor transaksi dihasilkan otomatis dan hanya diinformasikan setelah tersimpan.
- Tombol berubah menjadi “Menyimpan…” dan dinonaktifkan untuk mencegah kirim ganda.

### 3.3 Mencatat Uang Keluar

`Dashboard → Uang Keluar → tanggal + jumlah + keterangan + kategori + bukti opsional → Simpan → saldo diperbarui`

- Kategori hanya berasal dari Laporan aktif.
- Tidak ada pilihan rekening atau akun kas.

### 3.4 Mengelola transaksi sebagai Petugas

- **Input:** tombol Uang Masuk dan/atau Uang Keluar tampil sesuai izin.
- **Edit:** aksi “Ubah” tampil untuk transaksi sendiri atau semua transaksi sesuai izin.
- **Pembatalan:** aksi “Batalkan” tampil hanya sesuai keputusan hak akses yang disetujui.
- Tidak ada hard delete. Pembatalan meminta alasan, mempertahankan data, dan mengeluarkannya dari saldo/laporan normal.
- Pada Laporan Ditutup/Diarsipkan seluruh aksi mutasi hilang dan banner baca-saja tampil.

### 3.5 Membuat laporan PDF

`Laporan Periode → pilih filter → Tampilkan → periksa total → Buat PDF → status antrean → Unduh`

## 4. Spesifikasi layar

### 4.1 Login

- Konten: logo/nama aplikasi, email atau nama pengguna, kata sandi, tampilkan/sembunyikan kata sandi, tombol “Masuk”, bantuan hubungi admin.
- Mobile: satu kartu tanpa ilustrasi berat; keyboard tidak menutupi tombol saat halaman digulir.
- Desktop: kartu maksimum 420 piksel di tengah, panel merek opsional tanpa informasi operasional.
- State: kosong, kredensial salah, akun nonaktif, rate limit, sesi berakhir.
- Aksesibilitas: label selalu terlihat; pesan error terhubung ke input.

### 4.2 Pemilihan Laporan

- Kartu berisi nama, deskripsi singkat, periode, status, dan peran pengguna.
- Urutan: Aktif terlebih dahulu, lalu Ditutup, kemudian Diarsipkan.
- Mobile satu kolom; desktop grid maksimal tiga kolom.
- Empty state: “Belum ada Laporan yang dapat Anda akses” dan petunjuk menghubungi admin.

### 4.3 Dashboard Laporan

- Header: nama, periode, status, tombol pindah Laporan.
- Ringkasan: Saldo, total Uang Masuk, total Uang Keluar, masuk/keluar bulan berjalan.
- CTA utama: “Uang Masuk” dan “Uang Keluar”, masing-masing disembunyikan jika tidak berizin.
- Isi: lima transaksi terbaru dan pengeluaran per kategori.
- Saldo negatif: label “Saldo negatif”, ikon peringatan, angka bertanda minus, dan teks penjelas.
- Viewer tidak melihat CTA mutasi.

### 4.4 Form Uang Masuk

- Field berurutan: tanggal, jumlah Rupiah, keterangan, bukti transaksi opsional.
- Jenis transaksi tampil sebagai label tetap “Uang Masuk”, bukan pilihan.
- Jumlah memakai input numerik, `inputmode=numeric`, minimum Rp1.
- Tombol utama lebar penuh pada mobile dan tetap mudah dijangkau.

### 4.5 Form Uang Keluar

- Field berurutan: tanggal, jumlah Rupiah, keterangan, kategori pengeluaran, bukti opsional.
- Jenis transaksi tetap “Uang Keluar”.
- Kategori nonaktif tidak dapat dipilih tetapi tetap dapat terlihat pada transaksi lama.
- Bila belum ada kategori aktif, form tidak dapat disimpan dan mengarahkan Admin ke pengaturan kategori.

### 4.6 Daftar transaksi dan filter

- Filter: tanggal awal/akhir, jenis, kategori, pencarian keterangan, urutan kronologis.
- Total sesuai filter tampil di atas hasil.
- Desktop: tabel No, Tanggal, Keterangan, Pengeluaran, Pemasukan, Kategori, Status/Aksi.
- Mobile: setiap transaksi menjadi kartu berlabel; nominal dan jenis terlihat tanpa menggulir horizontal.
- Transaksi batal diberi teks “Batal” dan coret visual sekunder; tidak masuk total normal.
- Pagination server ditempatkan setelah hasil.

### 4.7 Detail, edit, dan pembatalan transaksi

- Detail: nomor, status, tanggal, jenis, jumlah, keterangan, kategori, bukti, pembuat, waktu buat/ubah.
- Tombol “Ubah” dan “Batalkan transaksi” dihitung terpisah berdasarkan izin.
- Form edit hanya mengubah tanggal, jumlah, keterangan, dan kategori.
- Pembatalan menggunakan halaman/dialog berisi ringkasan transaksi, alasan wajib minimal 5 karakter, tombol bahaya “Ya, batalkan”, dan tombol “Kembali”.
- Setelah batal, detail tetap tersedia dengan alasan, pelaku, dan waktu pembatalan.

### 4.8 Pratinjau laporan periode

- Filter: tanggal awal, tanggal akhir, jenis opsional, kategori opsional.
- Ringkasan: saldo sebelum periode, pemasukan periode, pengeluaran periode, saldo akhir.
- Tabel mengikuti enam kolom PDF.
- Tombol “Buat PDF” hanya tampil bila berizin.
- Perubahan filter selalu menghitung ulang semua ringkasan dari sumber yang sama.

### 4.9 Status pembuatan dan unduhan PDF

- Status: Menunggu, Diproses, Berhasil, atau Gagal.
- Menunggu/Diproses: indikator teks dan waktu permintaan; halaman tetap berfungsi tanpa animasi.
- Berhasil: tombol “Unduh PDF”, ukuran berkas, dan waktu selesai.
- Gagal: pesan aman, tombol “Coba lagi”, serta ID referensi untuk admin.
- Daftar hanya menampilkan PDF dari Laporan yang boleh diakses pengguna.

### 4.10 Slide TV 16:9

Semua slide memakai safe area 5%, teks besar, kontras tinggi, waktu pembaruan, dan transisi fade minimal.

1. Pembuka: logo, nama Laporan, periode, waktu pembaruan.
2. Ringkasan: total masuk, total keluar, saldo; label selalu ditampilkan.
3. Kategori: daftar/bar pengeluaran per kategori dengan nilai dan persentase.
4. Bulanan: perbandingan masuk/keluar per bulan, legenda langsung dan pola/label.
5. Transaksi terbaru: hanya jika Admin mengaktifkan; keterangan dapat disembunyikan sesuai keputusan privasi.
6. Penutup: pesan transparansi dan waktu pembaruan.

Jika slide dinonaktifkan, layar hanya menampilkan “Tayangan tidak aktif” tanpa angka lama.

### 4.11 Admin — pengguna dan akses per Laporan

- Cari pengguna yang sudah terdaftar atau buat pengguna baru.
- Pilih peran: Admin Laporan, Petugas, Viewer.
- Untuk Petugas tersedia sakelar terpisah: tambah masuk, tambah keluar, edit sendiri, edit semua, batalkan, buat PDF.
- Ringkasan izin tampil sebelum disimpan.
- Daftar anggota menunjukkan peran, status akun, izin efektif, serta aksi ubah/cabut akses.
- Pencabutan akses memakai konfirmasi; tidak menghapus akun.

### 4.12 Admin — pengaturan Laporan dan saldo awal

- Nama, keterangan, logo, warna, tanggal mulai/selesai opsional, status.
- Saldo awal memiliki format Rupiah.
- Perubahan saldo awal setelah transaksi meminta alasan dan menampilkan dampak pada saldo.
- Penutupan, pembukaan kembali, dan pengarsipan memakai konfirmasi serta alasan bila diwajibkan PRD.

### 4.13 Admin — kategori pengeluaran

- Daftar berurutan berisi nama, warna, status, bawaan, dan aksi ubah/nonaktifkan.
- Tambah/ubah: nama, warna, urutan, kategori bawaan.
- Kategori yang sudah dipakai tidak memiliki tombol hapus permanen.
- Nama ganda menampilkan error di field nama.

### 4.14 Admin — pengaturan PDF

- Logo, judul, footer admin, ukuran margin, dan pratinjau struktur A4.
- Struktur enam kolom tidak dapat diubah pada MVP.
- Menjelaskan bahwa tabel panjang mengulang header dan nomor halaman.

### 4.15 Admin — pengaturan slide

- Aktif/nonaktif, periode, susunan slide, durasi, tema, logo, tampilkan nominal, kategori, transaksi terbaru, interval pembaruan, masa aktif.
- Peringatan privasi muncul saat mengaktifkan transaksi terbaru.
- Token URL hanya ditampilkan penuh saat dibuat/diganti.
- Tombol “Buka pratinjau 16:9” tersedia.

### 4.16 Super Admin — backup, restore, audit, dan status sistem

- Backup: status terakhir, jadwal harian/mingguan, retensi, tujuan, enkripsi, “Backup sekarang”.
- Restore: pilih backup terverifikasi, target restore, autentikasi ulang, konfirmasi ketik nama target; tidak digabung dengan tombol backup.
- Audit: filter waktu, pengguna, Laporan, tindakan; detail sebelum/sesudah baca-saja.
- Status: database, antrean, scheduler, ruang disk, backup terakhir; setiap status memakai teks dan waktu pemeriksaan.

## 5. State lintas layar

| State | Perilaku |
|---|---|
| Kosong | Menjelaskan kondisi dan memberikan satu aksi berikutnya bila pengguna berizin |
| Loading | Mempertahankan struktur; teks “Memuat…” dapat dibaca pembaca layar |
| Gagal | Pesan aman, tindakan ulang, ID referensi bila operasional |
| Tidak berwenang | HTTP 403, penjelasan akses Laporan, kembali ke pemilihan Laporan |
| Sesi login berakhir | Simpan tujuan aman, arahkan ke login, jangan menyimpan ulang form otomatis |
| Laporan Ditutup | Banner “Baca-saja”; semua mutasi disembunyikan/diblokir |
| Laporan Diarsipkan | Banner “Diarsipkan”; baca-saja dan berada di bagian terpisah |
| Saldo negatif | Ikon + teks “Saldo negatif” + nilai minus; tidak memblokir transaksi |
| Koneksi slide putus | Data terakhir boleh tampil dengan label “Pembaruan tertunda” |
| Slide dinonaktifkan | Tidak menampilkan data/angka lama |

## 6. Validasi formulir

- Tanggal wajib valid; tanggal akhir tidak sebelum tanggal awal.
- Nominal wajib integer Rupiah lebih dari nol; pemisah ribuan hanya presentasi.
- Keterangan wajib, maksimum 1.000 karakter.
- Uang Keluar wajib memakai kategori aktif milik Laporan aktif.
- Uang Masuk tidak memiliki field kategori.
- Bukti opsional: JPG, PNG, atau PDF, maksimum 5 MB.
- Alasan pembatalan minimum 5 dan maksimum 500 karakter.
- Error muncul di dekat field, ringkasan error muncul di awal form, fokus berpindah ke error pertama.
- Tombol submit tidak aktif saat permintaan sedang diproses; refresh aman tidak membuat transaksi ganda.

## 7. Responsivitas

| Lebar | Tata letak |
|---|---|
| 360–767 px | Satu kolom, padding 16 px, tombol utama penuh, tabel menjadi kartu |
| 768–1023 px | Dua kolom bila relevan, navigasi header, filter membungkus |
| ≥1024 px | Sidebar 240 px, konten maksimum 1200 px, tabel penuh |

- Tidak ada kontrol yang membutuhkan hover.
- Tidak ada overflow horizontal pada 360 piksel.
- Dialog kritis menjadi halaman penuh/bottom sheet pada mobile.
- Safe area perangkat diperhitungkan untuk tombol bawah.

## 8. Aksesibilitas dan interaksi

- Target sentuh minimum 44 × 44 piksel.
- Fokus keyboard terlihat dengan outline 3 piksel.
- Urutan tab mengikuti urutan visual.
- Semua input mempunyai label permanen; placeholder bukan label.
- Status dinamis diumumkan melalui live region yang sopan.
- Kontras teks mengikuti WCAG AA; teks normal minimal 4,5:1.
- Ikon dekoratif disembunyikan dari pembaca layar; ikon aksi memiliki nama aksesibel.
- Konfirmasi berisiko menyebut objek dan akibat, bukan hanya “Apakah Anda yakin?”.
- Animasi menghormati `prefers-reduced-motion`.

## 9. Spesifikasi PDF

- A4 portrait, margin 12–15 mm.
- Font server DejaVu Sans: judul 16 pt, isi 9 pt, metadata 8 pt.
- Urutan: identitas → periode → ringkasan → tabel → footer.
- Kolom: No 7%, Tanggal 13%, Keterangan 34%, Pengeluaran 15%, Pemasukan 15%, Kategori 16%.
- Angka rata kanan; format Rupiah Indonesia; nilai kosong memakai “—”.
- `thead` berulang tiap halaman; baris tidak bertumpuk; teks panjang membungkus.
- Halaman lanjutan menampilkan nama Laporan/periode ringkas.
- Footer memuat teks admin dan “Halaman X dari Y”.
- Saldo negatif diberi label “NEGATIF” dan tanda minus agar terbaca hitam-putih.

## 10. Keputusan dan asumsi yang perlu persetujuan

1. **Pembatalan oleh Petugas:** bagian hak akses menyebut izin pembatalan Petugas, tetapi baseline PRD menyebut hanya Admin Laporan. Rekomendasi desain: Petugas dapat membatalkan hanya bila izin khusus diaktifkan; default mati.
2. **Keterangan pada slide:** rekomendasi desain: transaksi terbaru default mati; bila aktif, keterangan tetap disembunyikan kecuali Admin mengaktifkannya secara eksplisit.
3. **Saldo awal setelah transaksi:** rekomendasi desain: boleh diubah Admin dengan alasan wajib, pratinjau dampak, dan audit.
4. **Tanda tangan PDF:** belum ditentukan. Rekomendasi MVP: footer teks admin tanpa blok tanda tangan.
5. **Lokasi backup kedua:** belum ditentukan; desain menyediakan pilihan disk/NAS dan S3-compatible.
6. **Impor data lama:** belum ditentukan dan tidak menjadi layar operasional MVP sebelum format sumber disetujui.
7. **Istilah:** rekomendasi tetap menggunakan “Laporan” di UI dan tidak menampilkan `report_session`.

## 11. Acceptance checklist desain

### Cakupan

- [x] Enam belas layar/area wajib memiliki spesifikasi.
- [x] Alur input, edit, dan pembatalan transaksi dijelaskan berdasarkan izin.
- [x] Mobile, tablet, desktop, PDF A4, dan TV 16:9 memiliki aturan.
- [x] State kosong, loading, gagal, 403, sesi berakhir, baca-saja, dan saldo negatif tersedia.
- [x] Tidak ada rekening, transfer, rekonsiliasi, jurnal, atau neraca.

### Mobile dan aksesibilitas

- [x] Tata letak 360 piksel satu kolom tanpa tabel lebar.
- [x] Target sentuh minimum 44 × 44 piksel.
- [x] Nominal membuka keypad numerik.
- [x] Warna bukan satu-satunya pembeda.
- [x] Alur inti tidak bergantung pada JavaScript.

### PDF dan TV

- [x] PDF A4 memiliki enam kolom dan header tabel berulang.
- [x] Slide memiliki enam jenis tayangan, safe area, dan fallback koneksi.
- [x] Slide nonaktif tidak memperlihatkan data lama.

### Gerbang persetujuan pemilik produk

- [x] Form Uang Masuk disetujui.
- [x] Form Uang Keluar disetujui.
- [x] Aturan edit/pembatalan Petugas memakai rekomendasi desain.
- [x] Format PDF disetujui.
- [x] Konten dan privasi slide TV memakai rekomendasi desain.
- [x] Asumsi pada bagian 10 memakai baseline rekomendasi.

Tidak boleh melanjutkan ke Tahap 2 sebelum seluruh butir gerbang yang relevan disetujui.
