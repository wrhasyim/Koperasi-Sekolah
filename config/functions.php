<?php
// config/functions.php

// --- FORMATTING ---
function formatRp($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}

function tglIndo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    if(count($pecahkan) < 3) return $tanggal;
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// --- AUTHENTICATION ---
function cekLogin(){
    if(!isset($_SESSION['user'])){
        header("Location: login.php");
        exit;
    }
}

// --- [DIPERBAIKI] LOG SYSTEM (AUDIT TRAIL) ---
// Fungsi ini sekarang mencatat IP dan Role menggunakan tabel 'anggota'
function catatLog($pdo, $user_id, $aksi, $keterangan){
    try {
        // 1. Ambil Role User dari tabel 'anggota' (Bukan 'users')
        $stmt = $pdo->prepare("SELECT role FROM anggota WHERE id = ?");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn() ?: 'unknown';

        // 2. Ambil IP Address
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // 3. Simpan ke Database
        $sql = "INSERT INTO log_aktivitas (user_id, role, aksi, keterangan, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([$user_id, $role, $aksi, $keterangan, $ip]);
        
    } catch (Exception $e) {
        // Silent error: Jangan sampai gagal log menghentikan transaksi utama
    }
}

// --- UTILITIES ---
function cekStatusPeriode($pdo, $tanggal){
    $tgl = explode('-', $tanggal);
    $bulan = (int)($tgl[1] ?? 0);
    $tahun = (int)($tgl[0] ?? 0);

    $stmt = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    
    return $stmt->rowCount() > 0;
}

// --- FLASH MESSAGE ---
function setFlash($type, $message){
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function displayFlash(){
    if(isset($_SESSION['flash'])){
        $type = $_SESSION['flash']['type'];
        $msg  = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        
        $icon = 'info-circle';
        if($type == 'success') $icon = 'check-circle';
        if($type == 'danger') $icon = 'exclamation-triangle';
        
        echo "
        <div class='alert alert-$type alert-dismissible fade show shadow-sm border-0 mb-4' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='fas fa-$icon me-2 fs-5'></i>
                <div>$msg</div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

// --- PENGATURAN ---
function getPengaturan($pdo, $kunci) {
    $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE kunci = ?");
    $stmt->execute([$kunci]);
    $res = $stmt->fetch();
    return $res ? $res['nilai'] : ''; 
}

function getAllPengaturan($pdo) {
    $stmt = $pdo->query("SELECT kunci, nilai FROM pengaturan");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
}

// --- HELPER NAMA ANGGOTA ---
function getNamaAnggota($pdo, $id){
    if(!$id) return '-';
    $stmt = $pdo->prepare("SELECT nama_lengkap FROM anggota WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Tidak Diketahui';
}
?>