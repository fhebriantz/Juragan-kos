<?php
$page_title = 'Bantuan';
$base_url = '..';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">

        <!-- Selamat Datang -->
        <div class="table-wrapper mb-4">
            <div class="text-center py-3">
                <i class="bi bi-house-door-fill fs-1 text-primary"></i>
                <h4 class="fw-bold mt-2">Selamat Datang di Juragan Kos App</h4>
                <p class="text-muted mb-0">Aplikasi pencatatan keuangan kos & kontrakan. Cocok untuk Anda yang punya satu atau banyak lokasi kos.</p>
            </div>
        </div>

        <!-- ====== MULAI CEPAT ====== -->
        <div class="table-wrapper mb-4">
            <h5 class="fw-bold mb-2"><i class="bi bi-rocket-takeoff me-2"></i>Cara Mulai Menggunakan</h5>
            <p class="text-muted mb-3">Baru pertama kali? Ikuti langkah-langkah ini secara berurutan:</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">1</div>
                            <h6 class="fw-bold">Isi Data Pemilik</h6>
                            <p class="small text-muted mb-2">Buka menu <strong>Pengaturan</strong>. Isi nama usaha, nama Anda sebagai pemilik, dan nomor HP. Data ini akan muncul di kuitansi pembayaran.</p>
                            <a href="pengaturan.php" class="btn btn-sm btn-outline-primary">Buka Pengaturan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">2</div>
                            <h6 class="fw-bold">Daftarkan Properti</h6>
                            <p class="small text-muted mb-2">Properti = lokasi kos atau kontrakan Anda. Punya 1 kos? Buat 1 properti. Punya 3 kos di tempat berbeda? Buat 3 properti.</p>
                            <a href="properti.php" class="btn btn-sm btn-outline-success">Buka Properti</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">3</div>
                            <h6 class="fw-bold">Daftarkan Kamar</h6>
                            <p class="small text-muted mb-2">Untuk setiap properti, tambahkan kamar-kamarnya beserta harga sewa per bulan (dan per tahun jika ada).</p>
                            <a href="kamar.php" class="btn btn-sm btn-outline-info">Buka Kamar</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">4</div>
                            <h6 class="fw-bold">Masukkan Penyewa</h6>
                            <p class="small text-muted mb-2">Daftarkan nama penyewa, pilih kamar, dan isi <strong>tanggal masuk</strong> (menentukan jatuh tempo bayar). <strong>Wajib isi No. HP</strong> agar bisa tagih lewat WhatsApp.</p>
                            <a href="penyewa.php" class="btn btn-sm btn-outline-warning">Buka Penyewa</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">5</div>
                            <h6 class="fw-bold">Setup Tagihan Rutin</h6>
                            <p class="small text-muted mb-2">Buka <strong>Tagihan Operasional</strong> > tab <strong>Tagihan Rutin</strong>. Daftarkan PLN, PDAM, Keamanan, dll. Tagihan akan muncul otomatis setiap bulan.</p>
                            <a href="tagihan_operasional.php?tab=template" class="btn btn-sm btn-outline-danger">Buka Tagihan Rutin</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-dark">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;font-weight:bold;">6</div>
                            <h6 class="fw-bold">Selesai!</h6>
                            <p class="small text-muted mb-2">Buka <strong>Dashboard</strong> setiap hari. Semua pengingat tagihan, penyewa nunggak, dan ringkasan keuangan ada di sana. Tinggal klik tombol untuk bayar.</p>
                            <a href="../index.php" class="btn btn-sm btn-outline-dark">Buka Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== ALUR HARIAN ====== -->
        <div class="table-wrapper mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2"></i>Yang Perlu Dilakukan Sehari-hari</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary"><i class="bi bi-1-circle me-2"></i>Buka Dashboard</h6>
                            <p class="small text-muted mb-0">Cek apakah ada peringatan merah/kuning. Jika ada, artinya ada penyewa yang belum bayar atau tagihan PLN/PDAM yang belum dilunasi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-success"><i class="bi bi-2-circle me-2"></i>Penyewa Bayar Sewa?</h6>
                            <p class="small text-muted mb-0">Klik tombol <strong>"Catat Bayar"</strong> di Dashboard. Isi nominal, pilih tunai/transfer, klik simpan. Peringatan otomatis hilang. Bisa langsung cetak kuitansi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-warning"><i class="bi bi-3-circle me-2"></i>Tagihan PLN/PDAM Datang?</h6>
                            <p class="small text-muted mb-0">Klik <strong>"Isi Nominal"</strong> di Dashboard, masukkan nominal sesuai tagihan. Setelah diisi, klik <strong>"Bayar"</strong> saat sudah dibayar. Otomatis tercatat sebagai pengeluaran.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-danger"><i class="bi bi-4-circle me-2"></i>Ada Perbaikan/Beli Sesuatu?</h6>
                            <p class="small text-muted mb-0">Perbaikan kamar (servis AC, ganti keran) &rarr; catat di <strong>Maintenance</strong>. Belanja umum (beli sapu, dll) &rarr; catat di <strong>Pengeluaran</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== PANDUAN FITUR ====== -->
        <div class="table-wrapper mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-book me-2"></i>Panduan Lengkap per Menu</h5>
            <div class="accordion" id="accordionBantuan">

                <!-- Dashboard -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#helpDashboard">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard (Halaman Utama)
                        </button>
                    </h2>
                    <div id="helpDashboard" class="accordion-collapse collapse show" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p>Dashboard adalah halaman pertama yang Anda lihat saat membuka aplikasi. Semua pengingat penting ada di sini.</p>

                            <h6 class="fw-bold mt-3">Peringatan Warna Merah</h6>
                            <p class="small">Artinya ada masalah yang perlu segera ditangani:</p>
                            <ul class="small">
                                <li><strong>"Ada X Penyewa Nunggak Bayar!"</strong> — Ada penyewa yang sudah lewat tanggal jatuh tempo tapi belum bayar sewa. Klik untuk langsung ke halaman Pemasukan.</li>
                                <li><strong>"Tagihan PLN/PDAM Lewat Jatuh Tempo!"</strong> — Ada tagihan listrik/air/keamanan yang belum dibayar dan sudah melewati tanggal jatuh tempo. Klik untuk ke halaman Tagihan Operasional.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Peringatan Warna Kuning</h6>
                            <p class="small">Artinya ada yang perlu diperhatikan dalam waktu dekat:</p>
                            <ul class="small">
                                <li><strong>"X Penyewa Jatuh Tempo dalam 3 Hari"</strong> — Penyewa akan segera jatuh tempo. Bisa langsung kirim pengingat lewat WhatsApp.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Tombol di Setiap Kartu Penyewa</h6>
                            <ul class="small">
                                <li><strong>Catat Bayar</strong> (biru) — Klik jika penyewa sudah bayar. Muncul form popup, isi nominal dan metode bayar, lalu simpan. Peringatan langsung hilang.</li>
                                <li><strong>Tagih via WA</strong> (hijau) — Klik untuk mengirim pesan pengingat lewat WhatsApp. Pesan otomatis berisi nama, kamar, jatuh tempo, dan nominal. Tombol ini hanya muncul jika No. HP penyewa sudah diisi.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Tombol di Setiap Kartu Tagihan Operasional</h6>
                            <ul class="small">
                                <li><strong>Isi Nominal</strong> (kuning) — Muncul jika nominal belum diisi (PLN, PDAM). Klik, masukkan nominal tagihan bulan ini, simpan.</li>
                                <li><strong>Bayar</strong> (hijau) — Muncul jika nominal sudah diisi. Klik untuk menandai sudah dibayar. Otomatis tercatat sebagai pengeluaran.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Filter Properti</h6>
                            <p class="small mb-0">Jika punya lebih dari 1 lokasi kos, gunakan dropdown "Properti" di atas untuk melihat data per lokasi saja.</p>
                        </div>
                    </div>
                </div>

                <!-- Properti -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpProperti">
                            <i class="bi bi-building me-2"></i> Properti (Lokasi Kos/Kontrakan)
                        </button>
                    </h2>
                    <div id="helpProperti" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p><strong>Properti = lokasi kos atau kontrakan.</strong> Setiap lokasi yang berbeda alamat harus didaftarkan sebagai properti terpisah.</p>

                            <div class="alert alert-light border small mb-3">
                                <strong>Contoh:</strong><br>
                                Anda punya "Kos Melati" di Jl. Melati dan "Kontrakan Mawar" di Jl. Mawar.<br>
                                &rarr; Daftarkan 2 properti: <em>Kos Melati</em> dan <em>Kontrakan Mawar</em>.
                            </div>

                            <ul class="small">
                                <li>Isi <strong>nama</strong> dan <strong>alamat</strong> properti.</li>
                                <li>Pilih <strong>tipe</strong>: Kos, Kontrakan, atau Apartemen.</li>
                                <li>Semua data (kamar, penyewa, keuangan) otomatis terhubung ke properti masing-masing.</li>
                                <li>Properti <strong>tidak bisa dihapus</strong> jika masih ada kamar di dalamnya. Hapus kamarnya dulu.</li>
                                <li>Di kartu properti, Anda bisa langsung lihat berapa kamar terisi, kosong, dan pemasukan bulan ini.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Kamar -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpKamar">
                            <i class="bi bi-door-open me-2"></i> Kamar
                        </button>
                    </h2>
                    <div id="helpKamar" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <ul class="small">
                                <li>Setiap kamar harus dikaitkan ke <strong>properti</strong>. Pilih properti dulu, baru tambah kamar.</li>
                                <li>Isi <strong>nomor/nama kamar</strong> (misal: 01, 02, A1, B2), <strong>harga sewa bulanan</strong>, dan <strong>fasilitas</strong>.</li>
                                <li><strong>Status kamar</strong> ada 3:
                                    <ul>
                                        <li><span class="badge bg-secondary">Kosong</span> — belum ada penyewa</li>
                                        <li><span class="badge bg-success">Terisi</span> — ada penyewa aktif (otomatis berubah saat penyewa masuk)</li>
                                        <li><span class="badge bg-warning text-dark">Maintenance</span> — sedang dalam perbaikan, tidak bisa disewakan</li>
                                    </ul>
                                </li>
                                <li>Gunakan filter <strong>Properti</strong> di atas untuk melihat kamar per lokasi.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Penyewa -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpPenyewa">
                            <i class="bi bi-people me-2"></i> Penyewa
                        </button>
                    </h2>
                    <div id="helpPenyewa" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <h6 class="fw-bold">Menambah Penyewa Baru</h6>
                            <ol class="small">
                                <li>Isi nama, No. HP (wajib untuk fitur WA), No. KTP, dan alamat asal.</li>
                                <li>Pilih kamar (hanya kamar berstatus "Kosong" yang muncul).</li>
                                <li>Isi <strong>tanggal masuk</strong> — ini penting karena menentukan kapan jatuh tempo bayar setiap bulan.</li>
                            </ol>

                            <div class="alert alert-light border small mb-3">
                                <strong>Contoh jatuh tempo:</strong><br>
                                Budi masuk tanggal <strong>15 Januari 2026</strong>.<br>
                                &rarr; Jatuh tempo bayar sewa Budi: setiap <strong>tanggal 15</strong> tiap bulan.<br>
                                &rarr; Jika tanggal 15 sudah lewat dan belum bayar, muncul peringatan merah di Dashboard.
                            </div>

                            <h6 class="fw-bold mt-3">Penyewa Pindah/Keluar (Checkout)</h6>
                            <ul class="small">
                                <li>Klik tombol <strong>checkout</strong> (ikon kotak dengan panah) di daftar penyewa.</li>
                                <li>Status penyewa berubah jadi "Nonaktif" dan status kamar otomatis kembali ke "Kosong".</li>
                                <li>Data penyewa tidak dihapus, tetap tersimpan untuk histori.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Pemasukan -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpPemasukan">
                            <i class="bi bi-cash-stack me-2"></i> Pemasukan (Uang Masuk)
                        </button>
                    </h2>
                    <div id="helpPemasukan" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p class="small">Semua uang yang masuk dicatat di sini.</p>

                            <h6 class="fw-bold">Cara Tercepat: Langsung dari Dashboard</h6>
                            <p class="small">Saat ada peringatan penyewa nunggak, klik <strong>"Catat Bayar"</strong> di Dashboard. Form langsung terisi otomatis (nama penyewa, nominal). Tinggal klik simpan.</p>

                            <h6 class="fw-bold mt-3">Cara Manual: dari Menu Pemasukan</h6>
                            <ol class="small">
                                <li>Pilih <strong>kategori</strong>: Sewa, Denda Telat, Jasa Laundry, Parkir, atau Lainnya.</li>
                                <li>Pilih <strong>penyewa</strong> (opsional untuk kategori non-sewa).</li>
                                <li>Isi <strong>nominal</strong>, <strong>tanggal</strong>, dan <strong>periode bulan</strong> yang dibayar.</li>
                                <li>Pilih <strong>metode bayar</strong>: Tunai atau Transfer.</li>
                                <li>Klik Simpan. Setelah tersimpan, bisa langsung <strong>cetak kuitansi</strong>.</li>
                            </ol>

                            <div class="alert alert-info small py-2 mb-0">
                                <i class="bi bi-lightbulb me-1"></i>
                                <strong>Tips:</strong> Isi <strong>periode bulan</strong> dengan benar (misal: Maret 2026 untuk sewa bulan Maret). Ini yang digunakan sistem untuk menentukan apakah penyewa sudah bayar bulan ini atau belum.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpPengeluaran">
                            <i class="bi bi-wallet2 me-2"></i> Pengeluaran (Uang Keluar)
                        </button>
                    </h2>
                    <div id="helpPengeluaran" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p class="small">Untuk mencatat pengeluaran <strong>di luar tagihan rutin</strong>.</p>

                            <h6 class="fw-bold">Contoh yang dicatat di sini:</h6>
                            <ul class="small">
                                <li>Beli perabotan (sapu, ember, jemuran)</li>
                                <li>Beli perlengkapan (lampu cadangan, kunci duplikat)</li>
                                <li>Ongkos tukang harian</li>
                                <li>Biaya transportasi urusan kos</li>
                            </ul>

                            <div class="alert alert-warning small py-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Penting:</strong> Jangan catat PLN, PDAM, atau Keamanan di sini! Gunakan menu <strong>Tagihan Operasional</strong> untuk itu. Saat Anda klik "Bayar" di Tagihan Operasional, pengeluaran <strong>otomatis tercatat</strong>. Kalau dicatat di dua tempat, laporan keuangan jadi dobel.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tagihan Operasional -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpTagihan">
                            <i class="bi bi-lightning me-2"></i> Tagihan Operasional (PLN, PDAM, dll)
                        </button>
                    </h2>
                    <div id="helpTagihan" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p class="small">Halaman untuk mengelola semua tagihan bulanan seperti listrik, air, keamanan, WiFi, dan lainnya. Ada 3 tab:</p>

                            <h6 class="fw-bold mt-3"><i class="bi bi-list-check me-1"></i> Tab: Tagihan Bulanan</h6>
                            <p class="small">Daftar semua tagihan yang harus dibayar. Tagihan rutin muncul otomatis setiap awal bulan.</p>
                            <ul class="small">
                                <li>Tagihan bertanda <span class="badge bg-warning text-dark">Belum diisi</span> berarti nominalnya belum Anda masukkan (biasanya PLN/PDAM karena nominalnya berubah tiap bulan). Klik <strong>"Isi Nominal"</strong>, masukkan nominal sesuai tagihan yang datang.</li>
                                <li>Setelah nominal terisi, klik tombol hijau <strong>"Bayar"</strong> saat Anda sudah benar-benar membayar tagihannya. Status berubah jadi <span class="badge bg-success">Lunas</span> dan otomatis tercatat sebagai pengeluaran.</li>
                                <li>Salah klik bayar? Klik tombol <strong>undo</strong> (panah putar) untuk membatalkan.</li>
                            </ul>

                            <h6 class="fw-bold mt-3"><i class="bi bi-repeat me-1"></i> Tab: Tagihan Rutin</h6>
                            <p class="small">Setup tagihan yang <strong>muncul otomatis setiap bulan</strong>. Cukup setup sekali, tidak perlu input ulang tiap bulan.</p>

                            <div class="alert alert-light border small mb-2">
                                <strong>Cara setup:</strong>
                                <ol class="mb-0 mt-1">
                                    <li>Pilih <strong>properti</strong> (misal: Kos Melati).</li>
                                    <li>Pilih atau ketik <strong>jenis tagihan</strong> (PLN, PDAM, Keamanan, WiFi, atau ketik sendiri).</li>
                                    <li>Isi <strong>keterangan</strong> (misal: nomor meter, ID pelanggan, atau catatan lainnya).</li>
                                    <li>Atur <strong>tanggal jatuh tempo</strong> (misal: PLN jatuh tempo setiap tanggal 20).</li>
                                    <li>
                                        Atur <strong>switch "Isi nominal manual per bulan"</strong>:
                                        <ul>
                                            <li><strong>Nyalakan</strong> untuk PLN & PDAM — karena nominalnya beda tiap bulan. Nanti Anda isi nominalnya setelah tagihan datang.</li>
                                            <li><strong>Matikan</strong> untuk Keamanan, WiFi — karena nominalnya tetap. Isi nominal tetap di bawahnya.</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>

                            <ul class="small">
                                <li>Template bisa di-<strong>pause</strong> (nonaktifkan) tanpa dihapus. Misal: properti sedang kosong total, pause dulu agar tagihan tidak di-generate.</li>
                                <li>Template bisa <strong>diaktifkan kembali</strong> kapan saja.</li>
                            </ul>

                            <h6 class="fw-bold mt-3"><i class="bi bi-plus-circle me-1"></i> Tab: Tambah Sekali Bayar</h6>
                            <p class="small mb-0">Untuk tagihan yang <strong>tidak rutin</strong> — hanya terjadi sekali atau jarang. Contoh: pajak bumi &amp; bangunan, perbaikan pompa air, perpanjangan izin. Ketik nama tagihan, nominal, tanggal jatuh tempo, dan keterangan.</p>
                        </div>
                    </div>
                </div>

                <!-- Maintenance -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpMaintenance">
                            <i class="bi bi-tools me-2"></i> Maintenance (Perbaikan)
                        </button>
                    </h2>
                    <div id="helpMaintenance" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p class="small">Catat semua perbaikan agar tahu kamar mana yang paling sering rusak dan berapa biayanya.</p>

                            <h6 class="fw-bold">2 Jenis Maintenance:</h6>
                            <ul class="small">
                                <li><strong>Spesifik Kamar</strong> — Perbaikan untuk kamar tertentu. Contoh: ganti keran kamar 04, servis AC kamar 02, perbaiki pintu kamar 06. <strong>Selalu pilih kamarnya</strong> agar tercatat.</li>
                                <li><strong>Umum</strong> — Perbaikan untuk bangunan/fasilitas bersama yang tidak terkait kamar tertentu. Contoh: perbaiki genteng bocor, cat tembok lorong, beli sapu.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Rekap per Kamar (Fitur Evaluasi)</h6>
                            <p class="small">Di bagian atas halaman ada tabel rekap tahunan. Tabel ini menunjukkan kamar mana yang paling "rewel":</p>
                            <ul class="small">
                                <li><span class="badge bg-success">Normal</span> — 1-2 perbaikan/tahun, wajar.</li>
                                <li><span class="badge bg-warning text-dark">Perhatikan</span> — 3-4 perbaikan/tahun, mulai sering.</li>
                                <li><span class="badge bg-danger">Perlu Evaluasi</span> — 5+ perbaikan/tahun, pertimbangkan ganti total fasilitasnya (AC baru, renovasi kamar mandi, dll) agar tidak terus-terusan keluar biaya.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Laporan -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpLaporan">
                            <i class="bi bi-graph-up me-2"></i> Laporan Keuangan
                        </button>
                    </h2>
                    <div id="helpLaporan" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <p class="small">Halaman untuk menjawab pertanyaan: <strong>"Bulan ini untung bersih berapa?"</strong></p>

                            <h6 class="fw-bold">Cara Baca Laporan:</h6>
                            <div class="alert alert-light border small mb-3">
                                <strong>Laba Bersih = Pemasukan - Pengeluaran</strong><br><br>
                                <strong>Pemasukan</strong> = Uang sewa + Denda + Laundry + Parkir + Lainnya<br>
                                <strong>Pengeluaran</strong> = PLN + PDAM + Keamanan + Maintenance + Pengeluaran Lainnya
                            </div>

                            <ul class="small">
                                <li><strong>Grafik batang</strong> — Hijau = pemasukan, Merah = pengeluaran. Semakin tinggi hijau dibanding merah, semakin untung.</li>
                                <li><strong>Garis biru</strong> — Laba bersih per bulan. Jika garisnya di bawah nol, bulan itu rugi.</li>
                                <li><strong>Tabel detail</strong> — Rincian per bulan: sewa, PLN, PDAM, keamanan, maintenance, dll.</li>
                                <li>Gunakan filter <strong>Properti</strong> untuk melihat mana lokasi yang paling menguntungkan.</li>
                                <li>Klik <strong>"Cetak"</strong> untuk mencetak laporan. Sidebar dan navigasi otomatis disembunyikan saat cetak.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Kuitansi -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpKuitansi">
                            <i class="bi bi-printer me-2"></i> Cetak Kuitansi
                        </button>
                    </h2>
                    <div id="helpKuitansi" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <ul class="small">
                                <li>Kuitansi bisa dicetak setelah mencatat pembayaran sewa.</li>
                                <li>Ada 2 cara akses: dari halaman <strong>Pemasukan</strong> (klik ikon printer) atau dari <strong>Dashboard</strong> setelah klik "Catat Bayar" (muncul link cetak di notifikasi hijau).</li>
                                <li>Kuitansi berisi: nama properti, alamat, nama penyewa, kamar, nominal, terbilang (dalam huruf), tanggal, tanda tangan.</li>
                                <li>Klik tombol <strong>"Cetak Kuitansi"</strong> di halaman kuitansi untuk mencetak lewat printer.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan & Reset -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpReset">
                            <i class="bi bi-gear me-2"></i> Pengaturan, Kustomisasi & Reset Data
                        </button>
                    </h2>
                    <div id="helpReset" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                        <div class="accordion-body">
                            <h6 class="fw-bold">Pengaturan Umum</h6>
                            <ul class="small">
                                <li><strong>Logo Usaha</strong> — Upload logo (PNG, JPG, WebP, maks 2 MB). Ditampilkan di sidebar. Bisa dihapus kapan saja.</li>
                                <li><strong>Nama Usaha</strong> — Ditampilkan di sidebar aplikasi.</li>
                                <li><strong>Nama Pemilik & No. HP</strong> — Ditampilkan di kuitansi pembayaran.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Kustomisasi Warna Sidebar</h6>
                            <ul class="small">
                                <li><strong>Warna Preset</strong> — Pilih dari 12 warna yang tersedia (klik kotak warna). Perubahan langsung terlihat di sidebar.</li>
                                <li><strong>Warna Kustom</strong> — Gunakan color picker untuk memilih warna bebas. Kode warna ditampilkan di samping.</li>
                                <li><strong>Mode Font</strong> — Pilih <em>Light</em> (font putih, untuk sidebar gelap) atau <em>Dark</em> (font hitam, untuk sidebar terang). Sesuaikan dengan warna sidebar yang dipilih.</li>
                                <li>Pratinjau warna + font langsung terlihat sebelum disimpan.</li>
                            </ul>

                            <h6 class="fw-bold mt-3">Isi Data Demo</h6>
                            <p class="small">Mengisi database dengan data contoh (2 properti, 12 kamar, 10 penyewa, transaksi 3 bulan terakhir) berdasarkan bulan aktif. Hanya bisa dilakukan jika database kosong. Reset database dulu jika masih ada data.</p>

                            <h6 class="fw-bold mt-3">Reset Transaksi</h6>
                            <p class="small">Menghapus semua catatan keuangan: pemasukan, pengeluaran, maintenance, dan tagihan operasional. Data properti, kamar, dan penyewa <strong>tetap aman</strong>. Berguna jika ingin mulai pencatatan dari awal tanpa harus daftar ulang kamar dan penyewa.</p>
                            <p class="small">Cara: ketik <code>HAPUS-TRANSAKSI</code> lalu klik tombol reset.</p>

                            <h6 class="fw-bold mt-3">Reset Seluruh Database</h6>
                            <p class="small">Menghapus <strong>SEMUA data</strong> termasuk properti, kamar, penyewa, dan semua transaksi. Hanya pengaturan umum yang tetap. Berguna saat pertama kali setup (untuk menghapus data contoh) atau jika ingin mulai benar-benar dari nol.</p>
                            <p class="small mb-0">Cara: ketik <code>HAPUS-SEMUA</code> lalu klik tombol reset.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ====== PETA FITUR ====== -->
        <div class="table-wrapper mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-signpost-2 me-2"></i>Peta Fitur — Harus Catat di Mana?</h5>
            <p class="text-muted small mb-3">Bingung harus catat di menu mana? Lihat panduan ini:</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kejadian</th>
                            <th>Catat di Menu</th>
                            <th>Contoh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Penyewa bayar sewa bulanan</td>
                            <td><span class="badge bg-primary">Dashboard</span> atau <span class="badge bg-primary">Pemasukan</span></td>
                            <td>Budi bayar sewa Maret Rp 1.200.000</td>
                        </tr>
                        <tr>
                            <td>Penyewa bayar denda telat</td>
                            <td><span class="badge bg-primary">Pemasukan</span></td>
                            <td>Denda telat Budi Rp 50.000</td>
                        </tr>
                        <tr>
                            <td>Ada pemasukan laundry/parkir</td>
                            <td><span class="badge bg-primary">Pemasukan</span></td>
                            <td>Jasa laundry Rp 150.000</td>
                        </tr>
                        <tr>
                            <td>Bayar listrik PLN</td>
                            <td><span class="badge bg-warning text-dark">Tagihan Operasional</span></td>
                            <td>PLN Maret Rp 1.100.000</td>
                        </tr>
                        <tr>
                            <td>Bayar air PDAM</td>
                            <td><span class="badge bg-warning text-dark">Tagihan Operasional</span></td>
                            <td>PDAM Maret Rp 350.000</td>
                        </tr>
                        <tr>
                            <td>Bayar iuran keamanan/sampah</td>
                            <td><span class="badge bg-warning text-dark">Tagihan Operasional</span></td>
                            <td>Keamanan RT Rp 200.000</td>
                        </tr>
                        <tr>
                            <td>Bayar WiFi bulanan</td>
                            <td><span class="badge bg-warning text-dark">Tagihan Operasional</span></td>
                            <td>IndiHome Rp 350.000</td>
                        </tr>
                        <tr>
                            <td>Servis AC kamar tertentu</td>
                            <td><span class="badge bg-info">Maintenance</span> (Spesifik)</td>
                            <td>Servis AC kamar 04 Rp 350.000</td>
                        </tr>
                        <tr>
                            <td>Perbaiki genteng bocor</td>
                            <td><span class="badge bg-info">Maintenance</span> (Umum)</td>
                            <td>Perbaiki genteng Rp 450.000</td>
                        </tr>
                        <tr>
                            <td>Beli sapu, ember, perabotan</td>
                            <td><span class="badge bg-dark">Pengeluaran</span></td>
                            <td>Beli alat kebersihan Rp 85.000</td>
                        </tr>
                        <tr>
                            <td>Bayar pajak bumi & bangunan</td>
                            <td><span class="badge bg-warning text-dark">Tagihan Operasional</span> (Sekali Bayar)</td>
                            <td>PBB 2026 Rp 500.000</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ====== FAQ ====== -->
        <div class="table-wrapper">
            <h5 class="fw-bold mb-3"><i class="bi bi-question-circle me-2"></i>Pertanyaan yang Sering Ditanyakan</h5>
            <div class="accordion" id="accordionFAQ">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Peringatan di Dashboard tidak muncul, padahal ada penyewa yang belum bayar?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <p>Peringatan hanya muncul jika:</p>
                            <ol>
                                <li>Penyewa berstatus <strong>Aktif</strong>.</li>
                                <li>Jatuh tempo bulan ini sudah <strong>lewat</strong> atau tinggal <strong>3 hari lagi</strong>.</li>
                                <li>Belum ada catatan pemasukan dengan kategori "Sewa" untuk <strong>periode bulan ini</strong>.</li>
                            </ol>
                            <p class="mb-0">Pastikan saat mencatat pembayaran, isi <strong>periode bulan</strong> dengan benar (misal: 2026-03 untuk Maret 2026). Jika periode salah, sistem menganggap bulan ini belum dibayar.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Tombol "Tagih via WA" tidak muncul di Dashboard?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            Tombol WhatsApp hanya muncul jika <strong>No. HP penyewa sudah diisi</strong>. Buka menu <strong>Penyewa</strong>, klik edit, lalu isi nomor HP. Format yang didukung: <code>08123456789</code> atau <code>628123456789</code> — keduanya bisa.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Pengeluaran PLN tercatat dua kali di laporan?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <p>Ini terjadi karena Anda mencatat PLN di <strong>dua tempat</strong>:</p>
                            <ol>
                                <li>Di menu <strong>Pengeluaran</strong> (input manual).</li>
                                <li>Di menu <strong>Tagihan Operasional</strong> (klik "Bayar" yang otomatis catat ke pengeluaran).</li>
                            </ol>
                            <p class="mb-0"><strong>Solusi:</strong> Untuk PLN, PDAM, dan tagihan rutin lainnya, <strong>hanya gunakan Tagihan Operasional</strong>. Menu Pengeluaran hanya untuk pengeluaran lain-lain yang tidak rutin (beli perabotan, dll). Hapus entri yang dobel di menu Pengeluaran.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Bagaimana cara menghapus data contoh saat pertama kali buka?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <ol>
                                <li>Buka menu <strong>Pengaturan</strong>.</li>
                                <li>Scroll ke bawah ke bagian <strong>"Reset Seluruh Database"</strong>.</li>
                                <li>Ketik <code>HAPUS-SEMUA</code> di kolom konfirmasi.</li>
                                <li>Klik tombol <strong>Reset</strong>. Semua data contoh akan dihapus.</li>
                                <li>Lalu ikuti langkah "Cara Mulai Menggunakan" di atas.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Apakah data saya aman? Tersimpan di mana?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <p>Semua data tersimpan <strong>di komputer Anda sendiri</strong> (dalam file database lokal). Tidak ada data yang dikirim ke internet.</p>
                            <p class="mb-0"><strong>Tips backup:</strong> Salin file <code>database/juragan_kos.db</code> ke flashdisk atau Google Drive secara berkala (misal setiap minggu). Jika komputer rusak, data bisa dikembalikan dari backup.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            Penyewa saya ada yang bayar tahunan, bisa?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            Bisa. Saat mendaftarkan penyewa, pilih <strong>Tipe Sewa: Tahunan</strong>. Saat mencatat pembayaran di Pemasukan, isi nominal dengan harga tahunan. Perlu dicatat bahwa pengingat di Dashboard tetap per bulan, jadi setelah mencatat bayar tahunan, pengingat bulan-bulan berikutnya tetap perlu dicatat (bisa dengan nominal Rp 0 dan keterangan "sudah bayar tahunan").
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            Bisa diakses dari HP / tablet?
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <p>Bisa. Aplikasi sudah <strong>responsive</strong> — tampilan otomatis menyesuaikan layar HP dan tablet. Fitur mobile meliputi:</p>
                            <ul class="mb-0">
                                <li>Sidebar menjadi overlay (buka/tutup via tombol hamburger di kiri atas).</li>
                                <li>Tabel, form, kartu statistik, dan badge semuanya menyesuaikan layar kecil.</li>
                                <li>Modal (popup) full-width di layar HP.</li>
                                <li>Input tidak auto-zoom di iOS.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                            Bagaimana cara mengubah warna sidebar?
                        </button>
                    </h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body small">
                            <ol class="mb-0">
                                <li>Buka menu <strong>Pengaturan</strong>.</li>
                                <li>Di bagian <strong>Warna Sidebar</strong>, klik salah satu kotak warna preset atau gunakan color picker untuk warna kustom.</li>
                                <li>Jika sidebar menggunakan warna terang (kuning, oranye, dll), ubah <strong>Mode Font</strong> ke <em>Dark</em> agar teks terbaca.</li>
                                <li>Klik <strong>Simpan Warna</strong>. Perubahan langsung terlihat.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
