<?php
$page_title = 'Dashboard';
$base_url = '.';
require_once 'config/database.php';

// Auto-seed: hanya jika database benar-benar baru (belum pernah di-seed atau di-reset)
$ada_properti = $db->querySingle("SELECT COUNT(*) FROM properti");
$pernah_reset = getPengaturan($db, 'sudah_reset');
if ($ada_properti == 0 && !$pernah_reset) {
    require_once 'seed.php';
}

// Auto-generate tagihan bulanan dari template
generateTagihanBulanan($db);

// Handle aksi langsung dari Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Bayar tagihan operasional langsung dari Dashboard
    if ($action === 'bayar_ops') {
        $id = (int)$_POST['id'];
        $nominal_bayar = (int)str_replace('.', '', $_POST['nominal'] ?? '0');

        // Update nominal & status
        $stmt = $db->prepare("UPDATE tagihan_operasional SET nominal = :nom, status = 'Sudah Bayar', tanggal_bayar = :tgl WHERE id = :id");
        $stmt->bindValue(':nom', $nominal_bayar, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', date('Y-m-d'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $tagihan = $db->querySingle("SELECT * FROM tagihan_operasional WHERE id = $id", true);
        if ($tagihan) {
            $stmt = $db->prepare("INSERT INTO pengeluaran (properti_id, kategori, keterangan, nominal, tanggal, no_meter, id_pelanggan) VALUES (:prop, :kat, :ket, :nom, :tgl, :meter, :pelanggan)");
            $stmt->bindValue(':prop', $tagihan['properti_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':kat', $tagihan['jenis'], SQLITE3_TEXT);
            $stmt->bindValue(':ket', 'Bayar ' . $tagihan['jenis'] . ' periode ' . $tagihan['periode'] . ($tagihan['keterangan'] ? ' - ' . $tagihan['keterangan'] : ''), SQLITE3_TEXT);
            $stmt->bindValue(':nom', $nominal_bayar, SQLITE3_INTEGER);
            $stmt->bindValue(':tgl', date('Y-m-d'), SQLITE3_TEXT);
            $stmt->bindValue(':meter', $tagihan['no_meter'], SQLITE3_TEXT);
            $stmt->bindValue(':pelanggan', $tagihan['id_pelanggan'], SQLITE3_TEXT);
            $stmt->execute();
        }

        $prop = $_POST['filter_properti'] ?? '';
        header('Location: index.php?pesan=ops_dibayar' . ($prop ? "&properti=$prop" : ''));
        exit;
    }

    // Catat bayar sewa penyewa langsung dari Dashboard
    if ($action === 'bayar_sewa') {
        $penyewa_id = (int)$_POST['penyewa_id'];
        $kamar_id = (int)$_POST['kamar_id'];
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $properti_id = (int)$_POST['properti_id'];
        $periode = $_POST['periode'] ?? date('Y-m');
        $metode = $_POST['metode_bayar'] ?? 'Tunai';

        $tipe_sewa = $_POST['tipe_sewa'] ?? 'Bulanan';

        $stmt = $db->prepare("INSERT INTO pemasukan (properti_id, penyewa_id, kamar_id, kategori, nominal, tanggal, periode_bulan, metode_bayar) VALUES (:prop, :penyewa, :kamar, 'Sewa', :nom, :tgl, :per, :metode)");
        $stmt->bindValue(':prop', $properti_id, SQLITE3_INTEGER);
        $stmt->bindValue(':penyewa', $penyewa_id, SQLITE3_INTEGER);
        $stmt->bindValue(':kamar', $kamar_id, SQLITE3_INTEGER);
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':tgl', date('Y-m-d'), SQLITE3_TEXT);
        $stmt->bindValue(':per', $periode, SQLITE3_TEXT);
        $stmt->bindValue(':metode', $metode, SQLITE3_TEXT);
        $stmt->execute();

        // Jika tahunan, set bayar_sampai = 12 bulan dari sekarang
        if ($tipe_sewa === 'Tahunan') {
            $bayar_sampai = date('Y-m-d', strtotime('+12 months'));
            $stmt2 = $db->prepare("UPDATE penyewa SET bayar_sampai = :bs WHERE id = :id");
            $stmt2->bindValue(':bs', $bayar_sampai, SQLITE3_TEXT);
            $stmt2->bindValue(':id', $penyewa_id, SQLITE3_INTEGER);
            $stmt2->execute();
        }

        // Jika harian, update bayar_sampai ke tanggal_keluar (lunas)
        if ($tipe_sewa === 'Harian') {
            $tgl_keluar = $db->querySingle("SELECT tanggal_keluar FROM penyewa WHERE id = $penyewa_id");
            if ($tgl_keluar) {
                $stmt3 = $db->prepare("UPDATE penyewa SET bayar_sampai = :bs WHERE id = :id");
                $stmt3->bindValue(':bs', $tgl_keluar, SQLITE3_TEXT);
                $stmt3->bindValue(':id', $penyewa_id, SQLITE3_INTEGER);
                $stmt3->execute();
            }
            // Auto-checkout jika dicentang
            if (isset($_POST['auto_checkout'])) {
                $kamar_checkout = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $penyewa_id");
                $db->exec("UPDATE penyewa SET status = 'Nonaktif' WHERE id = $penyewa_id");
                if ($kamar_checkout) {
                    $db->exec("UPDATE kamar SET status = 'Kosong' WHERE id = $kamar_checkout");
                }
            }
        }

        $last_id = $db->lastInsertRowID();
        $prop = $_POST['filter_properti'] ?? '';
        header('Location: index.php?pesan=sewa_dibayar&cetak=' . $last_id . ($prop ? "&properti=$prop" : ''));
        exit;
    }

    // Checkout harian (sudah lunas, checkout saja)
    if ($action === 'checkout_harian') {
        $penyewa_id = (int)$_POST['penyewa_id'];
        $kamar_id = $db->querySingle("SELECT kamar_id FROM penyewa WHERE id = $penyewa_id");
        $db->exec("UPDATE penyewa SET status = 'Nonaktif' WHERE id = $penyewa_id");
        if ($kamar_id) {
            $db->exec("UPDATE kamar SET status = 'Kosong' WHERE id = $kamar_id");
        }
        $prop = $_POST['filter_properti'] ?? '';
        header('Location: index.php?pesan=checkout_ok' . ($prop ? "&properti=$prop" : ''));
        exit;
    }

    // Simpan nominal tagihan operasional (tanpa bayar, hanya update nominal)
    if ($action === 'isi_nominal_ops') {
        $id = (int)$_POST['id'];
        $nominal = (int)str_replace('.', '', $_POST['nominal'] ?? '0');
        $stmt = $db->prepare("UPDATE tagihan_operasional SET nominal = :nom WHERE id = :id");
        $stmt->bindValue(':nom', $nominal, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $prop = $_POST['filter_properti'] ?? '';
        header('Location: index.php?pesan=nominal_disimpan' . ($prop ? "&properti=$prop" : ''));
        exit;
    }
}

$is_demo = getPengaturan($db, 'is_demo') === '1';
$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';

$today = date('Y-m-d');
$bulan_ini = date('Y-m');

$where_prop_k = $filter_properti ? "AND k.properti_id = $filter_properti" : "";
$where_prop = $filter_properti ? "AND properti_id = $filter_properti" : "";

// --- Statistik ---
$total_kamar = $db->querySingle("SELECT COUNT(*) FROM kamar k WHERE 1=1 $where_prop_k");
$kamar_terisi = $db->querySingle("SELECT COUNT(*) FROM kamar k WHERE status = 'Terisi' $where_prop_k");
$kamar_kosong = $db->querySingle("SELECT COUNT(*) FROM kamar k WHERE status = 'Kosong' $where_prop_k");

$where_prop_pm = $filter_properti ? "AND properti_id = $filter_properti" : "";
$pemasukan_bulan = $db->querySingle("SELECT COALESCE(SUM(nominal),0) FROM pemasukan WHERE strftime('%Y-%m', tanggal) = '$bulan_ini' $where_prop_pm");
$pengeluaran_bulan = $db->querySingle("SELECT COALESCE(SUM(nominal),0) FROM pengeluaran WHERE strftime('%Y-%m', tanggal) = '$bulan_ini' $where_prop");
$maintenance_bulan = $db->querySingle("SELECT COALESCE(SUM(biaya),0) FROM maintenance WHERE strftime('%Y-%m', tanggal) = '$bulan_ini' $where_prop");
$total_pengeluaran = $pengeluaran_bulan + $maintenance_bulan;
$laba_bulan = $pemasukan_bulan - $total_pengeluaran;

// --- Tagihan Penyewa (Jatuh Tempo) ---
$where_penyewa = $filter_properti ? "AND k.properti_id = $filter_properti" : "";
$penyewa_aktif = $db->query("
    SELECT p.*, k.nomor_kamar, k.harga_bulanan, k.harga_tahunan, k.properti_id as prop_id, pr.nama as nama_properti
    FROM penyewa p
    LEFT JOIN kamar k ON p.kamar_id = k.id
    LEFT JOIN properti pr ON k.properti_id = pr.id
    WHERE p.status = 'Aktif' $where_penyewa
");

$alert_nunggak = [];
$alert_mendekati = [];
$info_tahunan = [];
$alert_harian_checkout = []; // Penyewa harian sudah lunas, mendekati/hari ini checkout
$alert_harian_nunggak = [];  // Penyewa harian perpanjang tapi ada malam belum dibayar
$alert_harian_lewat = [];    // Penyewa harian lewat batas checkout
while ($row = $penyewa_aktif->fetchArray(SQLITE3_ASSOC)) {
    // === Penyewa HARIAN ===
    if ($row['tipe_sewa'] === 'Harian' && $row['tanggal_keluar']) {
        $harga_malam = $row['harga_sewa'] ?: 0;
        $sisa_checkout = (int)((strtotime($row['tanggal_keluar']) - strtotime($today)) / 86400);
        $total_malam = max(1, (int)((strtotime($row['tanggal_keluar']) - strtotime($row['tanggal_masuk'])) / 86400));
        $total_tagihan = $total_malam * $harga_malam;

        // Hitung total yang sudah dibayar dari tabel pemasukan
        $total_dibayar = $db->querySingle("SELECT COALESCE(SUM(nominal),0) FROM pemasukan WHERE penyewa_id = {$row['id']} AND kategori = 'Sewa'");
        $sisa_tagihan = $total_tagihan - $total_dibayar;
        $malam_belum_bayar = ($harga_malam > 0) ? max(0, (int)ceil($sisa_tagihan / $harga_malam)) : 0;

        $row['harga_malam'] = $harga_malam;
        $row['total_malam'] = $total_malam;
        $row['total_tagihan'] = $total_tagihan;
        $row['total_dibayar'] = $total_dibayar;
        $row['sisa_checkout'] = $sisa_checkout;
        $row['malam_belum_bayar'] = $malam_belum_bayar;
        $row['nominal_belum_bayar'] = max(0, $sisa_tagihan);

        if ($sisa_checkout < 0 && $sisa_tagihan > 0) {
            // Lewat batas checkout DAN masih ada tagihan
            $row['status_harian'] = 'Lewat';
            $alert_harian_lewat[] = $row;
        } elseif ($sisa_tagihan > 0 && $sisa_checkout <= 1) {
            // Ada sisa tagihan dan mendekati/hari checkout
            $row['status_harian'] = 'Nunggak';
            $alert_harian_nunggak[] = $row;
        } elseif ($sisa_checkout < 0 && $sisa_tagihan <= 0) {
            // Lewat checkout tapi sudah lunas → pengingat checkout
            $row['status_harian'] = 'Checkout';
            $alert_harian_checkout[] = $row;
        } elseif ($sisa_tagihan <= 0 && $sisa_checkout <= 1 && $sisa_checkout >= 0) {
            // Sudah lunas, checkout hari ini/besok
            $row['status_harian'] = 'Checkout';
            $alert_harian_checkout[] = $row;
        }
        continue;
    }

    // === Penyewa TAHUNAN ===
    if ($row['tipe_sewa'] === 'Tahunan' && $row['bayar_sampai'] && $row['bayar_sampai'] >= $today) {
        $row['sisa_hari'] = (int)((strtotime($row['bayar_sampai']) - strtotime($today)) / 86400);
        if ($row['sisa_hari'] <= 30) {
            $info_tahunan[] = $row;
        }
        continue;
    }

    // === Penyewa BULANAN ===
    $jt_tanggal = (int)$row['jatuh_tempo_tanggal'];
    $bulan_jt = date('Y-m');
    $jt = $bulan_jt . '-' . str_pad($jt_tanggal, 2, '0', STR_PAD_LEFT);

    if ($jt < $today) {
        $sudah_bayar = $db->querySingle("SELECT COUNT(*) FROM pemasukan WHERE penyewa_id = {$row['id']} AND kategori = 'Sewa' AND periode_bulan = '$bulan_jt'");
        if (!$sudah_bayar) {
            $selisih = (strtotime($today) - strtotime($jt)) / 86400;
            $row['jatuh_tempo'] = $jt;
            $row['selisih_hari'] = (int)$selisih;
            $row['status_bayar'] = 'Nunggak';
            $alert_nunggak[] = $row;
        }
    } else {
        $selisih = (strtotime($jt) - strtotime($today)) / 86400;
        if ($selisih <= 3) {
            $sudah_bayar = $db->querySingle("SELECT COUNT(*) FROM pemasukan WHERE penyewa_id = {$row['id']} AND kategori = 'Sewa' AND periode_bulan = '$bulan_jt'");
            if (!$sudah_bayar) {
                $row['jatuh_tempo'] = $jt;
                $row['selisih_hari'] = (int)$selisih;
                $row['status_bayar'] = 'Mendekati';
                $alert_mendekati[] = $row;
            }
        }
    }
}

$alert_penyewa = array_merge($alert_nunggak, $alert_mendekati);

// --- Tagihan Operasional ---
$where_ops = $filter_properti ? "AND properti_id = $filter_properti" : "";
$tagihan_ops = $db->query("
    SELECT t.*, pr.nama as nama_properti
    FROM tagihan_operasional t
    LEFT JOIN properti pr ON t.properti_id = pr.id
    WHERE t.status = 'Belum Bayar' $where_ops
    ORDER BY t.jatuh_tempo ASC
");
$alert_ops_terlambat = [];
$alert_ops_mendekati = [];
while ($row = $tagihan_ops->fetchArray(SQLITE3_ASSOC)) {
    $selisih = (strtotime($row['jatuh_tempo']) - strtotime($today)) / 86400;
    $row['selisih_hari'] = (int)$selisih;
    if ($selisih < 0) {
        $alert_ops_terlambat[] = $row;
    } elseif ($selisih <= 7) {
        $alert_ops_mendekati[] = $row;
    }
}
$alert_operasional = array_merge($alert_ops_terlambat, $alert_ops_mendekati);

// Helper: convert 08xx ke 628xx untuk WA
function convertToWA($no_hp) {
    $no = preg_replace('/[^0-9]/', '', $no_hp);
    if (str_starts_with($no, '0')) {
        $no = '62' . substr($no, 1);
    }
    return $no;
}

// Helper: generate WA link
function generateWALink($penyewa) {
    $nama = $penyewa['nama'];
    $kamar = $penyewa['nomor_kamar'] ?? '-';
    $properti = $penyewa['nama_properti'] ?? '';
    $jatuh_tempo = formatTanggal($penyewa['jatuh_tempo']);
    $tagihan = formatRupiah($penyewa['harga_bulanan'] ?? 0);

    $pesan = "Halo *{$nama}*,\n\n"
        . "Kami ingin mengingatkan bahwa tagihan sewa kos Anda:\n\n"
        . "Properti: *{$properti}*\n"
        . "Kamar: *{$kamar}*\n"
        . "Jatuh Tempo: *{$jatuh_tempo}*\n"
        . "Total Tagihan: *{$tagihan}*\n\n"
        . "Mohon segera melakukan pembayaran. Terima kasih.";

    $no_wa = convertToWA($penyewa['no_hp'] ?? '');
    if (!$no_wa) return '';
    return 'https://wa.me/' . $no_wa . '?text=' . rawurlencode($pesan);
}

require_once 'includes/header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php if ($_GET['pesan'] === 'ops_dibayar'): ?>
            <i class="bi bi-check-circle me-1"></i> Tagihan operasional berhasil dibayar & dicatat ke pengeluaran!
        <?php elseif ($_GET['pesan'] === 'checkout_ok'): ?>
            <i class="bi bi-check-circle me-1"></i> Penyewa berhasil checkout! Kamar kembali kosong.
        <?php elseif ($_GET['pesan'] === 'nominal_disimpan'): ?>
            <i class="bi bi-check-circle me-1"></i> Nominal tagihan berhasil disimpan! Klik "Bayar" jika sudah siap membayar.
        <?php elseif ($_GET['pesan'] === 'sewa_dibayar'): ?>
            <i class="bi bi-check-circle me-1"></i> Pembayaran sewa berhasil dicatat!
            <?php if (isset($_GET['cetak'])): ?>
                <a href="pages/kuitansi.php?id=<?= (int)$_GET['cetak'] ?>" class="alert-link ms-2" target="_blank"><i class="bi bi-printer"></i> Cetak Kuitansi</a>
            <?php endif; ?>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($is_demo): ?>
<div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
    <div class="d-flex align-items-center">
        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
        <div>
            <strong>Mode Demo</strong> — Aplikasi berisi data contoh agar Anda bisa menjelajahi semua fitur.
            Untuk mulai menggunakan data nyata, hapus data demo melalui
            <a href="pages/pengaturan.php" class="alert-link fw-bold">Pengaturan &rarr; Reset Seluruh Database</a>.
            Butuh panduan? Buka <a href="pages/bantuan.php" class="alert-link fw-bold">halaman Bantuan</a>.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

<?php
// ==================== ALERT AREA ====================
$ada_alert = !empty($alert_nunggak) || !empty($alert_mendekati) || !empty($alert_ops_terlambat) || !empty($info_tahunan) || !empty($alert_harian_lewat) || !empty($alert_harian_nunggak) || !empty($alert_harian_checkout);
if ($ada_alert):
?>
<div class="mb-4" id="alertArea">
    <?php if (!empty($alert_nunggak)): ?>
    <a href="pages/pemasukan.php" class="alert alert-danger d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <strong>Ada <?= count($alert_nunggak) ?> Penyewa Nunggak Bayar!</strong>
            <div class="small">Klik untuk mencatat pembayaran di halaman Pemasukan.</div>
        </div>
        <i class="bi bi-chevron-right ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($alert_ops_terlambat)):
        $jenis_terlambat = array_unique(array_column($alert_ops_terlambat, 'jenis'));
        $label_jenis = implode(', ', $jenis_terlambat);
    ?>
    <a href="pages/tagihan_operasional.php?status=Belum+Bayar" class="alert alert-danger d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-lightning-fill fs-4 me-3"></i>
        <div>
            <strong>Tagihan <?= htmlspecialchars($label_jenis) ?> Lewat Jatuh Tempo!</strong>
            <div class="small">Klik untuk membayar di halaman Tagihan Operasional.</div>
        </div>
        <i class="bi bi-chevron-right ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($alert_mendekati)): ?>
    <a href="pages/pemasukan.php" class="alert alert-warning d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-clock-fill fs-4 me-3"></i>
        <div>
            <strong><?= count($alert_mendekati) ?> Penyewa Jatuh Tempo dalam 3 Hari ke Depan</strong>
            <div class="small">Klik untuk mencatat pembayaran atau kirim pengingat via WhatsApp.</div>
        </div>
        <i class="bi bi-chevron-right ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($alert_harian_lewat)): ?>
    <a href="#tabelHarian" class="alert alert-danger d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
        <div>
            <strong><?= count($alert_harian_lewat) ?> Penyewa Harian Lewat Batas Checkout!</strong>
            <div class="small">Sudah melewati tanggal checkout. Segera tindak lanjuti.</div>
        </div>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($alert_harian_nunggak)): ?>
    <a href="#tabelHarian" class="alert alert-warning d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-cash-coin fs-4 me-3"></i>
        <div>
            <strong><?= count($alert_harian_nunggak) ?> Penyewa Harian Ada Sisa Tagihan</strong>
            <div class="small">Perpanjang hari tapi ada malam yang belum dibayar. Klik untuk detail.</div>
        </div>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($alert_harian_checkout)): ?>
    <a href="#tabelHarian" class="alert alert-info d-flex align-items-center mb-2 text-decoration-none">
        <i class="bi bi-box-arrow-right fs-4 me-3"></i>
        <div>
            <strong><?= count($alert_harian_checkout) ?> Penyewa Harian Checkout <?= $alert_harian_checkout[0]['sisa_checkout'] == 0 ? 'Hari Ini' : 'Besok' ?></strong>
            <div class="small">Sudah lunas. Pengingat checkout saja.</div>
        </div>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($info_tahunan)): ?>
    <?php foreach ($info_tahunan as $it): ?>
    <div class="alert alert-info d-flex align-items-center mb-2">
        <i class="bi bi-calendar-event fs-4 me-3"></i>
        <div>
            <strong>Sewa Tahunan <?= htmlspecialchars($it['nama']) ?></strong> (Kamar <?= htmlspecialchars($it['nomor_kamar']) ?>) —
            <?php if ($it['sisa_hari'] <= 0): ?>
                <span class="text-danger fw-bold">Sudah habis! Perlu perpanjangan.</span>
            <?php else: ?>
                Berakhir <?= formatTanggal($it['bayar_sampai']) ?> <span class="badge bg-info ms-1">Sisa <?= $it['sisa_hari'] ?> hari</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Statistik Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-door-open"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Kamar</div>
                    <div class="fw-bold fs-4"><?= $total_kamar ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <div class="text-muted small">Terisi / Kosong</div>
                    <div class="fw-bold fs-4"><?= $kamar_terisi ?> / <?= $kamar_kosong ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 border-info">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="text-muted small">Pemasukan Bulan Ini</div>
                    <div class="fw-bold fs-5"><?= formatRupiah($pemasukan_bulan) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-4 <?= $laba_bulan >= 0 ? 'border-success' : 'border-danger' ?>">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon <?= $laba_bulan >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?> me-3">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="text-muted small">Laba Bersih Bulan Ini</div>
                    <div class="fw-bold fs-5"><?= formatRupiah($laba_bulan) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Tabel Tagihan Penyewa -->
    <div class="col-lg-6">
        <div class="table-wrapper" id="tabelTagihanPenyewa">
            <h6 class="fw-bold mb-3"><i class="bi bi-bell-fill text-danger me-2"></i>Tagihan Penyewa</h6>
            <?php if (empty($alert_penyewa)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="mt-2 mb-0">Tidak ada tagihan mendekati jatuh tempo</p>
                </div>
            <?php else: ?>
                <?php foreach ($alert_penyewa as $ap):
                    $wa_link = generateWALink($ap);
                    $modal_sewa_id = 'modalSewa' . $ap['id'];
                ?>
                    <div class="alert-card <?= $ap['status_bayar'] == 'Nunggak' ? 'danger' : 'warning' ?> p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($ap['nama']) ?></strong>
                                <span class="text-muted">- Kamar <?= htmlspecialchars($ap['nomor_kamar']) ?></span>
                                <br><small class="text-muted"><?= htmlspecialchars($ap['nama_properti'] ?? '') ?></small>
                                <br><small class="text-muted">Jatuh tempo: <?= formatTanggal($ap['jatuh_tempo']) ?></small>
                                <br><small>Tagihan: <strong><?= formatRupiah($ap['harga_bulanan'] ?? 0) ?></strong></small>
                            </div>
                            <div class="text-end">
                                <?php if ($ap['status_bayar'] == 'Nunggak'): ?>
                                    <span class="badge bg-danger badge-status mb-1">Nunggak <?= $ap['selisih_hari'] ?> hari</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark badge-status mb-1">H-<?= $ap['selisih_hari'] ?></span>
                                <?php endif; ?>
                                <div class="mt-1 d-flex flex-column gap-1">
                                    <button type="button" class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#<?= $modal_sewa_id ?>">
                                        <i class="bi bi-cash me-1"></i>Catat Bayar
                                    </button>
                                    <?php if ($wa_link): ?>
                                    <a href="<?= $wa_link ?>" target="_blank" class="btn btn-sm btn-success w-100">
                                        <i class="bi bi-whatsapp me-1"></i>Tagih via WA
                                    </a>
                                    <?php else: ?>
                                    <small class="text-muted">No. HP belum diisi</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Bayar Sewa -->
                    <?php
                    $is_tahunan = ($ap['tipe_sewa'] === 'Tahunan');
                    $nominal_default = $is_tahunan ? ($ap['harga_tahunan'] ?? 0) : ($ap['harga_bulanan'] ?? 0);
                    ?>
                    <div class="modal fade" id="<?= $modal_sewa_id ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="bayar_sewa">
                                    <input type="hidden" name="penyewa_id" value="<?= $ap['id'] ?>">
                                    <input type="hidden" name="kamar_id" value="<?= $ap['kamar_id'] ?>">
                                    <input type="hidden" name="properti_id" value="<?= $ap['prop_id'] ?? 0 ?>">
                                    <input type="hidden" name="tipe_sewa" value="<?= $ap['tipe_sewa'] ?>">
                                    <input type="hidden" name="periode" value="<?= date('Y-m') ?>">
                                    <input type="hidden" name="filter_properti" value="<?= $filter_properti ?>">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">
                                            <i class="bi bi-cash-stack me-2"></i>Catat Bayar Sewa <?= $is_tahunan ? '(Tahunan)' : '' ?>
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div><strong><?= htmlspecialchars($ap['nama']) ?></strong></div>
                                            <div class="small text-muted"><?= htmlspecialchars($ap['nama_properti'] ?? '') ?> — Kamar <?= htmlspecialchars($ap['nomor_kamar']) ?></div>
                                            <div class="small text-muted">
                                                Tipe sewa: <strong><?= $ap['tipe_sewa'] ?></strong>
                                                <?php if ($is_tahunan): ?>
                                                    — Setelah dibayar, warning tidak akan muncul selama 12 bulan ke depan.
                                                <?php else: ?>
                                                    — Periode: <?= date('F Y') ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nominal Bayar <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" name="nominal" class="form-control input-rupiah" required value="<?= number_format($nominal_default, 0, ',', '.') ?>">
                                            </div>
                                            <small class="text-muted">
                                                <?php if ($is_tahunan): ?>
                                                    Harga tahunan: <?= formatRupiah($ap['harga_tahunan'] ?? 0) ?>. Ubah jika berbeda.
                                                <?php else: ?>
                                                    Harga bulanan: <?= formatRupiah($ap['harga_bulanan'] ?? 0) ?>. Ubah jika berbeda.
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Metode Bayar</label>
                                            <select name="metode_bayar" class="form-select">
                                                <option value="Tunai">Tunai</option>
                                                <option value="Transfer">Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Pembayaran</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php
            $semua_harian = array_merge($alert_harian_lewat, $alert_harian_nunggak, $alert_harian_checkout);
            if (!empty($semua_harian)):
            ?>
                <h6 class="fw-bold mt-4 mb-3" id="tabelHarian"><i class="bi bi-clock-history text-warning me-2"></i>Penyewa Harian</h6>
                <?php foreach ($semua_harian as $ah):
                    $modal_harian_id = 'modalHarian' . $ah['id'];
                    $wa_link_h = ($ah['no_hp'] && $ah['malam_belum_bayar'] > 0) ? generateWALink(array_merge($ah, [
                        'jatuh_tempo' => $ah['tanggal_keluar'],
                        'harga_bulanan' => $ah['nominal_belum_bayar']
                    ])) : '';
                    // Warna card
                    $card_class = match($ah['status_harian']) {
                        'Lewat' => 'danger',
                        'Nunggak' => 'warning',
                        default => 'info'
                    };
                ?>
                    <div class="alert-card <?= $card_class ?> p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($ah['nama']) ?></strong>
                                <span class="text-muted">- Kamar <?= htmlspecialchars($ah['nomor_kamar']) ?></span>
                                <br><small class="text-muted"><?= htmlspecialchars($ah['nama_properti'] ?? '') ?></small>
                                <br><small class="text-muted">Check-in: <?= formatTanggal($ah['tanggal_masuk']) ?> — Checkout: <?= formatTanggal($ah['tanggal_keluar']) ?></small>
                                <br><small><?= $ah['total_malam'] ?> malam x <?= formatRupiah($ah['harga_malam']) ?> = <?= formatRupiah($ah['total_tagihan']) ?></small>
                                <?php if ($ah['total_dibayar'] > 0): ?>
                                    <br><small class="text-muted">Sudah dibayar: <?= formatRupiah($ah['total_dibayar']) ?></small>
                                <?php endif; ?>
                                <?php if ($ah['status_harian'] === 'Checkout'): ?>
                                    <br><small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Lunas — pengingat checkout</small>
                                <?php elseif ($ah['nominal_belum_bayar'] > 0): ?>
                                    <br><small class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Sisa tagihan: <?= formatRupiah($ah['nominal_belum_bayar']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <?php if ($ah['status_harian'] === 'Lewat'): ?>
                                    <span class="badge bg-danger badge-status mb-1">Lewat <?= abs($ah['sisa_checkout']) ?> hari</span>
                                <?php elseif ($ah['status_harian'] === 'Nunggak'): ?>
                                    <span class="badge bg-warning text-dark badge-status mb-1">Sisa tagihan</span>
                                <?php else: ?>
                                    <span class="badge bg-info badge-status mb-1"><?= $ah['sisa_checkout'] == 0 ? 'Checkout hari ini' : 'Checkout besok' ?></span>
                                <?php endif; ?>
                                <div class="mt-1 d-flex flex-column gap-1">
                                    <?php if ($ah['malam_belum_bayar'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#<?= $modal_harian_id ?>">
                                        <i class="bi bi-cash me-1"></i>Bayar Sisa
                                    </button>
                                    <?php if ($wa_link_h): ?>
                                    <a href="<?= $wa_link_h ?>" target="_blank" class="btn btn-sm btn-success w-100">
                                        <i class="bi bi-whatsapp me-1"></i>Tagih via WA
                                    </a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="checkout_harian">
                                        <input type="hidden" name="penyewa_id" value="<?= $ah['id'] ?>">
                                        <input type="hidden" name="filter_properti" value="<?= $filter_properti ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100" onclick="return confirm('Checkout <?= htmlspecialchars($ah['nama']) ?>?')">
                                            <i class="bi bi-box-arrow-right me-1"></i>Checkout
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($ah['malam_belum_bayar'] > 0): ?>
                    <!-- Modal Bayar Sisa Harian -->
                    <div class="modal fade" id="<?= $modal_harian_id ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="bayar_sewa">
                                    <input type="hidden" name="penyewa_id" value="<?= $ah['id'] ?>">
                                    <input type="hidden" name="kamar_id" value="<?= $ah['kamar_id'] ?>">
                                    <input type="hidden" name="properti_id" value="<?= $ah['prop_id'] ?? 0 ?>">
                                    <input type="hidden" name="tipe_sewa" value="Harian">
                                    <input type="hidden" name="periode" value="<?= date('Y-m') ?>">
                                    <input type="hidden" name="filter_properti" value="<?= $filter_properti ?>">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2"></i>Bayar Sisa (Harian)</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div><strong><?= htmlspecialchars($ah['nama']) ?></strong></div>
                                            <div class="small text-muted"><?= htmlspecialchars($ah['nama_properti'] ?? '') ?> — Kamar <?= htmlspecialchars($ah['nomor_kamar']) ?></div>
                                            <div class="small text-muted">Check-in: <?= formatTanggal($ah['tanggal_masuk']) ?> — Checkout: <?= formatTanggal($ah['tanggal_keluar']) ?></div>
                                            <div class="small mt-1">
                                                Total tagihan: <strong><?= formatRupiah($ah['total_tagihan']) ?></strong>
                                                (<?= $ah['total_malam'] ?> malam x <?= formatRupiah($ah['harga_malam']) ?>)
                                                <?php if ($ah['total_dibayar'] > 0): ?>
                                                    <br>Sudah dibayar: <strong><?= formatRupiah($ah['total_dibayar']) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Sisa yang Harus Dibayar <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" name="nominal" class="form-control input-rupiah" required value="<?= number_format($ah['nominal_belum_bayar'], 0, ',', '.') ?>">
                                            </div>
                                            <small class="text-muted">Sisa: <?= formatRupiah($ah['nominal_belum_bayar']) ?>. Ubah jika berbeda.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Metode Bayar</label>
                                            <select name="metode_bayar" class="form-select">
                                                <option value="Tunai">Tunai</option>
                                                <option value="Transfer">Transfer</option>
                                            </select>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="auto_checkout" value="1" checked id="ckOut<?= $ah['id'] ?>">
                                            <label class="form-check-label small" for="ckOut<?= $ah['id'] ?>">Otomatis checkout setelah bayar</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Bayar & Checkout</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Tagihan Operasional -->
    <div class="col-lg-6">
        <div class="table-wrapper" id="tabelTagihanOps">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>Tagihan Operasional</h6>
            <?php if (empty($alert_operasional)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="mt-2 mb-0">Tidak ada tagihan operasional mendekati jatuh tempo</p>
                </div>
            <?php else: ?>
                <?php foreach ($alert_operasional as $ao):
                    $modal_ops_id = 'modalOps' . $ao['id'];
                    $belum_isi_nominal = ($ao['nominal'] <= 0);
                ?>
                    <div class="alert-card <?= $ao['selisih_hari'] < 0 ? 'danger' : ($ao['selisih_hari'] <= 3 ? 'warning' : 'info') ?> p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($ao['jenis']) ?></strong>
                                <span class="text-muted">- <?= htmlspecialchars($ao['nama_properti'] ?? '') ?></span>
                                <?php if ($ao['keterangan']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($ao['keterangan']) ?></small>
                                <?php endif; ?>
                                <?php if ($belum_isi_nominal): ?>
                                    <br><small class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Nominal belum diisi</small>
                                <?php else: ?>
                                    <br><small>Nominal: <strong><?= formatRupiah($ao['nominal']) ?></strong></small>
                                <?php endif; ?>
                                <br><small class="text-muted">Jatuh tempo: <?= formatTanggal($ao['jatuh_tempo']) ?></small>
                            </div>
                            <div class="text-end">
                                <?php if ($ao['selisih_hari'] < 0): ?>
                                    <span class="badge bg-danger badge-status mb-1">Terlambat <?= abs($ao['selisih_hari']) ?> hari</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark badge-status mb-1">H-<?= $ao['selisih_hari'] ?></span>
                                <?php endif; ?>
                                <div class="mt-1">
                                    <?php if ($belum_isi_nominal): ?>
                                    <button type="button" class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" data-bs-target="#<?= $modal_ops_id ?>">
                                        <i class="bi bi-pencil-square me-1"></i>Isi Nominal
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success w-100" data-bs-toggle="modal" data-bs-target="#<?= $modal_ops_id ?>">
                                        <i class="bi bi-check-circle me-1"></i>Bayar
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Operasional -->
                    <div class="modal fade" id="<?= $modal_ops_id ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="<?= $belum_isi_nominal ? 'isi_nominal_ops' : 'bayar_ops' ?>">
                                    <input type="hidden" name="id" value="<?= $ao['id'] ?>">
                                    <input type="hidden" name="filter_properti" value="<?= $filter_properti ?>">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">
                                            <?php if ($belum_isi_nominal): ?>
                                                <i class="bi bi-pencil-square me-2"></i>Isi Nominal <?= htmlspecialchars($ao['jenis']) ?>
                                            <?php else: ?>
                                                <i class="bi bi-lightning me-2"></i>Bayar <?= htmlspecialchars($ao['jenis']) ?>
                                            <?php endif; ?>
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div><strong><?= htmlspecialchars($ao['jenis']) ?></strong> — <?= htmlspecialchars($ao['nama_properti'] ?? '') ?></div>
                                            <div class="small text-muted">Periode: <?= $ao['periode'] ? date('F Y', strtotime($ao['periode'] . '-01')) : '-' ?></div>
                                            <?php if ($ao['keterangan']): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($ao['keterangan']) ?></div>
                                            <?php endif; ?>
                                            <div class="small text-muted">Jatuh tempo: <?= formatTanggal($ao['jatuh_tempo']) ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">
                                                <?= $belum_isi_nominal ? 'Nominal Tagihan Bulan Ini' : 'Nominal Bayar' ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" name="nominal" class="form-control input-rupiah" required value="<?= $ao['nominal'] > 0 ? number_format($ao['nominal'], 0, ',', '.') : '' ?>" placeholder="Masukkan nominal" autofocus>
                                            </div>
                                            <?php if ($belum_isi_nominal): ?>
                                                <small class="text-muted">Masukkan nominal sesuai tagihan <?= htmlspecialchars($ao['jenis']) ?> bulan ini. Setelah diisi, Anda bisa langsung membayar.</small>
                                            <?php else: ?>
                                                <small class="text-muted">Ubah nominal jika perlu, lalu klik Bayar.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <?php if ($belum_isi_nominal): ?>
                                            <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Simpan Nominal</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Bayar & Catat Pengeluaran</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Maintenance Terakhir -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="table-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-tools text-secondary me-2"></i>Maintenance Terakhir</h6>
                <a href="pages/maintenance.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Properti</th>
                            <th>Kamar</th>
                            <th>Tipe</th>
                            <th>Keterangan</th>
                            <th class="text-end">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_mt = $filter_properti ? "WHERE m.properti_id = $filter_properti" : "";
                        $mt = $db->query("
                            SELECT m.*, k.nomor_kamar, pr.nama as nama_properti
                            FROM maintenance m
                            LEFT JOIN kamar k ON m.kamar_id = k.id
                            LEFT JOIN properti pr ON m.properti_id = pr.id
                            $where_mt
                            ORDER BY m.tanggal DESC LIMIT 5
                        ");
                        $ada_mt = false;
                        while ($row = $mt->fetchArray(SQLITE3_ASSOC)):
                            $ada_mt = true;
                        ?>
                        <tr>
                            <td><?= formatTanggal($row['tanggal']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($row['nama_properti'] ?? '-') ?></small></td>
                            <td><?= $row['nomor_kamar'] ? 'Kamar ' . htmlspecialchars($row['nomor_kamar']) : '<span class="text-muted">Umum</span>' ?></td>
                            <td><span class="badge <?= $row['tipe'] == 'Spesifik' ? 'bg-info' : 'bg-secondary' ?>"><?= $row['tipe'] ?></span></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td class="text-end fw-semibold"><?= formatRupiah($row['biaya']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$ada_mt): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data maintenance</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
