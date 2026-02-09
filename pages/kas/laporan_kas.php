<?php
// FILTER BULAN & TAHUN
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Query Data Kas
$sql = "SELECT * FROM transaksi_kas 
        WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? 
        ORDER BY tanggal ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bulan, $tahun]);
$transaksi = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan Arus Kas</h1>
    
    <form class="d-flex gap-2" method="GET" action="index.php">
        <input type="hidden" name="page" value="laporan_kas">
        <select name="bulan" class="form-select form-select-sm">
            <?php 
            $bln_arr = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
            foreach($bln_arr as $k => $v){
                $sel = ($k == $bulan) ? 'selected' : '';
                echo "<option value='$k' $sel>$v</option>";
            }
            ?>
        </select>
        <select name="tahun" class="form-select form-select-sm">
            <?php for($i=2024; $i<=date('Y')+1; $i++){
                $sel = ($i == $tahun) ? 'selected' : '';
                echo "<option value='$i' $sel>$i</option>";
            } ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <button type="button" class="btn btn-sm btn-success" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Tgl</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $saldo = 0;
                    $total_masuk = 0;
                    $total_keluar = 0;

                    foreach($transaksi as $row): 
                        // Hitung Saldo Berjalan
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
                        <td><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-end text-success"><?= $masuk!=0 ? number_format($masuk) : '-' ?></td>
                        <td class="text-end text-danger"><?= $keluar!=0 ? number_format($keluar) : '-' ?></td>
                        <td class="text-end fw-bold bg-light"><?= number_format($saldo) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="3" class="text-center">TOTAL BULAN INI</td>
                        <td class="text-end text-success"><?= number_format($total_masuk) ?></td>
                        <td class="text-end text-danger"><?= number_format($total_keluar) ?></td>
                        <td class="text-end"><?= formatRp($saldo) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if(empty($transaksi)): ?>
            <div class="alert alert-warning text-center mt-3">Tidak ada data transaksi di bulan ini.</div>
        <?php endif; ?>
    </div>
</div>