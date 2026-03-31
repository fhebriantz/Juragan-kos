# Juragan Kos App

Aplikasi manajemen kos & kontrakan berbasis web. Mendukung multi-properti, 3 tipe sewa (bulanan/tahunan/harian), tagihan operasional otomatis, notifikasi WhatsApp, cetak kuitansi, dan laporan laba/rugi.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP Native (minimal 8.1, tanpa framework) |
| Database | SQLite (file-based, tanpa MySQL) |
| Frontend | Bootstrap 5.3 + Bootstrap Icons |
| Grafik | Chart.js 4 |
| Server | PHP Built-in Server / Apache / Nginx |

## Persyaratan

- PHP >= 8.1 dengan ekstensi `sqlite3` dan `gd` (untuk upload gambar)
- Browser modern (Chrome, Firefox, Edge, Safari)
- Koneksi internet hanya untuk CDN Bootstrap & Chart.js (bisa di-offline-kan)

## Instalasi

```bash
# 1. Clone repository
git clone <repo-url> juragan-kos
cd juragan-kos

# 2. Pastikan folder uploads bisa ditulis
mkdir -p uploads/ktp
chmod 755 uploads/ktp

# 3. (Opsional) Isi data demo
php seed.php

# 4. Jalankan server
php -S localhost:8080

# 5. Buka browser → http://localhost:8080
```

Database SQLite otomatis dibuat di `database/juragan_kos.db` saat pertama kali diakses. Tidak perlu setup database manual.

## Struktur Folder

```
juragan-kos/
├── config/
│   └── database.php            # Koneksi SQLite, create table, migrasi, helper
├── includes/
│   ├── header.php              # Layout: head, sidebar navigasi, navbar
│   └── footer.php              # Script & closing tags
├── pages/
│   ├── properti.php            # CRUD properti (multi-lokasi)
│   ├── kamar.php               # CRUD kamar per properti
│   ├── penyewa.php             # CRUD penyewa + upload KTP + checkout
│   ├── pemasukan.php           # Catat pemasukan (sewa, denda, laundry, dll)
│   ├── pengeluaran.php         # Catat pengeluaran non-rutin
│   ├── maintenance.php         # Maintenance per kamar + umum + rekap evaluasi
│   ├── tagihan_operasional.php # Tagihan rutin + sekali bayar (3 tab)
│   ├── laporan.php             # Laporan laba/rugi + grafik (dinamis)
│   ├── kuitansi.php            # Cetak kuitansi pembayaran
│   ├── pengaturan.php          # Setting umum + reset data
│   └── bantuan.php             # Panduan penggunaan lengkap
├── assets/
│   ├── css/style.css           # Custom styles + print CSS
│   └── js/app.js               # Sidebar toggle, confirm delete, format rupiah
├── uploads/
│   └── ktp/                    # Foto KTP penyewa (git-ignored)
├── database/                   # Folder database (git-ignored)
│   └── juragan_kos.db          # File SQLite
├── phpdesktop/                 # PHP Desktop runtime (git-ignored, download manual)
├── dist/                       # Hasil build (git-ignored)
├── build.sh                    # Build script (Linux/macOS)
├── build.bat                   # Build script (Windows)
├── phpdesktop-settings.json    # Konfigurasi PHP Desktop untuk production
├── seed.php                    # Generator data demo
├── index.php                   # Dashboard + notifikasi + aksi bayar
├── .gitignore
└── README.md
```

## Data Demo

Jalankan `php seed.php` untuk mengisi data contoh:

| Data | Jumlah |
|------|--------|
| Properti | 2 (Kos Melati + Kontrakan Mawar) |
| Kamar | 12 (8 kos + 4 kontrakan) |
| Penyewa | 11 (bulanan, tahunan, harian, nonaktif) |
| Pemasukan | 27+ transaksi (3 bulan terakhir) |
| Pengeluaran | 10+ transaksi |
| Maintenance | 11 (spesifik kamar + umum) |
| Template Tagihan | 4 (PLN x2, PDAM, Keamanan) |
| Tagihan Operasional | 8 (sudah bayar + belum bayar) |

Untuk reset data demo:
- **Via terminal:** `rm database/juragan_kos.db && php seed.php`
- **Via aplikasi:** Pengaturan → Reset Seluruh Database → ketik `HAPUS-SEMUA`

## Skema Database

### Relasi

