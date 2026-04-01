<?php
$page_title = 'Pengaturan';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$pesan = '';
$pesan_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'simpan';

    if ($action === 'simpan') {
        $fields = ['nama_usaha', 'no_hp_pemilik', 'nama_pemilik', 'warna_sidebar', 'mode_font_sidebar'];
        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            $stmt = $db->prepare("INSERT OR REPLACE INTO pengaturan (id, kunci, nilai) VALUES ((SELECT id FROM pengaturan WHERE kunci = :k), :k, :v)");
            $stmt->bindValue(':k', $field, SQLITE3_TEXT);
            $stmt->bindValue(':v', $value, SQLITE3_TEXT);
            $stmt->execute();
        }

        // Upload logo
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../uploads/logo/' . $filename;

                // Hapus logo lama
                $logo_lama = getPengaturan($db, 'logo');
                if ($logo_lama) {
                    $path_lama = __DIR__ . '/../uploads/logo/' . $logo_lama;
                    if (file_exists($path_lama)) unlink($path_lama);
                }

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $stmt = $db->prepare("INSERT OR REPLACE INTO pengaturan (id, kunci, nilai) VALUES ((SELECT id FROM pengaturan WHERE kunci = 'logo'), 'logo', :v)");
                    $stmt->bindValue(':v', $filename, SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }

        // Hapus logo jika diminta
        if (!empty($_POST['hapus_logo'])) {
            $logo_lama = getPengaturan($db, 'logo');
            if ($logo_lama) {
                $path_lama = __DIR__ . '/../uploads/logo/' . $logo_lama;
                if (file_exists($path_lama)) unlink($path_lama);
            }
            $db->exec("DELETE FROM pengaturan WHERE kunci = 'logo'");
        }

        header('Location: pengaturan.php?pesan=sukses');
        exit;
    }

    if ($action === 'isi_demo') {
        $ada_data = $db->querySingle("SELECT COUNT(*) FROM properti");
        if ($ada_data > 0) {
            $pesan = 'Database masih berisi data. Reset seluruh database terlebih dahulu sebelum mengisi data demo.';
            $pesan_type = 'danger';
        } else {
            // Hapus flag sudah_reset agar seed bisa jalan
            $db->exec("DELETE FROM pengaturan WHERE kunci = 'sudah_reset'");
            require_once __DIR__ . '/../seed.php';
            header('Location: pengaturan.php?pesan=demo_diisi');
            exit;
        }
    }

    // Validasi kode konfirmasi
    $kode = trim($_POST['kode_konfirmasi'] ?? '');

    if ($action === 'reset_transaksi') {
        if ($kode !== 'HAPUS-TRANSAKSI') {
            $pesan = 'Kode konfirmasi salah! Ketik HAPUS-TRANSAKSI untuk melanjutkan.';
            $pesan_type = 'danger';
        } else {
            $db->exec("DELETE FROM pemasukan");
            $db->exec("DELETE FROM pengeluaran");
            $db->exec("DELETE FROM maintenance");
            $db->exec("DELETE FROM tagihan_operasional");
            // Reset auto-increment
            $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('pemasukan','pengeluaran','maintenance','tagihan_operasional')");
            header('Location: pengaturan.php?pesan=reset_transaksi');
            exit;
        }
    }

    if ($action === 'reset_database') {
        if ($kode !== 'HAPUS-SEMUA') {
            $pesan = 'Kode konfirmasi salah! Ketik HAPUS-SEMUA untuk melanjutkan.';
            $pesan_type = 'danger';
        } else {
            $db->exec("DELETE FROM pemasukan");
            $db->exec("DELETE FROM pengeluaran");
            $db->exec("DELETE FROM maintenance");
            $db->exec("DELETE FROM tagihan_operasional");
            $db->exec("DELETE FROM template_tagihan");
            $db->exec("DELETE FROM penyewa");
            $db->exec("DELETE FROM kamar");
            $db->exec("DELETE FROM properti");
            // Reset semua auto-increment kecuali pengaturan
            $db->exec("DELETE FROM sqlite_sequence WHERE name != 'pengaturan'");
            // Hapus flag demo & tandai sudah pernah reset
            $db->exec("DELETE FROM pengaturan WHERE kunci = 'is_demo'");
            $db->exec("INSERT OR REPLACE INTO pengaturan (id, kunci, nilai) VALUES ((SELECT id FROM pengaturan WHERE kunci = 'sudah_reset'), 'sudah_reset', '1')");
            header('Location: pengaturan.php?pesan=reset_database');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
        echo match($_GET['pesan']) {
            'reset_transaksi' => '<i class="bi bi-check-circle me-1"></i> Semua data transaksi (pemasukan, pengeluaran, maintenance, tagihan operasional) berhasil dihapus!',
            'reset_database' => '<i class="bi bi-check-circle me-1"></i> Semua data berhasil dihapus! Database kembali kosong.',
            'demo_diisi' => '<i class="bi bi-check-circle me-1"></i> Data demo berhasil diisi berdasarkan bulan ' . date('F Y') . '! Buka <a href="../index.php" class="alert-link">Dashboard</a> untuk melihat hasilnya.',
            default => '<i class="bi bi-check-circle me-1"></i> Pengaturan berhasil disimpan!'
        };
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($pesan): ?>
    <div class="alert alert-<?= $pesan_type ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($pesan) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center g-4">
    <!-- Pengaturan Umum -->
    <div class="col-lg-6">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i>Pengaturan Umum</h6>
            <p class="text-muted small mb-3">Pengaturan identitas pemilik. Untuk nama & alamat per lokasi, kelola di menu <a href="properti.php">Properti</a>.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="simpan">
                <div class="mb-3">
                    <label class="form-label">Logo Usaha</label>
                    <?php $logo_saat_ini = getPengaturan($db, 'logo'); ?>
                    <?php if ($logo_saat_ini): ?>
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <img src="../uploads/logo/<?= htmlspecialchars($logo_saat_ini) ?>" alt="Logo" style="max-height: 64px; max-width: 120px; border-radius: 8px; object-fit: contain; background: #f0f0f0; padding: 4px;">
                            <label class="btn btn-sm btn-outline-danger">
                                <input type="checkbox" name="hapus_logo" value="1" class="d-none" onchange="this.closest('form').querySelector('[name=logo]').value=''; this.closest('.d-flex').style.display='none';">
                                <i class="bi bi-trash"></i> Hapus
                            </label>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                    <small class="text-muted">Format: PNG, JPG, WebP. Maks 2 MB. Ditampilkan di sidebar.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Usaha</label>
                    <input type="text" name="nama_usaha" class="form-control" value="<?= htmlspecialchars(getPengaturan($db, 'nama_usaha')) ?>">
                    <small class="text-muted">Ditampilkan di sidebar & header</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Pemilik/Pengelola</label>
                    <input type="text" name="nama_pemilik" class="form-control" value="<?= htmlspecialchars(getPengaturan($db, 'nama_pemilik')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP Pemilik</label>
                    <input type="text" name="no_hp_pemilik" class="form-control" value="<?= htmlspecialchars(getPengaturan($db, 'no_hp_pemilik')) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Pengaturan</button>
            </form>
        </div>
    </div>

    <!-- Warna Sidebar -->
    <div class="col-lg-6">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><i class="bi bi-palette me-2"></i>Warna Sidebar</h6>
            <p class="text-muted small mb-3">Pilih warna preset atau gunakan color picker untuk menyesuaikan tampilan sidebar.</p>
            <?php $warna_sidebar_saat_ini = getPengaturan($db, 'warna_sidebar') ?: '#212529'; ?>
            <div class="d-flex flex-wrap gap-2 mb-3" id="presetWarna">
                <?php
                $presets = [
                    '#212529' => 'Gelap',
                    '#1a1a2e' => 'Navy Gelap',
                    '#0f3460' => 'Biru Tua',
                    '#4e73df' => 'Biru',
                    '#1cc88a' => 'Hijau',
                    '#6f42c1' => 'Ungu',
                    '#e74a3b' => 'Merah',
                    '#fd7e14' => 'Oranye',
                    '#20c997' => 'Teal',
                    '#6610f2' => 'Indigo',
                    '#495057' => 'Abu-abu',
                    '#2c3e50' => 'Midnight',
                ];
                foreach ($presets as $hex => $nama): ?>
                    <button type="button" class="btn-preset-warna <?= $warna_sidebar_saat_ini === $hex ? 'active' : '' ?>"
                            data-warna="<?= $hex ?>" title="<?= $nama ?>">
                        <span style="background-color: <?= $hex ?>"></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <form method="POST" id="formWarnaSidebar">
                <input type="hidden" name="action" value="simpan">
                <input type="hidden" name="nama_usaha" value="<?= htmlspecialchars(getPengaturan($db, 'nama_usaha')) ?>">
                <input type="hidden" name="nama_pemilik" value="<?= htmlspecialchars(getPengaturan($db, 'nama_pemilik')) ?>">
                <input type="hidden" name="no_hp_pemilik" value="<?= htmlspecialchars(getPengaturan($db, 'no_hp_pemilik')) ?>">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <label class="form-label mb-0">Warna Kustom</label>
                    <input type="color" name="warna_sidebar" id="inputWarnaSidebar" class="form-control form-control-color" value="<?= htmlspecialchars($warna_sidebar_saat_ini) ?>">
                    <code id="kodeWarnaSidebar"><?= htmlspecialchars($warna_sidebar_saat_ini) ?></code>
                </div>
                <?php $mode_font_saat_ini = getPengaturan($db, 'mode_font_sidebar') ?: 'light'; ?>
                <div class="mb-3">
                    <label class="form-label mb-2">Mode Font Sidebar</label>
                    <div class="d-flex gap-2" id="modeFontSidebar">
                        <button type="button" class="btn btn-sm <?= $mode_font_saat_ini === 'light' ? 'btn-dark' : 'btn-outline-dark' ?>" data-mode="light" onclick="setModeFont('light')">
                            <i class="bi bi-brightness-high me-1"></i> Light <small class="opacity-75">(bg gelap)</small>
                        </button>
                        <button type="button" class="btn btn-sm <?= $mode_font_saat_ini === 'dark' ? 'btn-secondary' : 'btn-outline-secondary' ?>" data-mode="dark" onclick="setModeFont('dark')">
                            <i class="bi bi-moon me-1"></i> Dark <small class="opacity-75">(bg terang)</small>
                        </button>
                    </div>
                    <input type="hidden" name="mode_font_sidebar" id="inputModeFont" value="<?= htmlspecialchars($mode_font_saat_ini) ?>">
                </div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="small text-muted">Pratinjau:</span>
                    <div id="pratinjauSidebar" style="width: 100%; height: 40px; border-radius: 8px; background-color: <?= htmlspecialchars($warna_sidebar_saat_ini) ?>; display: flex; align-items: center; padding: 0 12px;">
                        <span id="pratinjauFont" style="color: <?= $mode_font_saat_ini === 'dark' ? '#212529' : 'white' ?>; font-size: 0.85rem;"><i class="bi bi-house-door-fill me-2"></i>Contoh Menu Sidebar</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Warna</button>
            </form>
        </div>
    </div>

    <!-- Tentang & Reset Data -->
    <div class="col-lg-6">
        <!-- Tentang Aplikasi -->
        <div class="form-wrapper mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Tentang Aplikasi</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted" style="width:140px;">Aplikasi</td>
                    <td><strong>Juragan Kos</strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Versi</td>
                    <td>1.0.0</td>
                </tr>
                <tr>
                    <td class="text-muted">Deskripsi</td>
                    <td>Sistem manajemen kos &amp; kontrakan</td>
                </tr>
                <tr>
                    <td class="text-muted">Developer</td>
                    <td>Lutfi Febrianto</td>
                </tr>
                <tr>
                    <td class="text-muted">Teknologi</td>
                    <td>PHP, SQLite, Bootstrap 5</td>
                </tr>
            </table>
        </div>

        <!-- Isi Data Demo -->
        <?php $db_kosong = $db->querySingle("SELECT COUNT(*) FROM properti") == 0; ?>
        <div class="form-wrapper mb-4 border border-info">
            <h6 class="fw-bold mb-1 text-info"><i class="bi bi-box-seam me-2"></i>Isi Data Demo</h6>
            <p class="text-muted small mb-3">
                Mengisi database dengan data contoh (2 properti, 12 kamar, 10 penyewa, transaksi 3 bulan terakhir, dll)
                berdasarkan bulan aktif <strong><?= date('F Y') ?></strong>.
                Cocok untuk menjelajahi fitur aplikasi sebelum menggunakan data nyata.
            </p>
            <?php if ($db_kosong): ?>
                <form method="POST" data-confirm="Database akan diisi dengan data demo berdasarkan bulan <?= date('F Y') ?>. Lanjutkan?">
                    <input type="hidden" name="action" value="isi_demo">
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-box-seam me-1"></i> Isi Data Demo
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning py-2 mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Database masih berisi data. <strong>Reset Seluruh Database</strong> terlebih dahulu untuk mengisi data demo.
                </div>
            <?php endif; ?>
        </div>

        <!-- Reset Transaksi -->
        <div class="form-wrapper mb-4 border border-warning">
            <h6 class="fw-bold mb-1 text-warning"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset Data Transaksi</h6>
            <p class="text-muted small mb-3">Menghapus semua data: <strong>Pemasukan, Pengeluaran, Maintenance, dan Tagihan Operasional</strong>. Data Properti, Kamar, dan Penyewa tetap aman.</p>

            <?php
            $jml_pemasukan = $db->querySingle("SELECT COUNT(*) FROM pemasukan");
            $jml_pengeluaran = $db->querySingle("SELECT COUNT(*) FROM pengeluaran");
            $jml_maintenance = $db->querySingle("SELECT COUNT(*) FROM maintenance");
            $jml_tagihan = $db->querySingle("SELECT COUNT(*) FROM tagihan_operasional");
            ?>
            <div class="row g-2 mb-3">
                <div class="col-6"><span class="badge bg-light text-dark w-100 py-2"><?= $jml_pemasukan ?> Pemasukan</span></div>
                <div class="col-6"><span class="badge bg-light text-dark w-100 py-2"><?= $jml_pengeluaran ?> Pengeluaran</span></div>
                <div class="col-6"><span class="badge bg-light text-dark w-100 py-2"><?= $jml_maintenance ?> Maintenance</span></div>
                <div class="col-6"><span class="badge bg-light text-dark w-100 py-2"><?= $jml_tagihan ?> Tagihan Ops</span></div>
            </div>

            <form method="POST" id="formResetTransaksi" data-confirm="Yakin ingin menghapus SEMUA data transaksi? Aksi ini tidak bisa dibatalkan!">
                <input type="hidden" name="action" value="reset_transaksi">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ketik <code>HAPUS-TRANSAKSI</code> untuk konfirmasi:</label>
                    <input type="text" name="kode_konfirmasi" class="form-control" placeholder="HAPUS-TRANSAKSI" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Transaksi
                </button>
            </form>
        </div>

        <!-- Reset Database -->
        <div class="form-wrapper border border-danger">
            <h6 class="fw-bold mb-1 text-danger"><i class="bi bi-trash3 me-2"></i>Reset Seluruh Database</h6>
            <p class="text-muted small mb-3">Menghapus <strong>SEMUA data</strong> termasuk Properti, Kamar, Penyewa, dan seluruh transaksi. Hanya Pengaturan Umum yang dipertahankan. <strong class="text-danger">Tidak bisa dibatalkan!</strong></p>

            <?php
            $jml_properti = $db->querySingle("SELECT COUNT(*) FROM properti");
            $jml_kamar = $db->querySingle("SELECT COUNT(*) FROM kamar");
            $jml_penyewa = $db->querySingle("SELECT COUNT(*) FROM penyewa");
            $total_data = $jml_properti + $jml_kamar + $jml_penyewa + $jml_pemasukan + $jml_pengeluaran + $jml_maintenance + $jml_tagihan;
            ?>
            <div class="alert alert-danger py-2 mb-3 small">
                <i class="bi bi-database me-1"></i> Total data yang akan dihapus: <strong><?= $total_data ?> record</strong>
                (<?= $jml_properti ?> properti, <?= $jml_kamar ?> kamar, <?= $jml_penyewa ?> penyewa, <?= $jml_pemasukan + $jml_pengeluaran + $jml_maintenance + $jml_tagihan ?> transaksi)
            </div>

            <form method="POST" id="formResetDatabase" data-confirm="PERINGATAN: Semua data akan dihapus permanen!\n\nProperti, Kamar, Penyewa, dan semua transaksi akan hilang.\n\nYakin ingin melanjutkan?">
                <input type="hidden" name="action" value="reset_database">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ketik <code>HAPUS-SEMUA</code> untuk konfirmasi:</label>
                    <input type="text" name="kode_konfirmasi" class="form-control" placeholder="HAPUS-SEMUA" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3 me-1"></i> Reset Seluruh Database
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
