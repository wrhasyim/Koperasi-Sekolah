<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

cekLogin();
$user = $_SESSION['user'];
$role = $user['role'];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fc; color: #4a5568; overflow-x: hidden; }
        
        /* Sidebar Modern */
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%); color: white; transition: all 0.3s; box-shadow: 4px 0 24px rgba(0,0,0,0.05); }
        .sidebar a { color: #cbd5e0; text-decoration: none; display: block; padding: 14px 24px; font-size: 0.9rem; border-left: 3px solid transparent; transition: 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: linear-gradient(90deg, rgba(66, 153, 225, 0.15) 0%, transparent 100%); color: #63b3ed; border-left-color: #63b3ed; font-weight: 600; }
        .nav-header { padding: 24px 24px 10px; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; color: #718096; letter-spacing: 1.2px; }

        /* Card Modern */
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02); transition: transform 0.2s, box-shadow 0.2s; background: #fff; }
        .card:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); }
        .card-header { background: transparent; border-bottom: 1px solid #edf2f7; padding: 1.25rem 1.5rem; font-weight: 600; }
        
        /* Gradients & Colors */
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important; }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important; }
        .bg-gradient-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important; }
        .bg-gradient-danger { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%) !important; }
        .bg-gradient-info { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important; }

        /* Elements */
        .btn { border-radius: 10px; padding: 8px 16px; font-weight: 500; letter-spacing: 0.3px; transition: 0.2s; }
        .btn-primary { background-color: #4e73df; border-color: #4e73df; box-shadow: 0 4px 12px rgba(78, 115, 223, 0.25); }
        .btn-primary:hover { background-color: #2e59d9; transform: translateY(-1px); }
        
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 14px; text-transform: uppercase; }
        
        .table thead th { background-color: #f8f9fa; color: #8898aa; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; border-bottom: none; padding: 1rem; }
        .table td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #edf2f7; color: #5a5c69; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 3px; }
        
        .content { padding: 30px; width: 100%; }

        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -260px; height: 100vh; width: 260px; z-index: 1050; }
            .sidebar.active { left: 0; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
            .overlay.active { display: block; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="d-flex">
    <div class="sidebar" id="sidebar">
        <div class="p-4 text-center border-bottom border-secondary border-opacity-25">
            <h5 class="mb-0 fw-bold text-white tracking-wide">KOPERASI</h5>
            <small class="text-white-50">Sistem Terintegrasi</small>
        </div>
        
        <div class="py-3">
            <a href="dashboard" class="<?= $page=='dashboard'?'active':'' ?>"><i class="fas fa-th-large me-3" style="width:20px"></i> Dashboard</a>

            <?php if($role == 'admin' || $role == 'staff' || $role == 'pengurus'): ?>
            <div class="nav-header">Master Data</div>
            <a href="data_anggota" class="<?= $page=='data_anggota'?'active':'' ?>"><i class="fas fa-users me-3" style="width:20px"></i> Data Anggota</a>
            <?php endif; ?>

            <?php if($role != 'guru'): ?> 
            <div class="nav-header">Simpanan</div>
    <?php if($role == 'admin' || $role == 'staff'): ?>
    <a href="simpanan/transaksi_simpanan" class="<?= $page=='simpanan/transaksi_simpanan'?'active':'' ?>">
        <i class="fas fa-wallet me-3" style="width:20px"></i> Transaksi Simpanan
    </a>
    <?php endif; ?>
    
    <a href="simpanan/laporan_simpanan" class="<?= $page=='simpanan/laporan_simpanan'?'active':'' ?>">
        <i class="fas fa-file-invoice me-3" style="width:20px"></i> Laporan Simpanan
    </a>
<?php endif; ?>

            <?php if($role != 'guru'): ?>
            <div class="nav-header">Inventory</div>
                <a href="titipan/titipan" class="<?= $page=='titipan/titipan'?'active':'' ?>"><i class="fas fa-box-open me-3" style="width:20px"></i> Titipan Guru</a>
                <a href="inventory/stok_sekolah" class="<?= $page=='inventory/stok_sekolah'?'active':'' ?>"><i class="fas fa-tshirt me-3" style="width:20px"></i> Stok Sekolah</a>
                <a href="inventory/stok_eskul" class="<?= $page=='inventory/stok_eskul'?'active':'' ?>"><i class="fas fa-user-astronaut me-3" style="width:20px"></i> Stok Eskul</a>
            <?php endif; ?>

            <div class="nav-header">System</div>
            <a href="profil" class="<?= $page=='profil'?'active':'' ?>"><i class="fas fa-user-circle me-3" style="width:20px"></i> Profil Saya</a>
            
            <?php if($role == 'admin'): ?>
                <a href="utilitas/backup" class="<?= $page=='utilitas/backup'?'active':'' ?>"><i class="fas fa-database me-3" style="width:20px"></i> Backup Data</a>
            <?php endif; ?>
            <?php if($role == 'admin' || $role == 'pengurus'): ?>
                <a href="utilitas/tutup_buku" class="<?= $page=='utilitas/tutup_buku'?'active':'' ?>"><i class="fas fa-book-dead me-3" style="width:20px"></i> Tutup Buku</a>
            <?php endif; ?>

            <div class="mt-4 pb-5 px-3">
                <a href="process/auth_logout.php" class="btn btn-danger w-100 shadow-sm"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="content">
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
                $filename = "pages/" . $page . ".php";
                if(file_exists($filename)){
                    include $filename;
                } else {
                    echo "<div class='card border-0 shadow-sm p-5 text-center'><div class='card-body'><h3 class='text-muted'>404 Not Found</h3></div></div>";
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