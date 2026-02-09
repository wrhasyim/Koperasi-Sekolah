<?php
// QUERY STATISTIK (Sama seperti sebelumnya)
$q_kas = $pdo->query("SELECT SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tm, SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tk FROM transaksi_kas")->fetch();
$saldo_kas = $q_kas['tm'] - $q_kas['tk'];
$q_sihara = $pdo->query("SELECT SUM(jumlah) as total FROM simpanan WHERE jenis_simpanan='hari_raya' AND tipe_transaksi='setor'")->fetch();
$q_anggota = $pdo->query("SELECT COUNT(*) as total FROM anggota WHERE status_aktif=1")->fetch();
$q_stok = $pdo->query("SELECT COUNT(*) as total FROM stok_barang WHERE stok < 5")->fetch();
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

<div class="row g-4 mb-5">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-success text-white h-100 overflow-hidden border-0">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-money-bill-wave fa-6x"></i>
                </div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Saldo Kas Tunai</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($saldo_kas) ?></h2>
                <small class="text-white-50 mt-2 d-block">Dana siap pakai (Liquid)</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-primary text-white h-100 overflow-hidden border-0">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-wallet fa-6x"></i>
                </div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Aset Sihara</h6>
                <h2 class="display-6 fw-bold mb-0"><?= formatRp($q_sihara['total']) ?></h2>
                <small class="text-white-50 mt-2 d-block">Tabungan Anggota</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-info text-white h-100 overflow-hidden border-0">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-users fa-6x"></i>
                </div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Total Anggota</h6>
                <h2 class="display-6 fw-bold mb-0"><?= $q_anggota['total'] ?></h2>
                <small class="text-white-50 mt-2 d-block">Guru & Staff Aktif</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-gradient-danger text-white h-100 overflow-hidden border-0">
            <div class="card-body position-relative p-4">
                <div class="position-absolute top-0 end-0 opacity-25" style="margin-top: -10px; margin-right: -10px;">
                    <i class="fas fa-exclamation-triangle fa-6x"></i>
                </div>
                <h6 class="text-uppercase text-white-50 ls-1 mb-1">Stok Menipis</h6>
                <h2 class="display-6 fw-bold mb-0"><?= $q_stok['total'] ?></h2>
                <small class="text-white-50 mt-2 d-block">Perlu Restock Segera</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pb-0">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-exchange-alt me-2 text-primary"></i> Transaksi Terakhir</h6>
                <a href="kas/laporan_kas" class="btn btn-sm btn-light rounded-pill px-3">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Info</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_trx as $row): ?>
                            <tr>
                                <td>
                                    <span class="d-block fw-bold text-dark"><?= date('d M', strtotime($row['tanggal'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="d-block text-dark"><?= htmlspecialchars($row['keterangan']) ?></span>
                                    <span class="badge bg-light text-secondary border border-secondary border-opacity-25 rounded-pill fw-normal" style="font-size: 0.7rem;">
                                        <?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
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
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-rocket me-2 text-warning"></i> Akses Cepat</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="kas/kas_penjualan" class="btn btn-light p-3 text-start shadow-sm d-flex align-items-center border transition-hover">
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle me-3 text-success">
                            <i class="fas fa-cash-register fa-lg"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Kasir Penjualan</div>
                            <small class="text-muted">Input omzet harian</small>
                        </div>
                    </a>
                    
                    <a href="titipan/titipan" class="btn btn-light p-3 text-start shadow-sm d-flex align-items-center border transition-hover">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 text-primary">
                            <i class="fas fa-box-open fa-lg"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Konsinyasi Guru</div>
                            <small class="text-muted">Cek stok titipan</small>
                        </div>
                    </a>

                    <a href="inventory/stok_sekolah" class="btn btn-light p-3 text-start shadow-sm d-flex align-items-center border transition-hover">
                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle me-3 text-warning">
                            <i class="fas fa-tshirt fa-lg"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Distribusi Seragam</div>
                            <small class="text-muted">Catat pengambilan siswa</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>