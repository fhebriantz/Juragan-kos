<?php
$page_title = 'Pengeluaran';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $properti_id = (int)($_POST['properti_id'] ?? 0) ?: null;
        $kategori = $_POST['kategori'] ?? '';
        $sub_kategori = trim($_POST['sub_kategori'] ?? '');
        $kamar_id = (int)($_POST['kamar_id'] ?? 0) ?: null;
        $keterangan = trim($_POST['keterangan'] ?? '');
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $no_meter = trim($_POST['no_meter'] ?? '');
        $id_pelanggan = trim($_POST['id_pelanggan'] ?? '');

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO pengeluaran (properti_id, kategori, sub_kategori, kamar_id, keterangan, nominal, tanggal, no_meter, id_pelanggan) VALUES (:prop, :kat, :sub, :kamar, :ket, :nom, :tgl, :meter, :pelanggan)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE pengeluaran SET properti_id = :prop, kategori = :kat, sub_kategori = :sub, kamar_id = :kamar, keterangan = :ket, nominal = :nom, tanggal = :tgl, no_meter = :meter, id_pelanggan = :pelanggan WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':kat', $kategori, SQLITE3_TEXT);
        $stmt->bindValue(':sub', $sub_kategori, SQLITE3_TEXT);
        $stmt->bindValue(':kamar', $kamar_id, SQLITE3_INTEGER);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', $tanggal, SQLITE3_TEXT);
        $stmt->bindValue(':meter', $no_meter, SQLITE3_TEXT);
        $stmt->bindValue(':pelanggan', $id_pelanggan, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: pengeluaran.php?pesan=sukses' . ($filter_properti ? "&properti=$filter_properti" : '') . "&bulan=$filter_bulan");
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM pengeluaran WHERE id = $id");
        header('Location: pengeluaran.php?pesan=dihapus' . ($filter_properti ? "&properti=$filter_properti" : '') . "&bulan=$filter_bulan");
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM pengeluaran WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $edit_data = $result->fetchArray(SQLITE3_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Data pengeluaran berhasil <?= $_GET['pesan'] === 'dihapus' ? 'dihapus' : 'disimpan' ?>!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter -->
<div class="mb-3 no-print">
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
        <label class="form-label mb-0 fw-semibold">Properti:</label>
        <select name="properti" class="form-select form-select-sm" style="width: auto; min-width: 200px;">
            <option value="">-- Semua --</option>
            <?php foreach ($properti_list as $pl): ?>
                <option value="<?= $pl['id'] ?>" <?= $filter_properti == $pl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pl['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="form-label mb-0 fw-semibold ms-2">Bulan:</label>
        <input type="month" name="bulan" class="form-control form-control-sm" style="width: auto;" value="<?= $filter_bulan ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><?= $edit_data ? 'Edit Pengeluaran' : 'Catat Pengeluaran' ?></h6>
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
                                <?= htmlspecialchars($pl['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Untuk PLN, PDAM, Keamanan, dan tagihan rutin lainnya, gunakan <a href="tagihan_operasional.php" class="alert-link">Tagihan Operasional</a> agar tidak tercatat dua kali.
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="kategori" class="form-control" required value="<?= htmlspecialchars($edit_data['kategori'] ?? '') ?>" placeholder="Contoh: Beli Perabotan, Perbaikan, dll">
                </div>
                <input type="hidden" name="no_meter" value="">
                <input type="hidden" name="id_pelanggan" value="">
                <div class="mb-3">
                    <label class="form-label">Nominal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="nominal" class="form-control input-rupiah" required value="<?= isset($edit_data) ? number_format($edit_data['nominal'], 0, ',', '.') : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" required value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan'] ?? '') ?></textarea>
                </div>
                <input type="hidden" name="kamar_id" value="">
                <input type="hidden" name="sub_kategori" value="">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="pengeluaran.php?properti=<?= $filter_properti ?>&bulan=<?= $filter_bulan ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-wrapper">
            <h6 class="fw-bold mb-3">Riwayat Pengeluaran</h6>
            <?php
            $where_prop = $filter_properti ? "AND pg.properti_id = $filter_properti" : "";
            $total = $db->querySingle("SELECT COALESCE(SUM(nominal),0) FROM pengeluaran pg WHERE strftime('%Y-%m', tanggal) = '$filter_bulan' $where_prop");
            ?>
            <div class="alert alert-warning py-2 mb-3">
                Total Pengeluaran: <strong><?= formatRupiah($total) ?></strong>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $data = $db->query("
                            SELECT pg.*, pr.nama as nama_properti
                            FROM pengeluaran pg
                            LEFT JOIN properti pr ON pg.properti_id = pr.id
                            WHERE strftime('%Y-%m', pg.tanggal) = '$filter_bulan' $where_prop
                            ORDER BY pg.tanggal DESC
                        ");
                        $ada = false;
                        while ($row = $data->fetchArray(SQLITE3_ASSOC)):
                            $ada = true;
                        ?>
                        <tr>
                            <td><?= formatTanggal($row['tanggal']) ?></td>
                            <?php if (!$filter_properti): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '-') ?></small></td>
                            <?php endif; ?>
                            <td><span class="badge bg-dark badge-status"><?= $row['kategori'] ?></span></td>
                            <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                            <td class="text-end fw-semibold text-danger"><?= formatRupiah($row['nominal']) ?></td>
                            <td class="text-center">
                                <a href="pengeluaran.php?edit=<?= $row['id'] ?>&properti=<?= $filter_properti ?>&bulan=<?= $filter_bulan ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada): ?>
                        <tr><td colspan="<?= $filter_properti ? 5 : 6 ?>" class="text-center text-muted py-3">Belum ada data pengeluaran</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
