<?php
// pages/dashboard.php

// =========================================================
// BAGIAN 1: DASHBOARD KHUSUS STAFF (OPERASIONAL HARIAN)
// =========================================================
if($_SESSION['user']['role'] == 'staff') {
    $today = date('Y-m-d');
    $trx = $pdo->query("SELECT COUNT(*) FROM transaksi_kas WHERE tanggal = '$today'")->fetchColumn();
    
    $stok_k_menipis = $pdo->query("SELECT COUNT(*) FROM stok_koperasi WHERE stok < 5")->fetchColumn() ?: 0;
    $stok_s_menipis = $pdo->query("SELECT COUNT(*) FROM stok_sekolah WHERE stok < 5")->fetchColumn() ?: 0;
    $alert_stok = $stok_k_menipis + $stok_s_menipis;
    
    // Sinkronisasi kolom 'status_bayar' sesuai database
    $titipan = $pdo->query("SELECT COUNT(*) FROM titipan WHERE status_bayar = 'belum'")->fetchColumn() ?: 0;
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
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded"><i class="fas fa-cash-register fa-2x text-primary"></i></div>
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
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded"><i class="fas fa-exclamation-triangle fa-2x text-warning"></i></div>
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
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded"><i class="fas fa-box-open fa-2x text-info"></i></div>
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
    // 1. SALDO KAS FISIK (Total seluruh uang)
    $q_kas = $pdo->query("SELECT SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE -jumlah END) as saldo FROM transaksi_kas")->fetch();
    $kas_fisik = $q_kas['saldo'] ?? 0;

    // 2. KEWAJIBAN (Tabungan Anggota + Hutang Titipan ke Guru)
    $q_simp = $pdo->query("SELECT SUM(CASE WHEN tipe_transaksi = 'setor' THEN jumlah ELSE -jumlah END) as total FROM simpanan")->fetch();
    $total_tabungan = $q_simp['total'] ?? 0;

    // Sinkronisasi kolom 'stok_terjual' dan 'harga_modal'
    $q_titip = $pdo->query("SELECT SUM(stok_terjual * harga_modal) as hutang FROM titipan WHERE status_bayar = 'belum'")->fetch();
    $hutang_titipan = $q_titip['hutang'] ?? 0;

    $total_kewajiban = $total_tabungan + $hutang_titipan;
    $dana_bebas = $kas_fisik - $total_kewajiban;

    $jml_anggota = $pdo->query("SELECT COUNT(*) FROM anggota WHERE status_aktif=1")->fetchColumn();

    $stok_menipis_list = [];
    try {
        $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query("SELECT nama_barang as nama, 'Koperasi' as jenis, stok as sisa FROM stok_koperasi WHERE stok <= 5")->fetchAll());
        $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query("SELECT nama_barang as nama, 'Titipan' as jenis, (stok_awal - stok_terjual) as sisa FROM titipan WHERE (stok_awal - stok_terjual) <= 5 AND status_bayar = 'belum'")->fetchAll());
    } catch (Exception $e) {}

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 7;
    $recent_trx = $pdo->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT $limit")->fetchAll();
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
                    <div class="d-flex justify-content-between"><span>Tabungan:</span> <span><?= formatRp($total_tabungan) ?></span></div>
                    <div class="d-flex justify-content-between"><span>Titipan:</span> <span><?= formatRp($hutang_titipan) ?></span></div>
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
                <small class="text-white-50">Dana murni milik koperasi</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-exchange-alt me-2 text-primary"></i> Mutasi Kas Terakhir</h6>
                <a href="index.php?page=kas/laporan_kas" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="bg-light">
                        <tr><th class="ps-4">Tanggal</th><th>Keterangan</th><th class="text-end pe-4">Nominal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_trx as $row): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?= date('d M', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="d-block text-dark"><?= htmlspecialchars($row['keterangan']) ?></span>
                                <span class="badge bg-light text-secondary border rounded-pill fw-normal" style="font-size: 0.7rem;"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <span class="fw-bold <?= $row['arus']=='masuk'?'text-success':'text-danger' ?>">
                                    <?= $row['arus']=='masuk'?'+':'-' ?> <?= number_format($row['jumlah']) ?>
                                </span>
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
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle p-3"><i class="fas fa-users fa-lg"></i></div>
                <div class="ms-3"><h6 class="mb-0 fw-bold">Anggota Aktif</h6><div class="fw-bold fs-4"><?= $jml_anggota ?></div></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-danger m-0"><i class="fas fa-bell me-2"></i> Perlu Restock</h6>
                <span class="badge bg-danger rounded-pill"><?= count($stok_menipis_list) ?></span>
            </div>
            <div class="table-responsive" style="max-height: 350px;">
                <table class="table table-striped table-hover mb-0 small">
                    <tbody>
                        <?php foreach($stok_menipis_list as $brg): ?>
                        <tr>
                            <td class="ps-3"><span class="fw-bold d-block"><?= $brg['nama'] ?></span><small class="text-muted"><?= $brg['jenis'] ?></small></td>
                            <td class="text-center pe-3"><span class="badge bg-danger"><?= $brg['sisa'] ?></span></td>
                        </tr>
                        <?php endforeach; if(empty($stok_menipis_list)): ?>
                        <tr><td colspan="2" class="text-center py-4 text-muted">Stok aman semua.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php } ?>