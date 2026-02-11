<?php
// pages/kas/laporan_kas.php
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- FILTER: HANYA KAS MURNI (Keluarkan Transaksi Seragam & Eskul dari Laporan ini) ---
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
        ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

$total_masuk_periode = 0;
$total_keluar_periode = 0;

foreach($transaksi as $t){
    if($t['arus'] == 'masuk') $total_masuk_periode += $t['jumlah'];
    else $total_keluar_periode += $t['jumlah'];
}
$saldo_akhir_periode = $total_masuk_periode - $total_keluar_periode;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">Laporan Arus Kas Operasional</h2>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
            <div class="card-body p-4">
                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Total Pemasukan</h6>
                <h3 class="mb-0 fw-bold"><?= formatRp($total_masuk_periode) ?></h3>
            </div>
            <i class="fas fa-arrow-down fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
            <div class="card-body p-4">
                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Total Pengeluaran</h6>
                <h3 class="mb-0 fw-bold"><?= formatRp($total_keluar_periode) ?></h3>
            </div>
            <i class="fas fa-arrow-up fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
            <div class="card-body p-4">
                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Surplus / Defisit</h6>
                <h3 class="mb-0 fw-bold"><?= formatRp($saldo_akhir_periode) ?></h3>
            </div>
            <i class="fas fa-wallet fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25"></i>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-4">
    <div class="card-body p-3">
        <form class="row g-2 align-items-center" method="GET">
            <input type="hidden" name="page" value="kas/laporan_kas">
            <div class="col-auto"><span class="fw-bold text-muted small me-2">FILTER:</span></div>
            <div class="col-auto"><input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>"></div>
            <div class="col-auto text-muted small">s/d</div>
            <div class="col-auto"><input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-dark px-3 rounded-pill fw-bold">Terapkan</button></div>
            <div class="col-auto ms-auto">
                <a href="process/export_laporan_kas.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-success rounded-pill px-3" target="_blank"><i class="fas fa-file-excel me-1"></i> Excel</a>
                <a href="pages/kas/cetak_laporan_kas.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-secondary rounded-pill px-3" target="_blank"><i class="fas fa-print me-1"></i> Print</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-light sticky-top">
                <tr><th class="ps-4 py-3">Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end py-3">Masuk</th><th class="text-end pe-4 py-3">Keluar</th></tr>
            </thead>
            <tbody>
                <?php foreach($transaksi as $row): 
                    $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
                    $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-secondary"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><span class="badge bg-light text-dark border px-2"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></span></td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="text-end text-success fw-bold"><?= $masuk > 0 ? formatRp($masuk) : '-' ?></td>
                    <td class="text-end text-danger fw-bold pe-4"><?= $keluar > 0 ? formatRp($keluar) : '-' ?></td>
                </tr>
                <?php endforeach; if(empty($transaksi)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>