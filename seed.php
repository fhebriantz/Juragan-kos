<?php
/**
 * Seed Dummy Data — Juragan Kos App
 *
 * Jalankan: php seed.php
 * Atau otomatis saat pertama kali buka aplikasi (jika database kosong).
 *
 * Data yang di-generate:
 * - 2 Properti (Kos + Kontrakan)
 * - 12 Kamar (8 + 4)
 * - 9 Penyewa aktif + 1 nonaktif
 * - Pemasukan 3 bulan terakhir
 * - Pengeluaran 3 bulan terakhir
 * - Maintenance (spesifik + umum)
 * - Tagihan operasional (ada yang nunggak, mendekati, sudah bayar)
 */

require_once __DIR__ . '/config/database.php';

// Cek apakah sudah ada data
$ada_properti = $db->querySingle("SELECT COUNT(*) FROM properti");
if ($ada_properti > 0) {
    if (php_sapi_name() === 'cli') {
        echo "Database sudah berisi data. Jalankan reset di Pengaturan terlebih dahulu.\n";
    }
    return;
}

echo php_sapi_name() === 'cli' ? "Mengisi data dummy...\n" : "";

$today = date('Y-m-d');
$bulan_ini = date('Y-m');
$bulan_lalu = date('Y-m', strtotime('first day of -1 month'));
$bulan_lalu2 = date('Y-m', strtotime('first day of -2 months'));

