<?php
// pages/kas/rekap_honor.php

// --- 1. SETUP FILTER ---
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- 2. HITUNG SURPLUS KAS OPERASIONAL ---
// Ambil transaksi murni (tanpa seragam/eskul)
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
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

// --- 3. KONFIGURASI PERSENTASE & HITUNG ALOKASI ---
$alloc = ['staff'=>0, 'pengurus'=>0, 'pembina'=>0, 'dansos'=>0, 'kas'=>0];

if($surplus > 0){
    $alloc['staff']    = $surplus * 0.20; // 20%
    $alloc['pengurus'] = $surplus * 0.15; // 15%
    $alloc['pembina']  = $surplus * 0.05; // 5%
    $alloc['dansos']   = $surplus * 0.10; // 10%
    $alloc['kas']      = $surplus * 0.50; // 50%
}
?>

<style>
    .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.3s; background: #fff; overflow: hidden; }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
    .btn-print-custom { border-radius: 50px; font-weight: bold; font-size: 0.8rem; padding: 8px 20px; transition: 0.2s; }
    .btn-print-custom:hover { transform: scale(1.05); }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Manajemen Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Rekapitulasi Honor & Dana Sosial</h2>
    </div>
</div>

<div class="card card-modern bg-gradient-primary text-white mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="text-white-50 text-uppercase small fw-bold mb-1">Total Surplus (SHU Sementara)</h5>
                <h2 class="mb-0 fw-bold"><?= formatRp($surplus) ?></h2>
                <small class="text-white-50">Periode: <?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?></small>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-wallet fa-4x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-4">
    <div class="card-body p-3">
        <form class="d-flex gap-2 align-items-center" method="GET">
            <span class="fw-bold small text-muted text-uppercase"><i class="fas fa-filter me-2"></i>Periode Hitung:</span>
            <input type="date" name="tgl_awal" class="form-control form-control-sm border-0 bg-light fw-bold" style="width:auto;" value="<?= $tgl_awal ?>">
            <span class="fw-bold text-muted">-</span>
            <input type="date" name="tgl_akhir" class="form-control form-control-sm border-0 bg-light fw-bold" style="width:auto;" value="<?= $tgl_akhir ?>">
            <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold shadow-sm">Hitung Ulang</button>
        </form>
    </div>
</div>

<?php if($surplus <= 0): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-3">
        <i class="fas fa-exclamation-triangle me-2"></i> 
        <strong>Tidak ada surplus!</strong> Pengeluaran melebihi atau sama dengan pemasukan, sehingga tidak ada honor yang bisa dibagikan.
    </div>
<?php else: ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-primary text-uppercase mb-1">Honor Staff / Petugas</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Alokasi 20%</span>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['staff']) ?></h3>
                <p class="text-muted small mb-4">Dana ini dialokasikan untuk petugas harian atau staff administrasi koperasi.</p>
                
                <a href="pages/kas/cetak_honor.php?tipe=staff&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                   target="_blank" class="btn btn-primary w-100 btn-print-custom shadow-sm">
                    <i class="fas fa-print me-2"></i> Cetak Slip Honor Staff
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-success text-uppercase mb-1">Honor Pengurus</h6>
                        <span class="badge bg-success bg-opacity-10 text-success">Alokasi 15%</span>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['pengurus']) ?></h3>
                <p class="text-muted small mb-4">Dana apresiasi bagi ketua, sekretaris, dan bendahara koperasi.</p>
                
                <a href="pages/kas/cetak_honor.php?tipe=pengurus&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                   target="_blank" class="btn btn-success w-100 btn-print-custom shadow-sm">
                    <i class="fas fa-print me-2"></i> Cetak Slip Pengurus
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-danger text-uppercase mb-1">Dana Sosial (Dansos)</h6>
                        <span class="badge bg-danger bg-opacity-10 text-danger">Alokasi 10%</span>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="fas fa-hand-holding-heart fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['dansos']) ?></h3>
                <p class="text-muted small mb-4">Untuk kegiatan sosial, sumbangan, atau bantuan bagi anggota yang sakit/musibah.</p>
                
                <a href="pages/kas/cetak_honor.php?tipe=dansos&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                   target="_blank" class="btn btn-danger w-100 btn-print-custom shadow-sm">
                    <i class="fas fa-print me-2"></i> Cetak Bukti Dansos
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-modern h-100 border-start border-5 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold text-warning text-uppercase mb-1">Honor Pembina</h6>
                        <span class="badge bg-warning bg-opacity-10 text-dark">Alokasi 5%</span>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= formatRp($alloc['pembina']) ?></h3>
                <p class="text-muted small mb-4">Dana kehormatan untuk Kepala Sekolah atau Pembina Koperasi.</p>
                
                <a href="pages/kas/cetak_honor.php?tipe=pembina&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" 
                   target="_blank" class="btn btn-warning text-white w-100 btn-print-custom shadow-sm">
                    <i class="fas fa-print me-2"></i> Cetak Slip Pembina
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>