<?php
if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
}
$page_title = $page_title ?? 'Dashboard';
$nama_usaha = getPengaturan($db, 'nama_usaha');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Juragan Kos App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base_url ?? '.' ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white" id="sidebar">
        <div class="sidebar-header p-3 text-center border-bottom border-secondary">
            <h5 class="mb-0"><i class="bi bi-house-door-fill"></i> Juragan Kos</h5>
            <small class="text-muted"><?= htmlspecialchars($nama_usaha) ?></small>
        </div>
        <nav class="nav flex-column p-2">
            <a class="nav-link text-white <?= $page_title == 'Dashboard' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/index.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <hr class="border-secondary my-1">
            <small class="text-muted px-3 mb-1">KELOLA</small>
            <a class="nav-link text-white <?= $page_title == 'Properti' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/properti.php">
                <i class="bi bi-building me-2"></i> Properti
            </a>
            <a class="nav-link text-white <?= $page_title == 'Kamar' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/kamar.php">
                <i class="bi bi-door-open me-2"></i> Kamar
            </a>
            <a class="nav-link text-white <?= $page_title == 'Penyewa' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/penyewa.php">
                <i class="bi bi-people me-2"></i> Penyewa
            </a>
            <hr class="border-secondary my-1">
            <small class="text-muted px-3 mb-1">KEUANGAN</small>
            <a class="nav-link text-white <?= $page_title == 'Pemasukan' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/pemasukan.php">
                <i class="bi bi-cash-stack me-2"></i> Pemasukan
            </a>
            <a class="nav-link text-white <?= $page_title == 'Pengeluaran' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/pengeluaran.php">
                <i class="bi bi-wallet2 me-2"></i> Pengeluaran
            </a>
            <a class="nav-link text-white <?= $page_title == 'Maintenance' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/maintenance.php">
                <i class="bi bi-tools me-2"></i> Maintenance
            </a>
            <a class="nav-link text-white <?= $page_title == 'Tagihan Operasional' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/tagihan_operasional.php">
                <i class="bi bi-lightning me-2"></i> Tagihan Operasional
            </a>
            <hr class="border-secondary my-1">
            <small class="text-muted px-3 mb-1">LAPORAN</small>
            <a class="nav-link text-white <?= $page_title == 'Laporan' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/laporan.php">
                <i class="bi bi-graph-up me-2"></i> Laporan Keuangan
            </a>
            <hr class="border-secondary my-1">
            <a class="nav-link text-white <?= $page_title == 'Pengaturan' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/pengaturan.php">
                <i class="bi bi-gear me-2"></i> Pengaturan
            </a>
            <a class="nav-link text-white <?= $page_title == 'Bantuan' ? 'active' : '' ?>" href="<?= $base_url ?? '.' ?>/pages/bantuan.php">
                <i class="bi bi-question-circle me-2"></i> Bantuan
            </a>
        </nav>
    </div>

    <!-- Content -->
    <div class="content-wrapper flex-grow-1">
        <nav class="navbar navbar-light bg-white shadow-sm px-3">
            <button class="btn btn-sm btn-outline-secondary" id="toggleSidebar">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-text fw-semibold"><?= htmlspecialchars($page_title) ?></span>
            <span class="navbar-text small text-muted"><?= date('d M Y') ?></span>
        </nav>
        <div class="container-fluid p-4">
