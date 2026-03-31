<?php
$page_title = 'Pengaturan';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$pesan = '';
$pesan_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'simpan';

    if ($action === 'simpan') {
        $fields = ['nama_usaha', 'no_hp_pemilik', 'nama_pemilik'];
        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            $stmt = $db->prepare("INSERT OR REPLACE INTO pengaturan (id, kunci, nilai) VALUES ((SELECT id FROM pengaturan WHERE kunci = :k), :k, :v)");
            $stmt->bindValue(':k', $field, SQLITE3_TEXT);
            $stmt->bindValue(':v', $value, SQLITE3_TEXT);
            $stmt->execute();
        }
        header('Location: pengaturan.php?pesan=sukses');
        exit;
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
            <form method="POST">
                <input type="hidden" name="action" value="simpan">
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

    <!-- Reset Data -->
    <div class="col-lg-6">
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

            <form method="POST" id="formResetTransaksi">
                <input type="hidden" name="action" value="reset_transaksi">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ketik <code>HAPUS-TRANSAKSI</code> untuk konfirmasi:</label>
                    <input type="text" name="kode_konfirmasi" class="form-control" placeholder="HAPUS-TRANSAKSI" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-warning" onclick="return confirm('Yakin ingin menghapus SEMUA data transaksi? Aksi ini tidak bisa dibatalkan!')">
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

            <form method="POST" id="formResetDatabase">
                <input type="hidden" name="action" value="reset_database">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ketik <code>HAPUS-SEMUA</code> untuk konfirmasi:</label>
                    <input type="text" name="kode_konfirmasi" class="form-control" placeholder="HAPUS-SEMUA" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('PERINGATAN: Semua data akan dihapus permanen!\n\nProperti, Kamar, Penyewa, dan semua transaksi akan hilang.\n\nYakin ingin melanjutkan?')">
                    <i class="bi bi-trash3 me-1"></i> Reset Seluruh Database
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
