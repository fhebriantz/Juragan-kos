<?php
$page_title = 'Pemasukan';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $penyewa_id = (int)($_POST['penyewa_id'] ?? 0) ?: null;
        $kamar_id = (int)($_POST['kamar_id'] ?? 0) ?: null;
        $properti_id = null;
        $kategori = $_POST['kategori'] ?? 'Sewa';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $periode_bulan = $_POST['periode_bulan'] ?? '';
        $metode_bayar = $_POST['metode_bayar'] ?? 'Tunai';

        if ($penyewa_id && !$kamar_id) {
            $kamar_id = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $penyewa_id");
        }
        if ($kamar_id) {
            $properti_id = $db->querySingle("SELECT properti_id FROM kamar WHERE id = $kamar_id");
        }

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, keterangan, nominal, tanggal, periode_bulan, metode_bayar) VALUES (:prop, :penyewa, :kamar, :kat, :ket, :nom, :tgl, :per, :metode)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE pemasukan SET properti_id = :prop, penyewa_id = :penyewa, kamar_id = :kamar, kategori = :kat, keterangan = :ket, nominal = :nom, tanggal = :tgl, periode_bulan = :per, metode_bayar = :metode WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':penyewa', $penyewa_id, SQLITE3_INTEGER);
        $stmt->bindValue(':kamar', $kamar_id, SQLITE3_INTEGER);
        $stmt->bindValue(':kat', $kategori, SQLITE3_TEXT);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', $tanggal, SQLITE3_TEXT);
        $stmt->bindValue(':per', $periode_bulan, SQLITE3_TEXT);
        $stmt->bindValue(':metode', $metode_bayar, SQLITE3_TEXT);
        $stmt->execute();

        // Jika kategori Sewa dan penyewa bertipe Tahunan, set bayar_sampai
        if ($kategori === 'Sewa' && $penyewa_id && $action === 'tambah') {
            $tipe_sewa_penyewa = $db->querySingle("SELECT tipe_sewa FROM penyewa WHERE id = $penyewa_id");
            if ($tipe_sewa_penyewa === 'Tahunan') {
                $bayar_sampai = date('Y-m-d', strtotime($tanggal . ' +12 months'));
                $stmt2 = $db->prepare("UPDATE penyewa SET bayar_sampai = :bs WHERE id = :id");
                $stmt2->bindValue(':bs', $bayar_sampai, SQLITE3_TEXT);
                $stmt2->bindValue(':id', $penyewa_id, SQLITE3_INTEGER);
                $stmt2->execute();
            }
        }

        $last_id = $action === 'tambah' ? $db->lastInsertRowID() : $id;
        header('Location: pemasukan.php?pesan=sukses&cetak=' . $last_id . ($filter_properti ? "&properti=$filter_properti" : '') . "&bulan=$filter_bulan");
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM pemasukan WHERE id = $id");
        header('Location: pemasukan.php?pesan=dihapus' . ($filter_properti ? "&properti=$filter_properti" : '') . "&bulan=$filter_bulan");
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM pemasukan WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $edit_data = $result->fetchArray(SQLITE3_ASSOC);
}

// Pre-fill dari Dashboard (klik "Catat Bayar")
$prefill_penyewa = (int)($_GET['penyewa_id'] ?? 0);
$prefill_nominal = (int)($_GET['nominal'] ?? 0);

