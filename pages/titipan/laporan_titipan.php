<?php
// pages/titipan/laporan_titipan.php

// --- 1. SETUP FILTER & DATA ---
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$guru_id  = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';

// Ambil Daftar Guru (Untuk Dropdown)
$stmt_guru = $pdo->query("SELECT id, nama_lengkap FROM anggota WHERE role NOT IN ('admin', 'staff') ORDER BY nama_lengkap ASC");
$list_guru = $stmt_guru->fetchAll();

// Siapkan Query Data Utama
$sql = "SELECT t.*, a.nama_lengkap 
        FROM titipan t 
        JOIN anggota a ON t.anggota_id = a.id 
        WHERE (t.tanggal_titip BETWEEN ? AND ?)";

$params = [$tgl_awal, $tgl_akhir];

// Jika ada filter guru, tambahkan kondisi
if (!empty($guru_id)) {
    $sql .= " AND t.anggota_id = ?";
    $params[] = $guru_id;
}

$sql .= " ORDER BY a.nama_lengkap ASC, t.tanggal_titip DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data_titipan = $stmt->fetchAll();

// --- 2. HITUNG RINGKASAN ---
$total_stok_awal = 0;
$total_terjual = 0;
$total_kewajiban = 0; 
$estimasi_laba = 0;

foreach($data_titipan as $d){
    $total_stok_awal += $d['stok_awal'];
    $total_terjual += $d['stok_terjual'];
    $total_kewajiban += ($d['stok_terjual'] * $d['harga_modal']);
    $laba_per_unit = $d['harga_jual'] - $d['harga_modal'];
    $estimasi_laba += ($laba_per_unit * $d['stok_terjual']);
}
?>

<style>
    .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.3s; background: #fff; overflow: hidden; }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e 0%, #dda20a 100%); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc 0%, #258391 100%); }
    .table-modern thead th { text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #6e707e; background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0; padding: 12px 15px; }
    .table-modern td { vertical-align: middle; padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #5a5c69; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Inventory</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Rekapitulasi Barang Titipan</h2>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card card-modern bg-gradient-primary text-white h-100">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold mb-1">Total Stok Masuk</div>
                <div class="h3 mb-0 fw-bold"><?= number_format($total_stok_awal) ?> <span class="fs-6 fw-normal">Unit</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-modern bg-gradient-success text-white h-100">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold mb-1">Total Terjual</div>
                <div class="h3 mb-0 fw-bold"><?= number_format($total_terjual) ?> <span class="fs-6 fw-normal">Unit</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-modern bg-gradient-warning text-white h-100">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold mb-1">Wajib Setor (Kewajiban)</div>
                <div class="h3 mb-0 fw-bold"><?= formatRp($total_kewajiban) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-modern bg-gradient-info text-white h-100">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold mb-1">Estimasi Laba Koperasi</div>
                <div class="h3 mb-0 fw-bold"><?= formatRp($estimasi_laba) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card card-modern mb-4">
    <div class="card-body py-3">
        <form class="row g-2 align-items-center" method="GET">
            <div class="col-auto">
                <span class="fw-bold text-secondary small text-uppercase"><i class="fas fa-filter me-2"></i>Filter:</span>
            </div>
            <div class="col-auto">
                <input type="date" name="tgl_awal" class="form-control form-control-sm border-light bg-light fw-bold text-secondary" value="<?= $tgl_awal ?>">
            </div>
            <div class="col-auto text-muted">-</div>
            <div class="col-auto">
                <input type="date" name="tgl_akhir" class="form-control form-control-sm border-light bg-light fw-bold text-secondary" value="<?= $tgl_akhir ?>">
            </div>
            
            <div class="col-auto ms-2">
                <select name="guru_id" class="form-select form-select-sm border-light bg-light fw-bold text-secondary" style="min-width: 200px;">
                    <option value="">-- Semua Guru/Penitip --</option>
                    <?php foreach($list_guru as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= $guru_id == $g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['nama_lengkap']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm">Terapkan</button>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">
                <a href="process/export_laporan_titipan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&guru_id=<?= $guru_id ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                    <i class="fas fa-file-excel me-2"></i> Excel
                </a>
                <a href="pages/titipan/cetak_laporan_titipan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&guru_id=<?= $guru_id ?>" target="_blank" class="btn btn-sm btn-dark rounded-pill px-3 shadow-sm">
                    <i class="fas fa-print me-2"></i> Print
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Tanggal & Barang</th>
                    <th>Pemilik</th>
                    <th class="text-end">Harga (Modal)</th>
                    <th class="text-center">Awal</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-center">Sisa</th>
                    <th class="text-end">Wajib Setor</th>
                    <th class="text-center pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data_titipan)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data titipan sesuai filter.</td></tr>
                <?php endif; 

                foreach($data_titipan as $row): 
                    $sisa = $row['stok_awal'] - $row['stok_terjual'];
                    $wajib_setor = $row['stok_terjual'] * $row['harga_modal'];
                    
                    $status_badge = ($row['status_bayar'] == 'lunas') 
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">LUNAS</span>'
                        : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">BELUM</span>';
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($row['tanggal_titip'])) ?></small>
                    </td>
                    <td>
                        <div class="small fw-bold text-primary"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($row['nama_lengkap']) ?></div>
                    </td>
                    <td class="text-end text-muted">
                        <?= number_format($row['harga_modal']) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border"><?= $row['stok_awal'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold"><?= $row['stok_terjual'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $sisa > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-light text-muted' ?>">
                            <?= $sisa ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        <?= formatRp($wajib_setor) ?>
                    </td>
                    <td class="text-center pe-4">
                        <?= $status_badge ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>