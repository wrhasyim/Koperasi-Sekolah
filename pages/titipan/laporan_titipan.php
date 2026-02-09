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
    .text-amount { font-family: 'Consolas', monospace; font-weight: 600; letter-spacing: -0.5px; }
</style>

<?php
// --- LOGIKA PHP ---

// 1. FILTER GURU
$guru_id = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';
$where_sql = "WHERE a.role NOT IN ('admin', 'staff')";
if($guru_id){
    $where_sql .= " AND t.anggota_id = '$guru_id'";
}

// 2. QUERY DATA TITIPAN
$sql = "SELECT t.*, a.nama_lengkap, a.role 
        FROM titipan t 
        JOIN anggota a ON t.anggota_id = a.id 
        $where_sql 
        ORDER BY a.nama_lengkap ASC, t.nama_barang ASC";
$data = $pdo->query($sql)->fetchAll();

// 3. QUERY DROPDOWN GURU
$list_guru = $pdo->query("SELECT * FROM anggota WHERE role NOT IN ('admin', 'staff') AND status_aktif = 1 ORDER BY nama_lengkap ASC")->fetchAll();

// 4. HITUNG RINGKASAN
$total_barang = 0;
$total_terjual = 0;
$total_omzet = 0;
$total_hak_guru = 0;
$total_laba = 0;

foreach($data as $row){
    $terjual = $row['stok_terjual'];
    $total_barang += $row['stok_awal'];
    $total_terjual += $terjual;
    
    $omzet = $terjual * $row['harga_jual'];
    $hak   = $terjual * $row['harga_modal'];
    $laba  = $omzet - $hak;
    
    $total_omzet += $omzet;
    $total_hak_guru += $hak;
    $total_laba += $laba;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Inventory</h6>
        <h2 class="h3 fw-bold mb-0">Rekapitulasi Titipan Guru</h2>
    </div>
    <a href="pages/titipan/cetak_laporan_titipan.php?guru_id=<?= $guru_id ?>" target="_blank" class="btn btn-dark shadow-sm rounded-pill px-4">
        <i class="fas fa-print me-2"></i> Cetak Laporan
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-modern bg-gradient-warning text-white p-3 border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Hak Guru</span>
                    <h2 class="mb-0 fw-bold"><?= formatRp($total_hak_guru) ?></h2>
                    <small class="text-white-50">Wajib disetor ke pemilik</small>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-modern bg-gradient-success text-white p-3 border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Laba Koperasi</span>
                    <h2 class="mb-0 fw-bold"><?= formatRp($total_laba) ?></h2>
                    <small class="text-white-50">Profit Bersih</small>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-modern bg-white p-3 border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Performa Penjualan</span>
                    <h2 class="mb-0 fw-bold text-dark"><?= $total_terjual ?> <span class="fs-6 text-muted fw-normal">/ <?= $total_barang ?> Unit</span></h2>
                    <small class="text-primary fw-bold">
                        <?= $total_barang > 0 ? round(($total_terjual/$total_barang)*100, 1) : 0 ?>% Terjual
                    </small>
                </div>
                <div class="bg-light rounded-circle p-3 text-primary">
                    <i class="fas fa-boxes fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form method="GET" action="titipan/laporan_titipan" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="fw-bold text-muted small text-uppercase"><i class="fas fa-filter me-2"></i>Filter Pemilik:</label>
            </div>
            <div class="col-auto flex-grow-1">
                <select name="guru_id" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                    <option value="">-- Tampilkan Semua Guru --</option>
                    <?php foreach($list_guru as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $guru_id == $g['id'] ? 'selected' : '' ?>>
                        <?= $g['nama_lengkap'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Pemilik & Barang</th>
                    <th class="text-center">Stok Awal</th>
                    <th class="text-center bg-warning bg-opacity-10 text-dark">Sisa Fisik</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-end">Harga Modal</th>
                    <th class="text-end pe-4">Wajib Setor</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
                
                if(empty($data)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada data.</td></tr>
                <?php endif;

                foreach($data as $row): 
                    $sisa = $row['stok_awal'] - $row['stok_terjual'];
                    $hak_item = $row['stok_terjual'] * $row['harga_modal'];
                    
                    $bg_color = $colors[$row['anggota_id'] % count($colors)];
                    $initial = strtoupper(substr($row['nama_lengkap'], 0, 1));
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3 shadow-sm" style="background-color: <?= $bg_color ?>; width: 35px; height: 35px; font-size: 14px;">
                                <?= $initial ?>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_barang']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($row['nama_lengkap']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center text-muted"><?= $row['stok_awal'] ?></td>
                    
                    <td class="text-center fw-bold fs-6 bg-warning bg-opacity-10 text-dark border-start border-end">
                        <?= $sisa ?>
                    </td>

                    <td class="text-center text-success fw-bold"><?= $row['stok_terjual'] ?></td>
                    <td class="text-end text-muted text-amount"><?= number_format($row['harga_modal']) ?></td>
                    <td class="text-end pe-4 fw-bold text-warning text-amount">
                        <?= formatRp($hak_item) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light fw-bold border-top">
                <tr>
                    <td colspan="5" class="text-end text-uppercase small ls-1 text-muted py-3">Total Wajib Setor</td>
                    <td class="text-end text-dark py-3 pe-4"><?= formatRp($total_hak_guru) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>