<?php
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- FILTER SUPER KETAT: KELUARKAN SERAGAM & ESKUL ---
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
        ORDER BY tanggal ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

// --- HITUNG RINGKASAN UNTUK KARTU ATAS ---
$total_masuk_periode = 0;
$total_keluar_periode = 0;
$saldo_akhir_periode = 0;

foreach($transaksi as $t){
    if($t['arus'] == 'masuk') $total_masuk_periode += $t['jumlah'];
    else $total_keluar_periode += $t['jumlah'];
}
$saldo_akhir_periode = $total_masuk_periode - $total_keluar_periode;


?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Arus Kas Koperasi</h2>
    </div>
    
    <div class="d-none d-md-block">
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border small fw-bold text-muted">
            <i class="far fa-calendar-alt me-2 text-primary"></i> <?= tglIndo(date('Y-m-d')) ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-white bg-opacity-25 rounded-pill fw-normal">Total Masuk</span>
                    <i class="fas fa-arrow-down opacity-50"></i>
                </div>
                <h3 class="mb-0 fw-bold"><?= formatRp($total_masuk_periode) ?></h3>
                <small class="text-white-50">Periode Terpilih</small>
            </div>
            <i class="fas fa-chart-line fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25" style="transform: rotate(-15deg);"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-danger text-white border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-white bg-opacity-25 rounded-pill fw-normal">Total Keluar</span>
                    <i class="fas fa-arrow-up opacity-50"></i>
                </div>
                <h3 class="mb-0 fw-bold"><?= formatRp($total_keluar_periode) ?></h3>
                <small class="text-white-50">Periode Terpilih</small>
            </div>
            <i class="fas fa-shopping-cart fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25" style="transform: rotate(-15deg);"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-primary text-white border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-white bg-opacity-25 rounded-pill fw-normal">Surplus / Defisit</span>
                    <i class="fas fa-wallet opacity-50"></i>
                </div>
                <h3 class="mb-0 fw-bold"><?= formatRp($saldo_akhir_periode) ?></h3>
                <small class="text-white-50">Selisih Arus Kas</small>
            </div>
            <i class="fas fa-coins fa-4x position-absolute bottom-0 end-0 mb-n1 me-3 opacity-25" style="transform: rotate(-15deg);"></i>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-4">
    <div class="card-body p-3">
        <form class="row g-2 align-items-center" method="GET">
            <div class="col-auto d-flex align-items-center">
                <span class="fw-bold text-muted small me-2 text-uppercase ls-1"><i class="fas fa-filter me-1"></i> Filter:</span>
            </div>
            <div class="col-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-calendar"></i></span>
                    <input type="date" name="tgl_awal" class="form-control border-start-0 shadow-none ps-2" value="<?= $tgl_awal ?>">
                </div>
            </div>
            <div class="col-auto text-muted small">s/d</div>
            <div class="col-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-calendar"></i></span>
                    <input type="date" name="tgl_akhir" class="form-control border-start-0 shadow-none ps-2" value="<?= $tgl_akhir ?>">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark px-3 rounded-pill fw-bold shadow-sm">Terapkan</button>
            </div>
            
            <div class="col-auto ms-auto d-flex gap-2">
    <a href="process/export_laporan_kas.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" target="_blank">
        <i class="fas fa-file-excel me-2"></i> Excel
    </a>
    
    <a href="pages/kas/cetak_laporan_kas.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-secondary rounded-pill px-3 shadow-sm" target="_blank">
        <i class="fas fa-print me-2"></i> Print
    </a>
</div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-list-alt me-2 text-primary"></i> Rincian Transaksi</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
            <thead class="bg-light text-secondary">
                <tr>
                    <th class="ps-4 py-3 text-uppercase small fw-bold ls-1" width="15%">Tanggal</th>
                    <th class="py-3 text-uppercase small fw-bold ls-1" width="20%">Kategori</th>
                    <th class="py-3 text-uppercase small fw-bold ls-1">Keterangan</th>
                    <th class="text-end py-3 text-uppercase small fw-bold ls-1" width="15%">Masuk</th>
                    <th class="text-end py-3 text-uppercase small fw-bold ls-1" width="15%">Keluar</th>
                    <th class="text-end pe-4 py-3 text-uppercase small fw-bold ls-1" width="15%">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $saldo = 0; 
                if(empty($transaksi)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>Tidak ada data transaksi pada periode ini.</td></tr>
                <?php endif;

                foreach($transaksi as $row): 
                    if($row['arus'] == 'masuk'){
                        $masuk = $row['jumlah']; $keluar = 0;
                        $saldo += $masuk; 
                        $text_class = "text-success";
                        $icon_trx = "<i class='fas fa-arrow-down text-success me-2 bg-success bg-opacity-10 p-2 rounded-circle' style='font-size:0.8rem;'></i>";
                    } else {
                        $masuk = 0; $keluar = $row['jumlah'];
                        $saldo -= $keluar; 
                        $text_class = "text-danger";
                        $icon_trx = "<i class='fas fa-arrow-up text-danger me-2 bg-danger bg-opacity-10 p-2 rounded-circle' style='font-size:0.8rem;'></i>";
                    }
                    
                    // Style badge kategori
                    $kat_clean = strtoupper(str_replace('_', ' ', $row['kategori']));
                    $badge_color = ($row['arus'] == 'masuk') ? 'primary' : 'warning text-dark';
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-secondary" style="font-family: monospace; font-size: 0.9rem;">
                        <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $badge_color ?> bg-opacity-10 text-<?= ($row['arus']=='masuk'?'primary':'dark') ?> border border-<?= $badge_color ?> border-opacity-25 rounded-pill px-3">
                            <?= $kat_clean ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?= $icon_trx ?>
                            <span class="text-dark"><?= htmlspecialchars($row['keterangan']) ?></span>
                        </div>
                    </td>
                    <td class="text-end fw-bold text-success bg-success bg-opacity-10 bg-gradient" style="--bs-bg-opacity: .05;">
                        <?= $masuk!=0 ? formatRp($masuk) : '-' ?>
                    </td>
                    <td class="text-end fw-bold text-danger bg-danger bg-opacity-10 bg-gradient" style="--bs-bg-opacity: .05;">
                        <?= $keluar!=0 ? formatRp($keluar) : '-' ?>
                    </td>
                    <td class="text-end pe-4 fw-bold text-dark bg-light border-start">
                        <?= formatRp($saldo) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card-footer bg-white p-4 border-top">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small">
                <i class="fas fa-info-circle me-1"></i> Data ini menampilkan arus kas murni operasional koperasi. Transaksi seragam & eskul dipisahkan di Laporan Distribusi.
            </div>
            <div class="col-md-6 text-end">
                <span class="text-uppercase small fw-bold text-muted me-3">Saldo Akhir Tercatat:</span>
                <span class="h4 fw-bold text-primary mb-0"><?= formatRp($saldo) ?></span>
            </div>
        </div>
    </div>
</div>