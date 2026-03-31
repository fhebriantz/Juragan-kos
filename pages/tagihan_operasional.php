<?php
$page_title = 'Tagihan Operasional';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';
$filter_status = $_GET['status'] ?? '';
$tab = $_GET['tab'] ?? 'tagihan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ====== TEMPLATE CRUD ======
    if ($action === 'tambah_template' || $action === 'edit_template') {
        $properti_id = (int)($_POST['properti_id'] ?? 0);
        $jenis = trim($_POST['jenis_custom'] ?? '') ?: trim($_POST['jenis_pilih'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $isi_manual = isset($_POST['isi_manual']) ? 1 : 0;
        $nominal_default = $isi_manual ? 0 : (int)str_replace('.', '', $_POST['nominal_default'] ?? '0');
        $jatuh_tempo_tanggal = (int)($_POST['jatuh_tempo_tanggal'] ?? 1);
        if ($jatuh_tempo_tanggal < 1) $jatuh_tempo_tanggal = 1;
        if ($jatuh_tempo_tanggal > 28) $jatuh_tempo_tanggal = 28;

        if ($action === 'tambah_template') {
            $stmt = $db->prepare("INSERT OR IGNORE INTO template_tagihan (properti_id, jenis, keterangan, nominal_default, isi_manual, jatuh_tempo_tanggal) VALUES (:prop, :jenis, :ket, :nom, :manual, :jt)");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE template_tagihan SET properti_id = :prop, jenis = :jenis, keterangan = :ket, nominal_default = :nom, isi_manual = :manual, jatuh_tempo_tanggal = :jt WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':jenis', $jenis, SQLITE3_TEXT);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->bindValue(':nom', $nominal_default, SQLITE3_INTEGER);
        $stmt->bindValue(':manual', $isi_manual, SQLITE3_INTEGER);
        $stmt->bindValue(':jt', $jatuh_tempo_tanggal, SQLITE3_INTEGER);
        $stmt->execute();

        generateTagihanBulanan($db);
        header('Location: tagihan_operasional.php?tab=template&pesan=template_ok' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'hapus_template') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM template_tagihan WHERE id = $id");
        header('Location: tagihan_operasional.php?tab=template&pesan=template_hapus' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'toggle_template') {
        $id = (int)$_POST['id'];
        $db->exec("UPDATE template_tagihan SET aktif = CASE WHEN aktif = 1 THEN 0 ELSE 1 END WHERE id = $id");
        header('Location: tagihan_operasional.php?tab=template&pesan=template_ok' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    // ====== TAGIHAN MANUAL (sekali jalan) ======
    if ($action === 'tambah_manual') {
        $properti_id = (int)($_POST['properti_id'] ?? 0) ?: null;
        $jenis = trim($_POST['jenis'] ?? '');
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $jatuh_tempo = $_POST['jatuh_tempo'] ?? date('Y-m-d');
        $keterangan = trim($_POST['keterangan'] ?? '');

        $stmt = $db->prepare("INSERT INTO tagihan_operasional (properti_id, jenis, nominal, periode, jatuh_tempo, keterangan, tipe, status) VALUES (:prop, :jenis, :nom, '', :jt, :ket, 'Sekali Bayar', 'Belum Bayar')");
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':jenis', $jenis, SQLITE3_TEXT);
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':jt', $jatuh_tempo, SQLITE3_TEXT);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: tagihan_operasional.php?pesan=manual_ok' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    // ====== TAGIHAN ACTIONS ======
    if ($action === 'update_nominal') {
        $id = (int)$_POST['id'];
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $stmt = $db->prepare("UPDATE tagihan_operasional SET nominal = :nom, keterangan = :ket WHERE id = :id");
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':ket', $keterangan, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        header('Location: tagihan_operasional.php?pesan=update_ok' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'bayar') {
        $id = (int)$_POST['id'];
        $db->exec("UPDATE tagihan_operasional SET status = 'Sudah Bayar', tanggal_bayar = '" . date('Y-m-d') . "' WHERE id = $id");

        $tagihan = $db->querySingle("SELECT * FROM tagihan_operasional WHERE id = $id", true);
        if ($tagihan) {
            $stmt = $db->prepare("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal) VALUES (:prop, :kat, :ket, :nom, :tgl)");
            $stmt->bindValue(':prop', $tagihan['properti_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':kat', $tagihan['jenis'], SQLITE3_TEXT);
            $ket = 'Bayar ' . $tagihan['jenis'];
            if ($tagihan['periode']) $ket .= ' periode ' . $tagihan['periode'];
            if ($tagihan['keterangan']) $ket .= ' - ' . $tagihan['keterangan'];
            $stmt->bindValue(':ket', $ket, SQLITE3_TEXT);
            $stmt->bindValue(':nom', $tagihan['nominal'], SQLITE3_INTEGER);
            $stmt->bindValue(':tgl', date('Y-m-d'), SQLITE3_TEXT);
            $stmt->execute();
        }

        header('Location: tagihan_operasional.php?pesan=dibayar' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'batal_bayar') {
        $id = (int)$_POST['id'];
        $db->exec("UPDATE tagihan_operasional SET status = 'Belum Bayar', tanggal_bayar = NULL WHERE id = $id");
        header('Location: tagihan_operasional.php?pesan=batal_bayar' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }

    if ($action === 'hapus_tagihan') {
        $id = (int)$_POST['id'];
        $db->exec("DELETE FROM tagihan_operasional WHERE id = $id");
        header('Location: tagihan_operasional.php?pesan=dihapus' . ($filter_properti ? "&properti=$filter_properti" : ''));
        exit;
    }
}

// Auto-generate tagihan bulan ini
generateTagihanBulanan($db);

$edit_template = null;
if (isset($_GET['edit_template'])) {
    $et_id = (int)$_GET['edit_template'];
    $stmt = $db->prepare("SELECT * FROM template_tagihan WHERE id = :id");
    $stmt->bindValue(':id', $et_id, SQLITE3_INTEGER);
    $edit_template = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $tab = 'template';
}

$edit_tagihan = null;
if (isset($_GET['edit_tagihan'])) {
    $etg_id = (int)$_GET['edit_tagihan'];
    $stmt = $db->prepare("SELECT * FROM tagihan_operasional WHERE id = :id");
    $stmt->bindValue(':id', $etg_id, SQLITE3_INTEGER);
    $edit_tagihan = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-<?= in_array($_GET['pesan'], ['template_hapus', 'dihapus', 'batal_bayar']) ? 'warning' : 'success' ?> alert-dismissible fade show" role="alert">
        <?php
        echo match($_GET['pesan']) {
            'template_ok' => 'Template tagihan berhasil disimpan!',
            'template_hapus' => 'Template tagihan berhasil dihapus.',
            'manual_ok' => 'Tagihan sekali bayar berhasil ditambahkan!',
            'update_ok' => 'Nominal tagihan berhasil diupdate!',
            'dibayar' => '<i class="bi bi-check-circle me-1"></i> Tagihan ditandai <strong>Sudah Bayar</strong> & dicatat ke pengeluaran!',
            'batal_bayar' => 'Status tagihan dikembalikan ke Belum Bayar.',
            'dihapus' => 'Tagihan berhasil dihapus.',
            default => 'Berhasil!'
        };
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-4 no-print">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'tagihan' ? 'active' : '' ?>" href="?tab=tagihan&properti=<?= $filter_properti ?>">
            <i class="bi bi-list-check me-1"></i>Tagihan Bulanan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'template' ? 'active' : '' ?>" href="?tab=template&properti=<?= $filter_properti ?>">
            <i class="bi bi-repeat me-1"></i>Tagihan Rutin
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'manual' ? 'active' : '' ?>" href="?tab=manual&properti=<?= $filter_properti ?>">
            <i class="bi bi-plus-circle me-1"></i>Tambah Sekali Bayar
        </a>
    </li>
</ul>

<?php if ($tab === 'template'): ?>
<!-- ====== TAB: TEMPLATE RECURRING ====== -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><?= $edit_template ? 'Edit Template' : 'Tambah Template Baru' ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_template ? 'edit_template' : 'tambah_template' ?>">
                <?php if ($edit_template): ?>
                    <input type="hidden" name="id" value="<?= $edit_template['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Properti <span class="text-danger">*</span></label>
                    <select name="properti_id" class="form-select" required>
                        <option value="">-- Pilih Properti --</option>
                        <?php foreach ($properti_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= ($edit_template['properti_id'] ?? $filter_properti) == $pl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Tagihan <span class="text-danger">*</span></label>
                    <select name="jenis_pilih" class="form-select mb-2" id="jenisPilihTemplate">
                        <option value="PLN" <?= ($edit_template['jenis'] ?? '') == 'PLN' ? 'selected' : '' ?>>PLN (Listrik)</option>
                        <option value="PDAM" <?= ($edit_template['jenis'] ?? '') == 'PDAM' ? 'selected' : '' ?>>PDAM (Air)</option>
                        <option value="Keamanan" <?= ($edit_template['jenis'] ?? '') == 'Keamanan' ? 'selected' : '' ?>>Keamanan/Sampah RT</option>
                        <option value="WiFi" <?= ($edit_template['jenis'] ?? '') == 'WiFi' ? 'selected' : '' ?>>WiFi/Internet</option>
                        <option value="__custom" <?= ($edit_template && !in_array($edit_template['jenis'], ['PLN','PDAM','Keamanan','WiFi'])) ? 'selected' : '' ?>>Lainnya (ketik sendiri)...</option>
                    </select>
                    <input type="text" name="jenis_custom" class="form-control" id="jenisCustomTemplate" placeholder="Ketik jenis tagihan..." value="<?= ($edit_template && !in_array($edit_template['jenis'], ['PLN','PDAM','Keamanan','WiFi'])) ? htmlspecialchars($edit_template['jenis']) : '' ?>" style="display:none">
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_template['keterangan'] ?? '') ?>" placeholder="No. meter, ID pelanggan, catatan, dll">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jatuh Tempo Setiap Tanggal <span class="text-danger">*</span></label>
                    <div class="input-group" style="max-width: 180px;">
                        <span class="input-group-text">Tgl</span>
                        <input type="number" name="jatuh_tempo_tanggal" class="form-control" min="1" max="28" required value="<?= $edit_template['jatuh_tempo_tanggal'] ?? 20 ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="isi_manual" id="switchIsiManual" <?= ($edit_template['isi_manual'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="switchIsiManual">Isi nominal manual per bulan</label>
                    </div>
                    <small class="text-muted">Aktifkan untuk tagihan yang nominalnya berubah tiap bulan (PLN, PDAM). Nonaktifkan untuk yang tetap (Keamanan, WiFi).</small>
                </div>
                <div class="mb-3" id="fieldNominalTetap">
                    <label class="form-label">Nominal Tetap/Bulan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="nominal_default" class="form-control input-rupiah" id="inputNominalTetap" value="<?= isset($edit_template) && !$edit_template['isi_manual'] ? number_format($edit_template['nominal_default'], 0, ',', '.') : '' ?>">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Template</button>
                    <?php if ($edit_template): ?>
                        <a href="tagihan_operasional.php?tab=template&properti=<?= $filter_properti ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="table-wrapper">
            <h6 class="fw-bold mb-3">Daftar Template</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Properti</th>
                            <th>Jenis</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Nominal</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tmpl = $db->query("
                            SELECT t.*, pr.nama as nama_properti
                            FROM template_tagihan t
                            JOIN properti pr ON t.properti_id = pr.id
                            ORDER BY pr.nama, t.jenis
                        ");
                        $ada_tmpl = false;
                        while ($row = $tmpl->fetchArray(SQLITE3_ASSOC)):
                            $ada_tmpl = true;
                        ?>
                        <tr class="<?= !$row['aktif'] ? 'table-secondary' : '' ?>">
                            <td>
                                <?= htmlspecialchars($row['nama_properti']) ?>
                                <?= $row['keterangan'] ? '<br><small class="text-muted">' . htmlspecialchars($row['keterangan']) . '</small>' : '' ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['jenis']) ?></strong></td>
                            <td>Tgl <?= $row['jatuh_tempo_tanggal'] ?></td>
                            <td class="text-end">
                                <?php if ($row['isi_manual']): ?>
                                    <span class="text-muted small"><i class="bi bi-pencil me-1"></i>Isi manual</span>
                                <?php else: ?>
                                    <?= formatRupiah($row['nominal_default']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['aktif'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $row['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_template">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $row['aktif'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= $row['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                        <i class="bi <?= $row['aktif'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                    </button>
                                </form>
                                <a href="tagihan_operasional.php?edit_template=<?= $row['id'] ?>&tab=template&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus_template">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada_tmpl): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada template. Tambahkan agar tagihan otomatis ter-generate setiap bulan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Jenis custom toggle
const jenisPilih = document.getElementById('jenisPilihTemplate');
const jenisCustom = document.getElementById('jenisCustomTemplate');
function toggleJenisCustom() {
    jenisCustom.style.display = jenisPilih.value === '__custom' ? 'block' : 'none';
    if (jenisPilih.value !== '__custom') jenisCustom.value = '';
}
jenisPilih?.addEventListener('change', toggleJenisCustom);
toggleJenisCustom();

// Toggle nominal tetap
const switchManual = document.getElementById('switchIsiManual');
const fieldNominal = document.getElementById('fieldNominalTetap');
function toggleNominalTetap() {
    fieldNominal.style.display = switchManual.checked ? 'none' : 'block';
    if (switchManual.checked) document.getElementById('inputNominalTetap').value = '';
}
switchManual?.addEventListener('change', toggleNominalTetap);
toggleNominalTetap();
</script>

<?php elseif ($tab === 'manual'): ?>
<!-- ====== TAB: TAMBAH MANUAL ====== -->
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="form-wrapper">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Tambah Tagihan Sekali Bayar</h6>
            <p class="text-muted small mb-3">Untuk tagihan yang tidak rutin/berulang (misal: pajak bumi, perbaikan pompa, dll).</p>
            <form method="POST">
                <input type="hidden" name="action" value="tambah_manual">
                <div class="mb-3">
                    <label class="form-label">Properti</label>
                    <select name="properti_id" class="form-select">
                        <option value="">-- Umum (tanpa properti) --</option>
                        <?php foreach ($properti_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= $filter_properti == $pl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis/Nama Tagihan <span class="text-danger">*</span></label>
                    <input type="text" name="jenis" class="form-control" required placeholder="Contoh: Pajak Bumi, Servis Pompa, dll">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="nominal" class="form-control input-rupiah" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="date" name="jatuh_tempo" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Tagihan</button>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ====== TAB: TAGIHAN BULANAN ====== -->

<!-- Filter -->
<div class="mb-3 no-print">
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
        <input type="hidden" name="tab" value="tagihan">
        <select name="properti" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
            <option value="">-- Semua Properti --</option>
            <?php foreach ($properti_list as $pl): ?>
                <option value="<?= $pl['id'] ?>" <?= $filter_properti == $pl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pl['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-select form-select-sm" style="width: auto;">
            <option value="">-- Semua Status --</option>
            <option value="Belum Bayar" <?= $filter_status === 'Belum Bayar' ? 'selected' : '' ?>>Belum Bayar</option>
            <option value="Sudah Bayar" <?= $filter_status === 'Sudah Bayar' ? 'selected' : '' ?>>Sudah Bayar</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
    </form>
</div>

<?php if ($edit_tagihan): ?>
<div class="table-wrapper mb-3">
    <h6 class="fw-bold mb-3">Edit Tagihan: <?= htmlspecialchars($edit_tagihan['jenis']) ?></h6>
    <form method="POST" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="update_nominal">
        <input type="hidden" name="id" value="<?= $edit_tagihan['id'] ?>">
        <div class="col-md-4">
            <label class="form-label">Nominal</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" name="nominal" class="form-control input-rupiah" value="<?= $edit_tagihan['nominal'] > 0 ? number_format($edit_tagihan['nominal'], 0, ',', '.') : '' ?>">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($edit_tagihan['keterangan'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
            <a href="tagihan_operasional.php?properti=<?= $filter_properti ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <?php if (!$filter_properti): ?><th>Properti</th><?php endif; ?>
                    <th>Jenis</th>
                    <th>Tipe</th>
                    <th>Periode / Jatuh Tempo</th>
                    <th class="text-end">Nominal</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $where_parts = [];
                if ($filter_properti) $where_parts[] = "t.properti_id = $filter_properti";
                if ($filter_status) $where_parts[] = "t.status = '" . SQLite3::escapeString($filter_status) . "'";
                $where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";
                $data = $db->query("
                    SELECT t.*, pr.nama as nama_properti
                    FROM tagihan_operasional t
                    LEFT JOIN properti pr ON t.properti_id = pr.id
                    $where
                    ORDER BY t.status ASC, t.jatuh_tempo DESC, t.jenis ASC
                ");
                $ada = false;
                $today = date('Y-m-d');
                while ($row = $data->fetchArray(SQLITE3_ASSOC)):
                    $ada = true;
                    $terlambat = ($row['status'] === 'Belum Bayar' && $row['jatuh_tempo'] < $today);
                ?>
                <tr class="<?= $row['status'] == 'Sudah Bayar' ? 'table-success' : ($terlambat ? 'table-danger' : '') ?>">
                    <?php if (!$filter_properti): ?>
                    <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '-') ?></small></td>
                    <?php endif; ?>
                    <td>
                        <strong><?= htmlspecialchars($row['jenis']) ?></strong>
                        <?= $row['keterangan'] ? '<br><small class="text-muted">' . htmlspecialchars($row['keterangan']) . '</small>' : '' ?>
                    </td>
                    <td><span class="badge <?= $row['tipe'] === 'Rutin' ? 'bg-primary' : 'bg-secondary' ?> badge-status"><?= $row['tipe'] ?></span></td>
                    <td>
                        <?= $row['periode'] ? date('M Y', strtotime($row['periode'] . '-01')) . ' — ' : '' ?>
                        <?= formatTanggal($row['jatuh_tempo']) ?>
                        <?php if ($terlambat): ?>
                            <br><small class="text-danger fw-semibold">Terlambat <?= (int)((strtotime($today) - strtotime($row['jatuh_tempo'])) / 86400) ?> hari</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold">
                        <?php if ($row['nominal'] > 0): ?>
                            <?= formatRupiah($row['nominal']) ?>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Belum diisi</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Sudah Bayar'): ?>
                            <span class="badge bg-success badge-status"><i class="bi bi-check-circle me-1"></i>Lunas</span>
                            <?php if ($row['tanggal_bayar']): ?>
                                <br><small class="text-muted"><?= formatTanggal($row['tanggal_bayar']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-danger badge-status">Belum Bayar</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'Belum Bayar'): ?>
                            <?php if ($row['nominal'] > 0): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="bayar">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Bayar" onclick="return confirm('Bayar <?= htmlspecialchars($row['jenis']) ?> <?= formatRupiah($row['nominal']) ?>?')">
                                    <i class="bi bi-check-lg"></i> Bayar
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="tagihan_operasional.php?edit_tagihan=<?= $row['id'] ?>&properti=<?= $filter_properti ?>" class="btn btn-sm btn-outline-primary" title="<?= $row['nominal'] > 0 ? 'Edit' : 'Isi Nominal' ?>">
                                <i class="bi bi-pencil"></i><?= $row['nominal'] <= 0 ? ' Isi Nominal' : '' ?>
                            </a>
                        <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="batal_bayar">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Batalkan" onclick="return confirm('Batalkan status bayar?')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="hapus_tagihan">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$ada): ?>
                <tr><td colspan="<?= $filter_properti ? 6 : 7 ?>" class="text-center text-muted py-3">Belum ada tagihan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
