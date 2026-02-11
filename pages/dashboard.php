<?php
// pages/dashboard.php

// =========================================================
// BAGIAN 1: DASHBOARD KHUSUS STAFF (OPERASIONAL HARIAN)
// =========================================================
if($_SESSION['user']['role'] == 'staff') {
    // 1. Hitung Transaksi Hari Ini
    $today = date('Y-m-d');
    $trx = $pdo->query("SELECT COUNT(*) FROM transaksi_kas WHERE tanggal LIKE '$today%'")->fetchColumn();
    
    // 2. Hitung Stok Menipis (Warning)
    $stok_koperasi = 0;
    try {
        $stok_koperasi = $pdo->query("SELECT COUNT(*) FROM stok_koperasi WHERE stok < 5")->fetchColumn();
    } catch(Exception $e){}
    
    $stok_sekolah = 0;
    try {
        $stok_sekolah = $pdo->query("SELECT COUNT(*) FROM stok_sekolah WHERE stok < 5")->fetchColumn();
    } catch(Exception $e){}
    
    $alert_stok = $stok_koperasi + $stok_sekolah;
    
    // 3. Hitung Titipan Belum Bayar (Pending)
    $titipan = 0;
    try {
        $titipan = $pdo->query("SELECT COUNT(*) FROM titipan WHERE status != 'lunas'")->fetchColumn();
    } catch(Exception $e){}
?>
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark">Halo, Staff <?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?>! 👋</h2>
            <p class="text-muted">Selamat bertugas. Berikut ringkasan operasional hari ini.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-cash-register fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Transaksi Hari Ini</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?= $trx ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Stok Menipis (< 5)</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?= $alert_stok ?> <small class="fs-6 text-muted">Item</small></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-box-open fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase text-muted small fw-bold mb-1">Titipan Aktif</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?= $titipan ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php 
// =========================================================
// BAGIAN 2: DASHBOARD ADMIN & PENGURUS (KEUANGAN LENGKAP)
// =========================================================
} else {

    // --- 1. HITUNG SALDO KAS FISIK (Total Uang di Tangan) ---
    // Menghitung seluruh uang masuk dikurangi uang keluar dari semua kategori
    $q_kas = $pdo->query("SELECT SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE -jumlah END) as saldo FROM transaksi_kas")->fetch();
    $kas_fisik = $q_kas['saldo'] ?? 0;

    // --- 2. HITUNG KEWAJIBAN / DANA MENGENDAP (Uang Orang Lain) ---
    // a. Tabungan Siswa/Guru (Saldo Simpanan)
    $q_simp = $pdo->query("SELECT SUM(saldo) as saldo FROM simpanan")->fetch();
    $total_tabungan = $q_simp['saldo'] ?? 0;

    // b. Hutang Titipan (Barang laku tapi uang belum disetor ke guru)
    $hutang_titipan = 0;
    try {
        $q_titip = $pdo->query("SELECT SUM(stok_terjual * harga_beli) as hutang FROM titipan WHERE status != 'lunas'")->fetch();
        $hutang_titipan = $q_titip['hutang'] ?? 0;
    } catch(Exception $e){}

    $total_kewajiban = $total_tabungan + $hutang_titipan;

    // --- 3. HITUNG DANA BEBAS (Modal Sendiri / Real Cash) ---
    $dana_bebas = $kas_fisik - $total_kewajiban;

    // --- 4. DATA PENDUKUNG (Member & Stok) ---
    $q_anggota = $pdo->query("SELECT COUNT(*) as total FROM anggota WHERE status_aktif=1")->fetch();
    $jml_anggota = $q_anggota['total'];

    // Stok Menipis
    $limit_stok = 5;
    $stok_menipis_list = [];

    // Cek Koperasi
    try {
        $sql = "SELECT id, nama_barang as nama, 'Koperasi' as jenis, stok as sisa FROM stok_koperasi WHERE stok <= $limit_stok";
        $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
    } catch (Exception $e) {}

    // Cek Titipan
    try {
        $sql = "SELECT id, nama_barang as nama, 'Titipan' as jenis, (stok_awal - stok_terjual) as sisa FROM titipan WHERE (stok_awal - stok_terjual) <= $limit_stok AND status != 'lunas'";
        $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
    } catch (Exception $e) {}

    $total_alert = count($stok_menipis_list);

    // Transaksi Terakhir
    $recent_trx = $pdo->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 7")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Financial Overview</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">Dashboard Koperasi</h2>
    </div>
    <div class="d-none d-md-block">
        <span class="bg-white px-3 py-2 rounded-pill shadow-sm text-muted small border">
            <i class="far fa-calendar-alt me-2 text-primary"></i> <?= date('d M Y') ?>
        </span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card bg-primary text-white h-100 overflow-hidden border-0 shadow-lg rounded-4">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25 m-3"><i class="fas fa-wallet fa-5x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Total Kas Fisik</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($kas_fisik) ?></h2>
                <small class="text-white-50">Semua uang di laci & rekening</small>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card bg-danger text-white h-100 overflow-hidden border-0 shadow-lg rounded-4">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25 m-3"><i class="fas fa-hand-holding-usd fa-5x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Dana Milik Anggota</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($total_kewajiban) ?></h2>
                <div class="mt-2 small text-white-50 border-top pt-2" style="border-color: rgba(255,255,255,0.2) !important;">
                    <div class="d-flex justify-content-between">
                        <span>Tabungan:</span> <span><?= formatRp($total_tabungan) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Titipan Pending:</span> <span><?= formatRp($hutang_titipan) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card bg-success text-white h-100 overflow-hidden border-0 shadow-lg rounded-4">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25 m-3"><i class="fas fa-coins fa-5x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Modal Bebas (Aman)</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($dana_bebas) ?></h2>
                <small class="text-white-50">Dana aman untuk belanja operasional</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-exchange-alt me-2 text-primary"></i> Mutasi Kas Terakhir</h6>
                <a href="kas/laporan_kas" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-end pe-4">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_trx as $row): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?= date('d M', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="d-block text-dark"><?= htmlspecialchars($row['keterangan']) ?></span>
                                <span class="badge bg-light text-secondary border rounded-pill fw-normal" style="font-size: 0.7rem;">
                                    <?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if($row['arus'] == 'masuk'): ?>
                                    <span class="text-success fw-bold">+ <?= number_format($row['jumlah']) ?></span>
                                <?php else: ?>
                                    <span class="text-danger fw-bold">- <?= number_format($row['jumlah']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-bold">Anggota Aktif</h6>
                        <small class="text-muted">Guru & Staff</small>
                    </div>
                    <div class="fw-bold fs-4 text-dark"><?= $jml_anggota ?></div>
                </div>
            </div>
        </div>

        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-danger m-0"><i class="fas fa-bell me-2"></i> Perlu Restock</h6>
                <span class="badge bg-danger rounded-pill"><?= $total_alert ?></span>
            </div>
            <div class="card-body p-0">
                <?php if($total_alert > 0): ?>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-striped table-hover mb-0 small">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="ps-3">Barang</th>
                                <th class="text-center pe-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stok_menipis_list as $brg): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold d-block text-dark"><?= htmlspecialchars($brg['nama']) ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= $brg['jenis'] ?></span>
                                </td>
                                <td class="text-center pe-3">
                                    <?php if($brg['sisa'] <= 0): ?>
                                        <span class="badge bg-danger">HABIS</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-danger"><?= $brg['sisa'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-2 text-success opacity-50"></i>
                        <p class="mb-0 small">Stok aman.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-center p-3">
                <div class="d-grid gap-2">
                    <a href="inventory/stok_koperasi" class="btn btn-outline-dark btn-sm rounded-pill fw-bold">Cek Gudang</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>