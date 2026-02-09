<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Cek Login Wajib
cekLogin();

$user = $_SESSION['user'];
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Koperasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; }
        .sidebar a { color: #b0c4de; text-decoration: none; display: block; padding: 10px 20px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; }
        .content { padding: 20px; background: #f8f9fa; min-height: 100vh; }
        .nav-header { padding: 20px; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; color: #7f8c8d; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar col-md-2 d-none d-md-block">
        <div class="p-3 text-center border-bottom border-secondary">
            <h5 class="mb-0">KOPERASI</h5>
            <small>Internal System</small>
        </div>
        
        <a href="index.php?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>"><i class="fas fa-home me-2"></i> Dashboard</a>

        <div class="nav-header">Master Data</div>
        <a href="index.php?page=data_anggota" class="<?= $page=='data_anggota'?'active':'' ?>"><i class="fas fa-users me-2"></i> Data Anggota</a>

        <div class="nav-header">Simpanan Anggota</div>
        <a href="index.php?page=simpanan_sihara" class="<?= $page=='simpanan_sihara'?'active':'' ?>"><i class="fas fa-wallet me-2"></i> Rekap Sihara</a>
        <a href="index.php?page=simpanan_simjib" class="<?= $page=='simpanan_simjib'?'active':'' ?>"><i class="fas fa-file-invoice-dollar me-2"></i> Rekap Simjib</a>
        <a href="index.php?page=simpanan_simpok" class="<?= $page=='simpanan_simpok'?'active':'' ?>"><i class="fas fa-coins me-2"></i> Rekap Simpok</a>

        <?php if($user['role'] == 'admin' || $user['role'] == 'staff'): ?>
        <div class="nav-header">Kas & Keuangan</div>
        <a href="index.php?page=kas_penjualan" class="<?= $page=='kas_penjualan'?'active':'' ?>"><i class="fas fa-cash-register me-2"></i> Penjualan Harian</a>
        <a href="index.php?page=kas_belanja" class="<?= $page=='kas_belanja'?'active':'' ?>"><i class="fas fa-shopping-cart me-2"></i> Belanja Stok</a>
        <a href="index.php?page=laporan_kas" class="<?= $page=='laporan_kas'?'active':'' ?>"><i class="fas fa-book me-2"></i> Laporan Arus Kas</a>
        <?php endif; ?>

        <div class="nav-header">Konsinyasi</div>
        <a href="index.php?page=titipan" class="<?= $page=='titipan'?'active':'' ?>"><i class="fas fa-box-open me-2"></i> Titipan Guru</a>

        <div class="nav-header">Stok Seragam</div>
        <a href="index.php?page=stok_sekolah" class="<?= $page=='stok_sekolah'?'active':'' ?>"><i class="fas fa-tshirt me-2"></i> Seragam Sekolah</a>
        <a href="index.php?page=stok_eskul" class="<?= $page=='stok_eskul'?'active':'' ?>"><i class="fas fa-user-astronaut me-2"></i> Seragam Eskul</a>

        <div class="border-top border-secondary mt-4">
            <a href="process/auth_logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>

    <div class="col-md-10 content w-100">
        <div class="container-fluid">
            <?php
                $filename = "pages/" . $page . ".php";
                if(file_exists($filename)){
                    include $filename;
                } else {
                    echo "<div class='alert alert-warning'>Halaman <b>" . htmlspecialchars($page) . "</b> belum dibuat!</div>";
                }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>