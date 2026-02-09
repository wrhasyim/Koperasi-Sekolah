<?php
// --- 1. SETUP DATABASE CICILAN (ANTI-ERROR) ---
// Membuat tabel otomatis jika belum ada, agar halaman tidak crash
$pdo->exec("CREATE TABLE IF NOT EXISTS cicilan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anggota_id INT,
    kategori_barang VARCHAR(50), 
    nama_barang VARCHAR(100),
    total_tagihan DECIMAL(10,2),
    terbayar DECIMAL(10,2),
    sisa DECIMAL(10,2),
    status ENUM('lunas','belum') DEFAULT 'belum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- 2. QUERY TRANSAKSI MASUK (Uang yang sudah diterima) ---
$sql_masuk = "SELECT * FROM transaksi_kas 
              WHERE (tanggal BETWEEN ? AND ?) 
              AND kategori IN ('penjualan_seragam', 'penjualan_eskul') 
              ORDER BY tanggal ASC";
$trx_masuk = $pdo->prepare($sql_masuk);
$trx_masuk->execute([$tgl_awal, $tgl_akhir]);
$data_masuk = $trx_masuk->fetchAll();

// --- 3. QUERY PIUTANG (Yang belum dibayar) ---
$data_piutang = [];
try {
    $sql_piutang = "SELECT c.*, a.nama_lengkap 
                    FROM cicilan c 
                    JOIN anggota a ON c.anggota_id = a.id 
                    WHERE c.status = 'belum'";
    $data_piutang = $pdo->query($sql_piutang)->fetchAll();
} catch (Exception $e) {
    // Jika masih ada error lain, set array kosong
    $data_piutang = [];
}

// Hitung Total
$total_seragam_masuk = 0;
$total_eskul_masuk = 0;
foreach($data_masuk as $d){
    if($d['kategori'] == 'penjualan_seragam') $total_seragam_masuk += $d['jumlah'];
    else $total_eskul_masuk += $d['jumlah'];
}
$total_piutang = 0;
foreach($data_piutang as $p) $total_piutang += $p['sisa'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Distribusi</h6>
        <h2 class="h3 fw-bold mb-0">Keuangan Seragam & Eskul</h2>
    </div>
    <button onclick="window.print()" class="btn btn-dark shadow-sm rounded-pill"><i class="fas fa-print me-2"></i> Cetak Laporan</button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-3 border-0 shadow-sm">
            <h6 class="text-white-50 text-uppercase small fw-bold">Uang Seragam Masuk</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_seragam_masuk) ?></h3>
            <small>Periode Ini</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white p-3 border-0 shadow-sm">
            <h6 class="text-white-50 text-uppercase small fw-bold">Uang Eskul Masuk</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_eskul_masuk) ?></h3>
            <small>Periode Ini</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white p-3 border-0 shadow-sm">
            <h6 class="text-white-50 text-uppercase small fw-bold">Total Piutang Siswa</h6>
            <h3 class="mb-0 fw-bold"><?= formatRp($total_piutang) ?></h3>
            <small>Belum Tertagih (Semua Periode)</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-money-bill-wave me-2"></i> Rincian Uang Masuk</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada pemasukan periode ini.</td></tr>
                    <?php endif; 
                    foreach($data_masuk as $row): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td>
                            <?php if($row['kategori']=='penjualan_seragam'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary">SERAGAM</span>
                            <?php else: ?>
                                <span class="badge bg-info bg-opacity-10 text-info">ESKUL</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-end fw-bold text-success pe-4">+ <?= number_format($row['jumlah']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-clock me-2"></i> Daftar Piutang Aktif</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Siswa</th>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th class="text-end pe-4">Sisa Hutang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(empty($data_piutang)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data piutang.</td></tr>
                    <?php endif;
                    
                    foreach($data_piutang as $row): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td>
                            <?php if($row['kategori_barang']=='sekolah'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary">SERAGAM</span>
                            <?php else: ?>
                                <span class="badge bg-info bg-opacity-10 text-info">ESKUL</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-danger fw-bold pe-4"><?= formatRp($row['sisa']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>