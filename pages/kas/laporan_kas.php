<?php
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// --- FILTER SUPER KETAT: KELUARKAN SERAGAM & ESKUL ---
// Kita memfilter berdasarkan kolom 'kategori' di tabel transaksi_kas
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
        ORDER BY tanggal ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

// LOGIKA EXPORT EXCEL
if(isset($_GET['export_excel'])){
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Arus_Kas_Koperasi_Murni.xls");
    echo "TANGGAL\tKATEGORI\tKETERANGAN\tMASUK\tKELUAR\tSALDO\n";
    
    $saldo = 0;
    foreach($transaksi as $row){
        $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
        $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
        $saldo += ($masuk - $keluar);
        
        // Bersihkan nama kategori agar enak dibaca di Excel
        $kat_clean = strtoupper(str_replace('_', ' ', $row['kategori']));
        
        echo $row['tanggal'] . "\t" . $kat_clean . "\t" . $row['keterangan'] . "\t" . $masuk . "\t" . $keluar . "\t" . $saldo . "\n";
    }
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan Utama</h6>
        <h2 class="h3 fw-bold mb-0">Laporan Arus Kas Koperasi</h2>
        <small class="text-danger fw-bold"><i class="fas fa-check-circle me-1"></i> Data Murni (Tanpa Uang Seragam/Eskul)</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text border-0 bg-white">Dari</span>
                <input type="date" name="tgl_awal" class="form-control border-0 shadow-sm" value="<?= $tgl_awal ?>">
            </div>
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text border-0 bg-white">S/d</span>
                <input type="date" name="tgl_akhir" class="form-control border-0 shadow-sm" value="<?= $tgl_akhir ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary shadow-sm px-3">Tampilkan</button>
            
            <div class="ms-auto">
                <a href="kas/laporan_kas?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&export_excel=true" class="btn btn-sm btn-success shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </a>
                <button type="button" class="btn btn-sm btn-dark shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end pe-4">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $saldo = 0; $total_masuk = 0; $total_keluar = 0;
                    if(empty($transaksi)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada transaksi koperasi pada periode ini.</td></tr>
                    <?php endif;

                    foreach($transaksi as $row): 
                        if($row['arus'] == 'masuk'){
                            $masuk = $row['jumlah']; $keluar = 0;
                            $saldo += $masuk; $total_masuk += $masuk;
                        } else {
                            $masuk = 0; $keluar = $row['jumlah'];
                            $saldo -= $keluar; $total_keluar += $keluar;
                        }
                    ?>
                    <tr>
                        <td class="ps-4 text-muted fw-bold"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill fw-normal">
                                <?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-end text-success fw-bold"><?= $masuk!=0 ? formatRp($masuk) : '-' ?></td>
                        <td class="text-end text-danger"><?= $keluar!=0 ? formatRp($keluar) : '-' ?></td>
                        <td class="text-end pe-4 fw-bold text-dark bg-light"><?= formatRp($saldo) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light fw-bold border-top">
                    <tr>
                        <td colspan="3" class="text-center text-uppercase small ls-1 text-muted py-3">Total Arus Kas Koperasi</td>
                        <td class="text-end text-success py-3 fs-6"><?= formatRp($total_masuk) ?></td>
                        <td class="text-end text-danger py-3"><?= formatRp($total_keluar) ?></td>
                        <td class="text-end pe-4 py-3 bg-white border-start"><?= formatRp($saldo) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>