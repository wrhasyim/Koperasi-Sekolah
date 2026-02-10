<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

cekLogin();
$user = $_SESSION['user'];
$role = $user['role'];
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// --- 1. LOGIKA BASE URL DINAMIS ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = "$protocol://$host$path/";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Koperasi Digital</title>
    <base href="<?= $base_url ?>"> 

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #4e73df; --primary-dark: #224abe;
            --success: #1cc88a; --info: #36b9cc; --warning: #f6c23e; --danger: #e74a3b;
            --dark: #2c3e50; --light: #f8f9fc;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #5a5c69; overflow-x: hidden; }
        
        /* SIDEBAR MODERN */
        .sidebar { min-height: 100vh; background: #ffffff; width: 260px; position: fixed; top: 0; left: 0; z-index: 100; border-right: 1px solid #e3e6f0; transition: 0.3s; }
        .sidebar .brand { height: 70px; display: flex; align-items: center; padding: 0 25px; font-weight: 800; font-size: 1.2rem; color: var(--primary); letter-spacing: 1px; border-bottom: 1px solid #f0f0f0; }
        .sidebar-menu { padding: 20px 0; height: calc(100vh - 70px); overflow-y: auto; }
        .nav-header { padding: 15px 25px 10px; font-size: 0.7rem; font-weight: 700; color: #b7b9cc; text-transform: uppercase; letter-spacing: 1px; }
        .nav-link { display: flex; align-items: center; padding: 12px 25px; color: #5a5c69; font-weight: 500; font-size: 0.9rem; transition: 0.2s; position: relative; }
        .nav-link i { width: 24px; font-size: 1rem; margin-right: 10px; color: #d1d3e2; transition: 0.2s; }
        .nav-link:hover { color: var(--primary); background: #f8f9fc; text-decoration: none; }
        .nav-link:hover i { color: var(--primary); }
        .nav-link.active { color: var(--primary); background: #f0f4ff; font-weight: 700; }
        .nav-link.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--primary); border-radius: 0 4px 4px 0; }
        .nav-link.active i { color: var(--primary); }

        /* CONTENT AREA */
        .main-content { margin-left: 260px; padding: 30px; transition: 0.3s; }
        
        /* CARD PREMIUM */
        .card { 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05); 
            background: #fff; 
            transition: box-shadow 0.2s; 
        }
        .card:hover { 
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15); 
        }
        .card-header { background: #fff; border-bottom: 1px solid #e3e6f0; padding: 1.2rem 1.5rem; font-weight: 700; color: var(--primary); }
        
        /* GRADIENTS */
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); }
        .bg-gradient-info    { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
        .bg-gradient-danger  { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }

        /* UTILITIES */
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; text-transform: uppercase; font-size: 14px; }
        .btn { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; }
        .table thead th { background: #f8f9fc; color: #858796; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 0; padding: 1rem; }
        .table td { padding: 1rem; vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #e3e6f0; }
        
        @media (max-width: 768px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99; }
            .overlay.active { display: block; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <i class="fas fa-shapes me-2"></i> KOPERASI
    </div>
    <div class="sidebar-menu">
        <a href="dashboard" class="nav-link <?= $page=='dashboard'?'active':'' ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>

        <?php if($role == 'admin' || $role == 'staff' || $role == 'pengurus'): ?>
        <div class="nav-header">Master Data</div>
        <a href="data_anggota" class="nav-link <?= $page=='data_anggota'?'active':'' ?>">
            <i class="fas fa-fw fa-users"></i> <span>Data Anggota</span>
        </a>
        <?php endif; ?>

        <?php if($role != 'guru'): ?> 
        <div class="nav-header">Transaksi & Keuangan</div>
            <?php if($role == 'admin' || $role == 'staff'): ?>
            <a href="simpanan/transaksi_simpanan" class="nav-link <?= $page=='simpanan/transaksi_simpanan'?'active':'' ?>">
                <i class="fas fa-fw fa-wallet"></i> <span>Transaksi Simpanan</span>
            </a>
            
            <a href="kas/penjualan_inventory" class="nav-link <?= $page=='kas/penjualan_inventory'?'active':'' ?>">
                <i class="fas fa-fw fa-tags"></i> <span>Kasir Seragam & Eskul</span>
            </a>
            
            <a href="kas/manajemen_cicilan" class="nav-link <?= $page=='kas/manajemen_cicilan'?'active':'' ?>">
                <i class="fas fa-fw fa-hand-holding-usd"></i> <span>Manajemen Cicilan</span>
            </a>

            <a href="kas/kas_penjualan" class="nav-link <?= $page=='kas/kas_penjualan'?'active':'' ?>">
                <i class="fas fa-fw fa-cash-register"></i> <span>Kasir Omzet Manual</span>
            </a>
            <a href="kas/kas_qris" class="nav-link <?= $page=='kas/kas_qris'?'active':'' ?>">
                <i class="fas fa-fw fa-qrcode"></i> <span>Transaksi QRIS</span>
            </a>
            <a href="kas/kas_belanja" class="nav-link <?= $page=='kas/kas_belanja'?'active':'' ?>">
                <i class="fas fa-fw fa-shopping-cart"></i> <span>Belanja Stok</span>
            </a>
            <?php endif; ?>
            
            <a href="kas/grafik_penjualan" class="nav-link <?= $page=='kas/grafik_penjualan' ?'active':'' ?>">
                <i class="fas fa-fw fa-chart-line"></i> <span>Grafik Keuangan</span>
            </a>

            <a href="kas/laporan_kas" class="nav-link <?= $page=='kas/laporan_kas' ?'active':'' ?>">
                <i class="fas fa-fw fa-book"></i> <span>Laporan Kas Koperasi</span>
            </a>
            
            <a href="kas/rekap_honor" class="nav-link <?= $page=='kas/rekap_honor' ?'active':'' ?>">
                <i class="fas fa-fw fa-hand-holding-usd"></i> <span>Rekap Honor & Dansos</span>
            </a>

            <a href="kas/laporan_distribusi" class="nav-link <?= $page=='kas/laporan_distribusi'?'active':'' ?>">
                <i class="fas fa-fw fa-file-contract"></i> <span>Laporan Distribusi</span>
            </a>

            <a href="laporan_rapat" class="nav-link <?= $page=='laporan_rapat'?'active':'' ?>">
                <i class="fas fa-fw fa-briefcase"></i> <span>Laporan Rapat</span>
            </a>

            <a href="simpanan/laporan_simpanan" class="nav-link <?= $page=='simpanan/laporan_simpanan'?'active':'' ?>">
                <i class="fas fa-fw fa-file-invoice"></i> <span>Laporan Simpanan</span>
            </a>
        <?php endif; ?>

        <?php if($role != 'guru'): ?>
        <div class="nav-header">Inventory</div>
            <a href="titipan/titipan" class="nav-link <?= $page=='titipan/titipan'?'active':'' ?>">
                <i class="fas fa-fw fa-box-open"></i> <span>Titipan Guru</span>
            </a>
            <a href="titipan/laporan_titipan" class="nav-link <?= $page=='titipan/laporan_titipan'?'active':'' ?>">
                <i class="fas fa-fw fa-clipboard-list"></i> <span>Laporan Titipan</span>
            </a>
            
            <a href="inventory/stok_koperasi" class="nav-link <?= $page=='inventory/stok_koperasi'?'active':'' ?>">
                <i class="fas fa-fw fa-store"></i> <span>Stok Koperasi</span>
            </a>

            <a href="inventory/stok_sekolah" class="nav-link <?= $page=='inventory/stok_sekolah'?'active':'' ?>">
                <i class="fas fa-fw fa-tshirt"></i> <span>Stok Seragam</span>
            </a>
            
            <a href="inventory/stok_eskul" class="nav-link <?= $page=='inventory/stok_eskul'?'active':'' ?>">
                <i class="fas fa-fw fa-user-astronaut"></i> <span>Stok Eskul</span>
            </a>
        <?php endif; ?>

        <div class="nav-header">System</div>
        <a href="profil" class="nav-link <?= $page=='profil'?'active':'' ?>">
            <i class="fas fa-fw fa-user-circle"></i> <span>Profil Saya</span>
        </a>
        
        <?php if($role == 'admin'): ?>
            <a href="utilitas/pengaturan" class="nav-link <?= $page=='utilitas/pengaturan'?'active':'' ?>">
                <i class="fas fa-fw fa-cog"></i> <span>Pengaturan Sistem</span>
            </a>

            <a href="utilitas/backup" class="nav-link <?= $page=='utilitas/backup'?'active':'' ?>">
                <i class="fas fa-fw fa-database"></i> <span>Backup Data</span>
            </a>
        <?php endif; ?>

        <?php if($role == 'admin' || $role == 'pengurus'): ?>
            <a href="utilitas/riwayat_tutup_buku" class="nav-link <?= $page=='utilitas/riwayat_tutup_buku'?'active':'' ?>">
                <i class="fas fa-fw fa-history"></i> <span>Riwayat Tutup Buku</span>
            </a>
        <?php endif; ?>

        <div class="mt-4 pb-5 px-3">
            <a href="logout" class="btn btn-danger w-100 shadow-sm"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    <nav class="navbar navbar-light bg-white shadow-sm mb-4 rounded-3 d-md-none border">
        <div class="container-fluid">
            <button class="btn btn-light" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars text-primary"></i>
            </button>
            <span class="navbar-brand mb-0 h1 fs-6 fw-bold">Koperasi Digital</span>
        </div>
    </nav>

    <div class="container-fluid p-0">
        <?php
            // [BARU] PANGGIL FLASH MESSAGE DISINI
            displayFlash();

            // --- 2. SECURITY & ROUTING (WHITELIST) ---
            $allowed_pages = [
                'dashboard', 
                'data_anggota', 
                'profil',
                // Modul Kas
                'kas/penjualan_inventory',
                'kas/manajemen_cicilan',
                'kas/kas_penjualan',
                'kas/kas_qris',
                'kas/kas_belanja',
                'kas/laporan_kas',
                'kas/rekap_honor', // [BARU] Routing Rekap Honor
                'kas/laporan_distribusi',
                'kas/grafik_penjualan', 
                'laporan_rapat',
                
                // Modul Simpanan
                'simpanan/transaksi_simpanan',
                'simpanan/laporan_simpanan',
                // Modul Inventory & Titipan
                'titipan/titipan',
                'titipan/laporan_titipan',
                'inventory/stok_koperasi',
                'inventory/stok_sekolah',
                'inventory/stok_eskul',
                // Modul Utilitas
                'utilitas/pengaturan', // [BARU] Routing Pengaturan
                'utilitas/backup',
                'utilitas/riwayat_tutup_buku',
                // Logout
                'logout'
            ];

            // Cek apakah halaman diminta ada di whitelist
            if(in_array($page, $allowed_pages)){
                // Handle Logout Khusus
                if($page == 'logout'){
                    include 'process/auth_logout.php';
                    exit;
                }

                $filename = "pages/" . $page . ".php";
                if(file_exists($filename)){
                    include $filename;
                } else {
                    echo "<div class='card border-0 shadow-sm p-5 text-center'><div class='card-body'><div class='display-1 text-muted mb-3'><i class='fas fa-exclamation-triangle'></i></div><h3 class='text-muted'>File Tidak Ditemukan</h3><p class='text-muted'>File <b>pages/$page.php</b> belum dibuat.</p></div></div>";
                }

            } else {
                echo "<div class='card border-0 shadow-sm p-5 text-center'><div class='card-body'><div class='display-1 text-danger mb-3'><i class='fas fa-ban'></i></div><h3 class='text-danger'>Akses Ditolak</h3><p class='text-muted'>Halaman tidak ditemukan atau Anda tidak memiliki akses.</p><a href='dashboard' class='btn btn-primary mt-3'>Kembali ke Dashboard</a></div></div>";
            }
        ?>
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