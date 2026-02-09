<?php
// --- 1. HITUNG SALDO KAS ---
$q_kas = $pdo->query("SELECT 
    SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
    FROM transaksi_kas");
$kas = $q_kas->fetch();
$saldo_kas = $kas['total_masuk'] - $kas['total_keluar'];

// --- 2. HITUNG TOTAL SIHARA ---
$q_sihara = $pdo->query("SELECT SUM(jumlah) as total FROM simpanan WHERE jenis_simpanan='hari_raya' AND tipe_transaksi='setor'");
$sihara = $q_sihara->fetch();

// --- 3. HITUNG JUMLAH ANGGOTA ---
$q_anggota = $pdo->query("SELECT COUNT(*) as total FROM anggota WHERE status_aktif=1");
$anggota = $q_anggota->fetch();

// --- 4. CEK STOK MENIPIS (KURANG DARI 5) ---
$q_stok = $pdo->query("SELECT COUNT(*) as total FROM stok_barang WHERE stok < 5");
$stok_kritis = $q_stok->fetch();

// --- 5. AMBIL 5 TRANSAKSI TERAKHIR ---
$q_recent = $pdo->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 5");
$recent_trx = $q_recent->fetchAll();

// --- 6. AMBIL DATA BARANG YANG MENIPIS ---
$q_stok_list = $pdo->query("SELECT * FROM stok_barang WHERE stok < 5 ORDER BY stok ASC LIMIT 5");
$stok_list = $q_stok_list->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Dashboard Overview</h1>
        <p class="text-muted">Halo, <b><?= $_SESSION['user']['nama'] ?></b>! Berikut laporan hari ini.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-secondary p-2"><i class="fas fa-calendar-alt me-1"></i> <?= tglIndo(date('Y-m-d')) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold">SALDO KAS TUNAI</small>
                        <h4 class="mb-0 fw-bold text-success"><?= formatRp($saldo_kas) ?></h4>
                    </div>
                    <div class="fs-1 text-success opacity-25"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold">TABUNGAN SIHARA</small>
                        <h4 class="mb-0 fw-bold text-primary"><?= formatRp($sihara['total']) ?></h4>
                    </div>
                    <div class="fs-1 text-primary opacity-25"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold">ANGGOTA AKTIF</small>
                        <h4 class="mb-0 fw-bold text-info"><?= $anggota['total'] ?> Orang</h4>
                    </div>
                    <div class="fs-1 text-info opacity-25"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold">STOK MENIPIS</small>
                        <h4 class="mb-0 fw-bold text-danger"><?= $stok_kritis['total'] ?> Barang</h4>
                    </div>
                    <div class="fs-1 text-danger opacity-25"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i> 5 Transaksi Kas Terakhir</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Tgl</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Arus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_trx as $row): ?>
                        <tr>
                            <td><?= date('d/m', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($row['keterangan']) ?></span>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></small>
                            </td>
                            <td class="text-end fw-bold">
                                <?= formatRp($row['jumlah']) ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['arus'] == 'masuk'): ?>
                                    <span class="badge bg-success"><i class="fas fa-arrow-down"></i> Masuk</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-arrow-up"></i> Keluar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_trx)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada transaksi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="kas/laporan_kas" class="text-decoration-none small">Lihat Semua Laporan &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0 small fw-bold"><i class="fas fa-box-open me-2"></i> Perlu Kulakan (Stok < 5)</h6>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach($stok_list as $s): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold d-block"><?= htmlspecialchars($s['nama_barang']) ?></span>
                        <small class="text-muted"><?= $s['ukuran'] ?></small>
                    </div>
                    <span class="badge bg-danger rounded-pill">Sisa <?= $s['stok'] ?></span>
                </li>
                <?php endforeach; ?>
                <?php if(empty($stok_list)): ?>
                    <li class="list-group-item text-center text-muted small py-3">Aman! Tidak ada stok kritis.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Akses Cepat</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="kas/kas_penjualan" class="btn btn-outline-success text-start">
                        <i class="fas fa-cash-register me-2"></i> Input Penjualan
                    </a>
                    <a href="titipan/titipan" class="btn btn-outline-primary text-start">
                        <i class="fas fa-box me-2"></i> Cek Titipan Guru
                    </a>
                    <a href="inventory/stok_sekolah" class="btn btn-outline-dark text-start">
                        <i class="fas fa-tshirt me-2"></i> Cek Stok Seragam
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>