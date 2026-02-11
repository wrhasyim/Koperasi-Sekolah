<?php
// pages/guru/dashboard_guru.php
$id = $_SESSION['user']['id'];

// Hitung Saldo Simpanan
$simpanan = $pdo->prepare("SELECT SUM(jumlah) FROM simpanan WHERE anggota_id = ?");
$simpanan->execute([$id]);
$total_simpanan = $simpanan->fetchColumn() ?: 0;

// Hitung Titipan (Pendapatan Belum Diambil)
// PERBAIKAN: Kolom 'stok_terjual', 'harga_modal', dan 'status_bayar' sesuai database
$titipan = $pdo->prepare("SELECT SUM(stok_terjual * harga_modal) FROM titipan WHERE anggota_id = ? AND status_bayar != 'lunas'");
$titipan->execute([$id]);
$total_titipan = $titipan->fetchColumn() ?: 0;

// Hitung Hutang (Pinjaman Aktif)
$hutang = $pdo->prepare("SELECT SUM(sisa_tagihan) FROM pinjaman_dana WHERE anggota_id = ? AND status != 'lunas'");
$hutang->execute([$id]);
$total_hutang = $hutang->fetchColumn() ?: 0;
?>

<h2 class="fw-bold text-dark mb-4">Selamat Datang, <?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'Guru') ?>!</h2>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-5 border-success">
            <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small">Saldo Simpanan Saya</h6>
                <h2 class="fw-bold text-success mb-0"><?= formatRp($total_simpanan) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-5 border-primary">
            <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small">Titipan (Belum Diambil)</h6>
                <h2 class="fw-bold text-primary mb-0"><?= formatRp($total_titipan) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
            <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small">Tagihan Pinjaman</h6>
                <h2 class="fw-bold text-danger mb-0"><?= formatRp($total_hutang) ?></h2>
            </div>
        </div>
    </div>
</div>