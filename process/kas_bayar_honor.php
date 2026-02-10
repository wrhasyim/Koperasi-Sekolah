<?php
// process/kas_bayar_honor.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Validasi Login
if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $tipe      = $_POST['tipe']; // staff, pengurus, dll
    $nominal   = $_POST['nominal'];
    $tgl_awal  = $_POST['tgl_awal'];
    $tgl_akhir = $_POST['tgl_akhir'];

    // Validasi Nominal
    if($nominal <= 0){
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nominal tidak valid atau 0.'];
        header("Location: ../index.php?page=kas/rekap_honor&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir");
        exit;
    }

    // Tentukan Kategori & Keterangan Khusus
    // Kategori dibuat unik agar bisa difilter (dikecualikan) saat hitung surplus
    $kategori = "bagi_hasil_" . $tipe; 
    
    $label_tipe = ucfirst($tipe);
    if($tipe == 'dansos') $label_tipe = 'Dana Sosial';

    $keterangan = "Pembayaran Honor/Jatah $label_tipe (Periode: $tgl_awal s/d $tgl_akhir)";
    $tanggal_bayar = date('Y-m-d');

    try {
        // Cek apakah sudah pernah dibayar di periode ini (Opsional, untuk mencegah dobel bayar)
        // Kita cek pakai string matching sederhana di keterangan
        $cek = $pdo->prepare("SELECT id FROM transaksi_kas WHERE keterangan LIKE ? AND kategori = ?");
        $cek->execute(["%$tgl_awal s/d $tgl_akhir%", $kategori]);
        
        if($cek->rowCount() > 0){
             $_SESSION['flash'] = ['type' => 'warning', 'message' => "Honor $label_tipe untuk periode ini sepertinya SUDAH DIBAYARKAN sebelumnya."];
        } else {
            // INSERT KE TRANSAKSI KAS (ARUS KELUAR)
            $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                    VALUES (?, ?, 'keluar', ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tanggal_bayar, $kategori, $nominal, $keterangan, $user_id]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => "Pembayaran $label_tipe berhasil dicatat sebagai pengeluaran."];
        }

    } catch(PDOException $e){
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mencatat transaksi: ' . $e->getMessage()];
    }

    // Redirect kembali
    header("Location: ../index.php?page=kas/rekap_honor&tgl_awal=$tgl_awal&tgl_akhir=$tgl_akhir");
    exit;
}
?>