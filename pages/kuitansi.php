<?php
require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die('ID tidak valid');
}

$stmt = $db->prepare("
    SELECT pm.*, p.nama, p.no_hp, k.nomor_kamar, pr.nama as nama_properti, pr.alamat as alamat_properti
    FROM pemasukan pm
    LEFT JOIN penyewa p ON pm.penyewa_id = p.id
    LEFT JOIN kamar k ON pm.kamar_id = k.id
    LEFT JOIN properti pr ON pm.properti_id = pr.id
    WHERE pm.id = :id
");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$data = $result->fetchArray(SQLITE3_ASSOC);

if (!$data) {
    die('Data tidak ditemukan');
}

$nama_usaha = getPengaturan($db, 'nama_usaha');
$no_hp_pemilik = getPengaturan($db, 'no_hp_pemilik');
$nama_pemilik = getPengaturan($db, 'nama_pemilik');

function terbilang($angka) {
    $angka = abs($angka);
    $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    if ($angka < 12) return $huruf[$angka];
    if ($angka < 20) return terbilang($angka - 10) . ' Belas';
    if ($angka < 100) return terbilang(floor($angka / 10)) . ' Puluh' . ($angka % 10 ? ' ' . terbilang($angka % 10) : '');
    if ($angka < 200) return 'Seratus' . ($angka - 100 ? ' ' . terbilang($angka - 100) : '');
    if ($angka < 1000) return terbilang(floor($angka / 100)) . ' Ratus' . ($angka % 100 ? ' ' . terbilang($angka % 100) : '');
    if ($angka < 2000) return 'Seribu' . ($angka - 1000 ? ' ' . terbilang($angka - 1000) : '');
    if ($angka < 1000000) return terbilang(floor($angka / 1000)) . ' Ribu' . ($angka % 1000 ? ' ' . terbilang($angka % 1000) : '');
    if ($angka < 1000000000) return terbilang(floor($angka / 1000000)) . ' Juta' . ($angka % 1000000 ? ' ' . terbilang($angka % 1000000) : '');
    return terbilang(floor($angka / 1000000000)) . ' Miliar' . ($angka % 1000000000 ? ' ' . terbilang($angka % 1000000000) : '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi #<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?> - Juragan Kos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f0f0; }
        .kuitansi {
            max-width: 700px;
            margin: 20px auto;
            background: white;
            padding: 40px;
            border: 2px solid #333;
            position: relative;
        }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 2px; }
        .header h2 { font-size: 16px; color: #555; font-weight: normal; }
        .header p { font-size: 12px; color: #777; }
        .no-kuitansi { text-align: right; margin-bottom: 15px; font-size: 14px; }
        .no-kuitansi span { font-weight: bold; color: #333; }
        .detail-table { width: 100%; margin-bottom: 20px; }
        .detail-table td { padding: 6px 8px; font-size: 14px; vertical-align: top; }
        .detail-table td:first-child { width: 160px; font-weight: 600; color: #555; }
        .nominal-box {
            background: #f8f9fa;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 15px 20px;
            text-align: center;
            margin: 20px 0;
        }
        .nominal-box .angka { font-size: 28px; font-weight: bold; color: #2d5016; }
        .nominal-box .terbilang { font-size: 12px; color: #666; font-style: italic; margin-top: 5px; }
        .footer-kuitansi { display: flex; justify-content: space-between; margin-top: 40px; }
        .ttd { text-align: center; width: 200px; }
        .ttd .garis { border-bottom: 1px solid #333; margin-top: 60px; margin-bottom: 5px; }
        .ttd .nama { font-weight: bold; }
        .stamp { position: absolute; top: 50%; right: 60px; transform: rotate(-15deg); font-size: 36px; color: rgba(28, 200, 138, 0.3); font-weight: bold; border: 4px solid rgba(28, 200, 138, 0.3); padding: 5px 15px; border-radius: 8px; }
        .btn-print { text-align: center; margin: 20px; }
        .btn-print button { padding: 10px 30px; font-size: 16px; background: #4e73df; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print button:hover { background: #3b5fc0; }
        @media print {
            body { background: white; }
            .kuitansi { margin: 0; border: none; padding: 20px; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="btn-print">
    <button onclick="window.print()"><b>Cetak Kuitansi</b></button>
</div>

<div class="kuitansi">
    <div class="stamp">LUNAS</div>

    <div class="header">
        <h1><?= htmlspecialchars($data['nama_properti'] ?? $nama_usaha) ?></h1>
        <h2>KUITANSI PEMBAYARAN</h2>
        <p><?= htmlspecialchars($data['alamat_properti'] ?? '') ?> | Telp: <?= htmlspecialchars($no_hp_pemilik) ?></p>
    </div>

    <div class="no-kuitansi">
        No: <span>KWT/<?= date('Y/m', strtotime($data['tanggal'])) ?>/<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></span>
    </div>

    <table class="detail-table">
        <tr>
            <td>Sudah Terima Dari</td>
            <td>: <?= htmlspecialchars($data['nama'] ?? 'Umum') ?></td>
        </tr>
        <tr>
            <td>Properti</td>
            <td>: <?= htmlspecialchars($data['nama_properti'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>Kamar</td>
            <td>: <?= $data['nomor_kamar'] ? 'Kamar ' . htmlspecialchars($data['nomor_kamar']) : '-' ?></td>
        </tr>
        <tr>
            <td>Untuk Pembayaran</td>
            <td>: <?= htmlspecialchars($data['kategori']) ?><?= $data['periode_bulan'] ? ' - Periode ' . date('F Y', strtotime($data['periode_bulan'] . '-01')) : '' ?></td>
        </tr>
        <tr>
            <td>Metode Bayar</td>
            <td>: <?= htmlspecialchars($data['metode_bayar']) ?></td>
        </tr>
        <tr>
            <td>Tanggal Bayar</td>
            <td>: <?= formatTanggal($data['tanggal']) ?></td>
        </tr>
        <?php if ($data['keterangan']): ?>
        <tr>
            <td>Keterangan</td>
            <td>: <?= htmlspecialchars($data['keterangan']) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="nominal-box">
        <div class="angka"><?= formatRupiah($data['nominal']) ?></div>
        <div class="terbilang"># <?= terbilang($data['nominal']) ?> Rupiah #</div>
    </div>

    <div class="footer-kuitansi">
        <div class="ttd">
            <small>Penyewa</small>
            <div class="garis"></div>
            <div class="nama"><?= htmlspecialchars($data['nama'] ?? '..................') ?></div>
        </div>
        <div class="ttd">
            <small><?= date('d F Y', strtotime($data['tanggal'])) ?></small>
            <div class="garis"></div>
            <div class="nama"><?= htmlspecialchars($nama_pemilik) ?></div>
            <small>Pengelola</small>
        </div>
    </div>
</div>

</body>
</html>
