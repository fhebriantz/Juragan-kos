<?php
$page_title = 'Properti';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama = trim($_POST['nama'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $tipe = $_POST['tipe'] ?? 'Kos';
        $catatan = trim($_POST['catatan'] ?? '');

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO properti (nama, alamat, tipe, catatan) VALUES (:nama, :alamat, :tipe, :catatan)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE properti SET nama = :nama, alamat = :alamat, tipe = :tipe, catatan = :catatan WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':nama', $nama, PDO::PARAM_STR);
        $stmt->bindValue(':alamat', $alamat, PDO::PARAM_STR);
        $stmt->bindValue(':tipe', $tipe, PDO::PARAM_STR);
        $stmt->bindValue(':catatan', $catatan, PDO::PARAM_STR);
        $stmt->execute();

        header('Location: properti.php?pesan=sukses');
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        // Cek apakah ada kamar di properti ini
        $jml_kamar = dbValue("SELECT COUNT(*) FROM kamar WHERE properti_id = $id");
        if ($jml_kamar > 0) {
            header('Location: properti.php?pesan=gagal_hapus');
            exit;
        }
        // Lepas referensi di tabel child agar tidak kena foreign key constraint
        $db->exec("UPDATE pemasukan SET properti_id = NULL WHERE properti_id = $id");
        $db->exec("UPDATE pengeluaran SET properti_id = NULL WHERE properti_id = $id");
        $db->exec("UPDATE maintenance SET properti_id = NULL WHERE properti_id = $id");
        $db->exec("DELETE FROM template_tagihan WHERE properti_id = $id");
        $db->exec("DELETE FROM tagihan_operasional WHERE properti_id = $id");
        $db->exec("DELETE FROM properti WHERE id = $id");
        header('Location: properti.php?pesan=dihapus');
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM properti WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, PDO::PARAM_INT);
    $stmt->execute();
    $edit_data = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <?php if ($_GET['pesan'] === 'gagal_hapus'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Tidak bisa menghapus properti yang masih memiliki kamar! Hapus semua kamar terlebih dahulu.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php else: ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Data properti berhasil <?= $_GET['pesan'] === 'dihapus' ? 'dihapus' : 'disimpan' ?>!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit_data ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $edit_data ? 'Edit Properti' : 'Tambah Properti' ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nama Properti <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>" placeholder="Contoh: Kos Melati, Kontrakan Jl. Mawar">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap properti"><?= htmlspecialchars($edit_data['alamat'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe</label>
                    <select name="tipe" class="form-select">
                        <option value="Kos" <?= ($edit_data['tipe'] ?? '') == 'Kos' ? 'selected' : '' ?>>Kos</option>
                        <option value="Kontrakan" <?= ($edit_data['tipe'] ?? '') == 'Kontrakan' ? 'selected' : '' ?>>Kontrakan</option>
                        <option value="Apartemen" <?= ($edit_data['tipe'] ?? '') == 'Apartemen' ? 'selected' : '' ?>>Apartemen</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['catatan'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="properti.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Properti -->
    <div class="col-lg-8">
        <div class="row g-3">
            <?php
            $properti_list = $db->query("SELECT * FROM properti ORDER BY nama");
            $ada = false;
            while ($row = $properti_list->fetch()):
                $ada = true;
                $jml_kamar = dbValue("SELECT COUNT(*) FROM kamar WHERE properti_id = {$row['id']}");
                $jml_terisi = dbValue("SELECT COUNT(*) FROM kamar WHERE properti_id = {$row['id']} AND status = 'Terisi'");
                $jml_kosong = dbValue("SELECT COUNT(*) FROM kamar WHERE properti_id = {$row['id']} AND status = 'Kosong'");
                $jml_mt = dbValue("SELECT COUNT(*) FROM kamar WHERE properti_id = {$row['id']} AND status = 'Maintenance'");
                $pemasukan = dbValue("SELECT COALESCE(SUM(nominal),0) FROM pemasukan WHERE properti_id = {$row['id']} AND " . sqlYearMonth('tanggal') . " = '" . date('Y-m') . "'");
            ?>
            <div class="col-md-6">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($row['nama']) ?></h6>
                                <span class="badge bg-<?= $row['tipe'] == 'Kos' ? 'primary' : ($row['tipe'] == 'Kontrakan' ? 'success' : 'info') ?> badge-status mt-1"><?= $row['tipe'] ?></span>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="properti.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <?php if ($row['alamat']): ?>
                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['alamat']) ?></p>
                        <?php endif; ?>
                        <div class="row g-2 mt-2">
                            <div class="col-4 text-center">
                                <div class="small text-muted">Kamar</div>
                                <div class="fw-bold"><?= $jml_kamar ?></div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="small text-muted">Terisi</div>
                                <div class="fw-bold text-success"><?= $jml_terisi ?></div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="small text-muted">Kosong</div>
                                <div class="fw-bold text-secondary"><?= $jml_kosong ?></div>
                            </div>
                        </div>
                        <?php if ($jml_mt > 0): ?>
                            <div class="mt-2"><span class="badge bg-warning text-dark"><i class="bi bi-tools me-1"></i><?= $jml_mt ?> kamar maintenance</span></div>
                        <?php endif; ?>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Pemasukan bulan ini:</span>
                            <span class="fw-bold text-success"><?= formatRupiah($pemasukan) ?></span>
                        </div>
                        <div class="mt-2">
                            <a href="kamar.php?properti=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-door-open me-1"></i> Lihat Kamar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if (!$ada): ?>
            <div class="col-12">
                <div class="table-wrapper text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="text-muted mt-2">Belum ada properti. Tambahkan properti pertama Anda!</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
