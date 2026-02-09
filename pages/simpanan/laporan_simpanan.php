<?php
// --- STYLE CSS MODERN KHUSUS HALAMAN INI ---
?>
<style>
    .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.3s; background: #fff; }
    .card-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
    .avatar-circle { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 18px; }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
    .table-modern th { text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #8898aa; border-bottom: 2px solid #e3e6f0; padding: 15px; }
    .table-modern td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f0f0f0; }
    .text-amount { font-family: 'Consolas', monospace; font-weight: 600; letter-spacing: -0.5px; }
</style>

<?php
// --- LOGIKA PHP ---

// 1. MODE DETAIL (KARTU SIMPANAN PER ANGGOTA)
if(isset($_GET['detail_id'])):
    $id_anggota = $_GET['detail_id'];
    
    // Ambil Data Anggota
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
    $stmt->execute([$id_anggota]);
    $member = $stmt->fetch();

    // Ambil Transaksi (Urut dari lama ke baru untuk hitung saldo berjalan)
    $sql = "SELECT * FROM simpanan WHERE anggota_id = ? ORDER BY tanggal ASC, id ASC";
    $transaksi = $pdo->prepare($sql);
    $transaksi->execute([$id_anggota]);
    
    // Generate warna avatar random
    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
    $bg_color = $colors[$member['id'] % count($colors)];
    $initial = strtoupper(substr($member['nama_lengkap'], 0, 1));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Detail Transaksi</h6>
        <h2 class="h3 fw-bold mb-0">Kartu Simpanan</h2>
    </div>
    <div>
        <a href="pages/simpanan/cetak_laporan.php?type=detail&id=<?= $id_anggota ?>" target="_blank" class="btn btn-outline-dark shadow-sm rounded-pill px-3 me-2">
            <i class="fas fa-print me-2"></i> Cetak Kartu
        </a>
        <a href="simpanan/laporan_simpanan" class="btn btn-light shadow-sm rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>
</div>

<div class="card card-modern mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center">
            <div class="avatar-circle me-3 shadow-sm" style="background-color: <?= $bg_color ?>; width: 64px; height: 64px; font-size: 24px;">
                <?= $initial ?>
            </div>
            <div>
                <h4 class="mb-1 fw-bold"><?= htmlspecialchars($member['nama_lengkap']) ?></h4>
                <div class="d-flex gap-3 text-muted small">
                    <span><i class="fas fa-id-card me-1"></i> ID: <?= sprintf("%04d", $member['id']) ?></span>
                    <span><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($member['no_hp'] ?? '-') ?></span>
                    <span><i class="fas fa-user-tag me-1"></i> <?= ucfirst($member['role']) ?></span>
                </div>
            </div>
            <div class="ms-auto">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Status: Aktif</span>
            </div>
        </div>
    </div>
</div>

<div class="card card-modern">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-alt me-2"></i> Riwayat Mutasi</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debet (Masuk)</th>
                    <th class="text-end">Kredit (Keluar)</th>
                    <th class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $saldo = 0; $total_masuk = 0; $total_keluar = 0; $s_sihara = 0;
                
                foreach($transaksi as $row): 
                    $masuk = ($row['tipe_transaksi'] == 'setor') ? $row['jumlah'] : 0;
                    $keluar = ($row['tipe_transaksi'] == 'tarik') ? $row['jumlah'] : 0;
                    
                    // Hitung Saldo Global
                    $saldo += ($masuk - $keluar);
                    $total_masuk += $masuk; 
                    $total_keluar += $keluar;
                    
                    // Hitung Saldo Sihara Spesifik
                    if($row['jenis_simpanan'] == 'hari_raya'){
                        if($row['tipe_transaksi'] == 'setor') $s_sihara += $row['jumlah'];
                        else $s_sihara -= $row['jumlah'];
                    }
                ?>
                <tr>
                    <td class="text-muted"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td>
                        <?php 
                        if($row['jenis_simpanan']=='pokok') echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning">POKOK</span>';
                        elseif($row['jenis_simpanan']=='wajib') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success">WAJIB</span>';
                        else echo '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary">SIHARA</span>';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="text-end text-success text-amount"><?= $masuk!=0 ? '+'.number_format($masuk) : '-' ?></td>
                    <td class="text-end text-danger text-amount"><?= $keluar!=0 ? '-'.number_format($keluar) : '-' ?></td>
                    <td class="text-end fw-bold text-dark text-amount"><?= number_format($saldo) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-light p-3">
        <div class="alert alert-primary mb-0 border-0 shadow-sm d-flex align-items-center">
            <i class="fas fa-wallet fa-2x me-3"></i>
            <div>
                <small class="d-block text-uppercase">Saldo Sihara Tersedia (Bisa Ditarik)</small>
                <strong class="fs-5"><?= formatRp($s_sihara) ?></strong>
            </div>
        </div>
    </div>
