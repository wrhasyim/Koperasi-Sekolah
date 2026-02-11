<?php
// pages/utilitas/riwayat_tutup_buku.php
require_once 'config/database.php';

// Validasi Akses: Hanya Admin & Pengurus
if(!in_array($_SESSION['user']['role'], ['admin', 'pengurus'])){
    echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
    exit;
}

// PERBAIKAN: Mengganti 'dibuat_oleh' menjadi 'user_id' agar sesuai dengan database
$sql = "SELECT r.*, u.nama_lengkap 
        FROM tutup_buku r 
        LEFT JOIN anggota u ON r.user_id = u.id 
        ORDER BY r.id DESC";
$riwayat = $pdo->query($sql)->fetchAll();

// Bulan & Tahun ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Akuntansi</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">Riwayat Tutup Buku</h2>
    </div>
    
    <form action="process/tutup_buku_aksi.php" method="POST" onsubmit="return confirm('PERINGATAN!\n\nSemua transaksi bulan ini akan dikunci. Lanjutkan?')">
        <input type="hidden" name="bulan" value="<?= $bulan_ini ?>">
        <input type="hidden" name="tahun" value="<?= $tahun_ini ?>">
        <button type="submit" class="btn btn-danger rounded-pill shadow-sm fw-bold px-4">
            <i class="fas fa-lock me-2"></i> TUTUP BUKU BULAN INI
        </button>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold m-0 text-dark"><i class="fas fa-history me-2"></i> Log Penutupan Periode</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Periode</th>
                    <th>Tgl Proses</th>
                    <th>Oleh</th>
                    <th class="text-end">Saldo Kas Fisik</th>
                    <th class="text-end pe-4">Total Aset</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($riwayat)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat tutup buku.</td></tr>
                <?php endif; 
                foreach($riwayat as $r): 
                    $nm_bulan = date('F', mktime(0, 0, 0, $r['bulan'], 10));
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary"><?= $nm_bulan ?> <?= $r['tahun'] ?></td>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($r['nama_lengkap'] ?? 'System') ?></td>
                    <td class="text-end fw-bold"><?= formatRp($r['saldo_akhir']) ?></td>
                    <td class="text-end pe-4 fw-bold"><?= formatRp($r['saldo_akhir']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>