// Penyewa aktif grouped by properti
$penyewa_aktif = $db->query("
    SELECT p.id, p.nama, k.nomor_kamar, p.kamar_id, pr.nama as nama_properti
    FROM penyewa p
    LEFT JOIN kamar k ON p.kamar_id = k.id
    LEFT JOIN properti pr ON k.properti_id = pr.id
    WHERE p.status = 'Aktif'
    ORDER BY pr.nama, p.nama
");
$penyewa_options = [];
while ($row = $penyewa_aktif->fetchArray(SQLITE3_ASSOC)) {
    $penyewa_options[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Data pemasukan berhasil <?= $_GET['pesan'] === 'dihapus' ? 'dihapus' : 'disimpan' ?>!
        <?php if (isset($_GET['cetak'])): ?>
            <a href="kuitansi.php?id=<?= (int)$_GET['cetak'] ?>" class="alert-link ms-2" target="_blank"><i class="bi bi-printer"></i> Cetak Kuitansi</a>
        <?php endif; ?>
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
            <h6 class="fw-bold mb-3"><?= $edit_data ? 'Edit Pemasukan' : 'Catat Pemasukan' ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori" class="form-select">
                        <option value="Sewa" <?= ($edit_data['kategori'] ?? '') == 'Sewa' ? 'selected' : '' ?>>Sewa Kos/Kontrakan</option>
                        <option value="Denda" <?= ($edit_data['kategori'] ?? '') == 'Denda' ? 'selected' : '' ?>>Denda Telat Bayar</option>
                        <option value="Laundry" <?= ($edit_data['kategori'] ?? '') == 'Laundry' ? 'selected' : '' ?>>Jasa Laundry</option>
                        <option value="Parkir" <?= ($edit_data['kategori'] ?? '') == 'Parkir' ? 'selected' : '' ?>>Parkir Tambahan</option>
                        <option value="Lainnya" <?= ($edit_data['kategori'] ?? '') == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Penyewa</label>
                    <select name="penyewa_id" class="form-select">
                        <option value="">-- Pilih Penyewa --</option>
                        <?php
                        $current_prop = '';
                        foreach ($penyewa_options as $po):
                            if (($po['nama_properti'] ?? '') !== $current_prop):
                                if ($current_prop !== '') echo '</optgroup>';
                                $current_prop = $po['nama_properti'] ?? 'Tanpa Properti';
                                echo '<optgroup label="' . htmlspecialchars($current_prop) . '">';
                            endif;
                        ?>
                            <option value="<?= $po['id'] ?>" data-kamar="<?= $po['kamar_id'] ?>" <?= ($edit_data['penyewa_id'] ?? $prefill_penyewa) == $po['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($po['nama']) ?> - Kamar <?= htmlspecialchars($po['nomor_kamar']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_prop !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <?php
                $prefill_kamar = '';
                if (!empty($edit_data['kamar_id'])) {
                    $prefill_kamar = $edit_data['kamar_id'];
                } elseif ($prefill_penyewa) {
                    $prefill_kamar = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $prefill_penyewa");
                }
                ?>
                <input type="hidden" name="kamar_id" value="<?= $prefill_kamar ?>">
                <div class="mb-3">
                    <label class="form-label">Nominal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="nominal" class="form-control input-rupiah" required value="<?= isset($edit_data) ? number_format($edit_data['nominal'], 0, ',', '.') : ($prefill_nominal ? number_format($prefill_nominal, 0, ',', '.') : '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" required value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Periode Bulan</label>
                    <input type="month" name="periode_bulan" class="form-control" value="<?= $edit_data['periode_bulan'] ?? date('Y-m') ?>">
                    <small class="text-muted">Bulan mana yang dibayar</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Metode Bayar</label>
                    <select name="metode_bayar" class="form-select">
                        <option value="Tunai" <?= ($edit_data['metode_bayar'] ?? '') == 'Tunai' ? 'selected' : '' ?>>Tunai</option>
                        <option value="Transfer" <?= ($edit_data['metode_bayar'] ?? '') == 'Transfer' ? 'selected' : '' ?>>Transfer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="pemasukan.php?properti=<?= $filter_properti ?>&bulan=<?= $filter_bulan ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-wrapper">
            <h6 class="fw-bold mb-3">Riwayat Pemasukan</h6>
            <?php
            $where_prop = $filter_properti ? "AND pm.properti_id = $filter_properti" : "";
            $total = $db->querySingle("SELECT COALESCE(SUM(nominal),0) FROM pemasukan pm WHERE strftime('%Y-%m', tanggal) = '$filter_bulan' $where_prop");
            ?>
            <div class="alert alert-info py-2 mb-3">
                Total Pemasukan: <strong><?= formatRupiah($total) ?></strong>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Penyewa</th>
                            <th>Kategori</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $data = $db->query("
                            SELECT pm.*, p.nama, k.nomor_kamar, pr.nama as nama_properti
                            FROM pemasukan pm
                            LEFT JOIN penyewa p ON pm.penyewa_id = p.id
                            LEFT JOIN kamar k ON pm.kamar_id = k.id
                            LEFT JOIN properti pr ON pm.properti_id = pr.id
                            WHERE strftime('%Y-%m', pm.tanggal) = '$filter_bulan' $where_prop
                            ORDER BY pm.tanggal DESC
                        ");
                        $ada = false;
                        while ($row = $data->fetchArray(SQLITE3_ASSOC)):
                            $ada = true;
                        ?>
                        <tr>
                            <td><?= formatTanggal($row['tanggal']) ?></td>
                            <td>
                                <?= $row['nama'] ? htmlspecialchars($row['nama']) : '-' ?>
                                <?php if ($row['nomor_kamar']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '') ?> - Kamar <?= htmlspecialchars($row['nomor_kamar']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-success badge-status"><?= $row['kategori'] ?></span></td>
                            <td class="text-end fw-semibold text-success"><?= formatRupiah($row['nominal']) ?></td>
                            <td class="text-center">
                                <a href="kuitansi.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="Cetak" target="_blank"><i class="bi bi-printer"></i></a>
                                <a href="pemasukan.php?edit=<?= $row['id'] ?>&properti=<?= $filter_properti ?>&bulan=<?= $filter_bulan ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data pemasukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="penyewa_id"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.querySelector('input[name="kamar_id"]').value = opt.dataset.kamar || '';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
