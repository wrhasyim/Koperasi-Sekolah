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
    
    <base href="/ktm/"> 

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { overflow-x: hidden; background: #f4f6f9; }
        ::-webkit-scrollbar { width: 0px; background: transparent; }
        
        .sidebar { 
            min-height: 100vh; 
            background: #2c3e50; 
            color: white; 
            transition: all 0.3s;
        }
        .sidebar a { color: #b0c4de; text-decoration: none; display: block; padding: 12px 20px; font-size: 0.95rem;}
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left: 4px solid #3498db; }
        .nav-header { padding: 20px 20px 10px; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; color: #95a5a6; letter-spacing: 1px; }
        
        .content { padding: 20px; width: 100%; transition: all 0.3s; }

        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; height: 100vh; width: 250px; z-index: 999; }
            .sidebar.active { left: 0; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
            .overlay.active { display: block; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="d-flex">
    <div class="sidebar" id="sidebar">
        <div class="p-3 text-center border-bottom border-secondary bg-dark">
            <h5 class="mb-0 fw-bold">KOPERASI</h5>
            <small class="text-muted">Internal System</small>
        </div>
        
        <div class="py-2">
            <a href="dashboard" class="<?= $page=='dashboard'?'active':'' ?>"><i class="fas fa-home me-2"></i> Dashboard</a>

            <div class="nav-header">Master Data</div>
            <a href="data_anggota" class="<?= $page=='data_anggota'?'active':'' ?>"><i class="fas fa-users me-2"></i> Data Anggota</a>

            <div class="nav-header">Simpanan</div>
            <a href="simpanan/simpanan_sihara" class="<?= $page=='simpanan/simpanan_sihara'?'active':'' ?>"><i class="fas fa-wallet me-2"></i> Rekap Sihara</a>
            <a href="simpanan/simpanan_simjib" class="<?= $page=='simpanan/simpanan_simjib'?'active':'' ?>"><i class="fas fa-file-invoice-dollar me-2"></i> Rekap Simjib</a>
            <a href="simpanan/simpanan_simpok" class="<?= $page=='simpanan/simpanan_simpok'?'active':'' ?>"><i class="fas fa-coins me-2"></i> Rekap Simpok</a>

            <?php if($user['role'] == 'admin' || $user['role'] == 'staff'): ?>
            <div class="nav-header">Keuangan</div>
            <a href="kas/kas_penjualan" class="<?= $page=='kas/kas_penjualan'?'active':'' ?>"><i class="fas fa-cash-register me-2"></i> Penjualan Tunai</a>
            <a href="kas/kas_qris" class="<?= $page=='kas/kas_qris'?'active':'' ?>"><i class="fas fa-qrcode me-2"></i> Penjualan QRIS</a>
            <a href="kas/kas_belanja" class="<?= $page=='kas/kas_belanja'?'active':'' ?>"><i class="fas fa-shopping-cart me-2"></i> Belanja & Biaya</a>
            <a href="kas/laporan_kas" class="<?= $page=='kas/laporan_kas'?'active':'' ?>"><i class="fas fa-book me-2"></i> Laporan Kas</a>
            <?php endif; ?>

            <div class="nav-header">Konsinyasi</div>
            <a href="titipan/titipan" class="<?= $page=='titipan/titipan'?'active':'' ?>"><i class="fas fa-box-open me-2"></i> Titipan Guru</a>

            <div class="nav-header">Inventory</div>
            <a href="inventory/stok_sekolah" class="<?= $page=='inventory/stok_sekolah'?'active':'' ?>"><i class="fas fa-tshirt me-2"></i> Seragam Sekolah</a>
            <a href="inventory/stok_eskul" class="<?= $page=='inventory/stok_eskul'?'active':'' ?>"><i class="fas fa-user-astronaut me-2"></i> Seragam Eskul</a>

            <div class="nav-header">Pengaturan</div>
            <a href="profil" class="<?= $page=='profil'?'active':'' ?>"><i class="fas fa-user-circle me-2"></i> Profil Saya</a>
            <?php if($user['role'] == 'admin'): ?>
                <a href="utilitas/backup" class="<?= $page=='utilitas/backup'?'active':'' ?>"><i class="fas fa-database me-2"></i> Backup Data</a>
            <?php endif; ?>

            <div class="mt-4 pb-5">
                <a href="process/auth_logout.php" class="text-danger bg-dark"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
        <nav class="navbar navbar-light bg-white shadow-sm mb-4 rounded d-md-none">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand mb-0 h1 fs-6">Koperasi Sekolah</span>
            </div>
        </nav>

        <div class="container-fluid p-0">
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
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
    }
</script>
</body>
</html>