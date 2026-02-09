<?php
// DEFAULT TANGGAL
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// QUERY DENGAN RENTANG TANGGAL
$sql = "SELECT * FROM transaksi_kas 
        WHERE tanggal BETWEEN ? AND ? 
        ORDER BY tanggal ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

// DOWNLOAD EXCEL (Clean URL Logic)
if(isset($_GET['export_excel'])){
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Kas_$tgl_awal-sd-$tgl_akhir.xls");
    
    echo "TANGGAL\tKATEGORI\tKETERANGAN\tMASUK\tKELUAR\tSALDO\n";
    
    $saldo = 0;
    foreach($transaksi as $row){
        $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
        $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
        $saldo += ($masuk - $keluar);
        
        echo $row['tanggal'] . "\t" . 
             $row['kategori'] . "\t" . 
             $row['keterangan'] . "\t" . 
             $masuk . "\t" . 
             $keluar . "\t" . 
             $saldo . "\n";
    }
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan Arus Kas</h1>
    
    <form class="d-flex gap-2 align-items-center" method="GET" action="kas/laporan_kas">
        <div class="input-group input-group-sm">
            <span class="input-group-text">Dari</span>
            <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
        </div>
        
        <div class="input-group input-group-sm">
            <span class="input-group-text">S/d</span>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
        </div>

        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter"></i> Tampilkan
        </button>
        
        <a href="kas/laporan_kas?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&export_excel=true" class="btn btn-sm btn-success">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        
        <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </form>
</div>

<div class="alert alert-info py-2 mb-3">
    Menampilkan data periode: <strong><?= tglIndo($tgl_awal) ?></strong> s/d <strong><?= tglIndo($tgl_akhir) ?></strong>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0 text-sm">
                <thead class="table-dark">
                    <tr>
                        <th width="10%">Tanggal</th>
                        <th width="15%">Kategori</th>
                        <th>Keterangan</th>
                        <th width="12%" class="text-end">Masuk</th>
                        <th width="12%" class="text-end">Keluar</th>
                        <th width="12%" class="text-end bg-secondary text-white">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $saldo = 0;
                    $total_masuk = 0;
                    $total_keluar = 0;

                    foreach($transaksi as $row): 
                        if($row['arus'] == 'masuk'){
                            $masuk = $row['jumlah'];
                            $keluar = 0;
                            $saldo += $masuk;
                            $total_masuk += $masuk;
                        } else {
                            $masuk = 0;
                            $keluar = $row['jumlah'];
                            $saldo -= $keluar;
                            $total_keluar += $keluar;
                        }
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></span></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-end text-success"><?= $masuk!=0 ? number_format($masuk) : '-' ?></td>
                        <td class="text-end text-danger"><?= $keluar!=0 ? number_format($keluar) : '-' ?></td>
                        <td class="text-end fw-bold bg-light"><?= number_format($saldo) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold border-top-2">
                    <tr>
                        <td colspan="3" class="text-center">TOTAL PERIODE INI</td>
                        <td class="text-end text-success"><?= formatRp($total_masuk) ?></td>
                        <td class="text-end text-danger"><?= formatRp($total_keluar) ?></td>
                        <td class="text-end bg-dark text-white"><?= formatRp($saldo) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if(empty($transaksi)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-search fa-3x mb-3"></i><br>
                Tidak ada data transaksi pada rentang tanggal ini.
            </div>
        <?php endif; ?>
    </div>
</div>