<?php
/**
 * Database Reset & Reseed Endpoint
 *
 * Usage: GET /cron_reset.php?token=lutfi_dev_2026_secret
 */

header('Content-Type: application/json');

$secret = 'lutfi_dev_2026_secret';

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token tidak valid.']);
    exit;
}

require_once __DIR__ . '/config/database.php';

try {
    // Matikan foreign key checks saat reset
    if ($db_driver === 'sqlite') {
        $db->exec('PRAGMA foreign_keys = OFF');
    } else {
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    // Hapus semua data dari semua tabel (urutan: child tables dulu)
    $tables = [
        'tagihan_operasional',
        'template_tagihan',
        'maintenance',
        'pengeluaran',
        'pemasukan',
        'penyewa',
        'kamar',
        'properti',
        'pengaturan',
    ];

    foreach ($tables as $table) {
        $db->exec("DELETE FROM $table");
    }

    // Reset auto-increment
    resetAutoIncrement($db, $tables);

    // Nyalakan kembali foreign key checks
    if ($db_driver === 'sqlite') {
        $db->exec('PRAGMA foreign_keys = ON');
    } else {
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    // Re-insert default pengaturan
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_usaha', 'Juragan Kos')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('no_hp_pemilik', '08123456789')");
    $db->exec("INSERT INTO pengaturan (kunci, nilai) VALUES ('nama_pemilik', 'Nama Pemilik')");

    // Jalankan seed data dummy
    require __DIR__ . '/seed.php';

    echo json_encode([
        'status'  => 'success',
        'message' => 'Database berhasil di-reset dan seed data dummy.',
        'driver'  => $db_driver,
        'time'    => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Reset gagal: ' . $e->getMessage(),
    ]);
}
