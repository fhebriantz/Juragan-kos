<?php
$page_title = 'Laporan';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';

$properti_list = getPropertiList($db);
$filter_properti = $_GET['properti'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');

require_once __DIR__ . '/../includes/header.php';

$bulan_nama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$where_prop = $filter_properti ? "AND properti_id = $filter_properti" : "";

// === Cari semua kategori pengeluaran yang ada di tahun ini ===
$kat_result = $db->query("SELECT DISTINCT kategori FROM pengeluaran WHERE " . sqlYear('tanggal') . " = '$filter_tahun' $where_prop ORDER BY kategori");
$kategori_pengeluaran = [];
while ($row = $kat_result->fetch()) {
    $kategori_pengeluaran[] = $row['kategori'];
}
// Pastikan minimal ada kolom walaupun kosong
if (empty($kategori_pengeluaran)) {
    $kategori_pengeluaran = ['Operasional'];
}

// === Hitung data per bulan ===
$data_bulanan = [];
$total_pemasukan_tahun = 0;
$total_pengeluaran_tahun = 0;
$total_maintenance_tahun = 0;
$total_per_kategori = [];
foreach ($kategori_pengeluaran as $kat) {
    $total_per_kategori[$kat] = 0;
}

for ($m = 1; $m <= 12; $m++) {
    $bln = $filter_tahun . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);

    // Pemasukan
    $pemasukan_sewa = dbValue("SELECT COALESCE(SUM(nominal),0) FROM pemasukan WHERE " . sqlYearMonth('tanggal') . " = '$bln' AND kategori = 'Sewa' $where_prop");
    $pemasukan_lain = dbValue("SELECT COALESCE(SUM(nominal),0) FROM pemasukan WHERE " . sqlYearMonth('tanggal') . " = '$bln' AND kategori != 'Sewa' $where_prop");
    $total_masuk = $pemasukan_sewa + $pemasukan_lain;

    // Pengeluaran per kategori (dinamis)
    $pengeluaran_detail = [];
    $total_pengeluaran_bulan = 0;
    foreach ($kategori_pengeluaran as $kat) {
        $kat_escaped = dbEscape($kat);
        $nom = dbValue("SELECT COALESCE(SUM(nominal),0) FROM pengeluaran WHERE " . sqlYearMonth('tanggal') . " = '$bln' AND kategori = '$kat_escaped' $where_prop");
        $pengeluaran_detail[$kat] = $nom;
        $total_pengeluaran_bulan += $nom;
        $total_per_kategori[$kat] += $nom;
    }

    // Maintenance (terpisah dari tabel pengeluaran)
    $maintenance = dbValue("SELECT COALESCE(SUM(biaya),0) FROM maintenance WHERE " . sqlYearMonth('tanggal') . " = '$bln' $where_prop");

    $total_keluar = $total_pengeluaran_bulan + $maintenance;
    $laba = $total_masuk - $total_keluar;

    $data_bulanan[$m] = [
        'pemasukan_sewa' => $pemasukan_sewa,
        'pemasukan_lain' => $pemasukan_lain,
        'total_masuk' => $total_masuk,
        'pengeluaran_detail' => $pengeluaran_detail,
        'total_pengeluaran' => $total_pengeluaran_bulan,
        'maintenance' => $maintenance,
        'total_keluar' => $total_keluar,
        'laba' => $laba,
    ];

    $total_pemasukan_tahun += $total_masuk;
    $total_pengeluaran_tahun += $total_keluar;
    $total_maintenance_tahun += $maintenance;
}

$laba_tahun = $total_pemasukan_tahun - $total_pengeluaran_tahun;
$jumlah_kolom_pengeluaran = count($kategori_pengeluaran) + 2; // +maintenance +total

// Data grafik
$labels_json = json_encode(array_values($bulan_nama));
$pemasukan_json = json_encode(array_column($data_bulanan, 'total_masuk'));
$pengeluaran_json = json_encode(array_column($data_bulanan, 'total_keluar'));
$laba_json = json_encode(array_column($data_bulanan, 'laba'));

$nama_properti_filter = 'Semua Properti';
if ($filter_properti) {
    foreach ($properti_list as $pl) {
        if ($pl['id'] == $filter_properti) { $nama_properti_filter = $pl['nama']; break; }
    }
}
?>

