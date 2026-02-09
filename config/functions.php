<?php
// Format Rupiah
function formatRp($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Format Tanggal Indo
function tglIndo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    // Validasi agar tidak error jika format salah
    if(count($pecahkan) < 3) return $tanggal;
    
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// Cek Login
function cekLogin(){
    if(!isset($_SESSION['user'])){
        header("Location: login.php");
        exit;
    }
}

// FUNGSI BARU: Cek Status Tutup Buku
// Return TRUE jika periode sudah ditutup (Locked)
// Return FALSE jika periode masih aman (Open)
function cekStatusPeriode($pdo, $tanggal){
    $tgl = explode('-', $tanggal);
    $bulan = (int)$tgl[1];
    $tahun = (int)$tgl[0];

    $stmt = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    
    if($stmt->rowCount() > 0){
        return true; // SUDAH DITUTUP (TERKUNCI)
    }
    return false; // MASIH BUKA
}
?>