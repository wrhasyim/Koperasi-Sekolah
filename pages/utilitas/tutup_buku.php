<?php
// UPDATE: Izinkan Admin DAN Pengurus
if($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'pengurus'){
    echo "<script>alert('Akses Ditolak! Menu ini hanya untuk Admin & Pengurus.'); window.location='dashboard';</script>";
    exit;
}

// --- PROSES TUTUP BUKU ---
if(isset($_POST['proses_tutup_buku'])){
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $user_id = $_SESSION['user']['id'];

    // 1. Cek apakah sudah pernah tutup buku
    if(cekStatusPeriode($pdo, "$tahun-$bulan-01")){
        echo "<script>alert('Gagal! Periode ini sudah pernah ditutup.'); window.location='utilitas/tutup_buku';</script>";
        exit;
    }

    // 2. Hitung Saldo Awal
    $tgl_batas_awal = "$tahun-$bulan-01";
    $q_awal = $pdo->prepare("SELECT 
        SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tot_masuk,
        SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tot_keluar
        FROM transaksi_kas WHERE tanggal < ?");
    $q_awal->execute([$tgl_batas_awal]);
    $d_awal = $q_awal->fetch();
    $saldo_awal = $d_awal['tot_masuk'] - $d_awal['tot_keluar'];

    // 3. Hitung Mutasi Bulan Ini
    $q_mutasi = $pdo->prepare("SELECT 
        SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tot_masuk,
        SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tot_keluar
        FROM transaksi_kas WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $q_mutasi->execute([$bulan, $tahun]);
    $d_mutasi = $q_mutasi->fetch();
    
    $total_masuk = $d_mutasi['tot_masuk'] ?? 0;
    $total_keluar = $d_mutasi['tot_keluar'] ?? 0;
    $saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;

    // 4. Simpan
    try {
        $sql = "INSERT INTO tutup_buku (bulan, tahun, saldo_awal, total_masuk, total_keluar, saldo_akhir, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$bulan, $tahun, $saldo_awal, $total_masuk, $total_keluar, $saldo_akhir, $user_id]);
        
        echo "<script>alert('SUKSES! Buku bulan $bulan-$tahun berhasil ditutup.'); window.location='utilitas/tutup_buku';</script>";
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// --- HAPUS (Hanya Admin yang boleh membatalkan tutup buku) ---
if(isset($_GET['hapus_id'])){
    if($_SESSION['user']['role'] == 'admin'){
        $id = $_GET['hapus_id'];
        $pdo->prepare("DELETE FROM tutup_buku WHERE id = ?")->execute([$id]);
        echo "<script>alert('Tutup buku dibatalkan.'); window.location='utilitas/tutup_buku';</script>";
    } else {
        echo "<script>alert('Hanya Admin yang boleh membatalkan tutup buku!'); window.location='utilitas/tutup_buku';</script>";
    }
}

// AMBIL DATA
$riwayat = $pdo->query("SELECT t.*, a.nama_lengkap FROM tutup_buku t JOIN anggota a ON t.user_id = a.id ORDER BY t.tahun DESC, t.bulan DESC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tutup Buku Bulanan</h1>
</div>

<div class="alert alert-warning">
    <i class="fas fa-lock me-2"></i> <strong>Perhatian:</strong> Menu ini digunakan untuk mengunci laporan keuangan agar tidak bisa diedit lagi.
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">Proses Tutup Buku</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Pilih Bulan</label>
                        <select name="bulan" class="form-select" required>
                            <?php 
                            $bln_skrg = date('n');
                            $bln_ind = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                            foreach($bln_ind as $k => $v){
                                $sel = ($k == $bln_skrg - 1) ? 'selected' : ''; 
                                echo "<option value='$k' $sel>$v</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Tahun</label>
                        <select name="tahun" class="form-select" required>
                            <?php 
                            $thn_skrg = date('Y');
                            for($i=$thn_skrg; $i>=2024; $i--){
                                echo "<option value='$i'>$i</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="proses_tutup_buku" class="btn btn-primary" onclick="return confirm('Yakin kunci data periode ini?')">
                            <i class="fas fa-lock me-2"></i> Kunci Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Riwayat Kunci</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Periode</th>
                                <th class="text-end">Saldo Akhir</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($riwayat as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= $bln_ind[$r['bulan']] ?> <?= $r['tahun'] ?></strong><br>
                                    <small class="text-muted">Oleh: <?= htmlspecialchars($r['nama_lengkap']) ?></small>
                                </td>
                                <td class="text-end fw-bold text-primary"><?= formatRp($r['saldo_akhir']) ?></td>
                                <td class="text-center">
                                    <?php if($_SESSION['user']['role'] == 'admin'): ?>
                                    <a href="utilitas/tutup_buku?hapus_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Buka kunci periode ini?')" title="Buka Kunci">
                                        <i class="fas fa-lock-open"></i>
                                    </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>