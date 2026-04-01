<?php
$page_title = 'Maintenance';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $tipe = $_POST['tipe'] ?? 'Spesifik';
        $properti_id = (int)($_POST['properti_id'] ?? 0) ?: null;
        $kamar_id = ($tipe === 'Spesifik') ? ((int)($_POST['kamar_id'] ?? 0) ?: null) : null;
        $judul = trim($_POST['judul'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $biaya = (int)str_replace('.', '', $_POST['biaya'] ?? '0');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $status_mt = $_POST['status_mt'] ?? 'Selesai';

        // Auto-fill properti dari kamar
        if ($kamar_id && !$properti_id) {
            $properti_id = $db->querySingle("SELECT properti_id FROM kamar WHERE id = $kamar_id");
        }

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO maintenance (properti_id, kamar_id, tipe, judul, keterangan, biaya, tanggal, status) VALUES (:prop, :kamar, :tipe, :judul, :ket, :biaya, :tgl, :status)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE maintenance SET properti_id = :prop, kamar_id = :kamar, tipe = :tipe, judul = :judul, keterangan = :ket, biaya = :biaya, tanggal = :tgl, status = :status WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':kamar', $kamar_id, SQLITE3_INTEGER);
        $stmt->bindValue(':tipe', $tipe, SQLITE3_TEXT);
        $stmt->bindValue(':judul', $judul, SQLITE3_TEXT);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->bindValue(':biaya', $biaya, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', $tanggal, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status_mt, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: maintenance.php?pesan=sukses&tahun=' . $filter_tahun . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM maintenance WHERE id = $id");
        header('Location: maintenance.php?pesan=dihapus&tahun=' . $filter_tahun . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM maintenance WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $edit_data = $result->fetchArray(SQLITE3_ASSOC);
}

// Kamar grouped by properti
$kamar_all = $db->query("
    SELECT k.id, k.nomor_kamar, k.properti_id, pr.nama as nama_properti
    FROM kamar k
    JOIN properti pr ON k.properti_id = pr.id
    ORDER BY pr.nama, k.nomor_kamar
");
$kamar_options = [];
while ($row = $kamar_all->fetchArray(SQLITE3_ASSOC)) {
    $kamar_options[] = $row;
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Data maintenance berhasil <?= $_GET['pesan'] === 'dihapus' ? 'dihapus' : 'disimpan' ?>!
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
        <label class="form-label mb-0 fw-semibold ms-2">Tahun:</label>
        <select name="tahun" class="form-select form-select-sm" style="width: auto;">
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $filter_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><i class="bi bi-<?= $edit_data ? 'pencil-square' : 'wrench' ?> me-2"></i><?= $edit_data ? 'Edit Maintenance' : 'Catat Maintenance' ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Tipe Maintenance <span class="text-danger">*</span></label>
                    <select name="tipe" class="form-select" id="tipeMaintenanceSelect">
                        <option value="Spesifik" <?= ($edit_data['tipe'] ?? 'Spesifik') == 'Spesifik' ? 'selected' : '' ?>>Spesifik Kamar</option>
                        <option value="Umum" <?= ($edit_data['tipe'] ?? '') == 'Umum' ? 'selected' : '' ?>>Umum (Bangunan/Fasilitas Bersama)</option>
                    </select>
                </div>
                <div class="mb-3" id="fieldPropertiMt">
                    <label class="form-label">Properti <span class="text-danger">*</span></label>
                    <select name="properti_id" class="form-select" id="propertiMtSelect">
                        <option value="">-- Pilih Properti --</option>
                        <?php foreach ($properti_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= ($edit_data['properti_id'] ?? $filter_properti) == $pl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3" id="fieldKamarMaintenance">
                    <label class="form-label">Pilih Kamar <span class="text-danger">*</span></label>
                    <select name="kamar_id" class="form-select" id="kamarMaintenanceSelect">
                        <option value="">-- Pilih Kamar --</option>
                        <?php
                        $current_prop = '';
                        foreach ($kamar_options as $ko):
                            if ($ko['nama_properti'] !== $current_prop):
                                if ($current_prop !== '') echo '</optgroup>';
                                $current_prop = $ko['nama_properti'];
                                echo '<optgroup label="' . htmlspecialchars($current_prop) . '" data-properti="' . $ko['properti_id'] . '">';
                            endif;
                        ?>
                            <option value="<?= $ko['id'] ?>" data-properti="<?= $ko['properti_id'] ?>" <?= ($edit_data['kamar_id'] ?? '') == $ko['id'] ? 'selected' : '' ?>>
                                Kamar <?= htmlspecialchars($ko['nomor_kamar']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_prop !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Judul Perbaikan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" required value="<?= htmlspecialchars($edit_data['judul'] ?? '') ?>" placeholder="Contoh: Ganti keran, Servis AC">
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Biaya <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="biaya" class="form-control input-rupiah" required value="<?= isset($edit_data) ? number_format($edit_data['biaya'], 0, ',', '.') : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" required value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status_mt" class="form-select">
                        <option value="Selesai" <?= ($edit_data['status'] ?? '') == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="Proses" <?= ($edit_data['status'] ?? '') == 'Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                        <option value="Pending" <?= ($edit_data['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="maintenance.php?tahun=<?= $filter_tahun ?>&properti=<?= $filter_properti ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8 d-flex flex-column">
        <!-- Rekap per Kamar -->
        <div class="table-wrapper mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2"></i>Rekap Biaya Maintenance per Kamar (<?= $filter_tahun ?>)</h6>
            <?php
            $where_prop_mt = $filter_properti ? "AND k.properti_id = $filter_properti" : "";
            $rekap = $db->query("
                SELECT k.nomor_kamar, k.id as kamar_id, pr.nama as nama_properti,
                    COUNT(m.id) as jumlah_perbaikan,
                    COALESCE(SUM(m.biaya), 0) as total_biaya
                FROM kamar k
                JOIN properti pr ON k.properti_id = pr.id
                LEFT JOIN maintenance m ON m.kamar_id = k.id AND m.tipe = 'Spesifik' AND strftime('%Y', m.tanggal) = '$filter_tahun'
                WHERE 1=1 $where_prop_mt
                GROUP BY k.id
                ORDER BY total_biaya DESC
            ");
            $where_prop_umum = $filter_properti ? "AND properti_id = $filter_properti" : "";
            $total_mt_umum = $db->querySingle("SELECT COALESCE(SUM(biaya), 0) FROM maintenance WHERE tipe = 'Umum' AND strftime('%Y', tanggal) = '$filter_tahun' $where_prop_umum");
            $count_mt_umum = $db->querySingle("SELECT COUNT(*) FROM maintenance WHERE tipe = 'Umum' AND strftime('%Y', tanggal) = '$filter_tahun' $where_prop_umum");
            ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                            <th>Kamar</th>
                            <th class="text-center">Jumlah Perbaikan</th>
                            <th class="text-end">Total Biaya</th>
                            <th>Evaluasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $rekap->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <?php if (!$filter_properti): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti']) ?></small></td>
                            <?php endif; ?>
                            <td class="fw-semibold">Kamar <?= htmlspecialchars($row['nomor_kamar']) ?></td>
                            <td class="text-center"><?= $row['jumlah_perbaikan'] ?> kali</td>
                            <td class="text-end fw-semibold <?= $row['total_biaya'] > 0 ? 'text-danger' : '' ?>"><?= formatRupiah($row['total_biaya']) ?></td>
                            <td>
                                <?php if ($row['jumlah_perbaikan'] >= 5): ?>
                                    <span class="badge bg-danger badge-status"><i class="bi bi-exclamation-triangle me-1"></i>Perlu Evaluasi</span>
                                <?php elseif ($row['jumlah_perbaikan'] >= 3): ?>
                                    <span class="badge bg-warning text-dark badge-status"><i class="bi bi-exclamation-circle me-1"></i>Perhatikan</span>
                                <?php elseif ($row['jumlah_perbaikan'] > 0): ?>
                                    <span class="badge bg-success badge-status">Normal</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="table-light">
                            <?php if (!$filter_properti): ?><td></td><?php endif; ?>
                            <td class="fw-semibold">Maintenance Umum</td>
                            <td class="text-center"><?= $count_mt_umum ?> kali</td>
                            <td class="text-end fw-semibold text-danger"><?= formatRupiah($total_mt_umum) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Detail -->
        <div class="table-wrapper flex-grow-1" style="max-height: 500px; overflow-y: auto;">
            <h6 class="fw-bold mb-3"><i class="bi bi-tools me-2"></i>Riwayat Maintenance</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                            <th>Kamar</th>
                            <th>Perbaikan</th>
                            <th class="text-end">Biaya</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_detail = $filter_properti ? "AND m.properti_id = $filter_properti" : "";
                        $data = $db->query("
                            SELECT m.*, k.nomor_kamar, pr.nama as nama_properti
                            FROM maintenance m
                            LEFT JOIN kamar k ON m.kamar_id = k.id
                            LEFT JOIN properti pr ON m.properti_id = pr.id
                            WHERE strftime('%Y', m.tanggal) = '$filter_tahun' $where_detail
                            ORDER BY m.tanggal DESC
                        ");
                        $ada = false;
                        while ($row = $data->fetchArray(SQLITE3_ASSOC)):
                            $ada = true;
                            $status_class = match($row['status']) {
                                'Selesai' => 'bg-success',
                                'Proses' => 'bg-warning text-dark',
                                'Pending' => 'bg-secondary',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td><?= formatTanggal($row['tanggal']) ?></td>
                            <td><span class="badge <?= $row['tipe'] == 'Spesifik' ? 'bg-info' : 'bg-secondary' ?>"><?= $row['tipe'] ?></span></td>
                            <?php if (!$filter_properti): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '-') ?></small></td>
                            <?php endif; ?>
                            <td><?= $row['nomor_kamar'] ? 'Kamar ' . htmlspecialchars($row['nomor_kamar']) : '<span class="text-muted">-</span>' ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['judul']) ?></strong>
                                <?= $row['keterangan'] ? '<br><small class="text-muted">' . htmlspecialchars($row['keterangan']) . '</small>' : '' ?>
                            </td>
                            <td class="text-end fw-semibold text-danger"><?= formatRupiah($row['biaya']) ?></td>
                            <td><span class="badge <?= $status_class ?> badge-status"><?= $row['status'] ?></span></td>
                            <td class="text-center">
                                <a href="maintenance.php?edit=<?= $row['id'] ?>&tahun=<?= $filter_tahun ?>&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada): ?>
                        <tr><td colspan="<?= $filter_properti ? 7 : 8 ?>" class="text-center text-muted py-3">Belum ada data maintenance</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('tipeMaintenanceSelect')?.addEventListener('change', function() {
    const field = document.getElementById('fieldKamarMaintenance');
    const select = document.getElementById('kamarMaintenanceSelect');
    if (this.value === 'Umum') {
        field.style.display = 'none';
        select.removeAttribute('required');
        select.value = '';
    } else {
        field.style.display = 'block';
        select.setAttribute('required', 'required');
    }
});
document.getElementById('tipeMaintenanceSelect')?.dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
