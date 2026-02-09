<?php
// --- SETUP LOGIKA ---
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all'; // Mode: 'penjualan' atau 'all'

// --- FILTER KATEGORI ---
$filter_sql = "";
$judul_halaman = "Laporan Arus Kas";
if($mode == 'penjualan'){
    $judul_halaman = "Laporan Penjualan & Titipan"; // JUDUL DIPERJELAS
    // Hanya ambil Penjualan Harian & QRIS
    $filter_sql = "AND kategori IN ('penjualan_harian', 'qris_masuk')";
}

// --- QUERY DATA ---
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        $filter_sql 
        ORDER BY tanggal ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

// --- LOGIKA CEK TUTUP BUKU ---
$bulan_lalu = date('m', strtotime("-1 month"));
$tahun_lalu = date('Y', strtotime("-1 month"));
$nama_bulan_lalu = date('F Y', strtotime("-1 month")); 

$cek_tb = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
$cek_tb->execute([$bulan_lalu, $tahun_lalu]);
$is_closed = $cek_tb->rowCount() > 0;
$is_admin_pengurus = ($_SESSION['user']['role'] == 'admin' || $_SESSION['user']['role'] == 'pengurus');

// --- EXPORT EXCEL ---
if(isset($_GET['export_excel'])){
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_$mode" . "_$tgl_awal.xls");
    echo "TANGGAL\tKATEGORI\tKETERANGAN\tMASUK\tKELUAR\tSALDO\n";
    $saldo = 0;
    foreach($transaksi as $row){
        $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
        $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
        $saldo += ($masuk - $keluar);
        echo $row['tanggal'] . "\t" . $row['kategori'] . "\t" . $row['keterangan'] . "\t" . $masuk . "\t" . $keluar . "\t" . $saldo . "\n";
    }
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0"><?= $judul_halaman ?></h2>
    </div>
</div>

<?php if($mode == 'all' && !$is_closed && $is_admin_pengurus): ?>
<div class="card border-0 shadow-sm mb-4 bg-gradient-warning text-white">
    <div class="card-body p-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-2"></i> Tutup Buku Tersedia</h5>
            <p class="mb-0 opacity-75">Laporan periode <strong><?= $nama_bulan_lalu ?></strong> belum dikunci.</p>
        </div>
        <form action="process/tutup_buku_aksi.php" method="POST">
            <input type="hidden" name="bulan" value="<?= $bulan_lalu ?>">
            <input type="hidden" name="tahun" value="<?= $tahun_lalu ?>">
            <button type="submit" name="auto_tutup_buku" class="btn btn-light text-warning fw-bold shadow-sm px-4" onclick="return confirm('Yakin kunci data periode lalu?')">
                <i class="fas fa-lock me-2"></i> Kunci Sekarang
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form class="d-flex gap-2 align-items-center flex-wrap" method="GET" action="kas/laporan_kas">
            <?php if($mode == 'penjualan'): ?>
                <input type="hidden" name="mode" value="penjualan">
            <?php endif; ?>
            
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text border-0 bg-white text-muted">Dari</span>
                <input type="date" name="tgl_awal" class="form-control border-0 shadow-sm" value="<?= $tgl_awal ?>">
            </div>
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text border-0 bg-white text-muted">S/d</span>
                <input type="date" name="tgl_akhir" class="form-control border-0 shadow-sm" value="<?= $tgl_akhir ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary shadow-sm px-3">
                <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <div class="ms-auto">
                <a href="kas/laporan_kas?mode=<?= $mode ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&export_excel=true" class="btn btn-sm btn-success shadow-sm me-1">
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
                        <?php if($mode == 'all'): ?>
                        <th class="text-end">Keluar</th>
                        <th class="text-end pe-4">Saldo</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $saldo = 0; $total_masuk = 0; $total_keluar = 0;
                    if(empty($transaksi)): ?>
                        <tr><td colspan="<?= $mode=='all'?6:4 ?>" class="text-center py-5 text-muted">Tidak ada data.</td></tr>
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
                            <?php if($row['kategori'] == 'penjualan_harian'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">PENJUALAN / TITIPAN</span>
                            <?php elseif($row['kategori'] == 'qris_masuk'): ?>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">QRIS / TRANSFER</span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border"><?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-end text-success fw-bold"><?= $masuk!=0 ? formatRp($masuk) : '-' ?></td>
                        
                        <?php if($mode == 'all'): ?>
                        <td class="text-end text-danger"><?= $keluar!=0 ? formatRp($keluar) : '-' ?></td>
                        <td class="text-end pe-4 fw-bold text-dark bg-light"><?= formatRp($saldo) ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light fw-bold border-top">
                    <tr>
                        <td colspan="3" class="text-center text-uppercase small ls-1 text-muted py-3">
                            Total Periode (<?= tglIndo($tgl_awal) ?> s/d <?= tglIndo($tgl_akhir) ?>)
                        </td>
                        <td class="text-end text-success py-3 fs-6"><?= formatRp($total_masuk) ?></td>
                        <?php if($mode == 'all'): ?>
                        <td class="text-end text-danger py-3"><?= formatRp($total_keluar) ?></td>
                        <td class="text-end pe-4 py-3 bg-white border-start"><?= formatRp($saldo) ?></td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>