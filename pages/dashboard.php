<?php
// --- 1. LOGIKA QUERY STATISTIK ---

// A. Saldo Kas & Sihara & Anggota
$q_kas = $pdo->query("SELECT SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tm, SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tk FROM transaksi_kas")->fetch();
$saldo_kas = $q_kas['tm'] - $q_kas['tk'];
$q_sihara = $pdo->query("SELECT SUM(jumlah) as total FROM simpanan WHERE jenis_simpanan='hari_raya' AND tipe_transaksi='setor'")->fetch();
$q_anggota = $pdo->query("SELECT COUNT(*) as total FROM anggota WHERE status_aktif=1")->fetch();

// D. LOGIKA STOCK ALERT (GABUNGAN 4 SUMBER)
$limit_stok = 5;
$stok_menipis_list = [];

// 1. Cek Titipan
try {
    $sql = "SELECT id, nama_barang as nama, 'Titipan' as jenis, (stok_awal - stok_terjual) as sisa FROM titipan WHERE (stok_awal - stok_terjual) <= $limit_stok";
    $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
} catch (Exception $e) {}

// 2. Cek Stok Seragam (Tabel stok_sekolah)
try {
    $sql = "SELECT id, nama_barang as nama, 'Seragam' as jenis, stok as sisa FROM stok_sekolah WHERE stok <= $limit_stok";
    $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
} catch (Exception $e) {}

// 3. Cek Stok Koperasi (Tabel stok_koperasi - BARU)
try {
    $sql = "SELECT id, nama_barang as nama, 'Koperasi' as jenis, stok as sisa FROM stok_koperasi WHERE stok <= $limit_stok";
    $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
} catch (Exception $e) {}

// 4. Cek Stok Eskul
try {
    $sql = "SELECT id, nama_barang as nama, 'Eskul' as jenis, stok as sisa FROM stok_eskul WHERE stok <= $limit_stok";
    $stok_menipis_list = array_merge($stok_menipis_list, $pdo->query($sql)->fetchAll());
} catch (Exception $e) {}

$total_alert = count($stok_menipis_list);
$recent_trx = $pdo->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 5")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Overview</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">Dashboard Koperasi</h2>
    </div>
    <div class="d-none d-md-block">
        <span class="bg-white px-3 py-2 rounded-pill shadow-sm text-muted small border">
            <i class="far fa-calendar-alt me-2 text-primary"></i> <?= tglIndo(date('Y-m-d')) ?>
        </span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-success text-white h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;"><i class="fas fa-money-bill-wave fa-6x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Saldo Kas Tunai</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($saldo_kas) ?></h2>
                <small class="text-white-50 mt-2 d-block">Dana siap pakai (Liquid)</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-primary text-white h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;"><i class="fas fa-wallet fa-6x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Aset Sihara</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($q_sihara['total']) ?></h2>
                <small class="text-white-50 mt-2 d-block">Tabungan Anggota</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-info text-white h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;"><i class="fas fa-users fa-6x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Total Anggota</h6>
                <h2 class="display-6 fw-bold mb-0"><?= $q_anggota['total'] ?></h2>
                <small class="text-white-50 mt-2 d-block">Guru & Staff Aktif</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-danger text-white h-100 overflow-hidden border-0 shadow-sm">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;"><i class="fas fa-exclamation-triangle fa-6x"></i></div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Stok Menipis</h6>
                <h2 class="display-6 fw-bold mb-0"><?= $total_alert ?></h2>
                <small class="text-white-50 mt-2 d-block">Perlu Restock Segera</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-exchange-alt me-2 text-primary"></i> Transaksi Terakhir</h6>
                <a href="kas/laporan_kas" class="btn btn-sm btn-light rounded-pill px-3">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light"><tr><th class="ps-4">Info</th><th>Keterangan</th><th class="text-end pe-4">Jumlah</th></tr></thead>
                        <tbody>
                            <?php foreach($recent_trx as $row): ?>
                            <tr>
                                <td class="ps-4"><span class="d-block fw-bold text-dark"><?= date('d M', strtotime($row['tanggal'])) ?></span><small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small></td>
                                <td><span class="d-block text-dark"><?= htmlspecialchars($row['keterangan']) ?></span><span class="badge bg-light text-secondary border border-secondary border-opacity-25 rounded-pill fw-normal" style="font-size: 0.7rem;"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></span></td>
                                <td class="text-end pe-4"><?php if($row['arus'] == 'masuk'): ?><span class="text-success fw-bold">+ <?= number_format($row['jumlah']) ?></span><?php else: ?><span class="text-danger fw-bold">- <?= number_format($row['jumlah']) ?></span><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="fw-bold text-danger m-0"><i class="fas fa-bell me-2"></i> Perlu Restock (<?= $total_alert ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if($total_alert > 0): ?>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-striped table-hover mb-0 small">
                        <thead class="bg-danger text-white"><tr><th class="ps-3">Nama Barang</th><th>Jenis</th><th class="text-center pe-3">Sisa</th></tr></thead>
                        <tbody>
                            <?php foreach($stok_menipis_list as $brg): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= htmlspecialchars($brg['nama']) ?></td>
                                <td>
                                    <?php if($brg['jenis'] == 'Titipan'): ?><span class="badge bg-warning text-dark bg-opacity-25">Titipan</span>
                                    <?php elseif($brg['jenis'] == 'Seragam'): ?><span class="badge bg-primary text-primary bg-opacity-10">Seragam</span>
                                    <?php elseif($brg['jenis'] == 'Koperasi'): ?><span class="badge bg-success text-success bg-opacity-10">Koperasi</span>
                                    <?php else: ?><span class="badge bg-info text-info bg-opacity-10">Eskul</span><?php endif; ?>
                                </td>
                                <td class="text-center pe-3"><?php if($brg['sisa'] <= 0): ?><span class="badge bg-danger">HABIS</span><?php else: ?><span class="fw-bold text-danger"><?= $brg['sisa'] ?></span><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i><p class="mb-0">Semua Stok Aman.</p></div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-center">
                <div class="btn-group w-100 shadow-sm">
                    <a href="inventory/stok_koperasi" class="btn btn-sm btn-outline-success">Umum</a>
                    <a href="inventory/stok_sekolah" class="btn btn-sm btn-outline-primary">Seragam</a>
                    <a href="inventory/stok_eskul" class="btn btn-sm btn-outline-info">Eskul</a>
                </div>
            </div>
        </div>
    </div>
</div>