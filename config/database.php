<?php
// ===== DETEKSI MODE DATABASE =====
// Jika file config/mysql.php ada → pakai MySQL (untuk hosting online)
// Jika tidak ada → pakai SQLite (untuk PHPDesktop / lokal)
$db_driver = 'sqlite';
if (file_exists(__DIR__ . '/mysql.php')) {
    $db_driver = 'mysql';
    require_once __DIR__ . '/mysql.php';
}

// ===== KONEKSI DATABASE (PDO) =====
if ($db_driver === 'mysql') {
    $db = new PDO(
        "mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8mb4",
        $mysql_user, $mysql_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} else {
    $db_path = __DIR__ . '/../database/juragan_kos.db';
    $db = new PDO("sqlite:$db_path", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA foreign_keys = ON');
}

// ===== HELPER FUNCTIONS =====

// Query single value (pengganti SQLite3::querySingle tanpa param kedua)
function dbValue($sql) {
    global $db;
    $stmt = $db->query($sql);
    $val = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $val;
}

// Query single row (pengganti SQLite3::querySingle dengan true)
function dbRow($sql) {
    global $db;
    $stmt = $db->query($sql);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return $row;
}

// SQL year-month extraction (kompatibel SQLite & MySQL)
function sqlYearMonth($field) {
    global $db_driver;
    return $db_driver === 'mysql' ? "DATE_FORMAT($field, '%Y-%m')" : "strftime('%Y-%m', $field)";
}

// SQL year extraction
function sqlYear($field) {
    global $db_driver;
    return $db_driver === 'mysql' ? "DATE_FORMAT($field, '%Y')" : "strftime('%Y', $field)";
}

// Escape string untuk SQL (prefer prepared statements)
function dbEscape($val) {
    global $db;
    $q = $db->quote($val);
    return substr($q, 1, -1);
}

// Upsert pengaturan (INSERT OR REPLACE compatible)
function upsertPengaturan($db, $kunci, $nilai) {
    global $db_driver;
    if ($db_driver === 'mysql') {
        $stmt = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (:k, :v) ON DUPLICATE KEY UPDATE nilai = :v2");
        $stmt->bindValue(':v2', $nilai, PDO::PARAM_STR);
    } else {
        $stmt = $db->prepare("INSERT OR REPLACE INTO pengaturan (id, kunci, nilai) VALUES ((SELECT id FROM pengaturan WHERE kunci = :k), :k, :v)");
    }
    $stmt->bindValue(':k', $kunci, PDO::PARAM_STR);
    $stmt->bindValue(':v', $nilai, PDO::PARAM_STR);
    $stmt->execute();
}

// Reset auto-increment untuk tabel
function resetAutoIncrement($db, $tables) {
    global $db_driver;
    if ($db_driver === 'mysql') {
        foreach ($tables as $t) {
            $db->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
        }
    } else {
        $names = "'" . implode("','", $tables) . "'";
        $db->exec("DELETE FROM sqlite_sequence WHERE name IN ($names)");
    }
}

// INSERT OR IGNORE compatible
function sqlInsertIgnore() {
    global $db_driver;
    return $db_driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
}

// ===== CREATE TABLES =====
if ($db_driver === 'mysql') {
    $db->exec("CREATE TABLE IF NOT EXISTS properti (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama VARCHAR(255) NOT NULL,
        alamat TEXT,
        tipe VARCHAR(50) NOT NULL DEFAULT 'Kos',
        jumlah_kamar INT DEFAULT 0,
        catatan TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS kamar (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT NOT NULL,
        nomor_kamar VARCHAR(50) NOT NULL,
        fasilitas TEXT,
        harga_bulanan DOUBLE NOT NULL DEFAULT 0,
        harga_tahunan DOUBLE NOT NULL DEFAULT 0,
        status VARCHAR(50) NOT NULL DEFAULT 'Kosong',
        catatan TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id),
        UNIQUE KEY uq_kamar (properti_id, nomor_kamar)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS penyewa (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama VARCHAR(255) NOT NULL,
        no_hp VARCHAR(50),
        no_ktp VARCHAR(50),
        foto_ktp VARCHAR(255),
        alamat_asal TEXT,
        kamar_id INT,
        tanggal_masuk DATE NOT NULL,
        tanggal_keluar DATE,
        tipe_sewa VARCHAR(50) NOT NULL DEFAULT 'Bulanan',
        harga_sewa DOUBLE NOT NULL DEFAULT 0,
        jatuh_tempo_tanggal INT NOT NULL DEFAULT 1,
        bayar_sampai DATE,
        status VARCHAR(50) NOT NULL DEFAULT 'Aktif',
        catatan TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kamar_id) REFERENCES kamar(id)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS pemasukan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT,
        penyewa_id INT,
        kamar_id INT,
        kategori VARCHAR(100) NOT NULL DEFAULT 'Sewa',
        keterangan TEXT,
        nominal DOUBLE NOT NULL DEFAULT 0,
        tanggal DATE NOT NULL,
        periode_bulan VARCHAR(20),
        metode_bayar VARCHAR(50) DEFAULT 'Tunai',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id),
        FOREIGN KEY (penyewa_id) REFERENCES penyewa(id),
        FOREIGN KEY (kamar_id) REFERENCES kamar(id)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS pengeluaran (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT,
        kategori VARCHAR(100) NOT NULL,
        sub_kategori VARCHAR(100),
        kamar_id INT,
        keterangan TEXT,
        nominal DOUBLE NOT NULL DEFAULT 0,
        tanggal DATE NOT NULL,
        no_meter VARCHAR(100),
        id_pelanggan VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id),
        FOREIGN KEY (kamar_id) REFERENCES kamar(id)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS maintenance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT,
        kamar_id INT,
        tipe VARCHAR(50) NOT NULL DEFAULT 'Spesifik',
        judul VARCHAR(255) NOT NULL,
        keterangan TEXT,
        biaya DOUBLE NOT NULL DEFAULT 0,
        tanggal DATE NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Selesai',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id),
        FOREIGN KEY (kamar_id) REFERENCES kamar(id)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS template_tagihan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT NOT NULL,
        jenis VARCHAR(100) NOT NULL,
        keterangan TEXT,
        nominal_default DOUBLE NOT NULL DEFAULT 0,
        isi_manual TINYINT NOT NULL DEFAULT 0,
        jatuh_tempo_tanggal INT NOT NULL DEFAULT 1,
        aktif TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id),
        UNIQUE KEY uq_template (properti_id, jenis)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS tagihan_operasional (
        id INT PRIMARY KEY AUTO_INCREMENT,
        properti_id INT,
        jenis VARCHAR(100) NOT NULL,
        nominal DOUBLE NOT NULL DEFAULT 0,
        periode VARCHAR(20),
        jatuh_tempo DATE NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Belum Bayar',
        tanggal_bayar DATE,
        keterangan TEXT,
        tipe VARCHAR(50) NOT NULL DEFAULT 'Rutin',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (properti_id) REFERENCES properti(id)
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kunci VARCHAR(100) NOT NULL UNIQUE,
        nilai TEXT
    ) ENGINE=InnoDB");
} else {
    // ============ SQLite DDL (original) ============
    $db->exec("CREATE TABLE IF NOT EXISTS properti (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama TEXT NOT NULL,
        alamat TEXT,
        tipe TEXT NOT NULL DEFAULT 'Kos',
        jumlah_kamar INTEGER DEFAULT 0,
        catatan TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

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

    $db->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kunci TEXT NOT NULL UNIQUE,
        nilai TEXT
    )");

    // Migrasi kolom baru (SQLite only)
    $cols_p = [];
    $rp = $db->query("PRAGMA table_info(penyewa)");
    while ($c = $rp->fetch()) { $cols_p[] = $c['name']; }
    if (!in_array('bayar_sampai', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN bayar_sampai DATE");
    if (!in_array('foto_ktp', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN foto_ktp TEXT");
    if (!in_array('tanggal_keluar', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN tanggal_keluar DATE");
    if (!in_array('harga_sewa', $cols_p)) $db->exec("ALTER TABLE penyewa ADD COLUMN harga_sewa REAL NOT NULL DEFAULT 0");
}

// Default pengaturan
$cek = dbValue("SELECT COUNT(*) FROM pengaturan");
if ($cek == 0) {
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_usaha', 'Juragan Kos')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('no_hp_pemilik', '08123456789')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_pemilik', 'Nama Pemilik')");
}

function getPengaturan($db, $kunci) {
    $stmt = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci = :kunci");
    $stmt->bindValue(':kunci', $kunci, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
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
    return $result->fetchAll();
}

// Helper: auto-generate tagihan bulanan dari template
function generateTagihanBulanan($db) {
    $bulan_ini = date('Y-m');

    $templates = $db->query("SELECT * FROM template_tagihan WHERE aktif = 1")->fetchAll();
    foreach ($templates as $t) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tagihan_operasional WHERE jenis = :j AND properti_id = :p AND periode = :per");
        $stmt->bindValue(':j', $t['jenis'], PDO::PARAM_STR);
        $stmt->bindValue(':p', $t['properti_id'], PDO::PARAM_INT);
        $stmt->bindValue(':per', $bulan_ini, PDO::PARAM_STR);
        $stmt->execute();
        $sudah_ada = $stmt->fetchColumn();

        if (!$sudah_ada) {
            $tgl_jt = (int)$t['jatuh_tempo_tanggal'];
            $max_hari = (int)date('t', strtotime($bulan_ini . '-01'));
            if ($tgl_jt > $max_hari) $tgl_jt = $max_hari;
            $jatuh_tempo = $bulan_ini . '-' . str_pad($tgl_jt, 2, '0', STR_PAD_LEFT);

            $nominal = $t['isi_manual'] ? 0 : $t['nominal_default'];

            $ins = $db->prepare("INSERT INTO tagihan_operasional (properti_id, jenis, nominal, periode, jatuh_tempo, keterangan, tipe, status) VALUES (:prop, :jenis, :nom, :per, :jt, :ket, 'Rutin', 'Belum Bayar')");
            $ins->bindValue(':prop', $t['properti_id'], PDO::PARAM_INT);
            $ins->bindValue(':jenis', $t['jenis'], PDO::PARAM_STR);
            $ins->bindValue(':nom', $nominal, PDO::PARAM_INT);
            $ins->bindValue(':per', $bulan_ini, PDO::PARAM_STR);
            $ins->bindValue(':jt', $jatuh_tempo, PDO::PARAM_STR);
            $ins->bindValue(':ket', $t['keterangan'], PDO::PARAM_STR);
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
        $sql .= " WHERE k.properti_id = " . (int)$properti_id;
    }
    $sql .= " ORDER BY p.nama, k.nomor_kamar";
    return $db->query($sql)->fetchAll();
}
