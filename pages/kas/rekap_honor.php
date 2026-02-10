<?php
// pages/kas/rekap_honor.php

// 1. SETUP FILTER
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$filter_jenis = isset($_GET['filter_jenis']) ? $_GET['filter_jenis'] : 'all';

// 2. AMBIL PENGATURAN
$set = getAllPengaturan($pdo);

// 3. HITUNG SURPLUS KAS OPERASIONAL
// MENGECUALIKAN kategori pembayaran honor agar surplus tidak turun saat dibayar
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN (
            'penjualan_seragam', 'penjualan_eskul', 
            'bagi_hasil_staff', 'bagi_hasil_pengurus', 'bagi_hasil_pembina', 'bagi_hasil_dansos'
        ) 
        ORDER BY tanggal ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

$total_masuk = 0; 
$total_keluar = 0;

foreach($transaksi as $t){
    if($t['arus'] == 'masuk') $total_masuk += $t['jumlah'];
    else $total_keluar += $t['jumlah'];
}

$surplus = $total_masuk - $total_keluar;

// 4. HITUNG ALOKASI DINAMIS
$alloc = ['staff'=>0, 'pengurus'=>0, 'pembina'=>0, 'dansos'=>0];

if($surplus > 0){
    $alloc['staff']    = $surplus * ($set['persen_staff'] / 100);
    $alloc['pengurus'] = $surplus * ($set['persen_pengurus'] / 100);
    $alloc['pembina']  = $surplus * ($set['persen_pembina'] / 100);
    $alloc['dansos']   = $surplus * ($set['persen_dansos'] / 100);
}

// 5. CEK STATUS PEMBAYARAN (LOGIKA BARU)
// Mengecek apakah honor tipe X sudah dibayar di periode ini (berdasarkan string di keterangan)
$status_bayar = [];
$list_tipe = ['staff', 'pengurus', 'pembina', 'dansos'];

foreach($list_tipe as $tipe){
    $kategori_cek = "bagi_hasil_" . $tipe;
    // String pencarian harus sama persis dengan format di process/kas_bayar_honor.php
    $ket_cek = "%$tgl_awal s/d $tgl_akhir%"; 

    $cek = $pdo->prepare("SELECT id FROM transaksi_kas WHERE kategori = ? AND keterangan LIKE ?");
    $cek->execute([$kategori_cek, $ket_cek]);
    
    // True jika sudah ada data
    $status_bayar[$tipe] = ($cek->rowCount() > 0); 
}
?>

<style>
    .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.3s; background: #fff; overflow: hidden; }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .btn-action { border-radius: 50px; font-weight: bold; font-size: 0.8rem; padding: 8px 15px; transition: 0.2s; width: 100%; display: block; margin-top: 5px;}
    .btn-action:hover { transform: scale(1.02); }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Manajemen Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Rekapitulasi Honor & Dana Sosial</h2>
    </div>
    <a href="index.php?page=utilitas/pengaturan" class="btn btn-sm btn-light text-primary fw-bold rounded-pill shadow-sm">
        <i class="fas fa-cog me-2"></i> Pengaturan Persentase
    </a>
</div>

<div class="card card-modern bg-gradient-primary text-white mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="text-white-50 text-uppercase small fw-bold mb-1">Total Surplus (SHU Sementara)</h5>
                <h2 class="mb-0 fw-bold"><?= formatRp($surplus) ?></h2>
                <small class="text-white-50">Periode: <?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?></small>
                <div class="mt-2 text-white-50 small fst-italic">
                    *Surplus ini belum dikurangi pembayaran honor periode ini.
                </div>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-wallet fa-4x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-4">
    <div class="card-body p-3">
        <form class="row g-2 align-items-center" method="GET">
            <div class="col-auto fw-bold text-muted small text-uppercase"><i class="fas fa-filter me-2"></i>Filter:</div>
            <div class="col-auto"><input type="date" name="tgl_awal" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $tgl_awal ?>"></div>
            <div class="col-auto">-</div>
            <div class="col-auto"><input type="date" name="tgl_akhir" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $tgl_akhir ?>"></div>
            
            <div class="col-auto ms-3 fw-bold text-muted small text-uppercase">Tampilkan:</div>
            <div class="col-auto">
                <select name="filter_jenis" class="form-select form-select-sm border-0 bg-light fw-bold">
                    <option value="all" <?= $filter_jenis=='all'?'selected':'' ?>>Semua Honor</option>
                    <option value="staff" <?= $filter_jenis=='staff'?'selected':'' ?>>Honor Staff</option>
                    <option value="pengurus" <?= $filter_jenis=='pengurus'?'selected':'' ?>>Honor Pengurus</option>
                    <option value="pembina" <?= $filter_jenis=='pembina'?'selected':'' ?>>Honor Pembina</option>
                    <option value="dansos" <?= $filter_jenis=='dansos'?'selected':'' ?>>Dana Sosial</option>
                </select>
            </div>

            <div class="col-auto ms-2">
                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold shadow-sm">Terapkan</button>
            </div>
        </form>
    </div>
</div>

<?php if($surplus <= 0): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-3">
        <i class="fas fa-exclamation-triangle me-2"></i> 
        <strong>Tidak ada surplus!</strong> Pengeluaran operasional melebihi pemasukan.
    </div>
<?php else: ?>

