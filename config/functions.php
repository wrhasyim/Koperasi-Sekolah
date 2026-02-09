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
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// Cek Login
function cekLogin(){
    if(!isset($_SESSION['user'])){
        header("Location: login.php");
        exit;
    }
}
?>