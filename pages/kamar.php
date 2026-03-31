<?php
$page_title = 'Kamar';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $properti_id = (int)($_POST['properti_id'] ?? 0);
        $nomor_kamar = trim($_POST['nomor_kamar'] ?? '');
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $harga_bulanan = (int)str_replace('.', '', $_POST['harga_bulanan'] ?? '0');
        $harga_tahunan = (int)str_replace('.', '', $_POST['harga_tahunan'] ?? '0');
        $status = $_POST['status'] ?? 'Kosong';
        $catatan = trim($_POST['catatan'] ?? '');

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO kamar (properti_id, nomor_kamar, fasilitas, harga_bulanan, harga_tahunan, status, catatan) VALUES (:prop, :nomor, :fasilitas, :hb, :ht, :status, :catatan)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE kamar SET properti_id = :prop, nomor_kamar = :nomor, fasilitas = :fasilitas, harga_bulanan = :hb, harga_tahunan = :ht, status = :status, catatan = :catatan WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':nomor', $nomor_kamar, SQLITE3_TEXT);
        $stmt->bindValue(':fasilitas', $fasilitas, SQLITE3_TEXT);
        $stmt->bindValue(':hb', $harga_bulanan, SQLITE3_INTEGER);
        $stmt->bindValue(':ht', $harga_tahunan, SQLITE3_INTEGER);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':catatan', $catatan, SQLITE3_TEXT);
        $stmt->execute();

        $redir = $filter_properti ? "kamar.php?properti=$filter_properti&pesan=sukses" : "kamar.php?pesan=sukses";
        header("Location: $redir");
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM kamar WHERE id = $id");
        $redir = $filter_properti ? "kamar.php?properti=$filter_properti&pesan=dihapus" : "kamar.php?pesan=dihapus";
        header("Location: $redir");
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM kamar WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $edit_data = $result->fetchArray(SQLITE3_ASSOC);
    if ($edit_data && !$filter_properti) {
        $filter_properti = $edit_data['properti_id'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Data kamar berhasil <?= $_GET['pesan'] === 'dihapus' ? 'dihapus' : 'disimpan' ?>!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($properti_list)): ?>
    <div class="table-wrapper text-center py-5">
        <i class="bi bi-building fs-1 text-muted"></i>
        <p class="text-muted mt-2">Belum ada properti. <a href="properti.php">Tambah properti</a> terlebih dahulu sebelum menambahkan kamar.</p>
    </div>
<?php else: ?>

<!-- Filter Properti -->
<div class="mb-3 no-print">
    <form class="d-flex gap-2 align-items-center" method="GET">
        <label class="form-label mb-0 fw-semibold">Properti:</label>
        <select name="properti" class="form-select form-select-sm" style="width: auto; min-width: 200px;" onchange="this.form.submit()">
            <option value="">-- Semua Properti --</option>
            <?php foreach ($properti_list as $pl): ?>
                <option value="<?= $pl['id'] ?>" <?= $filter_properti == $pl['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pl['nama']) ?> (<?= $pl['tipe'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="row g-4">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><?= $edit_data ? 'Edit Kamar' : 'Tambah Kamar' ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Properti <span class="text-danger">*</span></label>
                    <select name="properti_id" class="form-select" required>
                        <option value="">-- Pilih Properti --</option>
                        <?php foreach ($properti_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= ($edit_data['properti_id'] ?? $filter_properti) == $pl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nama']) ?> (<?= $pl['tipe'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor/Nama Kamar <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_kamar" class="form-control" required value="<?= htmlspecialchars($edit_data['nomor_kamar'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Fasilitas</label>
                    <textarea name="fasilitas" class="form-control" rows="2"><?= htmlspecialchars($edit_data['fasilitas'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Bulanan</label>
                    <input type="text" name="harga_bulanan" class="form-control input-rupiah" value="<?= isset($edit_data) ? number_format($edit_data['harga_bulanan'], 0, ',', '.') : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Tahunan</label>
                    <input type="text" name="harga_tahunan" class="form-control input-rupiah" value="<?= isset($edit_data) ? number_format($edit_data['harga_tahunan'], 0, ',', '.') : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Kosong" <?= ($edit_data['status'] ?? '') == 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                        <option value="Terisi" <?= ($edit_data['status'] ?? '') == 'Terisi' ? 'selected' : '' ?>>Terisi</option>
                        <option value="Maintenance" <?= ($edit_data['status'] ?? '') == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['catatan'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="kamar.php?properti=<?= $filter_properti ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="col-lg-8">
        <div class="table-wrapper">
            <h6 class="fw-bold mb-3">Daftar Kamar</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                            <th>No. Kamar</th>
                            <th>Harga/Bulan</th>
                            <th>Status</th>
                            <th>Penyewa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = $filter_properti ? "WHERE k.properti_id = $filter_properti" : "";
                        $kamar_list = $db->query("
                            SELECT k.*, pr.nama as nama_properti, p.nama as nama_penyewa
                            FROM kamar k
                            JOIN properti pr ON k.properti_id = pr.id
                            LEFT JOIN penyewa p ON p.kamar_id = k.id AND p.status = 'Aktif'
                            $where
                            ORDER BY pr.nama, k.nomor_kamar ASC
                        ");
                        $ada = false;
                        while ($row = $kamar_list->fetchArray(SQLITE3_ASSOC)):
                            $ada = true;
                            $status_class = match($row['status']) {
                                'Terisi' => 'bg-success',
                                'Kosong' => 'bg-secondary',
                                'Maintenance' => 'bg-warning text-dark',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <?php if (!$filter_properti): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti']) ?></small></td>
                            <?php endif; ?>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nomor_kamar']) ?></td>
                            <td><?= formatRupiah($row['harga_bulanan']) ?></td>
                            <td><span class="badge <?= $status_class ?> badge-status"><?= $row['status'] ?></span></td>
                            <td><?= $row['nama_penyewa'] ? htmlspecialchars($row['nama_penyewa']) : '<span class="text-muted">-</span>' ?></td>
                            <td class="text-center">
                                <a href="kamar.php?edit=<?= $row['id'] ?>&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada): ?>
                        <tr><td colspan="<?= $filter_properti ? 5 : 6 ?>" class="text-center text-muted py-3">Belum ada data kamar</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