</div>

<?php 
// 2. MODE REKAP (HALAMAN UTAMA)
else: 
    // Query: EXCLUDE ADMIN DAN STAFF
    // Hanya Guru dan Pengurus yang tampil
    $sql = "SELECT a.id, a.nama_lengkap, a.role,
            SUM(CASE WHEN s.jenis_simpanan='pokok' THEN s.jumlah ELSE 0 END) as total_pokok,
            SUM(CASE WHEN s.jenis_simpanan='wajib' THEN s.jumlah ELSE 0 END) as total_wajib,
            SUM(CASE WHEN s.jenis_simpanan='hari_raya' AND s.tipe_transaksi='setor' THEN s.jumlah ELSE 0 END) as sihara_masuk,
            SUM(CASE WHEN s.jenis_simpanan='hari_raya' AND s.tipe_transaksi='tarik' THEN s.jumlah ELSE 0 END) as sihara_keluar
            FROM anggota a
            LEFT JOIN simpanan s ON a.id = s.anggota_id
            WHERE a.role NOT IN ('admin', 'staff') AND a.status_aktif = 1
            GROUP BY a.id
            ORDER BY a.nama_lengkap ASC";
    $data = $pdo->query($sql)->fetchAll();
    
    // Hitung Grand Total untuk Widget Atas
    $grand_aset = 0;
    $total_anggota = count($data);
    foreach($data as $d) {
        $grand_aset += ($d['total_pokok'] + $d['total_wajib'] + ($d['sihara_masuk'] - $d['sihara_keluar']));
    }
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Laporan Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Rekapitulasi Simpanan</h2>
    </div>
    <a href="pages/simpanan/cetak_laporan.php?type=rekap" target="_blank" class="btn btn-dark shadow-sm rounded-pill px-4">
        <i class="fas fa-print me-2"></i> Cetak Laporan Resmi
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-modern bg-gradient-primary text-white p-3 border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold">Total Aset Anggota</span>
                    <h2 class="mb-0 fw-bold"><?= formatRp($grand_aset) ?></h2>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="fas fa-vault fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-modern bg-white p-3 border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Total Nasabah</span>
                    <h2 class="mb-0 fw-bold text-dark"><?= $total_anggota ?> <span class="fs-6 text-muted fw-normal">Orang</span></h2>
                </div>
                <div class="bg-light rounded-circle p-3 text-primary">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Anggota</th>
                    <th class="text-end">Simpanan Pokok</th>
                    <th class="text-end">Simpanan Wajib</th>
                    <th class="text-end">Saldo Sihara</th>
                    <th class="text-end pe-4">Total Aset</th>
                    <th class="text-center">Opsi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
                foreach($data as $row): 
                    $saldo_sihara = $row['sihara_masuk'] - $row['sihara_keluar'];
                    $total_aset = $row['total_pokok'] + $row['total_wajib'] + $saldo_sihara;
                    
                    // Avatar Logic
                    $bg_color = $colors[$row['id'] % count($colors)];
                    $initial = strtoupper(substr($row['nama_lengkap'], 0, 1));
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3 shadow-sm" style="background-color: <?= $bg_color ?>; font-size: 14px; width: 35px; height: 35px;">
                                <?= $initial ?>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                <small class="text-muted"><?= ucfirst($row['role']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="text-end text-muted text-amount"><?= number_format($row['total_pokok']) ?></td>
                    <td class="text-end text-muted text-amount"><?= number_format($row['total_wajib']) ?></td>
                    <td class="text-end text-primary fw-bold text-amount"><?= number_format($saldo_sihara) ?></td>
                    <td class="text-end pe-4">
                        <span class="badge bg-success bg-opacity-10 text-success fs-6 fw-normal px-3 py-1 rounded-pill text-amount">
                            <?= formatRp($total_aset) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="simpanan/laporan_simpanan?detail_id=<?= $row['id'] ?>" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Lihat Detail">
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>