<?php
// process/kas_bayar_honor.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isset($_SESSION['user'])){ header("Location: ../login.php"); exit; }

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $tipe    = $_POST['tipe'];
    $nominal = $_POST['nominal']; // Nominal ini otomatis dari Sisa Akumulasi di form
    $user_id = $_SESSION['user']['id'];

    if($nominal <= 0){
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tidak ada saldo honor yang perlu dibayar.'];
        header("Location: ../index.php?page=kas/rekap_honor"); exit;
    }

    $kategori = "bagi_hasil_" . $tipe;
    $label = ucfirst($tipe);
    if($tipe == 'dansos') $label = 'Dana Sosial';

    // Keterangan Pembayaran
    $bulan_ini = date('F Y');
    $keterangan = "Pembayaran Akumulasi $label (Realisasi Bulan: $bulan_ini)";
    $tanggal = date('Y-m-d H:i:s');

    try {
        // Langsung Insert (Tanpa cek tanggal, karena yang dibayar adalah akumulasi hutang)
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, 'keluar', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $kategori, $nominal, $keterangan, $user_id]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Pembayaran $label sebesar " . formatRp($nominal) . " berhasil dicatat."];

    } catch(PDOException $e){
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
    }

    header("Location: ../index.php?page=kas/rekap_honor");
    exit;
}
?>