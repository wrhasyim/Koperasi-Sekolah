<?php
// --- 1. SETUP & FILTER ---
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'seragam'; // Default Tab

// Konfigurasi Query Berdasarkan Tab
if($tab == 'seragam'){
    $kategori_kas_target = 'penjualan_seragam';
    $kategori_cicilan_target = 'seragam';
    $judul_halaman = 'Keuangan Seragam Sekolah';
    $warna_tema = 'primary';
    $btn_class = 'btn-primary';
} else {
    $kategori_kas_target = 'penjualan_eskul';
    $kategori_cicilan_target = 'eskul';
    $judul_halaman = 'Keuangan Atribut Eskul';
    $warna_tema = 'info';
    $btn_class = 'btn-info text-white';
}

// --- 2. QUERY UANG MASUK (CASHFLOW) ---
$sql_masuk = "SELECT * FROM transaksi_kas 
              WHERE (tanggal BETWEEN ? AND ?) 
              AND kategori = ? 
              ORDER BY tanggal ASC";
$trx_masuk = $pdo->prepare($sql_masuk);
$trx_masuk->execute([$tgl_awal, $tgl_akhir, $kategori_kas_target]);
$data_masuk = $trx_masuk->fetchAll();

$total_masuk = 0;
foreach($data_masuk as $d) $total_masuk += $d['jumlah'];

// --- 3. QUERY PIUTANG (HUTANG SISWA) ---
$total_piutang = 0;
$data_piutang = [];
try {
    $sql_piutang = "SELECT * FROM cicilan 
                    WHERE status = 'belum' 
                    AND kategori_barang = ? 
                    ORDER BY created_at DESC";
    $stmt_piutang = $pdo->prepare($sql_piutang);
    $stmt_piutang->execute([$kategori_cicilan_target]);
    $data_piutang = $stmt_piutang->fetchAll();

    foreach($data_piutang as $p){
        $total_piutang += $p['sisa'];
    }
} catch (Exception $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Distribusi</h6>
        <h2 class="h3 fw-bold mb-0"><?= $judul_halaman ?></h2>
    </div>
    <button onclick="window.print()" class="btn btn-dark shadow-sm rounded-pill"><i class="fas fa-print me-2"></i> Cetak</button>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
    <li class="nav-item">
        <a class="nav-link <?= $tab=='seragam' ? 'active fw-bold' : '' ?>" href="kas/laporan_distribusi?tab=seragam&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">
            <i class="fas fa-tshirt me-2"></i> Seragam Sekolah
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab=='eskul' ? 'active fw-bold bg-info text-white' : 'text-dark' ?>" href="kas/laporan_distribusi?tab=eskul&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>">
            <i class="fas fa-user-astronaut me-2"></i> Atribut Eskul
        </a>
    </li>
</ul>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card bg-<?= $warna_tema ?> text-white p-4 border-0 shadow-sm rounded-4 position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 opacity-25 m-3"><i class="fas fa-money-bill-wave fa-3x"></i></div>
            <h6 class="text-white-50 text-uppercase small fw-bold">Total Pemasukan (<?= ucfirst($tab) ?>)</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_masuk) ?></h3>
            <small>Periode: <?= tglIndo($tgl_awal) ?> s/d <?= tglIndo($tgl_akhir) ?></small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-danger text-white p-4 border-0 shadow-sm rounded-4 position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 opacity-25 m-3"><i class="fas fa-clock fa-3x"></i></div>
            <h6 class="text-white-50 text-uppercase small fw-bold">Total Piutang Tertahan</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_piutang) ?></h3>
            <small>Tagihan Belum Lunas (Akumulasi)</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form class="d-flex gap-2 align-items-center" method="GET">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <span class="fw-bold small text-muted">Periode:</span>
            <input type="date" name="tgl_awal" class="form-control border-0 shadow-sm" style="width:auto;" value="<?= $tgl_awal ?>">
            <span class="fw-bold small text-muted">-</span>
            <input type="date" name="tgl_akhir" class="form-control border-0 shadow-sm" style="width:auto;" value="<?= $tgl_akhir ?>">
            <button type="submit" class="btn <?= $btn_class ?> btn-sm shadow-sm px-3 fw-bold">Filter</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom border-<?= $warna_tema ?> border-3">
                <h6 class="mb-0 fw-bold text-<?= $warna_tema ?>"><i class="fas fa-list me-2"></i> Rincian Penerimaan Uang</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-end pe-4">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data_masuk)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">Belum ada pemasukan di periode ini.</td></tr>
                            <?php endif; 
                            foreach($data_masuk as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end fw-bold text-success pe-4">+ <?= number_format($row['jumlah']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom border-danger border-3">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-circle me-2"></i> Daftar Piutang Siswa</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-3">Siswa & Kelas</th>
                                <th>Item</th>
                                <th class="text-end pe-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data_piutang)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">Tidak ada piutang.</td></tr>
                            <?php endif; 
                            foreach($data_piutang as $row): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($row['kelas']) ?></span>
                                </td>
                                <td><span class="d-block text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['nama_barang']) ?></span></td>
                                <td class="text-end text-danger fw-bold pe-3"><?= formatRp($row['sisa']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>