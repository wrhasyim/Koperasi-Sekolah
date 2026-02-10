<?php
// --- STYLE CSS MODERN ---
?>
<style>
    .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.3s; background: #fff; }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
    .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 16px; }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e 0%, #dda20a 100%); }
    .table-modern th { text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #8898aa; border-bottom: 2px solid #e3e6f0; padding: 15px; }
    .table-modern td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f0f0f0; }
</style>

<?php
// --- LOGIKA PHP ---

// 1. SETUP TANGGAL
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// 2. QUERY RIWAYAT SETORAN (Dari Transaksi Kas)
$sql = "SELECT * FROM transaksi_kas 
        WHERE kategori = 'pembayaran_titipan' 
        AND (tanggal BETWEEN ? AND ?)
        ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$riwayat = $stmt->fetchAll();

// 3. HITUNG RINGKASAN
$total_disetor = 0;
$total_laba = 0;
$total_transaksi = count($riwayat);

foreach($riwayat as $row){
    $total_disetor += $row['jumlah'];
    
    // EKSTRAK DATA LABA DARI KETERANGAN (Regex)
    // Mencari text "[Laba: 5000]" di dalam keterangan
    if (preg_match('/\[Laba:\s*(\d+)\]/', $row['keterangan'], $matches)) {
        $laba_item = (int)$matches[1];
        $total_laba += $laba_item;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Inventory</h6>
        <h2 class="h3 fw-bold mb-0">Riwayat Pembayaran & Laba Titipan</h2>
    </div>
    <button onclick="window.print()" class="btn btn-dark shadow-sm rounded-pill px-4">
        <i class="fas fa-print me-2"></i> Cetak
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-modern bg-gradient-success text-white p-4 border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Uang Disetor (Modal)</span>
                    <h2 class="mb-0 fw-bold display-6 mt-2"><?= formatRp($total_disetor) ?></h2>
                    <small class="text-white-50">Kewajiban Lunas</small>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="fas fa-hand-holding-usd fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card card-modern bg-gradient-primary text-white p-4 border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Keuntungan Koperasi</span>
                    <h2 class="mb-0 fw-bold display-6 mt-2"><?= formatRp($total_laba) ?></h2>
                    <small class="text-white-50">Profit dari Titipan</small>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="fw-bold text-muted small text-uppercase"><i class="fas fa-calendar-alt me-2"></i>Periode:</label>
            </div>
            <div class="col-auto"><input type="date" name="tgl_awal" class="form-control border-0 shadow-sm" value="<?= $tgl_awal ?>"></div>
            <div class="col-auto text-muted fw-bold">-</div>
            <div class="col-auto"><input type="date" name="tgl_akhir" class="form-control border-0 shadow-sm" value="<?= $tgl_akhir ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary shadow-sm fw-bold px-4 rounded-pill">Tampilkan</button></div>
        </form>
    </div>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Tanggal Bayar</th>
                    <th>Detail Transaksi</th>
                    <th class="text-end text-success">Laba Koperasi</th>
                    <th class="text-end pe-4 text-danger">Nominal Disetor</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($riwayat)): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada riwayat pembayaran pada periode ini.</td></tr>
                <?php endif; 

                foreach($riwayat as $row): 
                    // Ambil laba per baris
                    $laba_item = 0;
                    $desc = $row['keterangan'];
                    
                    // Bersihkan teks [Laba: xxx] dari tampilan user biasa agar rapi
                    $display_desc = preg_replace('/\[Laba:\s*\d+\]/', '', $desc);
                    
                    if (preg_match('/\[Laba:\s*(\d+)\]/', $desc, $matches)) {
                        $laba_item = (int)$matches[1];
                    }
                ?>
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold text-dark d-block"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></span>
                        <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                    </td>
                    <td>
                        <span class="d-block text-dark"><?= htmlspecialchars($display_desc) ?></span>
                        <span class="badge bg-light text-secondary border border-opacity-25 mt-1">Lunas</span>
                    </td>
                    <td class="text-end text-success fw-bold">
                        <?php if($laba_item > 0): ?>
                            + <?= formatRp($laba_item) ?>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <span class="fw-bold text-danger">- <?= formatRp($row['jumlah']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light fw-bold border-top">
                <tr>
                    <td colspan="2" class="text-end text-uppercase small ls-1 text-muted py-3">TOTAL</td>
                    <td class="text-end text-success py-3"><?= formatRp($total_laba) ?></td>
                    <td class="text-end text-danger py-3 pe-4"><?= formatRp($total_disetor) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>