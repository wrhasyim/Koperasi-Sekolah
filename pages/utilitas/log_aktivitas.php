<?php
// pages/utilitas/log_aktivitas.php
require_once 'config/database.php';

// Validasi Akses
if(!in_array($_SESSION['user']['role'], ['admin', 'pengurus'])){
    echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
    exit;
}

// Filter
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// QUERY DIPERBAIKI:
// Mengambil data log. Nama diambil dari tabel anggota jika ada.
// Role diambil langsung dari tabel log_aktivitas (kolom 'role') agar aman.
$sql = "SELECT l.*, a.nama_lengkap 
        FROM log_aktivitas l 
        LEFT JOIN anggota a ON l.user_id = a.id 
        WHERE DATE(l.created_at) BETWEEN ? AND ? 
        ORDER BY l.created_at DESC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keamanan Sistem</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-user-secret me-2"></i> Log Aktivitas User</h2>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="utilitas/log_aktivitas">
            <div class="col-auto fw-bold text-muted small">FILTER:</div>
            <div class="col-auto"><input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= $tgl_awal ?>"></div>
            <div class="col-auto">s/d</div>
            <div class="col-auto"><input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= $tgl_akhir ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-dark px-3 rounded-pill">Tampilkan</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-lg rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>User (Pelaku)</th>
                        <th>Role</th>
                        <th>Aksi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data.</td></tr>
                    <?php endif; 
                    foreach($logs as $row): 
                        $badge = 'secondary';
                        if($row['aksi'] == 'Login') $badge = 'success';
                        if($row['aksi'] == 'Hapus') $badge = 'danger';
                        
                        // FIX ERROR UNDEFINED KEY ROLE
                        // Pastikan kolom role ada, jika tidak pakai default '-'
                        $role_user = isset($row['role']) ? strtoupper($row['role']) : '-';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap'] ?: 'System/Unknown') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $role_user ?></span></td>
                        <td><span class="badge bg-<?= $badge ?> rounded-pill px-2"><?= strtoupper($row['aksi']) ?></span></td>
                        <td class="text-secondary"><?= htmlspecialchars($row['keterangan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>