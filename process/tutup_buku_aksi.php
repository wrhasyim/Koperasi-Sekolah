<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Validasi Akses
if(!isset($_SESSION['user']) || ($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'pengurus')){
    header("Location: ../index.php");
    exit;
}

if(isset($_POST['auto_tutup_buku'])){
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $user_id = $_SESSION['user']['id'];

    // 1. Cek Duplikasi
    if(cekStatusPeriode($pdo, "$tahun-$bulan-01")){
        echo "<script>alert('Gagal! Periode ini sudah ditutup sebelumnya.'); window.location='../kas/laporan_kas';</script>";
        exit;
    }

    // 2. Hitung Saldo Awal (Transaksi SEBELUM bulan target)
    $tgl_batas_awal = "$tahun-$bulan-01";
    $q_awal = $pdo->prepare("SELECT 
        SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tot_masuk,
        SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tot_keluar
        FROM transaksi_kas WHERE tanggal < ?");
    $q_awal->execute([$tgl_batas_awal]);
    $d_awal = $q_awal->fetch();
    $saldo_awal = $d_awal['tot_masuk'] - $d_awal['tot_keluar'];

    // 3. Hitung Mutasi (Transaksi PADA bulan target)
    $q_mutasi = $pdo->prepare("SELECT 
        SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as tot_masuk,
        SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as tot_keluar
        FROM transaksi_kas WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $q_mutasi->execute([$bulan, $tahun]);
    $d_mutasi = $q_mutasi->fetch();
    
    $total_masuk = $d_mutasi['tot_masuk'] ?? 0;
    $total_keluar = $d_mutasi['tot_keluar'] ?? 0;
    $saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;

    // 4. Simpan ke Database
    try {
        $sql = "INSERT INTO tutup_buku (bulan, tahun, saldo_awal, total_masuk, total_keluar, saldo_akhir, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$bulan, $tahun, $saldo_awal, $total_masuk, $total_keluar, $saldo_akhir, $user_id]);
        
        echo "<script>alert('SUKSES! Laporan bulan $bulan-$tahun berhasil dikunci.'); window.location='../kas/laporan_kas';</script>";
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='../kas/laporan_kas';</script>";
    }
} else {
    header("Location: ../index.php");
}
?>