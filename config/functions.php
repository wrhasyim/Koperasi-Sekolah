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

// --- LOG SYSTEM ---
function catatLog($pdo, $user_id, $aksi, $keterangan){
    try {
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aksi, keterangan) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $aksi, $keterangan]);
    } catch (Exception $e) {}
}

// --- UTILITIES ---
function cekStatusPeriode($pdo, $tanggal){
    $tgl = explode('-', $tanggal);
    $bulan = (int)$tgl[1];
    $tahun = (int)$tgl[0];

    $stmt = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    
    if($stmt->rowCount() > 0){ return true; }
    return false;
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

// --- [DIPERBAIKI] HELPER NAMA ANGGOTA ---
function getNamaAnggota($pdo, $id){
    if(!$id) return '-';
    $stmt = $pdo->prepare("SELECT nama_lengkap FROM anggota WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Tidak Diketahui';
}
?>