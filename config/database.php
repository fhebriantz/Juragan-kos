<?php
$db_path = __DIR__ . '/../database/juragan_kos.db';
$db = new SQLite3($db_path);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA foreign_keys = ON');

// ============ TABEL PROPERTI ============
$db->exec("CREATE TABLE IF NOT EXISTS properti (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    alamat TEXT,
    tipe TEXT NOT NULL DEFAULT 'Kos',
    jumlah_kamar INTEGER DEFAULT 0,
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ============ TABEL KAMAR ============
$db->exec("CREATE TABLE IF NOT EXISTS kamar (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER NOT NULL,
    nomor_kamar TEXT NOT NULL,
    fasilitas TEXT,
    harga_bulanan REAL NOT NULL DEFAULT 0,
    harga_tahunan REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'Kosong',
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id),
    UNIQUE(properti_id, nomor_kamar)
)");

// ============ TABEL PENYEWA ============
$db->exec("CREATE TABLE IF NOT EXISTS penyewa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    no_hp TEXT,
    no_ktp TEXT,
    foto_ktp TEXT,
    alamat_asal TEXT,
    kamar_id INTEGER,
    tanggal_masuk DATE NOT NULL,
    tanggal_keluar DATE,
    tipe_sewa TEXT NOT NULL DEFAULT 'Bulanan',
    harga_sewa REAL NOT NULL DEFAULT 0,
    jatuh_tempo_tanggal INTEGER NOT NULL DEFAULT 1,
    bayar_sampai DATE,
    status TEXT NOT NULL DEFAULT 'Aktif',
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id)
)");
// Migrasi kolom baru
$cols_p = [];
$rp = $db->query("PRAGMA table_info(penyewa)");
while ($c = $rp->fetchArray(SQLITE3_ASSOC)) { $cols_p[] = $c['name']; }
if (!in_array('bayar_sampai', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN bayar_sampai DATE");
if (!in_array('foto_ktp', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN foto_ktp TEXT");
if (!in_array('tanggal_keluar', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN tanggal_keluar DATE");
if (!in_array('harga_sewa', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN harga_sewa REAL NOT NULL DEFAULT 0");

// ============ TABEL PEMASUKAN ============
$db->exec("CREATE TABLE IF NOT EXISTS pemasukan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER,
    penyewa_id INTEGER,
    kamar_id INTEGER,
    kategori TEXT NOT NULL DEFAULT 'Sewa',
    keterangan TEXT,
    nominal REAL NOT NULL DEFAULT 0,
    tanggal DATE NOT NULL,
    periode_bulan TEXT,
    metode_bayar TEXT DEFAULT 'Tunai',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id),
    FOREIGN KEY (penyewa_id) REFERENCES penyewa(id),
    FOREIGN KEY (kamar_id) REFERENCES kamar(id)
)");

// ============ TABEL PENGELUARAN ============
$db->exec("CREATE TABLE IF NOT EXISTS pengeluaran (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER,
    kategori TEXT NOT NULL,
    sub_kategori TEXT,
    kamar_id INTEGER,
    keterangan TEXT,
    nominal REAL NOT NULL DEFAULT 0,
    tanggal DATE NOT NULL,
    no_meter TEXT,
    id_pelanggan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id),
    FOREIGN KEY (kamar_id) REFERENCES kamar(id)
)");

// ============ TABEL MAINTENANCE ============
$db->exec("CREATE TABLE IF NOT EXISTS maintenance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER,
    kamar_id INTEGER,
    tipe TEXT NOT NULL DEFAULT 'Spesifik',
    judul TEXT NOT NULL,
    keterangan TEXT,
    biaya REAL NOT NULL DEFAULT 0,
    tanggal DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'Selesai',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id),
    FOREIGN KEY (kamar_id) REFERENCES kamar(id)
)");

// ============ TABEL TEMPLATE TAGIHAN (rutin) ============
$db->exec("CREATE TABLE IF NOT EXISTS template_tagihan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER NOT NULL,
    jenis TEXT NOT NULL,
    keterangan TEXT,
    nominal_default REAL NOT NULL DEFAULT 0,
    isi_manual INTEGER NOT NULL DEFAULT 0,
    jatuh_tempo_tanggal INTEGER NOT NULL DEFAULT 1,
    aktif INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id),
    UNIQUE(properti_id, jenis)
)");

// ============ TABEL TAGIHAN OPERASIONAL ============
$db->exec("CREATE TABLE IF NOT EXISTS tagihan_operasional (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    properti_id INTEGER,
    jenis TEXT NOT NULL,
    nominal REAL NOT NULL DEFAULT 0,
    periode TEXT,
    jatuh_tempo DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'Belum Bayar',
    tanggal_bayar DATE,
    keterangan TEXT,
    tipe TEXT NOT NULL DEFAULT 'Rutin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properti_id) REFERENCES properti(id)
)");

// ============ TABEL PENGATURAN ============
$db->exec("CREATE TABLE IF NOT EXISTS pengaturan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kunci TEXT NOT NULL UNIQUE,
    nilai TEXT
)");

// Default pengaturan
$cek = $db->querySingle("SELECT COUNT(*) FROM pengaturan");
if ($cek == 0) {
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_usaha', 'Juragan Kos')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('no_hp_pemilik', '08123456789')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_pemilik', 'Nama Pemilik')");
}

function getPengaturan($db, $kunci) {
    $stmt = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci = :kunci");
    $stmt->bindValue(':kunci', $kunci, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray();
    return $row ? $row['nilai'] : '';
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $t = strtotime($tanggal);
    return date('d', $t) . ' ' . $bulan[(int)date('m', $t)] . ' ' . date('Y', $t);
}

// Helper: ambil semua properti untuk dropdown
function getPropertiList($db) {
    $result = $db->query("SELECT id, nama, tipe, alamat FROM properti ORDER BY nama");
    $list = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $list[] = $row;
    }
    return $list;
}

// Helper: auto-generate tagihan bulanan dari template
function generateTagihanBulanan($db) {
    $bulan_ini = date('Y-m');

    $templates = $db->query("SELECT * FROM template_tagihan WHERE aktif = 1");
    while ($t = $templates->fetchArray(SQLITE3_ASSOC)) {
        // Cek apakah sudah ada tagihan untuk jenis + properti + periode ini
        $stmt = $db->prepare("SELECT COUNT(*) FROM tagihan_operasional WHERE jenis = :j AND properti_id = :p AND periode = :per");
        $stmt->bindValue(':j', $t['jenis'], SQLITE3_TEXT);
        $stmt->bindValue(':p', $t['properti_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':per', $bulan_ini, SQLITE3_TEXT);
        $sudah_ada = $stmt->execute()->fetchArray()[0];

        if (!$sudah_ada) {
            $tgl_jt = (int)$t['jatuh_tempo_tanggal'];
            $max_hari = (int)date('t', strtotime($bulan_ini . '-01'));
            if ($tgl_jt > $max_hari) $tgl_jt = $max_hari;
            $jatuh_tempo = $bulan_ini . '-' . str_pad($tgl_jt, 2, '0', STR_PAD_LEFT);

            // Jika isi_manual = 1, nominal = 0 (user isi nanti). Jika 0, pakai nominal_default.
            $nominal = $t['isi_manual'] ? 0 : $t['nominal_default'];

            $ins = $db->prepare("INSERT INTO tagihan_operasional (properti_id, jenis, nominal, periode, jatuh_tempo, keterangan, tipe, status) VALUES (:prop, :jenis, :nom, :per, :jt, :ket, 'Rutin', 'Belum Bayar')");
            $ins->bindValue(':prop', $t['properti_id'], SQLITE3_INTEGER);
            $ins->bindValue(':jenis', $t['jenis'], SQLITE3_TEXT);
            $ins->bindValue(':nom', $nominal, SQLITE3_INTEGER);
            $ins->bindValue(':per', $bulan_ini, SQLITE3_TEXT);
            $ins->bindValue(':jt', $jatuh_tempo, SQLITE3_TEXT);
            $ins->bindValue(':ket', $t['keterangan'], SQLITE3_TEXT);
            $ins->execute();
        }
    }
}

// Helper: ambil kamar berdasarkan properti
function getKamarByProperti($db, $properti_id = null) {
    $sql = "SELECT k.id, k.nomor_kamar, k.status, k.harga_bulanan, p.nama as nama_properti, p.id as properti_id
            FROM kamar k
            JOIN properti p ON k.properti_id = p.id";
    if ($properti_id) {
        $sql .= " WHERE k.properti_id = $properti_id";
    }
    $sql .= " ORDER BY p.nama, k.nomor_kamar";
    $result = $db->query($sql);
    $list = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $list[] = $row;
    }
    return $list;
}