<div class="row g-4">
    
    <?php if($filter_jenis == 'all' || $filter_jenis == 'staff'): ?>
    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-primary text-uppercase mb-1">Honor Staff / Petugas</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Alokasi <?= $set['persen_staff'] ?>%</span>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['staff']) ?></h3>
                
                <div class="row g-2">
                    <div class="col-6">
                        <a href="pages/kas/cetak_honor.php?tipe=staff&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                           target="_blank" class="btn btn-outline-primary btn-action shadow-sm">
                            <i class="fas fa-print me-2"></i> Cetak
                        </a>
                    </div>
                    <div class="col-6">
                        <?php if($status_bayar['staff']): ?>
                            <button class="btn btn-light text-success fw-bold btn-action shadow-sm border" disabled>
                                <i class="fas fa-check-circle me-1"></i> Sudah Dibayar
                            </button>
                        <?php else: ?>
                            <form action="process/kas_bayar_honor.php" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini? Transaksi akan tercatat di Laporan Kas.')">
                                <input type="hidden" name="tipe" value="staff">
                                <input type="hidden" name="nominal" value="<?= $alloc['staff'] ?>">
                                <input type="hidden" name="tgl_awal" value="<?= $tgl_awal ?>">
                                <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                                <button type="submit" class="btn btn-primary btn-action shadow-sm">
                                    <i class="fas fa-money-bill-wave me-2"></i> Bayar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($filter_jenis == 'all' || $filter_jenis == 'pengurus'): ?>
    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-success text-uppercase mb-1">Honor Pengurus</h6>
                        <span class="badge bg-success bg-opacity-10 text-success">Alokasi <?= $set['persen_pengurus'] ?>%</span>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['pengurus']) ?></h3>
                
                <div class="row g-2">
                    <div class="col-6">
                        <a href="pages/kas/cetak_honor.php?tipe=pengurus&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                           target="_blank" class="btn btn-outline-success btn-action shadow-sm">
                            <i class="fas fa-print me-2"></i> Cetak
                        </a>
                    </div>
                    <div class="col-6">
                        <?php if($status_bayar['pengurus']): ?>
                            <button class="btn btn-light text-success fw-bold btn-action shadow-sm border" disabled>
                                <i class="fas fa-check-circle me-1"></i> Sudah Dibayar
                            </button>
                        <?php else: ?>
                            <form action="process/kas_bayar_honor.php" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini? Transaksi akan tercatat di Laporan Kas.')">
                                <input type="hidden" name="tipe" value="pengurus">
                                <input type="hidden" name="nominal" value="<?= $alloc['pengurus'] ?>">
                                <input type="hidden" name="tgl_awal" value="<?= $tgl_awal ?>">
                                <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                                <button type="submit" class="btn btn-success btn-action shadow-sm">
                                    <i class="fas fa-money-bill-wave me-2"></i> Bayar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($filter_jenis == 'all' || $filter_jenis == 'pembina'): ?>
    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-warning text-uppercase mb-1">Honor Pembina</h6>
                        <span class="badge bg-warning bg-opacity-10 text-dark">Alokasi <?= $set['persen_pembina'] ?>%</span>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['pembina']) ?></h3>
                
                <div class="row g-2">
                    <div class="col-6">
                        <a href="pages/kas/cetak_honor.php?tipe=pembina&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                           target="_blank" class="btn btn-outline-warning text-dark btn-action shadow-sm">
                            <i class="fas fa-print me-2"></i> Cetak
                        </a>
                    </div>
                    <div class="col-6">
                        <?php if($status_bayar['pembina']): ?>
                            <button class="btn btn-light text-success fw-bold btn-action shadow-sm border" disabled>
                                <i class="fas fa-check-circle me-1"></i> Sudah Dibayar
                            </button>
                        <?php else: ?>
                            <form action="process/kas_bayar_honor.php" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini? Transaksi akan tercatat di Laporan Kas.')">
                                <input type="hidden" name="tipe" value="pembina">
                                <input type="hidden" name="nominal" value="<?= $alloc['pembina'] ?>">
                                <input type="hidden" name="tgl_awal" value="<?= $tgl_awal ?>">
                                <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                                <button type="submit" class="btn btn-warning text-dark btn-action shadow-sm">
                                    <i class="fas fa-money-bill-wave me-2"></i> Bayar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($filter_jenis == 'all' || $filter_jenis == 'dansos'): ?>
    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-danger text-uppercase mb-1">Dana Sosial (Dansos)</h6>
                        <span class="badge bg-danger bg-opacity-10 text-danger">Alokasi <?= $set['persen_dansos'] ?>%</span>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="fas fa-hand-holding-heart fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['dansos']) ?></h3>
                
                <div class="row g-2">
                    <div class="col-6">
                        <a href="pages/kas/cetak_honor.php?tipe=dansos&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                           target="_blank" class="btn btn-outline-danger btn-action shadow-sm">
                            <i class="fas fa-print me-2"></i> Cetak
                        </a>
                    </div>
                    <div class="col-6">
                        <?php if($status_bayar['dansos']): ?>
                            <button class="btn btn-light text-success fw-bold btn-action shadow-sm border" disabled>
                                <i class="fas fa-check-circle me-1"></i> Sudah Dibayar
                            </button>
                        <?php else: ?>
                            <form action="process/kas_bayar_honor.php" method="POST" onsubmit="return confirm('Yakin ingin mencairkan dana ini? Transaksi akan tercatat di Laporan Kas.')">
                                <input type="hidden" name="tipe" value="dansos">
                                <input type="hidden" name="nominal" value="<?= $alloc['dansos'] ?>">
                                <input type="hidden" name="tgl_awal" value="<?= $tgl_awal ?>">
                                <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                                <button type="submit" class="btn btn-danger btn-action shadow-sm">
                                    <i class="fas fa-money-bill-wave me-2"></i> Bayar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>