```
properti (1) ──→ (N) kamar (1) ──→ (N) penyewa
    │                   │
    ├──→ pemasukan      ├──→ maintenance
    ├──→ pengeluaran
    ├──→ tagihan_operasional
    └──→ template_tagihan
```

### Tabel

| Tabel | Deskripsi |
|-------|-----------|
| `properti` | Lokasi kos/kontrakan (nama, alamat, tipe) |
| `kamar` | Kamar per properti (nomor, harga bulanan/tahunan, status, fasilitas) |
| `penyewa` | Data penyewa (nama, KTP, foto KTP, HP, tipe sewa, tanggal masuk/keluar, harga, bayar_sampai) |
| `pemasukan` | Uang masuk (sewa, denda, laundry, parkir, lainnya) |
| `pengeluaran` | Uang keluar non-rutin (beli perabotan, ongkos tukang, dll) |
| `maintenance` | Perbaikan spesifik kamar + umum (bangunan) |
| `template_tagihan` | Template tagihan rutin (PLN, PDAM, WiFi, dll) — auto-generate tiap bulan |
| `tagihan_operasional` | Tagihan per bulan (Rutin dari template + Sekali Bayar) |
| `pengaturan` | Key-value settings (nama usaha, pemilik, HP) |

### Kolom Penting

| Kolom | Nilai | Keterangan |
|-------|-------|------------|
| `kamar.status` | Kosong / Terisi / Maintenance | Otomatis berubah saat penyewa masuk/keluar |
| `penyewa.tipe_sewa` | Bulanan / Tahunan / Harian | Menentukan logika jatuh tempo & warning |
| `penyewa.tanggal_keluar` | DATE | Hanya untuk harian (tanggal checkout) |
| `penyewa.harga_sewa` | REAL | Harga per malam (harian) atau custom |
| `penyewa.bayar_sampai` | DATE | Untuk tahunan: tanggal akhir masa sewa |
| `penyewa.foto_ktp` | TEXT | Nama file foto KTP di `uploads/ktp/` |
| `template_tagihan.isi_manual` | 0/1 | 1 = nominal diisi manual per bulan (PLN, PDAM) |
| `template_tagihan.jatuh_tempo_tanggal` | 1-28 | Tanggal jatuh tempo per template |
| `tagihan_operasional.tipe` | Rutin / Sekali Bayar | Rutin = dari template, Sekali Bayar = manual |
| `tagihan_operasional.status` | Belum Bayar / Sudah Bayar | Saat bayar, otomatis insert ke pengeluaran |

## Fitur Lengkap

### Dashboard & Notifikasi
- **Alert merah:** penyewa nunggak, tagihan operasional terlambat, penyewa harian lewat checkout
- **Alert kuning:** jatuh tempo dalam 3 hari, penyewa harian ada sisa tagihan
- **Alert biru:** penyewa harian lunas (pengingat checkout), penyewa tahunan mendekati akhir masa sewa (30 hari)
- **Catat Bayar:** modal popup langsung dari Dashboard (nominal pre-fill, metode bayar)
- **Bayar tagihan operasional:** isi nominal → bayar → otomatis masuk pengeluaran
- **Tagih via WA:** auto-generate pesan WhatsApp (nama, kamar, jatuh tempo, nominal)
- **Filter properti:** lihat data per lokasi
- **Statistik:** total kamar, terisi/kosong, pemasukan bulan ini, laba bersih

### Multi-Properti
- Daftarkan banyak lokasi kos/kontrakan
- Setiap properti punya kamar, penyewa, keuangan sendiri
- Filter per properti di semua halaman
- Kartu properti: ringkasan terisi/kosong + pemasukan bulan ini

### Penyewa (3 Tipe Sewa)
- **Bulanan:** jatuh tempo otomatis dari tanggal masuk, warning tiap bulan
- **Tahunan:** bayar sekali, `bayar_sampai` otomatis 12 bulan, skip warning bulanan, pengingat 30 hari sebelum habis
- **Harian:** harga per malam, tanggal checkout, warning checkout + sisa tagihan, auto-checkout setelah bayar
- **Upload Foto KTP:** simpan foto/scan KTP (jpg, png, webp, max 5MB)
- **Detail penyewa:** klik nama untuk lihat data lengkap + preview foto KTP + download
- **Checkout:** status penyewa → Nonaktif, kamar → Kosong, data tetap tersimpan

