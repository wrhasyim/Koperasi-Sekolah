<?php
// Hitung Saldo Kas Real-time
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
    SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
    FROM transaksi_kas");
$kas = $stmt->fetch();
$saldo_kas = $kas['total_masuk'] - $kas['total_keluar'];

// Hitung Total Simpanan Sihara
$stmt2 = $pdo->query("SELECT SUM(jumlah) as total FROM simpanan WHERE jenis_simpanan='hari_raya' AND tipe_transaksi='setor'");
$sihara = $stmt2->fetch();
?>

<h3 class="mb-4">Dashboard Utama</h3>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Saldo Kas Tunai</div>
            <div class="card-body">
                <h2 class="card-title"><?= formatRp($saldo_kas) ?></h2>
                <p class="card-text">Total Uang di Laci/Brankas</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Total Tabungan Sihara</div>
            <div class="card-body">
                <h2 class="card-title"><?= formatRp($sihara['total']) ?></h2>
                <p class="card-text">Tabungan Anggota Terkumpul</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-light mb-3">
            <div class="card-header">Login Sebagai</div>
            <div class="card-body">
                <h4 class="card-title"><?= $user['nama'] ?></h4>
                <span class="badge bg-dark"><?= strtoupper($user['role']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Selamat Datang di Sistem Informasi Koperasi Sekolah. Silakan pilih menu di samping untuk memulai transaksi.
</div>