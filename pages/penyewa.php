<?php
$page_title = 'Penyewa';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama = trim($_POST['nama'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $no_ktp = trim($_POST['no_ktp'] ?? '');
        $alamat_asal = trim($_POST['alamat_asal'] ?? '');
        $kamar_id = (int)($_POST['kamar_id'] ?? 0);
        $tanggal_masuk = $_POST['tanggal_masuk'] ?? date('Y-m-d');
        $tipe_sewa = $_POST['tipe_sewa'] ?? 'Bulanan';
        $jatuh_tempo_tanggal = (int)date('d', strtotime($tanggal_masuk));
        $status = $_POST['status'] ?? 'Aktif';
        $catatan = trim($_POST['catatan'] ?? '');
        $tanggal_keluar = trim($_POST['tanggal_keluar'] ?? '') ?: null;
        $harga_sewa = (int)str_replace('.', '', $_POST['harga_sewa'] ?? '0');

        // bayar_sampai: ambil dari form jika Tahunan, pertahankan dari DB jika Harian, null jika Bulanan
        if ($tipe_sewa === 'Tahunan') {
            $bayar_sampai = trim($_POST['bayar_sampai'] ?? '') ?: null;
        } elseif ($action === 'edit') {
            // Pertahankan bayar_sampai yang sudah ada di database
            $id_temp = (int)$_POST['id'];
            $bayar_sampai = $db->querySingle("SELECT bayar_sampai FROM penyewa WHERE id = $id_temp");
        } else {
            $bayar_sampai = null;
        }

        // Upload foto KTP
        $foto_ktp = $_POST['foto_ktp_existing'] ?? '';
        if (!empty($_FILES['foto_ktp']['name']) && $_FILES['foto_ktp']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed) && $_FILES['foto_ktp']['size'] <= 5 * 1024 * 1024) {
                $filename = 'ktp_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/ktp/' . $filename;
                if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $dest)) {
                    // Hapus foto lama jika ada
                    if ($foto_ktp && file_exists(__DIR__ . '/../uploads/ktp/' . $foto_ktp)) {
                        unlink(__DIR__ . '/../uploads/ktp/' . $foto_ktp);
                    }
                    $foto_ktp = $filename;
                }
            }
        }

        if ($action === 'tambah') {
            $stmt = $db->prepare("INSERT INTO penyewa (nama, no_hp, no_ktp, foto_ktp, alamat_asal, kamar_id, tanggal_masuk, tanggal_keluar, tipe_sewa, harga_sewa, jatuh_tempo_tanggal, bayar_sampai, status, catatan) VALUES (:nama, :hp, :ktp, :foto, :alamat, :kamar, :tgl, :tgl_keluar, :tipe, :harga, :jt, :bs, :status, :catatan)");
            if ($kamar_id > 0) {
                $db->exec("UPDATE kamar SET status = 'Terisi' WHERE id = $kamar_id");
            }
        } else {
            $id = (int)$_POST['id'];
            $old = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $id");
            if ($old && $old != $kamar_id) {
                $db->exec("UPDATE kamar SET status = 'Kosong' WHERE id = $old");
            }
            if ($kamar_id > 0 && $status === 'Aktif') {
                $db->exec("UPDATE kamar SET status = 'Terisi' WHERE id = $kamar_id");
            }
            $stmt = $db->prepare("UPDATE penyewa SET nama = :nama, no_hp = :hp, no_ktp = :ktp, foto_ktp = :foto, alamat_asal = :alamat, kamar_id = :kamar, tanggal_masuk = :tgl, tanggal_keluar = :tgl_keluar, tipe_sewa = :tipe, harga_sewa = :harga, jatuh_tempo_tanggal = :jt, bayar_sampai = :bs, status = :status, catatan = :catatan WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':nama', $nama, SQLITE3_TEXT);
        $stmt->bindValue(':hp', $no_hp, SQLITE3_TEXT);
        $stmt->bindValue(':ktp', $no_ktp, SQLITE3_TEXT);
        $stmt->bindValue(':foto', $foto_ktp, SQLITE3_TEXT);
        $stmt->bindValue(':alamat', $alamat_asal, SQLITE3_TEXT);
        $stmt->bindValue(':kamar', $kamar_id ?: null, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', $tanggal_masuk, SQLITE3_TEXT);
        $stmt->bindValue(':tgl_keluar', $tanggal_keluar, SQLITE3_TEXT);
        $stmt->bindValue(':tipe', $tipe_sewa, SQLITE3_TEXT);
        $stmt->bindValue(':harga', $harga_sewa, SQLITE3_INTEGER);
        $stmt->bindValue(':jt', $jatuh_tempo_tanggal, SQLITE3_INTEGER);
        $stmt->bindValue(':bs', $bayar_sampai, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':catatan', $catatan, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: penyewa.php?pesan=sukses' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        // Hapus foto KTP
        $foto = $db->querySingle("SELECT foto_ktp FROM penyewa WHERE id = $id");
        if ($foto && file_exists(__DIR__ . '/../uploads/ktp/' . $foto)) {
            unlink(__DIR__ . '/../uploads/ktp/' . $foto);
        }
        $kamar_id = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $id");
        if ($kamar_id) {
            $db->exec("UPDATE kamar SET status = 'Kosong' WHERE id = $kamar_id");
        }
        $db->exec("DELETE FROM penyewa WHERE id = $id");
        header('Location: penyewa.php?pesan=dihapus' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'checkout') {
        $id = (int)$_POST['id'];
        $kamar_id = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $id");
        $db->exec("UPDATE penyewa SET status = 'Nonaktif' WHERE id = $id");
        if ($kamar_id) {
            $db->exec("UPDATE kamar SET status = 'Kosong' WHERE id = $kamar_id");
        }
        header('Location: penyewa.php?pesan=checkout' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM penyewa WHERE id = :id");
    $stmt->bindValue(':id', $edit_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $edit_data = $result->fetchArray(SQLITE3_ASSOC);
}

// Detail penyewa (lihat foto KTP)
$detail_data = null;
if (isset($_GET['detail'])) {
    $det_id = (int)$_GET['detail'];
    $stmt = $db->prepare("SELECT p.*, k.nomor_kamar, pr.nama as nama_properti FROM penyewa p LEFT JOIN kamar k ON p.kamar_id = k.id LEFT JOIN properti pr ON k.properti_id = pr.id WHERE p.id = :id");
    $stmt->bindValue(':id', $det_id, SQLITE3_INTEGER);
    $detail_data = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}

// Kamar kosong (grouped by properti)
$kamar_kosong = $db->query("
    SELECT k.id, k.nomor_kamar, k.harga_bulanan, k.harga_tahunan, k.properti_id, pr.nama as nama_properti
    FROM kamar k
    JOIN properti pr ON k.properti_id = pr.id
    WHERE k.status = 'Kosong'
    ORDER BY pr.nama, k.nomor_kamar
");
$kamar_options = [];
while ($row = $kamar_kosong->fetchArray(SQLITE3_ASSOC)) {
    $kamar_options[] = $row;
}
if ($edit_data && $edit_data['kamar_id']) {
    $kmr = $db->querySingle("SELECT k.id, k.nomor_kamar, k.harga_bulanan, k.harga_tahunan, k.properti_id, pr.nama as nama_properti FROM kamar k JOIN properti pr ON k.properti_id = pr.id WHERE k.id = {$edit_data['kamar_id']}", true);
    if ($kmr) {
        $found = false;
        foreach ($kamar_options as $ko) {
            if ($ko['id'] == $kmr['id']) { $found = true; break; }
        }
        if (!$found) $kamar_options[] = $kmr;
    }
}

// JSON kamar data untuk JS
$kamar_json = [];
foreach ($kamar_options as $ko) {
    $kamar_json[$ko['id']] = ['bulanan' => $ko['harga_bulanan'], 'tahunan' => $ko['harga_tahunan']];
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= match($_GET['pesan']) { 'dihapus' => 'Data penyewa berhasil dihapus!', 'checkout' => 'Penyewa berhasil checkout!', default => 'Data penyewa berhasil disimpan!' }; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($detail_data): ?>
<!-- Detail Penyewa -->
<div class="table-wrapper mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i>Detail Penyewa: <?= htmlspecialchars($detail_data['nama']) ?></h6>
        <a href="penyewa.php?properti=<?= $filter_properti ?>" class="btn btn-sm btn-secondary">Tutup</a>
    </div>
    <div class="row g-3">
        <div class="col-md-<?= $detail_data['foto_ktp'] ? '8' : '12' ?>">
            <table class="table table-borderless small mb-0">
                <tr><td class="fw-semibold" style="width:160px">Nama</td><td><?= htmlspecialchars($detail_data['nama']) ?></td></tr>
                <tr><td class="fw-semibold">No. HP</td><td><?= htmlspecialchars($detail_data['no_hp'] ?: '-') ?></td></tr>
                <tr><td class="fw-semibold">No. KTP</td><td><?= htmlspecialchars($detail_data['no_ktp'] ?: '-') ?></td></tr>
                <tr><td class="fw-semibold">Alamat Asal</td><td><?= htmlspecialchars($detail_data['alamat_asal'] ?: '-') ?></td></tr>
                <tr><td class="fw-semibold">Properti / Kamar</td><td><?= htmlspecialchars($detail_data['nama_properti'] ?? '-') ?> — Kamar <?= htmlspecialchars($detail_data['nomor_kamar'] ?? '-') ?></td></tr>
                <tr><td class="fw-semibold">Tipe Sewa</td><td><?= $detail_data['tipe_sewa'] ?><?php if ($detail_data['harga_sewa'] > 0): ?> — <?= formatRupiah($detail_data['harga_sewa']) ?><?php endif; ?></td></tr>
                <tr><td class="fw-semibold">Tanggal Masuk</td><td><?= formatTanggal($detail_data['tanggal_masuk']) ?></td></tr>
                <?php if ($detail_data['tanggal_keluar']): ?>
                <tr><td class="fw-semibold">Tanggal Keluar</td><td><?= formatTanggal($detail_data['tanggal_keluar']) ?></td></tr>
                <?php endif; ?>
                <?php if ($detail_data['bayar_sampai']): ?>
                <tr><td class="fw-semibold">Bayar Sampai</td><td><?= formatTanggal($detail_data['bayar_sampai']) ?></td></tr>
                <?php endif; ?>
                <tr><td class="fw-semibold">Status</td><td><span class="badge <?= $detail_data['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?>"><?= $detail_data['status'] ?></span></td></tr>
            </table>
        </div>
        <?php if ($detail_data['foto_ktp']): ?>
        <div class="col-md-4 text-center">
            <p class="fw-semibold small mb-2">Foto KTP</p>
            <a href="../uploads/ktp/<?= htmlspecialchars($detail_data['foto_ktp']) ?>" target="_blank">
                <img src="../uploads/ktp/<?= htmlspecialchars($detail_data['foto_ktp']) ?>" class="img-fluid rounded border" style="max-height: 250px;" alt="Foto KTP">
            </a>
            <br><a href="../uploads/ktp/<?= htmlspecialchars($detail_data['foto_ktp']) ?>" download class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-download me-1"></i>Download</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

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
    <div class="col-lg-4">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><?= $edit_data ? 'Edit Penyewa' : 'Tambah Penyewa' ?></h6>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <input type="hidden" name="foto_ktp_existing" value="<?= htmlspecialchars($edit_data['foto_ktp'] ?? '') ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nama Penyewa <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">No. KTP</label>
                        <input type="text" name="no_ktp" class="form-control" value="<?= htmlspecialchars($edit_data['no_ktp'] ?? '') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Foto KTP</label>
                        <input type="file" name="foto_ktp" class="form-control" accept="image/*">
                        <?php if (!empty($edit_data['foto_ktp'])): ?>
                            <small class="text-success"><i class="bi bi-check-circle me-1"></i>Foto sudah ada</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat Asal</label>
                    <textarea name="alamat_asal" class="form-control" rows="2"><?= htmlspecialchars($edit_data['alamat_asal'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pilih Kamar <span class="text-danger">*</span></label>
                    <select name="kamar_id" class="form-select" required id="selectKamar">
                        <option value="">-- Pilih Kamar --</option>
                        <?php
                        $current_properti = '';
                        foreach ($kamar_options as $ko):
                            if ($ko['nama_properti'] !== $current_properti):
                                if ($current_properti !== '') echo '</optgroup>';
                                $current_properti = $ko['nama_properti'];
                                echo '<optgroup label="' . htmlspecialchars($current_properti) . '">';
                            endif;
                        ?>
                            <option value="<?= $ko['id'] ?>" data-bulanan="<?= $ko['harga_bulanan'] ?>" data-tahunan="<?= $ko['harga_tahunan'] ?>" <?= ($edit_data['kamar_id'] ?? '') == $ko['id'] ? 'selected' : '' ?>>
                                Kamar <?= htmlspecialchars($ko['nomor_kamar']) ?> - <?= formatRupiah($ko['harga_bulanan']) ?>/bln
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_properti !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe Sewa <span class="text-danger">*</span></label>
                    <select name="tipe_sewa" class="form-select" id="selectTipeSewa">
                        <option value="Bulanan" <?= ($edit_data['tipe_sewa'] ?? '') == 'Bulanan' ? 'selected' : '' ?>>Bulanan</option>
                        <option value="Tahunan" <?= ($edit_data['tipe_sewa'] ?? '') == 'Tahunan' ? 'selected' : '' ?>>Tahunan</option>
                        <option value="Harian" <?= ($edit_data['tipe_sewa'] ?? '') == 'Harian' ? 'selected' : '' ?>>Harian</option>
                    </select>
                </div>
                <div class="mb-3" id="fieldHargaSewa">
                    <label class="form-label" id="labelHargaSewa">Harga Sewa</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="harga_sewa" class="form-control input-rupiah" id="inputHargaSewa" value="<?= ($edit_data['harga_sewa'] ?? 0) > 0 ? number_format($edit_data['harga_sewa'], 0, ',', '.') : '' ?>">
                    </div>
                    <small class="text-muted" id="hintHargaSewa"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_masuk" class="form-control" required value="<?= $edit_data['tanggal_masuk'] ?? date('Y-m-d') ?>">
                    <small class="text-muted" id="hintTanggalMasuk">Tanggal masuk menentukan jatuh tempo bulanan</small>
                </div>
                <div class="mb-3" id="fieldTanggalKeluar" style="display:none">
                    <label class="form-label">Tanggal Keluar (Checkout) <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_keluar" class="form-control" id="inputTanggalKeluar" value="<?= $edit_data['tanggal_keluar'] ?? '' ?>">
                    <small class="text-muted">Tanggal penyewa harian akan checkout</small>
                </div>
                <?php if ($edit_data && $edit_data['tipe_sewa'] === 'Tahunan'): ?>
                <div class="mb-3">
                    <label class="form-label">Sewa Dibayar Sampai</label>
                    <input type="date" name="bayar_sampai" class="form-control" value="<?= $edit_data['bayar_sampai'] ?? '' ?>">
                    <small class="text-muted">Otomatis terisi saat catat bayar tahunan. Bisa diubah manual.</small>
                </div>
                <?php endif; ?>
                <?php if ($edit_data): ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Aktif" <?= ($edit_data['status'] ?? '') == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Nonaktif" <?= ($edit_data['status'] ?? '') == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['catatan'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    <?php if ($edit_data): ?>
                        <a href="penyewa.php?properti=<?= $filter_properti ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-wrapper">
            <h6 class="fw-bold mb-3">Daftar Penyewa</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                            <th>Kamar</th>
                            <th>Tipe / Jatuh Tempo</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = $filter_properti ? "WHERE k.properti_id = $filter_properti" : "";
                        $penyewa_list = $db->query("
                            SELECT p.*, k.nomor_kamar, pr.nama as nama_properti
                            FROM penyewa p
                            LEFT JOIN kamar k ON p.kamar_id = k.id
                            LEFT JOIN properti pr ON k.properti_id = pr.id
                            $where
                            ORDER BY p.status ASC, p.nama ASC
                        ");
                        $ada = false;
                        while ($row = $penyewa_list->fetchArray(SQLITE3_ASSOC)):
                            $ada = true;
                        ?>
                        <tr class="<?= $row['status'] == 'Nonaktif' ? 'table-secondary' : '' ?>">
                            <td>
                                <a href="penyewa.php?detail=<?= $row['id'] ?>&properti=<?= $filter_properti ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($row['nama']) ?></strong>
                                </a>
                                <?php if ($row['foto_ktp']): ?>
                                    <i class="bi bi-card-image text-success ms-1" title="Foto KTP tersedia"></i>
                                <?php endif; ?>
                                <?php if ($row['no_hp']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['no_hp']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php if (!$filter_properti): ?>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '-') ?></small></td>
                            <?php endif; ?>
                            <td><?= $row['nomor_kamar'] ? 'Kamar ' . htmlspecialchars($row['nomor_kamar']) : '-' ?></td>
                            <td>
                                <?php if ($row['tipe_sewa'] === 'Harian'): ?>
                                    <span class="badge bg-warning text-dark badge-status">Harian</span>
                                    <?php if ($row['tanggal_keluar']): ?>
                                        <br><small class="text-muted">Checkout: <?= formatTanggal($row['tanggal_keluar']) ?></small>
                                    <?php endif; ?>
                                <?php elseif ($row['tipe_sewa'] === 'Tahunan'): ?>
                                    <span class="badge bg-info badge-status">Tahunan</span>
                                    <?php if ($row['bayar_sampai']): ?>
                                        <br><small class="text-muted">s/d <?= formatTanggal($row['bayar_sampai']) ?></small>
                                    <?php else: ?>
                                        <br><small class="text-muted">Belum bayar</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Tgl <strong><?= $row['jatuh_tempo_tanggal'] ?></strong> / bulan
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?> badge-status">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="penyewa.php?detail=<?= $row['id'] ?>&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="penyewa.php?edit=<?= $row['id'] ?>&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if ($row['status'] === 'Aktif'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="checkout">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning btn-delete" title="Checkout"><i class="bi bi-box-arrow-right"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada): ?>
                        <tr><td colspan="<?= $filter_properti ? 5 : 6 ?>" class="text-center text-muted py-3">Belum ada data penyewa</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const selectTipe = document.getElementById('selectTipeSewa');
const selectKamar = document.getElementById('selectKamar');
const fieldHarga = document.getElementById('fieldHargaSewa');
const inputHarga = document.getElementById('inputHargaSewa');
const labelHarga = document.getElementById('labelHargaSewa');
const hintHarga = document.getElementById('hintHargaSewa');
const fieldKeluar = document.getElementById('fieldTanggalKeluar');
const inputKeluar = document.getElementById('inputTanggalKeluar');
const hintTglMasuk = document.getElementById('hintTanggalMasuk');

function updateFormByTipe() {
    const tipe = selectTipe.value;
    const opt = selectKamar.options[selectKamar.selectedIndex];
    const bulanan = opt ? parseInt(opt.dataset.bulanan || 0) : 0;
    const tahunan = opt ? parseInt(opt.dataset.tahunan || 0) : 0;

    if (tipe === 'Harian') {
        labelHarga.textContent = 'Harga per Malam';
        hintHarga.textContent = 'Isi harga sewa per malam';
        fieldKeluar.style.display = 'block';
        inputKeluar.setAttribute('required', 'required');
        hintTglMasuk.textContent = 'Tanggal check-in';
    } else if (tipe === 'Tahunan') {
        labelHarga.textContent = 'Harga Tahunan';
        hintHarga.textContent = tahunan ? 'Harga kamar: Rp ' + new Intl.NumberFormat('id-ID').format(tahunan) + '/tahun' : '';
        if (!inputHarga.value && tahunan) inputHarga.value = new Intl.NumberFormat('id-ID').format(tahunan);
        fieldKeluar.style.display = 'none';
        inputKeluar.removeAttribute('required');
        hintTglMasuk.textContent = 'Tanggal masuk menentukan jatuh tempo tahunan';
    } else {
        labelHarga.textContent = 'Harga Bulanan';
        hintHarga.textContent = bulanan ? 'Harga kamar: Rp ' + new Intl.NumberFormat('id-ID').format(bulanan) + '/bulan' : '';
        if (!inputHarga.value && bulanan) inputHarga.value = new Intl.NumberFormat('id-ID').format(bulanan);
        fieldKeluar.style.display = 'none';
        inputKeluar.removeAttribute('required');
        hintTglMasuk.textContent = 'Tanggal masuk menentukan jatuh tempo bulanan';
    }
}

selectTipe?.addEventListener('change', updateFormByTipe);
selectKamar?.addEventListener('change', function() { inputHarga.value = ''; updateFormByTipe(); });
updateFormByTipe();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
