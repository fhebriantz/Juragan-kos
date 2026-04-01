-- ===================================================
-- Juragan Kos — MySQL Schema
-- ===================================================
-- Import file ini ke database MySQL Anda (InfinityFree, dll)
-- sebelum menjalankan aplikasi.
--
-- Cara import di InfinityFree:
-- 1. Masuk ke Control Panel → MySQL Databases → phpMyAdmin
-- 2. Pilih database yang sudah dibuat
-- 3. Klik tab Import → pilih file ini → klik Go
-- ===================================================

CREATE TABLE IF NOT EXISTS properti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(255) NOT NULL,
    alamat TEXT,
    tipe VARCHAR(50) NOT NULL DEFAULT 'Kos',
    jumlah_kamar INT DEFAULT 0,
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kamar (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penyewa (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pemasukan (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengeluaran (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS maintenance (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS template_tagihan (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tagihan_operasional (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengaturan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kunci VARCHAR(100) NOT NULL UNIQUE,
    nilai TEXT
) ENGINE=InnoDB;
