<?php
// Validasi Akses
if($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'pengurus'){
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard';</script>";
    exit;
}

// Fitur Hapus (Rollback) khusus Admin
if(isset($_GET['hapus_id']) && $_SESSION['user']['role'] == 'admin'){
    $id = $_GET['hapus_id'];
    $pdo->prepare("DELETE FROM tutup_buku WHERE id = ?")->execute([$id]);
    echo "<script>alert('Kunci periode dibuka kembali.'); window.location='utilitas/riwayat_tutup_buku';</script>";
}

// Ambil Data
$riwayat = $pdo->query("SELECT t.*, a.nama_lengkap FROM tutup_buku t JOIN anggota a ON t.user_id = a.id ORDER BY t.tahun DESC, t.bulan DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Pengaturan</h6>
        <h2 class="h3 fw-bold mb-0">Riwayat Tutup Buku</h2>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Periode</th>
                        <th class="text-end">Saldo Awal</th>
                        <th class="text-end text-success">Mutasi Masuk</th>
                        <th class="text-end text-danger">Mutasi Keluar</th>
                        <th class="text-end fw-bold">Saldo Akhir</th>
                        <th class="text-center">Dikunci Oleh</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $bln_ind = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
                    foreach($riwayat as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark"><?= $bln_ind[$r['bulan']] ?> <?= $r['tahun'] ?></span>
                            <small class="d-block text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
                        </td>
                        <td class="text-end text-muted"><?= formatRp($r['saldo_awal']) ?></td>
                        <td class="text-end text-success"><?= formatRp($r['total_masuk']) ?></td>
                        <td class="text-end text-danger"><?= formatRp($r['total_keluar']) ?></td>
                        <td class="text-end fw-bold text-dark"><?= formatRp($r['saldo_akhir']) ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['nama_lengkap']) ?></span></td>
                        <td class="text-center pe-4">
                            <?php if($_SESSION['user']['role'] == 'admin'): ?>
                            <a href="utilitas/riwayat_tutup_buku?hapus_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm rounded-circle" onclick="return confirm('Buka kunci periode ini? Data lama rawan berubah!')" title="Buka Kunci (Rollback)">
                                <i class="fas fa-lock-open"></i>
                            </a>
                            <?php else: ?>
                                <i class="fas fa-lock text-muted opacity-50"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($riwayat)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada riwayat tutup buku.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>