<!-- Filter -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Laporan Laba/Rugi <?= $filter_tahun ?> — <?= htmlspecialchars($nama_properti_filter) ?></h5>
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
        <select name="properti" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
            <option value="">-- Semua Properti --</option>
            <?php foreach ($properti_list as $pl): ?>
                <option value="<?= $pl['id'] ?>" <?= $filter_properti == $pl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pl['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" class="form-select form-select-sm" style="width:auto">
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $filter_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Tampilkan</button>
        <button type="button" class="btn btn-sm btn-outline-secondary no-print" onclick="appPrintPage()"><i class="bi bi-printer me-1"></i>Cetak</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 border-success">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Pemasukan <?= $filter_tahun ?></div>
                    <div class="fw-bold fs-4 text-success"><?= formatRupiah($total_pemasukan_tahun) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Pengeluaran <?= $filter_tahun ?></div>
                    <div class="fw-bold fs-4 text-danger"><?= formatRupiah($total_pengeluaran_tahun) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-start border-4 <?= $laba_tahun >= 0 ? 'border-primary' : 'border-warning' ?>">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon <?= $laba_tahun >= 0 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-warning bg-opacity-10 text-warning' ?> me-3">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="text-muted small">Laba Bersih <?= $filter_tahun ?></div>
                    <div class="fw-bold fs-4 <?= $laba_tahun >= 0 ? 'text-primary' : 'text-danger' ?>"><?= formatRupiah($laba_tahun) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="table-wrapper mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-2"></i>Grafik Pemasukan vs Pengeluaran</h6>
    <canvas id="chartLabaRugi" height="100"></canvas>
</div>

<!-- Tabel Detail -->
<div class="table-wrapper">
    <h6 class="fw-bold mb-3"><i class="bi bi-table me-2"></i>Detail Bulanan</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="text-center align-middle">Bulan</th>
                    <th colspan="3" class="text-center bg-success bg-opacity-10">Pemasukan</th>
                    <th colspan="<?= $jumlah_kolom_pengeluaran ?>" class="text-center bg-danger bg-opacity-10">Pengeluaran</th>
                    <th rowspan="2" class="text-center align-middle">Laba/Rugi</th>
                </tr>
                <tr>
                    <th class="text-end">Sewa</th>
                    <th class="text-end">Lainnya</th>
                    <th class="text-end fw-bold">Total</th>
                    <?php foreach ($kategori_pengeluaran as $kat): ?>
                        <th class="text-end"><?= htmlspecialchars($kat) ?></th>
                    <?php endforeach; ?>
                    <th class="text-end">Maintenance</th>
                    <th class="text-end fw-bold">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_bulanan as $m => $d): ?>
                <tr>
                    <td class="fw-semibold"><?= $bulan_nama[$m] ?></td>
                    <td class="text-end"><?= formatRupiah($d['pemasukan_sewa']) ?></td>
                    <td class="text-end"><?= formatRupiah($d['pemasukan_lain']) ?></td>
                    <td class="text-end fw-bold text-success"><?= formatRupiah($d['total_masuk']) ?></td>
                    <?php foreach ($kategori_pengeluaran as $kat): ?>
                        <td class="text-end"><?= formatRupiah($d['pengeluaran_detail'][$kat] ?? 0) ?></td>
                    <?php endforeach; ?>
                    <td class="text-end"><?= formatRupiah($d['maintenance']) ?></td>
                    <td class="text-end fw-bold text-danger"><?= formatRupiah($d['total_keluar']) ?></td>
                    <td class="text-end fw-bold <?= $d['laba'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($d['laba']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td>TOTAL</td>
                    <td class="text-end"><?= formatRupiah(array_sum(array_column($data_bulanan, 'pemasukan_sewa'))) ?></td>
                    <td class="text-end"><?= formatRupiah(array_sum(array_column($data_bulanan, 'pemasukan_lain'))) ?></td>
                    <td class="text-end text-success"><?= formatRupiah($total_pemasukan_tahun) ?></td>
                    <?php foreach ($kategori_pengeluaran as $kat): ?>
                        <td class="text-end"><?= formatRupiah($total_per_kategori[$kat]) ?></td>
                    <?php endforeach; ?>
                    <td class="text-end"><?= formatRupiah($total_maintenance_tahun) ?></td>
                    <td class="text-end text-danger"><?= formatRupiah($total_pengeluaran_tahun) ?></td>
                    <td class="text-end <?= $laba_tahun >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatRupiah($laba_tahun) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Keterangan -->
<div class="mt-3 small text-muted">
    <p class="mb-1"><strong>Keterangan:</strong></p>
    <ul class="mb-0">
        <li><strong>Pemasukan</strong> = Sewa kos + Denda + Laundry + Parkir + pemasukan lainnya.</li>
        <li><strong>Pengeluaran</strong> = Semua tagihan operasional (PLN, PDAM, Keamanan, WiFi, dll) + pengeluaran lain-lain. Tercatat otomatis saat tagihan ditandai "Bayar".</li>
        <li><strong>Maintenance</strong> = Biaya perbaikan kamar (spesifik) + perbaikan umum (bangunan). Dicatat terpisah di menu Maintenance.</li>
        <li><strong>Laba/Rugi</strong> = Total Pemasukan - Total Pengeluaran - Maintenance.</li>
    </ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartLabaRugi').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $labels_json ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?= $pemasukan_json ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.7)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1
            },
            {
                label: 'Pengeluaran',
                data: <?= $pengeluaran_json ?>,
                backgroundColor: 'rgba(231, 74, 59, 0.7)',
                borderColor: 'rgba(231, 74, 59, 1)',
                borderWidth: 1
            },
            {
                label: 'Laba Bersih',
                data: <?= $laba_json ?>,
                type: 'line',
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
