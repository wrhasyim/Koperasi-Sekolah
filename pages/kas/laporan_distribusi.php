<?php
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- 1. QUERY UANG MASUK (Dari Tabel Kas) ---
$sql_masuk = "SELECT * FROM transaksi_kas 
              WHERE (tanggal BETWEEN ? AND ?) 
              AND kategori IN ('penjualan_seragam', 'penjualan_eskul') 
              ORDER BY tanggal ASC";
$trx_masuk = $pdo->prepare($sql_masuk);
$trx_masuk->execute([$tgl_awal, $tgl_akhir]);
$data_masuk = $trx_masuk->fetchAll();

// Hitung Total Masuk per Kategori
$total_seragam_masuk = 0;
$total_eskul_masuk = 0;
foreach($data_masuk as $d){
    if($d['kategori'] == 'penjualan_seragam') $total_seragam_masuk += $d['jumlah'];
    else $total_eskul_masuk += $d['jumlah'];
}

// --- 2. QUERY PIUTANG (Dari Tabel Cicilan) ---
$total_piutang = 0;
$data_piutang = [];
try {
    // Ambil data yang statusnya 'belum' (Hutang)
    $sql_piutang = "SELECT * FROM cicilan WHERE status = 'belum' ORDER BY created_at DESC";
    $data_piutang = $pdo->query($sql_piutang)->fetchAll();

    foreach($data_piutang as $p){
        $total_piutang += $p['sisa'];
    }
} catch (Exception $e) {
    // Cegah error jika tabel belum ada
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Distribusi</h6>
        <h2 class="h3 fw-bold mb-0">Keuangan Seragam & Eskul</h2>
        <small class="text-muted">Laporan Penerimaan & Piutang Siswa</small>
    </div>
    <button onclick="window.print()" class="btn btn-dark shadow-sm rounded-pill"><i class="fas fa-print me-2"></i> Cetak Laporan</button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-3 border-0 shadow-sm rounded-3">
            <h6 class="text-white-50 text-uppercase small fw-bold">Uang Seragam Masuk</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_seragam_masuk) ?></h3>
            <small>Periode Terpilih</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white p-3 border-0 shadow-sm rounded-3">
            <h6 class="text-white-50 text-uppercase small fw-bold">Uang Eskul Masuk</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_eskul_masuk) ?></h3>
            <small>Periode Terpilih</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white p-3 border-0 shadow-sm rounded-3">
            <h6 class="text-white-50 text-uppercase small fw-bold">Total Piutang Siswa</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_piutang) ?></h3>
            <small>Belum Tertagih (Semua Periode)</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form class="d-flex gap-2 align-items-center" method="GET">
            <span class="fw-bold small text-muted">Periode Transaksi:</span>
            <input type="date" name="tgl_awal" class="form-control border-0 shadow-sm" style="width:auto;" value="<?= $tgl_awal ?>">
            <span class="fw-bold small text-muted">-</span>
            <input type="date" name="tgl_akhir" class="form-control border-0 shadow-sm" style="width:auto;" value="<?= $tgl_akhir ?>">
            <button type="submit" class="btn btn-sm btn-primary shadow-sm px-3">Filter</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-success"><i class="fas fa-money-bill-wave me-2"></i> Rincian Uang Masuk</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-end pe-4">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data_masuk)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada pemasukan pada periode ini.</td></tr>
                            <?php endif; 
                            foreach($data_masuk as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                <td>
                                    <?php if($row['kategori']=='penjualan_seragam'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">SERAGAM</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info">ESKUL</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                        <?= htmlspecialchars($row['keterangan']) ?>
                                    </span>
                                </td>
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
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-clock me-2"></i> Daftar Piutang Aktif</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-3">Siswa & Kelas</th>
                                <th>Item</th>
                                <th class="text-end pe-3">Sisa Hutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data_piutang)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada data piutang.</td></tr>
                            <?php endif; 
                            foreach($data_piutang as $row): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                                    <span class="badge bg-light text-secondary border rounded-1"><?= htmlspecialchars($row['kelas']) ?></span>
                                </td>
                                <td>
                                    <span class="d-block text-truncate" style="max-width: 150px;">
                                        <?= htmlspecialchars($row['nama_barang']) ?>
                                    </span>
                                    <?php if($row['kategori_barang']=='seragam'): ?>
                                        <span class="text-primary" style="font-size: 0.7rem;">SERAGAM</span>
                                    <?php else: ?>
                                        <span class="text-info" style="font-size: 0.7rem;">ESKUL</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-danger fw-bold pe-3"><?= formatRp($row['sisa']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="kas/manajemen_cicilan" class="text-decoration-none small fw-bold">Kelola Pembayaran &rarr;</a>
            </div>
        </div>
    </div>
</div>