### Tagihan Operasional (3 Tab)
- **Tagihan Bulanan:** daftar semua tagihan, isi nominal → bayar → lunas
- **Tagihan Rutin:** template auto-generate tiap bulan (jenis bebas, jatuh tempo per template, switch isi manual)
- **Tambah Sekali Bayar:** tagihan non-rutin (pajak, perbaikan pompa, dll)
- Saat bayar → otomatis insert ke tabel pengeluaran (tidak perlu input dua kali)

### Pengeluaran
- Khusus untuk pengeluaran **di luar tagihan operasional** (beli perabotan, ongkos tukang, dll)
- PLN/PDAM/Keamanan **tidak** dicatat di sini (sudah di Tagihan Operasional)

### Maintenance Kamar
- **Spesifik Kamar:** ganti keran kamar 04, servis AC kamar 02
- **Umum:** perbaiki genteng, beli sapu
- **Rekap tahunan per kamar:** evaluasi kamar "rewel" (Normal / Perhatikan / Perlu Evaluasi)

### Laporan Laba/Rugi
- Kolom pengeluaran **dinamis** (muncul sesuai kategori yang ada)
- Maintenance terpisah dari pengeluaran operasional
- Grafik bar (pemasukan vs pengeluaran) + garis laba bersih
- Filter per properti dan per tahun
- Print-friendly (sidebar otomatis disembunyikan)
- Keterangan rumus di bawah tabel

### Cetak Kuitansi
- Layout profesional: nama properti, alamat, penyewa, kamar, nominal, terbilang otomatis
- Akses dari Pemasukan (ikon printer) atau Dashboard (setelah catat bayar)

### Pengaturan & Reset
- Setting: nama usaha, nama pemilik, no HP
- **Reset Transaksi:** hapus pemasukan/pengeluaran/maintenance/tagihan, properti/kamar/penyewa aman
- **Reset Database:** hapus semua data, mulai dari nol
- Proteksi: kode konfirmasi + dialog confirm

### Bantuan (How-To)
- Cara mulai menggunakan (6 langkah)
- Alur kerja harian
- Panduan lengkap per menu
- Peta fitur (harus catat di mana?)
- FAQ

## Alur Pemisahan Pengeluaran

```
PLN, PDAM, Keamanan, WiFi, dll    → Tagihan Operasional (klik Bayar → auto ke pengeluaran)
Beli perabotan, ongkos, dll       → Pengeluaran (input manual)
Servis AC, ganti keran, dll       → Maintenance (punya rekap per kamar)
```

Semua masuk ke Laporan Keuangan. Tidak ada data yang tercatat dua kali.

## Konvensi Kode

- Semua teks UI dalam **Bahasa Indonesia**
- Query SQLite menggunakan **prepared statements**
- Helper functions di `config/database.php`:
  - `formatRupiah($angka)` — format ke "Rp 1.200.000"
  - `formatTanggal($tanggal)` — format ke "15 Maret 2026"
  - `getPengaturan($db, $kunci)` — ambil value dari tabel pengaturan
  - `getPropertiList($db)` — ambil semua properti untuk dropdown
  - `generateTagihanBulanan($db)` — auto-generate tagihan dari template
- CSS custom di `assets/css/style.css` + `@media print`
- JavaScript minimal di `assets/js/app.js`

## Build Desktop (PHP Desktop)

Aplikasi bisa di-build menjadi aplikasi desktop (.exe) menggunakan PHP Desktop.

### Langkah Build

1. Download [PHP Desktop](https://github.com/nicengi/phpdesktop/releases)
2. Ekstrak ke folder `phpdesktop/` di root project
3. Pastikan ekstensi `sqlite3` dan `pdo_sqlite` aktif di `phpdesktop/php/php.ini`
4. Jalankan build script:

```bash
# Linux / macOS
./build.sh

# Windows
build.bat
```

5. Hasil build ada di `dist/JuraganKos/`
6. Untuk distribusi: ZIP folder `dist/JuraganKos/` lalu kirim ke client

### Catatan

- Database baru akan otomatis dibuat saat aplikasi pertama kali dijalankan
- Folder `phpdesktop/` dan `dist/` sudah di-gitignore

## Backup & Restore

```bash
# Backup
cp database/juragan_kos.db database/backup_$(date +%Y%m%d).db

# Restore
cp database/backup_20260331.db database/juragan_kos.db

# Backup foto KTP juga
tar -czf backup_ktp_$(date +%Y%m%d).tar.gz uploads/ktp/
```

## Lisensi

Hak cipta Lutfi Febrianto. Seluruh hak dilindungi.