// ============ PROPERTI ============
$db->exec("INSERT INTO properti (id, nama, alamat, tipe) VALUES
    (1, 'Kos Melati', 'Jl. Melati No. 45, Bandung', 'Kos'),
    (2, 'Kontrakan Mawar', 'Jl. Mawar Blok C, Bandung', 'Kontrakan')
");

// ============ KAMAR — Kos Melati (8 kamar) ============
$db->exec("INSERT INTO kamar (id, properti_id, nomor_kamar, fasilitas, harga_bulanan, harga_tahunan, status) VALUES
    (1, 1, '01', 'AC, Kamar Mandi Dalam, WiFi', 1200000, 13000000, 'Terisi'),
    (2, 1, '02', 'AC, Kamar Mandi Dalam, WiFi', 1200000, 13000000, 'Terisi'),
    (3, 1, '03', 'AC, Kamar Mandi Dalam, WiFi', 1200000, 13000000, 'Terisi'),
    (4, 1, '04', 'AC, Kamar Mandi Dalam, WiFi', 1200000, 13000000, 'Terisi'),
    (5, 1, '05', 'Kipas Angin, Kamar Mandi Dalam', 900000, 10000000, 'Terisi'),
    (6, 1, '06', 'Kipas Angin, Kamar Mandi Dalam', 900000, 10000000, 'Terisi'),
    (7, 1, '07', 'Kipas Angin, Kamar Mandi Luar', 750000, 8000000, 'Kosong'),
    (8, 1, '08', 'Kipas Angin, Kamar Mandi Luar', 750000, 8000000, 'Maintenance')
");

// ============ KAMAR — Kontrakan Mawar (4 unit) ============
$db->exec("INSERT INTO kamar (id, properti_id, nomor_kamar, fasilitas, harga_bulanan, harga_tahunan, status) VALUES
    (9,  2, 'A1', '2 Kamar Tidur, Dapur, Carport', 2500000, 27000000, 'Terisi'),
    (10, 2, 'A2', '2 Kamar Tidur, Dapur, Carport', 2500000, 27000000, 'Terisi'),
    (11, 2, 'B1', '1 Kamar Tidur, Dapur', 1800000, 20000000, 'Terisi'),
    (12, 2, 'B2', '1 Kamar Tidur, Dapur', 1800000, 20000000, 'Kosong')
");

// ============ PENYEWA ============
// Jatuh tempo dinamis: diambil dari tanggal masuk

// --- Kos Melati ---
// Penyewa yang nunggak (jatuh tempo sudah lewat, belum bayar bulan ini)
$tgl_nunggak1 = date('Y-m-d', strtotime('-4 months'));
$jt_nunggak1 = (int)date('d', strtotime($tgl_nunggak1));

// Buat jatuh tempo yang sudah lewat beberapa hari lalu
$tgl_lewat = date('Y-m-d', strtotime('-3 months -5 days'));
$jt_lewat = max(1, min(28, (int)date('d') - 5)); // 5 hari lalu
$tgl_lewat_masuk = date('Y-m-d', strtotime("-3 months -" . (date('d') - $jt_lewat) . " days"));

// Penyewa yang mendekati jatuh tempo (1-2 hari lagi)
$jt_mendekati = min(28, (int)date('d') + 2); // 2 hari lagi
$tgl_mendekati_masuk = date('Y-m-d', strtotime("-2 months +" . ($jt_mendekati - (int)date('d')) . " days"));

// Penyewa yang sudah bayar bulan ini
$tgl_aman1 = date('Y-m-d', strtotime('-6 months'));

$db->exec("INSERT INTO penyewa (id, nama, no_hp, no_ktp, alamat_asal, kamar_id, tanggal_masuk, tipe_sewa, jatuh_tempo_tanggal, status) VALUES
    (1, 'Andi Pratama',    '081234567890', '3273012345670001', 'Garut',        1, '$tgl_lewat_masuk',     'Bulanan', $jt_lewat,     'Aktif'),
    (2, 'Budi Santoso',    '085678901234', '3273012345670002', 'Sumedang',     2, '$tgl_nunggak1',        'Bulanan', $jt_nunggak1,  'Aktif'),
    (3, 'Citra Dewi',      '087890123456', '3273012345670003', 'Tasikmalaya',  3, '$tgl_mendekati_masuk', 'Bulanan', $jt_mendekati, 'Aktif'),
    (4, 'Deni Firmansyah', '089012345678', '3273012345670004', 'Cimahi',       4, '$tgl_aman1',           'Bulanan', " . (int)date('d', strtotime($tgl_aman1)) . ", 'Aktif'),
    (5, 'Eka Putri',       '081345678901', '3273012345670005', 'Cianjur',      5, '$tgl_aman1',           'Bulanan', " . (int)date('d', strtotime($tgl_aman1)) . ", 'Aktif'),
    (6, 'Fajar Hidayat',   '082456789012', '3273012345670006', 'Majalengka',   6, '$tgl_aman1',           'Bulanan', " . (int)date('d', strtotime($tgl_aman1)) . ", 'Aktif')
");

// --- Kontrakan Mawar ---
// Hana Salsabila: sewa tahunan, bayar_sampai 4 bulan lagi (agar muncul alert sisa 30 hari di demo = tidak, tapi terlihat di daftar penyewa)
$hana_bayar_sampai = date('Y-m-d', strtotime('+4 months'));
$db->exec("INSERT INTO penyewa (id, nama, no_hp, no_ktp, alamat_asal, kamar_id, tanggal_masuk, tipe_sewa, jatuh_tempo_tanggal, bayar_sampai, status) VALUES
    (7, 'Gilang Ramadhan', '083567890123', '3273012345670007', 'Bandung',  9,  '" . date('Y-m-d', strtotime('-5 months')) . "', 'Bulanan', " . (int)date('d', strtotime('-5 months')) . ", NULL, 'Aktif'),
    (8, 'Hana Salsabila',  '084678901234', '3273012345670008', 'Cirebon',  10, '" . date('Y-m-d', strtotime('-8 months')) . "', 'Tahunan',  " . (int)date('d', strtotime('-8 months')) . ", '$hana_bayar_sampai', 'Aktif'),
    (9, 'Irfan Maulana',   '085789012345', '3273012345670009', 'Subang',   11, '" . date('Y-m-d', strtotime('-3 months')) . "', 'Bulanan', " . (int)date('d', strtotime('-3 months')) . ", NULL, 'Aktif')
");

// Penyewa harian: check-in 3 hari lalu, checkout besok, bayar 2 malam di awal → sisa 1 malam belum bayar
$tgl_checkin_harian = date('Y-m-d', strtotime('-2 days'));
$tgl_checkout_harian = date('Y-m-d', strtotime('+1 day'));
$tgl_bayar_sampai_harian = date('Y-m-d'); // bayar sampai hari ini, checkout besok → 1 malam sisa
$db->exec("INSERT INTO penyewa (id, nama, no_hp, no_ktp, alamat_asal, kamar_id, tanggal_masuk, tanggal_keluar, tipe_sewa, harga_sewa, jatuh_tempo_tanggal, bayar_sampai, status) VALUES
    (10, 'Kevin Tamu', '087901234567', '3273012345670010', 'Jakarta', 7, '$tgl_checkin_harian', '$tgl_checkout_harian', 'Harian', 150000, 1, '$tgl_bayar_sampai_harian', 'Aktif')
");
// Kamar 07 jadi terisi
$db->exec("UPDATE kamar SET status = 'Terisi' WHERE id = 7");
// Catat pembayaran awal 2 malam
$db->exec("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, keterangan, nominal, tanggal, periode_bulan, metode_bayar) VALUES
    (1, 10, 7, 'Sewa', 'Bayar 2 malam di awal', 300000, '$tgl_checkin_harian', '" . date('Y-m') . "', 'Tunai')");

// Penyewa nonaktif (sudah checkout)
$db->exec("INSERT INTO penyewa (id, nama, no_hp, no_ktp, alamat_asal, kamar_id, tanggal_masuk, tipe_sewa, jatuh_tempo_tanggal, status) VALUES
    (11, 'Joko Widodo', '086890123456', '3273012345670011', 'Solo', NULL, '" . date('Y-m-d', strtotime('-10 months')) . "', 'Bulanan', 15, 'Nonaktif')
");

// ============ PEMASUKAN (3 bulan terakhir) ============
$pemasukan_data = [];
$penyewa_harga = [
    1 => 1200000, 2 => 1200000, 3 => 1200000, 4 => 1200000,
    5 => 900000, 6 => 900000,
    7 => 2500000, 8 => 2500000, 9 => 1800000
];
$penyewa_kamar = [
    1 => [1,1], 2 => [2,1], 3 => [3,1], 4 => [4,1],
    5 => [5,1], 6 => [6,1],
    7 => [9,2], 8 => [10,2], 9 => [11,2]
];

foreach ([$bulan_lalu2, $bulan_lalu] as $bln) {
    foreach ($penyewa_harga as $pid => $harga) {
        $kamar_id = $penyewa_kamar[$pid][0];
        $prop_id = $penyewa_kamar[$pid][1];
        $tgl = $bln . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $db->exec("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, nominal, tanggal, periode_bulan, metode_bayar)
            VALUES ($prop_id, $pid, $kamar_id, 'Sewa', $harga, '$tgl', '$bln', '" . (rand(0,1) ? 'Tunai' : 'Transfer') . "')");
    }
}

// Bulan ini: hanya beberapa yang sudah bayar (4,5,6,7,8,9 sudah bayar; 1,2,3 belum — jadi muncul alert)
foreach ([4,5,6,7,8,9] as $pid) {
    $harga = $penyewa_harga[$pid];
    $kamar_id = $penyewa_kamar[$pid][0];
    $prop_id = $penyewa_kamar[$pid][1];
    $tgl = $bulan_ini . '-' . str_pad(rand(1, (int)date('d')), 2, '0', STR_PAD_LEFT);
    $db->exec("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, nominal, tanggal, periode_bulan, metode_bayar)
        VALUES ($prop_id, $pid, $kamar_id, 'Sewa', $harga, '$tgl', '$bulan_ini', 'Transfer')");
}

// Pemasukan lain-lain
$db->exec("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, keterangan, nominal, tanggal, periode_bulan, metode_bayar) VALUES
    (1, 2, 2, 'Denda', 'Denda telat bayar bulan lalu', 50000, '$bulan_lalu-15', '$bulan_lalu', 'Tunai'),
    (1, NULL, NULL, 'Laundry', 'Jasa laundry kos', 150000, '$bulan_lalu-20', '$bulan_lalu', 'Tunai'),
    (1, 5, 5, 'Parkir', 'Parkir motor tambahan', 50000, '$bulan_ini-05', '$bulan_ini', 'Tunai')
");

// ============ PENGELUARAN (3 bulan terakhir) ============
foreach ([$bulan_lalu2, $bulan_lalu, $bulan_ini] as $bln) {
    // PLN Kos Melati
    $db->exec("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal, no_meter, id_pelanggan) VALUES
        (1, 'PLN', 'Bayar listrik Kos Melati', " . rand(800000, 1200000) . ", '$bln-20', '541234567890', '12345678901')");
    // PLN Kontrakan
    $db->exec("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal, no_meter, id_pelanggan) VALUES
        (2, 'PLN', 'Bayar listrik Kontrakan Mawar', " . rand(600000, 900000) . ", '$bln-20', '541234567891', '12345678902')");
    // PDAM
    $db->exec("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal) VALUES
        (1, 'PDAM', 'Bayar air Kos Melati', " . rand(200000, 400000) . ", '$bln-15')");
    // Keamanan
    if ($bln !== $bulan_ini) {
        $db->exec("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal) VALUES
            (1, 'Keamanan', 'Iuran keamanan & sampah RT', 200000, '$bln-10')");
    }
}

// ============ MAINTENANCE ============
// Kamar 04 — kamar "rewel" (banyak perbaikan)
$db->exec("INSERT INTO maintenance (properti_id, kamar_id, tipe, judul, keterangan, biaya, tanggal, status) VALUES
    (1, 4, 'Spesifik', 'Ganti keran air',        'Keran bocor parah',              85000,  '$bulan_lalu2-10', 'Selesai'),
    (1, 4, 'Spesifik', 'Servis AC',              'AC tidak dingin, isi freon',     350000, '$bulan_lalu2-18', 'Selesai'),
    (1, 4, 'Spesifik', 'Ganti shower',           'Shower mati',                    120000, '$bulan_lalu-05',  'Selesai'),
    (1, 4, 'Spesifik', 'Perbaiki pintu',         'Engsel pintu kamar lepas',       75000,  '$bulan_lalu-22',  'Selesai'),
    (1, 4, 'Spesifik', 'Ganti lampu kamar mandi','Lampu mati',                     35000,  '$bulan_ini-03',   'Selesai')
");

// Kamar lain — normal
$db->exec("INSERT INTO maintenance (properti_id, kamar_id, tipe, judul, keterangan, biaya, tanggal, status) VALUES
    (1, 2, 'Spesifik', 'Ganti stop kontak',     'Stop kontak longgar',            45000,  '$bulan_lalu-12', 'Selesai'),
    (1, 8, 'Spesifik', 'Renovasi kamar mandi',  'Cat ulang & ganti kloset',       2500000,'$bulan_ini-01',  'Proses'),
    (2, 9, 'Spesifik', 'Perbaiki atap bocor',   'Bocor di dapur',                 500000, '$bulan_lalu-08', 'Selesai')
");

// Maintenance umum
$db->exec("INSERT INTO maintenance (properti_id, kamar_id, tipe, judul, keterangan, biaya, tanggal, status) VALUES
    (1, NULL, 'Umum', 'Perbaiki genteng bocor',   'Genteng pecah di lorong lantai 2', 450000, '$bulan_lalu2-25', 'Selesai'),
    (1, NULL, 'Umum', 'Beli sapu & alat kebersihan', NULL,                             85000,  '$bulan_lalu-01',  'Selesai'),
    (2, NULL, 'Umum', 'Cat pagar depan',           'Cat mengelupas',                   350000, '$bulan_ini-10',   'Selesai')
");

// ============ TEMPLATE TAGIHAN (rutin) ============
$db->exec("INSERT INTO template_tagihan (properti_id, jenis, keterangan, nominal_default, isi_manual, jatuh_tempo_tanggal, aktif) VALUES
    (1, 'PLN',      'No. Meter: 541234567890, ID: 12345678901', 0,      1, 20, 1),
    (2, 'PLN',      'No. Meter: 541234567891, ID: 12345678902', 0,      1, 20, 1),
    (1, 'PDAM',     'PDAM Kota Bandung',                        0,      1, 15, 1),
    (1, 'Keamanan', 'Iuran keamanan & sampah RT 05',            200000, 0, 10, 1)
");

// ============ TAGIHAN OPERASIONAL ============
// Bulan lalu — semua sudah bayar
$db->exec("INSERT INTO tagihan_operasional (properti_id, jenis, nominal, periode, jatuh_tempo, keterangan, tipe, status, tanggal_bayar) VALUES
    (1, 'PLN',      1050000, '$bulan_lalu', '$bulan_lalu-20', 'No. Meter: 541234567890', 'Rutin', 'Sudah Bayar', '$bulan_lalu-20'),
    (2, 'PLN',      750000,  '$bulan_lalu', '$bulan_lalu-20', 'No. Meter: 541234567891', 'Rutin', 'Sudah Bayar', '$bulan_lalu-21'),
    (1, 'PDAM',     320000,  '$bulan_lalu', '$bulan_lalu-15', 'PDAM Kota Bandung',       'Rutin', 'Sudah Bayar', '$bulan_lalu-15'),
    (1, 'Keamanan', 200000,  '$bulan_lalu', '$bulan_lalu-10', 'Iuran keamanan & sampah', 'Rutin', 'Sudah Bayar', '$bulan_lalu-10')
");

// Bulan ini — otomatis dari template
generateTagihanBulanan($db);

// ============ TANDAI DUMMY DATA ============
// Simpan flag bahwa ini data demo
$cek_demo = $db->querySingle("SELECT COUNT(*) FROM pengaturan WHERE kunci = 'is_demo'");
if (!$cek_demo) {
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('is_demo', '1')");
}

if (php_sapi_name() === 'cli') {
    echo "Selesai! Data dummy berhasil di-generate.\n";
    echo "Buka http://localhost:8080 untuk melihat hasilnya.\n";
}
